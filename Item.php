<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\config;

use Yii;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\validators\Validator;

/**
 * Item represents a single application configuration item.
 * It allows extraction and composition of the config value for the particular
 * config array keys sequence setup by [[path]].
 *
 * @see Manager
 *
 * @property string $value config parameter value.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class Item extends Model
{
    /**
     * @var mixed config parameter unique identifier.
     */
    public $id;
    /**
     * @var string label for the [[value]] attribute.
     */
    public $label = 'Value';
    /**
     * @var mixed config parameter value.
     */
    protected $_value;
    /**
     * @var array validation rules.
     * Unlike the configuration for the common model, each rule should not contain attribute name
     * as it already determined as [[value]].
     */
    public $rules = [];
    /**
     * @var string|array application config path. Path is sequence of the config array keys.
     * It could be either a string, where keys are separated by '.', or an array of keys.
     * For example:
     *
     * ```php
     * 'params.myparam';
     * ['params', 'myparam'];
     * 'components.securityManager.validationKey';
     * ['components', 'securityManager', 'validationKey'];
     * ```
     *
     * If path is not set it will point to [[\yii\base\Module::params]] with the key equals to [[id]].
     */
    public $path;
    /**
     * @var string brief description for the config item.
     */
    public $description;
    /**
     * @var string input type
     */
    public $type;
    /**
     * @var array options for select box
     */
    public $items;

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->_value = $value;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        if ($this->_value === null) {
            $this->_value = $this->extractCurrentValue();
        }
        return $this->_value;
    }

    /**
     * Returns the config path parts.
     * @return array config path parts.
     */
    public function getPathParts()
    {
        if (empty($this->path)) {
            $this->path = $this->composeDefaultPath();
        }
        if (is_array($this->path)) {
            $pathParts = $this->path;
        } else {
            $pathParts = explode('.', $this->path);
        }
        return $pathParts;
    }

    /**
     * @inheritdoc
     */
    public function attribute()
    {
        return [
            'value'
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'value' => $this->label,
        ];
    }

    /**
     * @inheritdoc
     */
    public function createValidators()
    {
        $validators = parent::createValidators();

        $rules = $this->rules;
        array_unshift($rules, ['safe']);

        foreach ($rules as $rule) {
            if ($rule instanceof Validator) {
                $validators->append($rule);
            } elseif (is_array($rule) && isset($rule[0])) { // attributes, validator type
                $validator = Validator::createValidator($rule[0], $this, ['value'], array_slice($rule, 1));
                $validators->append($validator);
            } else {
                throw new InvalidConfigException('Invalid validation rule: a rule must specify validator type.');
            }
        }
        return $validators;
    }

    /**
     * Composes default config path, which points to [[\yii\base\Module::params]] array
     * with key equal to [[id]].
     * @return array config path.
     */
    protected function composeDefaultPath()
    {
        return ['params', $this->id];
    }

    /**
     * Extracts current config item value from the current application instance.
     * @return mixed current value.
     */
    public function extractCurrentValue()
    {
        $pathParts = $this->getPathParts();
        return $this->findConfigPathValue(Yii::$app, $pathParts);
    }

    /**
     * Finds the given config path inside given source.
     * @param array|object $source config source
     * @param array $pathParts config path parts.
     * @return mixed config param value.
     * @throws Exception on failure.
     */
    protected function findConfigPathValue($source, array $pathParts)
    {
        if (empty($pathParts)) {
            throw new Exception('Empty extraction path.');
        }
        $name = array_shift($pathParts);
        if (is_array($source)) {
            if (array_key_exists($name, $source)) {
                $result = $source[$name];
            } else {
                throw new Exception('Key "' . $name . '" not present!');
            }
        } elseif (is_object($source)) {
            if (is_a($source, 'CModule') && $name == 'components') {
                $result = $source->getComponents(false);
            } else {
                if (isset($source->$name)) {
                    $result = $source->$name;
                } else {
                    if (is_a($source, 'ArrayAccess')) {
                        $result = $source[$name];
                    } else {
                        throw new Exception('Property "' . get_class($source) . '::' . $name . '" not present!');
                    }
                }
            }
        } else {
            throw new Exception('Unable to extract path "' . implode('.', $pathParts) . '" from "' . gettype($source) . '"');
        }
        if (empty($pathParts)) {
            return $result;
        } else {
            return $this->findConfigPathValue($result, $pathParts);
        }
    }

    /**
     * Composes application configuration array, which can setup this config item.
     * @return array application configuration array.
     */
    public function composeConfig()
    {
        $pathParts = $this->getPathParts();
        return $this->composeConfigPathValue($pathParts);
    }

    /**
     * Composes the configuration array by given path parts.
     * @param array $pathParts config path parts.
     * @return array configuration array segment.
     * @throws Exception on failure.
     */
    protected function composeConfigPathValue(array $pathParts)
    {
        if (empty($pathParts)) {
            throw new Exception('Empty extraction path.');
        }
        $basis = [];
        $name = array_shift($pathParts);
        if (empty($pathParts)) {
            $basis[$name] = $this->value;
        } else {
            $basis[$name] = $this->composeConfigPathValue($pathParts);
        }
        return $basis;
    }
}