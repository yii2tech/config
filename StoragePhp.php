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
     * Saves given values.
     * @param array $values in format: 'id' => 'value'
     * @return boolean success.
     */
    public function save(array $values)
    {
        $this->clear();
        $fileName = Yii::getAlias($this->fileName);
        FileHelper::createDirectory(dirname($fileName));
        $bytesWritten = file_put_contents($fileName, $this->composeFileContent($values));
        return ($bytesWritten > 0);
    }

    /**
     * Returns previously saved values.
     * @return array values in format: 'id' => 'value'
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
     * Clears all saved values.
     * @return boolean success.
     */
    public function clear()
    {
        $fileName = Yii::getAlias($this->fileName);
        if (file_exists($fileName)) {
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
        $content = "<?php\n\nreturn " . VarDumper::export($values, true) . ';';
        return $content;
    }
}