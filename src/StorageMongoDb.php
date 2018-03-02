<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\config;

use yii\di\Instance;
use yii\mongodb\Connection;
use yii\mongodb\Query;

/**
 * StorageMongoDb represents the configuration storage based on MongoDB collection.
 *
 * This storage requires [yiisoft/yii2-mongodb](https://github.com/yiisoft/yii2-mongodb) extension installed.
 *
 * You may use same collection for multiple configuration storage providing [[filter]] value.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class StorageMongoDb extends Storage
{
    use StorageFilterTrait;

    /**
     * @var Connection|array|string the MongoDB connection object or the application component ID of the MongoDB connection.
     * After the StorageMongoDb object is created, if you want to change this property, you should only assign it
     * with a MongoDB connection object.
     */
    public $db = 'mongodb';
    /**
     * @var string|array name of the collection, which should store values.
     */
    public $collection = 'AppConfig';


    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        $this->db = Instance::ensure($this->db, Connection::class);
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $values)
    {
        $this->clear();
        $data = [];
        foreach ($values as $id => $value) {
            $data[] = array_merge(
                $this->composeFilterCondition(),
                [
                    'id' => $id,
                    'value' => $value
                ]
            );
        }
        $this->db->getCollection($this->collection)->batchInsert($data);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        $query = new Query();
        $rows = $query->from($this->collection)
            ->andWhere($this->composeFilterCondition())
            ->all();
        $values = [];
        foreach ($rows as $row) {
            $values[$row['id']] = $row['value'];
        }
        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->db->getCollection($this->collection)->remove($this->composeFilterCondition());
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clearValue($id)
    {
        $this->db->getCollection($this->collection)->remove($this->composeFilterCondition(['id' => $id]));
        return true;
    }
}