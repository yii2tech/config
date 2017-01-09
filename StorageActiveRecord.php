<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\config;

/**
 * StorageActiveRecord is an configuration storage based on ActiveRecord.
 *
 * @see \yii\db\ActiveRecordInterface
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class StorageActiveRecord extends Storage
{
    /**
     * @var string name of the ActiveRecord class, which should be used for data finding and saving.
     * This class should match [[\yii\db\ActiveRecordInterface]] interface.
     */
    public $activeRecordClass;


    /**
     * @inheritdoc
     */
    public function save(array $values)
    {
        $activeRecordClass = $this->activeRecordClass;
        $this->clear();
        $result = true;
        foreach ($values as $id => $value) {
            /* @var $model \yii\db\ActiveRecordInterface */
            $data[] = [$id, $value];
            $model = new $activeRecordClass();
            $model->id = $id;
            $model->value = $value;
            $result = $result && $model->save(false);
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function get()
    {
        /* @var $activeRecordClass \yii\db\ActiveRecordInterface */
        $activeRecordClass = $this->activeRecordClass;
        $rows = $activeRecordClass::find()->asArray(true)->all();
        $values = [];
        foreach ($rows as $row) {
            $values[$row['id']] = $row['value'];
        }
        return $values;
    }

    /**
     * @inheritdoc
     */
    public function clear()
    {
        /* @var $activeRecordClass \yii\db\ActiveRecordInterface */
        $activeRecordClass = $this->activeRecordClass;
        $activeRecordClass::deleteAll();
        return true;
    }

    /**
     * @inheritdoc
     */
    public function clearValue($id)
    {
        /* @var $activeRecordClass \yii\db\ActiveRecordInterface */
        $activeRecordClass = $this->activeRecordClass;
        $activeRecordClass::deleteAll(['id' => $id]);
        return true;
    }
}