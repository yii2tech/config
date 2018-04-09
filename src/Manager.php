<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\config;

use Yii;
use yii\base\BootstrapInterface;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\base\Module;
use yii\caching\Cache;
use yii\di\Instance;
use yii\helpers\ArrayHelper;

/**
 * Manager allows management of the dynamic application configuration parameters.
 * Configuration parameters are set up via [[items]].
 * Parameters can be saved inside the persistent storage determined by [[storage]].
 *
 * Manager implements [[BootstrapInterface]], so if you want to apply it to application
 * you should place it at 'bootstrap' configuration section.
 *
 * Application configuration example:
 *
 * ```php
 * [
 *     'bootstrap' => [
 *         'configManager',
 *         // ...
 *     ],
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
 *                 'nullDisplay' => [
 *                     'path' => 'components.formatter.nullDisplay',
 *                     'label' => 'HTML representing not set value',
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
 * $configManager->configure(Yii::$app);
 * ```
 *
 * @see Item
 * @see Storage
 *
 * @property array[]|Item[]|string|callable $items public alias of {@link _items}.
 * @property Storage|array $storage public alias of {@link _storage}.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class Manager extends Component implements BootstrapInterface
{
    /**
     * @var Cache|array|string id of the cache object or the application component ID of the DB connection.
     * After the Manager object is created, if you want to change this property, you should only assign it
     * with a Cache object.
     */
    public $cache = 'cache';
    /**
     * @var string|callable id, which will be used to stored composed application configuration in the cache.
     * Since 1.0.4 this field can be specified as a PHP callback, which should return actual cache ID.
     * For example:
     *
     * ```php
     * function () {
     *     return 'config-' . Yii::$app->user->id;
     * }
     * ```
     */
    public $cacheId = __CLASS__;
    /**
     * @var int duration of cache for models in seconds.
     * `0` means never expire.
     * Set this parameter to a negative integer to avoid caching.
     */
    public $cacheDuration = 0;
    /**
     * @var bool whether to automatically restore item values from [[storage]] on component initialization.
     * Enabling this option make sense only in case you wish to use manager as a standalone data storage, using
     * [[getItemValues()]] or [[getItemValue()]] for the data access.
     * In cas you invoke [[configure()]], [[fetchConfig()]] or [[restoreValues()]] methods this option should be disabled,
     * otherwise it will cause redundant storage data reading.
     * @since 1.0.4
     */
    public $autoRestoreValues = false;
    /**
     * @var object|null configuration source object for the [[items]].
     * If not set current Yii application instance will be used.
     * @see Item::$source
     * @since 1.0.4
     */
    public $source;
    /**
     * @var bool whether to shutdown any exception throwing on attempt to apply configuration for object field or property.
     * If enabled exception will be logged instead of being thrown.
     * Note: this option will not affect configuration of [[Module::$components]] or [[Module::$modules]].
     * @since 1.0.6
     */
    public $ignoreConfigureError = false;

    /**
     * @var array[]|Item[]|string|callable config items in format: id => configuration.
     * This filed can be setup as PHP file name, which returns the array of items.
     * Since 1.0.4 this value can be specified as a PHP callback, which should return actual items configuration.
     * For example:
     *
     * ```php
     * function () {
     *     return [
     *         'appName' => [
     *             'path' => 'name',
     *             'label' => Yii::t('app', 'Application Name'),
     *         ]
     *     ];
     * }
     * ```
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
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        $this->cache = Instance::ensure($this->cache, Cache::className());

        if (!is_scalar($this->cacheId)) {
            $this->cacheId = call_user_func($this->cacheId);
        }

        if ($this->autoRestoreValues) {
            $this->restoreValues();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function bootstrap($app)
    {
        try {
            $this->configure($app);
        } catch (\Exception $exception) {
            // fetching config from storage like database may fail at initial point, like before DB migrations applied
            Yii::warning($exception->getMessage(), __METHOD__);
        }
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
        $config['source'] = $this->source;
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
                $fileName = Yii::getAlias($this->_items);
                if (file_exists($fileName)) {
                    $this->_items = require($fileName);
                    if (!is_array($this->_items)) {
                        throw new InvalidConfigException('File "' . $fileName . '" should return an array.');
                    }
                } else {
                    throw new InvalidConfigException('File "' . $this->_items . '" does not exist.');
                }
            } else {
                $items = call_user_func($this->_items);
                if (!is_array($items)) {
                    throw new InvalidConfigException('Callback for "' . get_class($this) . '::$items" should return an array.');
                }
                $this->_items = $items;
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
     * Returns value of the particular item.
     * @param string $id config item ID.
     * @return mixed item value.
     * @since 1.0.4
     */
    public function getItemValue($id)
    {
        $item = $this->getItem($id);
        return $item->getValue();
    }

    /**
     * Composes application configuration array from config items.
     * @return array application configuration.
     */
    public function composeConfig()
    {
        $config = [];
        foreach ($this->getItems() as $item) {
            $config = ArrayHelper::merge($config, $item->composeConfig());
        }
        return $config;
    }

    /**
     * Saves the current config item values into the persistent storage.
     * @return bool success.
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
     * @return bool success.
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
     * Clears config item values saved in the persistent storage.
     * @param string $id ID of the item to be cleared.
     * @return bool success.
     * @since 1.0.3
     */
    public function clearValue($id)
    {
        $result = $this->getStorage()->clearValue($id);
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
     * @return bool whether the validation is successful without any error.
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

    /**
     * Configures given module or other object with provided configuration array.
     * @param Module|object $module module or plain object to be configured.
     * @param array $config configuration array.
     */
    public function configure($module, $config = null)
    {
        if ($config === null) {
            $config = $this->fetchConfig();
        }

        if (!$module instanceof Module) {
            foreach ($config as $key => $value) {
                $this->configureObjectProperty($module, $key, $value);
            }
            return;
        }

        foreach ($config as $key => $value) {
            switch ($key) {
                case 'components':
                    $components = array_merge($module->getComponents(true), $module->getComponents(false));
                    $components = ArrayHelper::merge($components, $value);
                    $module->setComponents($components);
                    break;
                case 'modules':
                    $nestedModules = $module->getModules(false);
                    foreach ($nestedModules as $id => $nestedModule) {
                        if (!isset($value[$id])) {
                            continue;
                        }
                        if (is_object($nestedModule)) {
                            $this->configure($nestedModule, $value[$id]);
                        } else {
                            $nestedModules[$id] = ArrayHelper::merge($nestedModule, $value[$id]);
                        }
                    }
                    $module->setModules($nestedModules);
                    break;
                case 'params':
                    $module->params = ArrayHelper::merge($module->params, $value);
                    break;
                default:
                    $this->configureObjectProperty($module, $key, $value);
            }
        }
    }

    /**
     * Sets object property with given value, handling the possible exceptions if [[ignoreConfigureError]] is enabled.
     * @param object $object object to be configured.
     * @param string $name property name.
     * @param mixed $value property value.
     * @since 1.0.5
     */
    protected function configureObjectProperty($object, $name, $value)
    {
        if (!$this->ignoreConfigureError) {
            $object->{$name} = $value;
            return;
        }

        try {
            $object->{$name} = $value;
        } catch (\Exception $e) {
            Yii::warning($e->getMessage(), __METHOD__);
        } catch (\Throwable $e) {
            Yii::warning($e->getMessage(), __METHOD__);
        }
    }
}