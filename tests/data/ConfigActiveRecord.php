<?php

namespace yii2tech\tests\unit\config\data;

use yii\db\ActiveRecord;

/**
 * @property string $id
 * @property string $value
 * @property string $group
 */
class ConfigActiveRecord extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '_test_config';
    }
}