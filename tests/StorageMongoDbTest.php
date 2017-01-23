<?php

namespace yii2tech\tests\unit\config;

use yii\mongodb\Connection;
use yii2tech\config\StorageMongoDb;

/**
 * @group mongodb
 */
class StorageMongoDbTest extends TestCase
{
    /**
     * @var Connection MongoDB connection used for the test running.
     */
    protected $_db;

    public function setUp()
    {
        $this->mockApplication([
            'components' => [
                'mongodb' => $this->getDb()
            ],
        ]);
    }

    protected function tearDown()
    {
        $this->getDb()->getCollection($this->getTestCollectionName())->drop();
        parent::tearDown();
    }

    /**
     * @return StorageMongoDb test storage instance.
     */
    protected function createTestStorage()
    {
        return new StorageMongoDb([
            'collection' => $this->getTestCollectionName(),
        ]);
    }

    /**
     * @return string test collection name
     */
    protected function getTestCollectionName()
    {
        return '_test_config';
    }

    /**
     * @return Connection test database connection
     */
    protected function getDb()
    {
        if ($this->_db === null) {
            if (!extension_loaded('mongo')) {
                $this->markTestSkipped('mongo PHP extension required.');
                return null;
            }
            if (!class_exists('yii\mongodb\Connection')) {
                $this->markTestSkipped('"yiisoft/yii2-mongodb" extension required.');
                return null;
            }

            $connectionConfig = $this->getParam('mongodb', [
                'dsn' => 'mongodb://travis:test@localhost:27017',
                'defaultDatabaseName' => 'yii2test',
                'options' => [],
            ]);

            $this->_db = new Connection($connectionConfig);
            $this->_db->open();
        }
        return $this->_db;
    }

    // Tests :

    public function testSave()
    {
        $storage = $this->createTestStorage();
        $values = [
            'name1' => 'value1',
            'name2' => 'value2',
        ];
        $this->assertTrue($storage->save($values), 'Unable to save values!');
    }

    /**
     * @depends testSave
     */
    public function testGet()
    {
        $storage = $this->createTestStorage();
        $values = [
            'name1' => 'value1',
            'name2' => 'value2',
        ];
        $storage->save($values);
        $this->assertEquals($values, $storage->get(), 'Unable to get values!');
    }

    /**
     * @depends testGet
     */
    public function testClear()
    {
        $storage = $this->createTestStorage();
        $values = [
            'name1' => 'value1',
            'name2' => 'value2',
        ];
        $storage->save($values);

        $this->assertTrue($storage->clear(), 'Unable to clear values!');
        $this->assertEquals([], $storage->get(), 'Values are not cleared!');
    }

    /**
     * @depends testClear
     */
    public function testFilterUsage()
    {
        $storage1 = $this->createTestStorage();
        $storage1->filter = ['group' => '1'];

        $storage2 = $this->createTestStorage();
        $storage2->filter = ['group' => '2'];

        $values1 = [
            'name' => 'value1',
        ];
        $storage1->save($values1);
        $values2 = [
            'name' => 'value2',
        ];
        $storage2->save($values2);

        $this->assertEquals($values1, $storage1->get());
        $this->assertEquals($values2, $storage2->get());

        $storage1->clear();
        $this->assertEquals([], $storage1->get());
        $this->assertEquals($values2, $storage2->get());
    }
}