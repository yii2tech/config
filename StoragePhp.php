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
    public function save(array $values)
    {
        $this->clear();
        $fileName = Yii::getAlias($this->fileName);
        FileHelper::createDirectory(dirname($fileName));
        $bytesWritten = file_put_contents($fileName, $this->composeFileContent($values));
        $this->invalidateScriptCache($fileName);
        return ($bytesWritten > 0);
    }

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        $fileName = Yii::getAlias($this->fileName);
        if (file_exists($fileName)) {
            return require($fileName);
        } else {
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $fileName = Yii::getAlias($this->fileName);
        if (file_exists($fileName)) {
            $this->invalidateScriptCache($fileName);
            return unlink($fileName);
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
        $content = "<?php\n\nreturn " . VarDumper::export($values) . ';';
        return $content;
    }

    /**
     * Invalidates precompiled script cache (such as OPCache or APC) for the given file.
     * @param string $fileName file name.
     * @since 1.0.2
     */
    protected function invalidateScriptCache($fileName)
    {
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($fileName, true);
        }
        if (function_exists('apc_delete_file')) {
            @apc_delete_file($fileName);
        }
    }
}