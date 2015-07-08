<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\config;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\caching\Cache;
use yii\di\Instance;

/**
 * Manager allows management of the dynamic application configuration parameters.
 * Configuration parameters are set up via [[items]].
 * Parameters can be saved inside the persistent storage determined by [[storage]].
 *
 * Application configuration example:
 *
 * ```php
 * [
 *     'components' => [
 *         'configManager' => [
 *             'class' => 'yii2tech\config\Manager',
 *             'items' => [
 *                 'appName' => [
 *                     'path' => 'name',
 *                     'label' => 'Application Name',
 *                     'rules' => [
 *                         ['required']
 *                     ],
 *                 ],
 *                 'validationKey' => [
 *                     'path' => 'components.securityManager.validationKey',
 *                     'label' => 'CSRF Validation Key',
 *                     'rules' => [
 *                         ['required']
 *                     ],
 *                 ],
 *             ],
 *         ],
 *         ...
 *     ],
 * ];
 * ```
 *
 * Each configuration item is a model and so can be used to compose web form.
 *
 * Configuration apply example:
 *
 * ```php
 * $configManager = Yii::$app->get('configManager');
 * Yii::$app->configure($configManager->fetchConfig());
 * ```
 *
 * @see Item
 * @see Storage
 *
 * @property array[]|Item[]|string $items public alias of {@link _items}.
 * @property Storage|array $storage public alias of {@link _storage}.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class Manager extends Component
{
    /**
     * @var array[]|Item[]|string config items in format: id => configuration.
     * This filed can be setup as PHP file name, which returns the array of items.
     */
    private $_items = [];
    /**
     * @var Storage|array config storage.
     * It should be [[Storage]] instance or its array configuration.
     */
    private $_storage = [
        'class' => 'yii2tech\config\StorageDb'
    ];
    /**
     * @var Cache|array|string id of the cache object or the application component ID of the DB connection.
     * After the Manager object is created, if you want to change this property, you should only assign it
     * with a Cache object.
     */
    public $cache = 'cache';
    /**
     * @var string id, which will be used to stored composed application configuration
     * in the cache.
     */
    public $cacheId = __CLASS__;
    /**
     * @var integer duration of cache for models in seconds.
     * '0' means never expire.
     * Set this parameter to a negative integer to aviod caching.
     */
    public $cacheDuration = 0;


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->cache = Instance::ensure($this->cache, Cache::className());
    }

    /**
     * @param Storage|array|string $storage storage instance or configuration.
     */
    public function setStorage($storage)
    {
        $this->_storage = $storage;
    }

    /**
     * @return Storage storage instance.
     */
    public function getStorage()
    {
        if (!is_object($this->_storage)) {
            $this->_storage = Instance::ensure($this->_storage, Storage::className());
        }
        return $this->_storage;
    }

    /**
     * @param array|string $items items list or configuration file name.
     */
    public function setItems($items)
    {
        $this->_items = $items;
    }

    /**
     * @return Item[] config items list.
     */
    public function getItems()
    {
        $this->normalizeItems();
        $items = [];
        foreach ($this->_items as $id => $item) {
            $items[] = $this->getItem($id);
        }
        return $items;
    }

    /**
     * @param mixed $id item id
     * @return Item config item instance.
     * @throws InvalidParamException on failure.
     */
    public function getItem($id)
    {
        $this->normalizeItems();
        if (!array_key_exists($id, $this->_items)) {
            throw new InvalidParamException("Unknown config item '{$id}'.");
        }
        if (!is_object($this->_items[$id])) {
            $this->_items[$id] = $this->createItem($id, $this->_items[$id]);
        }
        return $this->_items[$id];
    }

    /**
     * Creates config item by given configuration.
     * @param mixed $id item id.
     * @param array $config item configuration.
     * @return Item config item instance
     */
    protected function createItem($id, array $config)
    {
        if (empty($config['class'])) {
            $config['class'] = Item::className();
        }
        $config['id'] = $id;
        return Yii::createObject($config);
    }

    /**
     * Normalizes [[items]] value, ensuring it is array.
     * @throws InvalidConfigException on failure
     */
    protected function normalizeItems()
    {
        if (!is_array($this->_items)) {
            if (is_string($this->_items)) {
                $fileName = $this->_items;
                if (file_exists($fileName)) {
                    $this->_items = require($fileName);
                    if (!is_array($this->_items)) {
                        throw new InvalidConfigException('File "' . $fileName . '" should return an array.');
                    }
                } else {
                    throw new InvalidConfigException('File "' . $this->_items . '" does not exist.');
                }
            } else {
                throw new InvalidConfigException('"' . get_class($this) . '::items" should be array or file name containing it.');
            }
        }
    }

    /**
     * @param array $itemValues config item values.
     * @return Manager self reference.
     */
    public function setItemValues(array $itemValues)
    {
        foreach ($itemValues as $id => $value) {
            $item = $this->getItem($id);
            $item->value = $value;
        }
        return $this;
    }

    /**
     * @return array config item values
     */
    public function getItemValues()
    {
        $itemValues = [];
        foreach ($this->getItems() as $item) {
            $itemValues[$item->id] = $item->value;
        }
        return $itemValues;
    }

    /**
     * Composes application configuration array from config items.
     * @return array application configuration.
     */
    public function composeConfig()
    {
        $itemConfigs = [];
        foreach ($this->getItems() as $item) {
            $itemConfigs[] = $item->composeConfig();
        }
        return call_user_func_array(['yii\helpers\ArrayHelper', 'merge'], $itemConfigs);
    }

    /**
     * Saves the current config item values into the persistent storage.
     * @return boolean success.
     */
    public function saveValues()
    {
        $result = $this->getStorage()->save($this->getItemValues());
        if ($result) {
            $this->cache->delete($this->cacheId);
        }
        return $result;
    }

    /**
     * Restores config item values from the persistent storage.
     * @return Manager self reference.
     */
    public function restoreValues()
    {
        return $this->setItemValues($this->getStorage()->get());
    }

    /**
     * Clears config item values saved in the persistent storage.
     * @return boolean success.
     */
    public function clearValues()
    {
        $result = $this->getStorage()->clear();
        if ($result) {
            $this->cache->delete($this->cacheId);
        }
        return $result;
    }

    /**
     * Composes the application configuration using config item values
     * from the persistent storage.
     * This method caches its result for the better performance.
     * @return array application configuration.
     */
    public function fetchConfig()
    {
        $config = $this->cache->get($this->cacheId);
        if ($config === false) {
            $this->restoreValues();
            $config = $this->composeConfig();
            $this->cache->set($this->cacheId, $config, $this->cacheDuration);
        }
        return $config;
    }

    /**
     * Performs the validation for all config item models at once.
     * @return boolean whether the validation is successful without any error.
     */
    public function validate()
    {
        $result = true;
        foreach ($this->getItems() as $item) {
            $isItemValid = $item->validate();
            $result = $result && $isItemValid;
        }
        return $result;
    }
}