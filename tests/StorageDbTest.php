<?php

namespace yii2tech\tests\unit\config;

use Yii;
use yii\db\Connection;
use yii2tech\config\StorageDb;

/**
 * Test case for the extension [[StorageDb]].
 * @see StorageDb
 */
class StorageDbTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->createTestConfigTable();
    }

    /**
     * @return string test table name
     */
    protected function getTestTableName()
    {
        return '_test_config';
    }

    /**
     * Creates test config table.
     */
    protected function createTestConfigTable()
    {
        $columns = [
            'id' => 'string',
            'value' => 'string',
        ];
        Yii::$app->db->createCommand()->createTable($this->getTestTableName(), $columns)->execute();
    }

    /**
     * @return StorageDb test storage instance.
     */
    protected function createTestStorage()
    {
        return new StorageDb([
            'table' => $this->getTestTableName(),
        ]);
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
     * @depends testGet
     */
    public function testClearItem()
    {
        $storage = $this->createTestStorage();
        $values = [
            'name1' => 'value1',
            'name2' => 'value2',
        ];
        $storage->save($values);

        $this->assertTrue($storage->clearValue('name1'), 'Unable to clear item value!');
        $this->assertEquals(['name2' => 'value2'], $storage->get(), 'Item value is not cleared!');
    }
}