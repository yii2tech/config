<?php

namespace yii2tech\tests\unit\config\data;

use yii\db\ActiveRecord;

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