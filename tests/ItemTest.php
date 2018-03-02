<?php

namespace yii2tech\tests\unit\config;

use Yii;
use yii\validators\RequiredValidator;
use yii2tech\config\Item;

/**
 * Test case for the extension [[Item]].
 * @see Item
 */
class ItemTest extends TestCase
{
    protected function setUp()
    {
        $this->mockApplication([
            'name' => 'Test Application Name',
            'components' => [
                'formatter' => [
                    'nullDisplay' => 'testNullDisplay',
                    'dateFormat' => 'large',
                ],
            ],
            'modules' => [
                'admin' => [
                    '__class' => \yii\base\Module::class,
                    'layout' => 'default',
                ],
            ],
            'params' => [
                'param1' => 'param1value',
                'param2' => 'param2value',
            ]
        ]);
    }

    public function testSetGet()
    {
        $model = new Item();

        $value = 'testValue';
        $model->setValue($value);
        $this->assertEquals($value, $model->getValue(), 'Unable to setup value!');

        $label = 'Test Label';
        $model->setLabel($label);
        $this->assertEquals($label, $model->getLabel(), 'Unable to setup label!');
    }

    /**
     * @depends testSetGet
     */
    public function testLabel()
    {
        $model = new Item();

        $label = 'Test Placeholder Label';
        $model->label = $label;

        $this->assertEquals($label, $model->getAttributeLabel('value'), 'Wrong value label!');
    }

    /**
     * @depends testLabel
     */
    public function testDefaultLabel()
    {
        $model = new Item();
        $model->id = 'testItem';
        $this->assertEquals('Test Item', $model->getAttributeLabel('value'), 'Unable to generate default value from string ID!');

        $model = new Item();
        $model->id = 10;
        $this->assertEquals('Value', $model->getAttributeLabel('value'), 'Wrong default label for not string ID!');
    }

    public function testGetHint()
    {
        $model = new Item();

        $description = 'TestPlaceholderDescription';
        $model->description = $description;

        $this->assertEquals($description, $model->getAttributeHint('value'), 'Wrong value hint!');
    }

    /**
     * @depends testSetGet
     */
    public function testSetupRules()
    {
        $model = new Item();

        $validationRules = [
            ['required'],
        ];
        $model->rules = $validationRules;
        $validatorList = $model->getValidators();

        $this->assertEquals(count($validationRules)+1, $validatorList->count(), 'Unable to set validation rules!');

        $validator = $validatorList->offsetGet(1);
        $this->assertTrue($validator instanceof RequiredValidator, 'Wrong validator created!');
    }

    /**
     * Data provider for {@link testExtractCurrentValue}
     * @return array test data.
     */
    public function dataProviderExtractCurrentValue()
    {
        return [
            [
                'name',
                'Test Application Name',
            ],
            [
                'params.param1',
                'param1value',
            ],
            [
                ['params', 'param1'],
                'param1value',
            ],
            [
                'components.formatter.nullDisplay',
                'testNullDisplay',
            ],
            [
                'modules.admin.layout',
                'default',
            ],
        ];
    }

    /**
     * @dataProvider dataProviderExtractCurrentValue
     *
     * @param $path
     * @param $expectedValue
     */
    public function testExtractCurrentValue($path, $expectedValue)
    {
        $model = new Item();
        $model->path = $path;
        $this->assertEquals($expectedValue, $model->extractCurrentValue());
    }

    /**
     * @depends testExtractCurrentValue
     */
    public function testGetDefaultValue()
    {
        $model = new Item();
        $model->path = 'params.param1';
        $defaultValue = $model->getValue();
        $this->assertEquals('param1value', $defaultValue, 'Wrong default value!');
    }

    /**
     * Data provider for {@link testComposeConfig}.
     * @return array test data.
     */
    public function dataProviderComposeConfig()
    {
        return [
            [
                'name',
                [
                    'name' => 'value'
                ],
            ],
            [
                'params.param1',
                [
                    'params' => [
                        'param1' => 'value'
                    ],
                ]
            ],
            [
                'components.formatter.nullDisplay',
                [
                    'components' => [
                        'formatter' => [
                            'nullDisplay' => 'value'
                        ],
                    ],
                ],
            ],
            [
                'modules.admin.layout',
                [
                    'modules' => [
                        'admin' => [
                            'layout' => 'value'
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataProviderComposeConfig
     *
     * @param $path
     * @param array $expectedConfig
     */
    public function testComposeConfig($path, array $expectedConfig)
    {
        $model = new Item();
        $model->path = $path;
        $model->value = 'value';
        $this->assertEquals($expectedConfig, $model->composeConfig());
    }

    /**
     * @depends testGetDefaultValue
     */
    public function testCustomSource()
    {
        $source = new \stdClass();
        $source->someField = 'some-value';

        $model = new Item();
        $model->source = $source;
        $model->path = ['someField'];
        $this->assertEquals($source->someField, $model->getValue(), 'Wrong default value from custom source!');
    }
}