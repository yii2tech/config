<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\config;

use Yii;
use yii\db\Connection;
use yii\db\Query;
use yii\di\Instance;

/**
 * StorageDb represents the configuration storage based on database table.
 * Example migration for such table:
 *
 * ```php
 * $tableName = 'AppConfig';
 * $columns = [
 *     'id' => 'string',
 *     'value' => 'text',
 *     'PRIMARY KEY(id)',
 * ];
 * $this->createTable($tableName, $columns);
 * ```
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class StorageDb extends Storage
{
    /**
     * @var Connection|array|string the DB connection object or the application component ID of the DB connection.
     * After the StorageDb object is created, if you want to change this property, you should only assign it
     * with a DB connection object.
     */
    public $db = 'db';
    /**
     * @var string name of the table, which should store values.
     */
    public $table = 'AppConfig';


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->db = Instance::ensure($this->db, Connection::className());
    }

    /**
     * Saves given values.
     * @param array $values in format: 'id' => 'value'
     * @return boolean success.
     */
    public function save(array $values)
    {
        $this->clear();
        $data = [];
        foreach ($values as $id => $value) {
            $data[] = [$id, $value];
        }
        $command = $this->db->createCommand()->batchInsert($this->table, ['id', 'value'], $data);
        $insertedRowsCount = $command->execute();
        return (count($values) === $insertedRowsCount);
    }

    /**
     * Returns previously saved values.
     * @return array values in format: 'id' => 'value'
     */
    public function get()
    {
        $query = new Query();
        $rows = $query->from($this->table)->all();
        $values = [];
        foreach ($rows as $row) {
            $values[$row['id']] = $row['value'];
        }
        return $values;
    }

    /**
     * Clears all saved values.
     * @return boolean success.
     */
    public function clear()
    {
        $this->db->createCommand()->delete($this->table)->execute();
        return true;
    }
}