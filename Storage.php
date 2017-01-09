<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\config;

use yii\base\Component;

/**
 * Storage represents the storage for configuration items in format: id => value.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
abstract class Storage extends Component
{
    /**
     * Saves given values.
     * @param array $values in format: 'id' => 'value'
     * @return bool success.
     */
    abstract public function save(array $values);

    /**
     * Returns previously saved values.
     * @return array values in format: 'id' => 'value'
     */
    abstract public function get();

    /**
     * Clears all saved values.
     * @return bool success.
     */
    abstract public function clear();

    /**
     * Clear saved value for the specified item.
     * @param string $id ID of the item to be cleared.
     * @return bool success.
     * @since 1.0.3
     */
    public function clearValue($id)
    {
        $items = $this->get();
        unset($items[$id]);
        return $this->save($items);
    }
}