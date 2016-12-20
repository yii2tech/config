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
use yii\di\ServiceLocator;
use yii\helpers\Inflector;
use yii\validators\Validator;

/**
 * Item represents a single application configuration item.
 * It allows extraction and composition of the config value for the particular
 * config array keys sequence setup by [[path]].
 *
 * @see Manager
 *
 * @property mixed $value config parameter value.
 * @property string $label label for the [[value]] attribute.
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
     * @var string brief description for the config item.
     */
    public $description;
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
     * 'components.formatter.nullDisplay';
     * ['components', 'formatter', 'nullDisplay'];
     * ```
     *
     * If path is not set it will point to [[\yii\base\Module::params]] with the key equals to [[id]].
     */
    public $path;
    /**
     * @var array options, which can be used to composed configuration form input fields.
     */
    public $inputOptions = [];

    /**
     * @var string label for the [[value]] attribute.
     */
    private $_label;
    /**
     * @var mixed config parameter value.
     */
    private $_value;
    /**
     * @var string label for the [[value]] attribute.
     */
    private $_label;

    /**
     * @param mixed $label config parameter label.
     */
    public function setLabel($label)
    {
        $this->_label = $label;
    }

    /**
     * @return mixed config parameter label.
     */
    public function getLabel()
    {
        if ($this->_label === null) {
            $this->_label = Inflector::camel2words($this->id);
        }
        return $this->_label;
    }

    /**
     * @param mixed $value config parameter value.
     */
    public function setValue($value)
    {
        $this->_value = $value;
    }

    /**
     * @return mixed config parameter value.
     */
    public function getValue()
    {
        if ($this->_value === null) {
            $this->_value = $this->extractCurrentValue();
        }
        return $this->_value;
    }

    /**
     * @return string label for the [[value]] attribute.
     * @since 1.0.3
     */
    public function getLabel()
    {
        if ($this->_label === null) {
            $this->_label = is_string($this->id) ? $this->generateAttributeLabel($this->id) : 'Value';
        }
        return $this->_label;
    }

    /**
     * @param string|null $label label for the [[value]] attribute.
     * @since 1.0.3
     */
    public function setLabel($label)
    {
        $this->_label = $label;
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
            return $this->path;
        }
        return explode('.', $this->path);
    }

    /**
     * @inheritdoc
     */
    public function attributes()
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
            'value' => $this->getLabel(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [
            'value' => $this->description
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
            if ($name === 'components' && ($source instanceof ServiceLocator)) {
                $result = $source->getComponents(true);
            } else {
                if (isset($source->$name)) {
                    $result = $source->$name;
                } else {
                    if ($source instanceof \ArrayAccess) {
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
        }
        return $this->findConfigPathValue($result, $pathParts);
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
