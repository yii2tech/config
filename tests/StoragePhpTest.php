<?php

namespace yii2tech\tests\unit\config;

use Yii;
use yii2tech\config\StoragePhp;

/**
 * Test case for the extension [[StoragePhp]].
 * @see StoragePhp
 */
class StoragePhpTest extends TestCase
{
    public function tearDown()
    {
        $fileName = $this->getTestFileName();
        if (file_exists($fileName)) {
            unlink($fileName);
        }

        parent::tearDown();
    }

    /**
     * @return string test file name
     */
    protected function getTestFileName()
    {
        return Yii::getAlias('@yii2tech/tests/unit/config/runtime') . DIRECTORY_SEPARATOR . 'test_config_' . getmypid() . '.php';
    }

    /**
     * @return StoragePhp test storage instance.
     */
    protected function createTestStorage()
    {
        return new StoragePhp([
            'fileName' => $this->getTestFileName(),
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
        $this->assertFileExists($storage->fileName, 'Unable to create file!');
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
}