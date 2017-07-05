<?php
/**
 * @author Самсонов Владимир <samsonov.sem@gmail.com>
 * @copyright Copyright &copy; S.E.M. 2017-
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */
namespace sem\filestorage\adapters;

use yii\web\UploadedFile;
use yii\base\NotSupportedException;
use sem\helpers\FileHelper;

/**
 * Класс реализует логику загрузки и сохранения удаленного файла
 */
class RemoteFile extends UploadedFile
{

    /**
     * @param string $url URL-адрес для загрузки файла
     * @inheritdoc
     */
    public function __construct($url, $config = array())
    {
        
        if (($f = @file_get_contents($url)) !== false) {
            
            
            if ($tmp = tempnam(sys_get_temp_dir(), "f_")) {
            
                if (@file_put_contents($tmp, $f) !== false) {

                    // Прямая инициализация объекта
                    $this->tempName = $tmp;
                    $this->name = basename($url);
                    $this->size = filesize($this->tempName);
                    $this->type = FileHelper::getMimeTypeByExtension($this->name);
                    $this->error = UPLOAD_ERR_OK;


                } else {

                    $this->error = UPLOAD_ERR_CANT_WRITE;

                }
            
            } else {

                $this->error = UPLOAD_ERR_NO_TMP_DIR;
            }
            
        } else {
            
            $this->error = UPLOAD_ERR_NO_FILE;
            
        }

        parent::__construct($config);
    }
    
    /**
     * @inheritdoc
     */
    public function saveAs($file, $deleteTempFile = true)
    {
        if ($this->error == UPLOAD_ERR_OK) {
            if ($deleteTempFile) {
                return rename($this->tempName, $file);
            } else {
                return copy($this->tempName, $file);
            }
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function getInstance($model, $attribute)
    {
        throw new NotSupportedException("Метод 'getInstance' не поддерживается для данной реализации");
    }
    
    /**
     * @inheritdoc
     */
    public static function getInstances($model, $attribute)
    {
        throw new NotSupportedException("Метод 'getInstances' не поддерживается для данной реализации");
    }
    
    /**
     * @inheritdoc
     */
    public static function getInstanceByName($name)
    {
        throw new NotSupportedException("Метод 'getInstanceByName' не поддерживается для данной реализации");
    }
    
    /**
     * @inheritdoc
     */    
    public static function getInstancesByName($name)
    {
        throw new NotSupportedException("Метод 'getInstancesByName' не поддерживается для данной реализации");
    }
    
    /**
     * @inheritdoc
     */
    public static function reset()
    {
        throw new NotSupportedException("Метод 'reset' не поддерживается для данной реализации");
    }
}
