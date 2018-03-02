<?php

namespace yii2tech\tests\unit\config;

use Yii;
use yii2tech\config\StorageActiveRecord;
use yii2tech\tests\unit\config\data\ConfigActiveRecord;

class StorageActiveRecordTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->createTestConfigTable();
    }

    /**
     * @return StorageActiveRecord test storage instance.
     */
    protected function createTestStorage()
    {
        return new StorageActiveRecord([
            'activeRecordClass' => ConfigActiveRecord::class,
        ]);
    }

    /**
     * Creates test config table.
     */
    protected function createTestConfigTable()
    {
        $columns = [
            'id' => 'string',
            'value' => 'string',
            'group' => 'string',
            'PRIMARY KEY([[id]], [[group]])'
        ];
        Yii::$app->db->createCommand()->createTable(ConfigActiveRecord::tableName(), $columns)->execute();
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

    /**
     * @depends testSave
     */
    public function testUpdate()
    {
        $storage = $this->createTestStorage();

        $storage->save([
            'name1' => 'value1',
            'name2' => 'value2',
            'name3' => 'value3',
        ]);

        ConfigActiveRecord::updateAll(['group' => 'insert']);

        $storage->save([
            'name1' => 'new-value1',
            'name4' => 'new-value4',
        ]);

        $records = ConfigActiveRecord::find()->all();
        $this->assertCount(2, $records);
        $this->assertEquals('insert', $records[0]->group);
    }
}