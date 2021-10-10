<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\config;

use Yii;
use yii\helpers\FileHelper;
use yii\helpers\VarDumper;

/**
 * StoragePhp represents the configuration storage based on local PHP files.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class StoragePhp extends Storage
{
    /**
     * @var string name of the file, which should be used to store values.
     */
    public $fileName = '@runtime/app_config.php';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        $this->fileName = Yii::getAlias($this->fileName);
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $values)
    {
        if (!file_exists($this->fileName)) {
            FileHelper::createDirectory(dirname($this->fileName));
        }
        $bytesWritten = file_put_contents($this->fileName, $this->composeFileContent($values));
        $this->invalidateScriptCache();
        return $bytesWritten > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        if (file_exists($this->fileName)) {
            return require($this->fileName);
        }
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        if (file_exists($this->fileName)) {
            $this->invalidateScriptCache();
            return unlink($this->fileName);
        }
        return true;
    }

    /**
     * Composes file content for the given values.
     * @param array $values values to be saved.
     * @return string file content.
     */
    protected function composeFileContent(array $values)
    {
        return "<?php\n\nreturn " . VarDumper::export($values) . ';';
    }

    /**
     * Invalidates precompiled script cache (such as OPCache or APC) for the given file.
     * @since 1.0.2
     */
    protected function invalidateScriptCache()
    {
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($this->fileName, true);
        }
        if (function_exists('apc_delete_file')) {
            @apc_delete_file($this->fileName);
        }
    }
}
