<?php

namespace yii2tech\tests\unit\config;

use yii2tech\config\Storage;
use yii2tech\config\StorageFilterTrait;

class StorageFilterTraitTest extends TestCase
{
    /**
     * Data provider for [[testComposeFilterCondition()]]
     * @return array test data
     */
    public function dataProviderComposeFilterCondition()
    {
        return [
            [
                [],
                [],
                [],
            ],
            [
                ['name' => 'value'],
                [],
                ['name' => 'value'],
            ],
            [
                ['name' => 'value'],
                ['email' => 'johndoe@example.com'],
                ['name' => 'value', 'email' => 'johndoe@example.com'],
            ],
            [
                ['name' => 'value'],
                ['name' => 'override'],
                ['name' => 'override'],
            ],
            [
                function () {
                    return ['name' => 'callback'];
                },
                [],
                ['name' => 'callback'],
            ],
        ];
    }

    /**
     * @dataProvider dataProviderComposeFilterCondition
     *
     * @param array|callable $filter
     * @param array $condition
     * @param array $expectedResult
     */
    public function testComposeFilterCondition($filter, $condition, $expectedResult)
    {
        $storage = new StorageFilter();
        $storage->filter = $filter;
        $this->assertEquals($expectedResult, $storage->composeFilterCondition($condition));
    }
}

class StorageFilter extends Storage
{
    use StorageFilterTrait;

    /**
     * {@inheritdoc}
     */
    public function save(array $values)
    {
        // blank
    }

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        // blank
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        // blank
    }
}