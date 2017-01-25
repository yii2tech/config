<?php

namespace yii2tech\tests\unit\config;

use Yii;
use yii\helpers\FileHelper;
use yii2tech\config\Item;
use yii2tech\config\Manager;
use yii2tech\config\StoragePhp;

/**
 * Test case for the extension [[Manager]].
 * @see Manager
 */
class ManagerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        FileHelper::createDirectory($this->getTestFilePath());
    }

    public function tearDown()
    {
        FileHelper::removeDirectory($this->getTestFilePath());
        parent::tearDown();
    }

    /**
     * @return string test file name
     */
    protected function getTestFilePath()
    {
        return Yii::getAlias('@yii2tech/tests/unit/config/runtime') . DIRECTORY_SEPARATOR . getmypid();
    }

    /**
     * Creates test config manager.
     * @return Manager config manager instance.
     */
    protected function createTestManager()
    {
        return new Manager([
            'storage' => [
                'class' => StoragePhp::className(),
                'fileName' => $this->getTestFilePath() . DIRECTORY_SEPARATOR . 'config.php',
            ],
        ]);
    }

    // Tests :

    public function testSetGet()
    {
        $manager = new Manager();

        $items = [
            new Item(),
            new Item(),
        ];
        $manager->setItems($items);
        $this->assertEquals($items, $manager->getItems(), 'Unable to setup items!');

        $storage = new StoragePhp();
        $manager->setStorage($storage);
        $this->assertEquals($storage, $manager->getStorage(), 'Unable to setup storage!');
    }

    /**
     * @depends testSetGet
     */
    public function testGetDefaultStorage()
    {
        $manager = new Manager();
        $storage = $manager->getStorage();
        $this->assertTrue(is_object($storage), 'Unable to get default storage!');
    }

    /**
     * @depends testSetGet
     */
    public function testGetItemById()
    {
        $manager = new Manager();

        $itemId = 'testItemId';
        $item = new Item();
        $manager->setItems([
            $itemId => $item
        ]);
        $this->assertEquals($item, $manager->getItem($itemId), 'Unable to get item by id!');
    }

    /**
     * @depends testGetItemById
     */
    public function testCreateItem()
    {
        $manager = new Manager();
        $manager->source = new \stdClass();

        $itemId = 'testItemId';
        $itemConfig = [
            'label' => 'testLabel'
        ];
        $manager->setItems([
            $itemId => $itemConfig
        ]);
        $item = $manager->getItem($itemId);
        $this->assertTrue(is_object($item), 'Unable to create item from config!');
        $this->assertEquals($itemConfig['label'], $item->label, 'Unable to setup attributes!');
        $this->assertSame($manager->source, $item->source, 'Unable to pass source to item!');
    }

    /**
     * @depends testCreateItem
     */
    public function testSetupItemsByFile()
    {
        $manager = new Manager();

        $items = [
            'item1' => [
                'label' => 'item1label'
            ],
            'item2' => [
                'label' => 'item2label'
            ],
        ];
        $fileName = $this->getTestFilePath() . DIRECTORY_SEPARATOR . 'config.php';
        $fileContent = '<?php return ' . var_export($items, true) . ';';
        file_put_contents($fileName, $fileContent);

        $manager->setItems($fileName);

        foreach ($items as $id => $itemConfig) {
            $item = $manager->getItem($id);
            $this->assertEquals($itemConfig['label'], $item->label, 'Wrong item label');
        }
    }

    /**
     * @depends testCreateItem
     */
    public function testSetupItemValues()
    {
        $manager = new Manager();
        $items = [
            'item1' => [],
            'item2' => [],
        ];
        $manager->setItems($items);

        $itemValues = [
            'item1' => 'item1value',
            'item2' => 'item2value',
        ];
        $manager->setItemValues($itemValues);
        $this->assertEquals($itemValues, $manager->getItemValues(), 'Unable to setup item values!');
    }

    /**
     * @depends testCreateItem
     */
    public function testComposeConfig()
    {
        $manager = new Manager();
        $items = [
            'item1' => [
                'path' => 'params.item1',
                'value' => 'item1value',
            ],
            'item2' => [
                'path' => 'params.item2',
                'value' => 'item2value',
            ],
        ];
        $manager->setItems($items);

        $config = $manager->composeConfig();
        $expectedConfig = [
            'params' => [
                'item1' => 'item1value',
                'item2' => 'item2value',
            ],
        ];
        $this->assertEquals($expectedConfig, $config, 'Wrong config composed!');
    }

    /**
     * @depends testSetupItemValues
     */
    public function testStoreValues()
    {
        $manager = $this->createTestManager();
        $items = [
            'item1' => [
                'value' => 'item1value',
            ],
            'item2' => [
                'value' => 'item2value',
            ],
        ];
        $manager->setItems($items);

        $this->assertTrue($manager->saveValues(), 'Unable to save values!');
        $itemValues = $manager->getItemValues();

        $emptyItemValues = [
            'item1' => [],
            'item2' => [],
        ];

        $manager->setItemValues($emptyItemValues);
        $manager->restoreValues();
        $this->assertEquals($itemValues, $manager->getItemValues(), 'Unable to restore values!');

        $manager->clearValues();

        $manager->setItemValues($emptyItemValues);
        $this->assertEquals($emptyItemValues, $manager->getItemValues(), 'Unable to clear values!');
    }

    /**
     * @depends testComposeConfig
     * @depends testStoreValues
     */
    public function testFetchConfig()
    {
        $manager = $this->createTestManager();
        $items = [
            'item1' => [
                'path' => 'params.item1',
                'value' => 'item1value',
            ],
            'item2' => [
                'path' => 'params.item2',
                'value' => 'item2value',
            ],
        ];
        $manager->setItems($items);
        $manager->saveValues();

        $manager = $this->createTestManager();
        $manager->setItems($items);

        $config = $manager->fetchConfig();
        $expectedConfig = [
            'params' => [
                'item1' => 'item1value',
                'item2' => 'item2value',
            ],
        ];
        $this->assertEquals($expectedConfig, $config, 'Wrong config composed!');
    }

    /**
     * @depends testSetupItemValues
     */
    public function testValidate()
    {
        $manager = new Manager();

        $itemId = 'testItem';
        $items = [
            $itemId => [
                'rules' => [
                    ['required']
                ]
            ],
        ];
        $manager->setItems($items);

        $itemValues = [
            $itemId => ''
        ];
        $manager->setItemValues($itemValues);
        $this->assertFalse($manager->validate(), 'Invalid values considered as valid!');

        $itemValues = [
            $itemId => 'some value'
        ];
        $manager->setItemValues($itemValues);
        $this->assertTrue($manager->validate(), 'Valid values considered as invalid!');
    }

    /**
     * @depends testFetchConfig
     */
    public function testConfigure()
    {
        $this->mockApplication([
            'name' => 'initial name',
            'modules' => [
                'admin' => [
                    'class' => 'yii\base\Module',
                    'layout' => 'default',
                ],
            ],
            'params' => [
                'param1' => 'initial1',
                'param2' => 'initial2',
            ],
        ]);
        $manager = new Manager();

        // plain :
        $manager->configure(Yii::$app, [
            'name' => 'new name',
        ]);
        $this->assertEquals('new name', Yii::$app->name, 'Unable to override plain field.');

        // params :
        $manager->configure(Yii::$app, [
            'params' => [
                'param1' => 'override1'
            ],
        ]);
        $this->assertEquals('override1', Yii::$app->params['param1'], 'Unable to override params.');
        $this->assertEquals('initial2', Yii::$app->params['param2'], 'Initial params are lost.');

        // components :
        $manager->configure(Yii::$app, [
            'components' => [
                'formatter' => [
                    'nullDisplay' => 'new null display'
                ],
            ],
        ]);
        $this->assertEquals('new null display', Yii::$app->formatter->nullDisplay, 'Unable to override component param.');

        // modules :
        $manager->configure(Yii::$app, [
            'modules' => [
                'admin' => [
                    'layout' => 'newLayout'
                ],
            ],
        ]);
        $this->assertEquals('newLayout', Yii::$app->getModule('admin')->layout, 'Unable to override module.');
    }

    /**
     * @depends testComposeConfig
     *
     * @see https://github.com/yii2tech/config/issues/1
     */
    public function testComposeConfigFromSingleItem()
    {
        $manager = new Manager();
        $items = [
            'singleItem' => [
                'path' => 'params.singleItem',
                'value' => 'single item',
            ],
        ];
        $manager->setItems($items);

        $config = $manager->composeConfig();
        $expectedConfig = [
            'params' => [
                'singleItem' => 'single item',
            ],
        ];
        $this->assertEquals($expectedConfig, $config, 'Wrong config composed!');
    }

    /**
     * @depends testSetupItemValues
     */
    public function testGetItemValue()
    {
        $manager = new Manager();
        $items = [
            'item1' => [],
            'item2' => [],
        ];
        $manager->setItems($items);

        $itemValues = [
            'item1' => 'item1value',
            'item2' => 'item2value',
        ];
        $manager->setItemValues($itemValues);

        $this->assertEquals('item1value', $manager->getItemValue('item1'));
        $this->assertEquals('item2value', $manager->getItemValue('item2'));
    }

    /**
     * @depends testSetupItemValues
     */
    public function testAutoRestoreValues()
    {
        $itemValues = [
            'item1' => 'value1',
            'item2' => 'value2',
        ];

        $fileName = $this->getTestFilePath() . DIRECTORY_SEPARATOR . 'values.php';
        $fileContent = '<?php return ' . var_export($itemValues, true) . ';';
        file_put_contents($fileName, $fileContent);

        $manager = new Manager([
            'autoRestoreValues' => true,
            'storage' => [
                'class' => StoragePhp::className(),
                'fileName' => $fileName,
            ],
            'items' => [
                'item1' => [],
                'item2' => [],
            ]
        ]);

        $this->assertEquals($itemValues, $manager->getItemValues());
    }

    public function testDynamicCacheId()
    {
        $manager = new Manager([
            'cacheId' => function () {
                return 'runtime';
            }
        ]);
        $this->assertEquals('runtime', $manager->cacheId);
    }
}