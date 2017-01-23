<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\config;

/**
 * StorageFilterTrait provides support for data saving filter for the [[Storage]], which
 * allows saving of multiple configuration using same entity, for example: same database table.
 *
 * This trait should be applied to [[Storage]] descendant.
 *
 * @see Storage
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0.4
 */
trait StorageFilterTrait
{
    /**
     * @var array|callable data saving filter in hash format, e.g. `['attribute' => 'value']`.
     * For example:
     *
     * ```php
     * [
     *     'group' => 'app',
     *     'category' => 'frontend',
     * ]
     * ```
     *
     * This value can also be specified as a PHP callback, which should return actual filter value.
     * For example:
     *
     * ```php
     * function () {
     *     return ['userId' => Yii::$app->user->id];
     * }
     * ```
     */
    public $filter;


    /**
     * Composes actual filter condition from the given base one, taking in account [[filter]] value.
     * @param array $condition base condition.
     * @return array filter condition.
     */
    public function composeFilterCondition($condition = [])
    {
        $result = [];
        if ($this->filter !== null) {
            if (is_callable($this->filter)) {
                $result = call_user_func($this->filter);
            } else {
                $result = $this->filter;
            }
        }

        if (!empty($condition)) {
            $result = array_merge($result, $condition);
        }

        return $result;
    }
}