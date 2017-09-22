<?php
/**
 * @author Самсонов Владимир <samsonov.sem@gmail.com>
 * @copyright Copyright &copy; S.E.M. 2017-
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */
namespace sem\filestorage;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\ErrorException;
use yii\helpers\Url;
use sem\helpers\FileHelper;
use sem\filestorage\models\BaseFile;

/**
 * Компонент-конфигуратор механизма работы с загружаемыми файлами пользователя
 * @property BaseFile $file
 */
class FileStorage extends Component
{

    /**
     * Наименование директори кеша
     */
    const CACHE_DIR_NAME = 'cache';

    /**
     * Базовый URL, который будет подставляться при генерации url к файлу.
     * Если false, то будет использован текущий хост при генерации абсолютных URL-адресов
     * @var string|false
     */
    public $storageBaseUrl = false;

    /**
     * Базовый путь к доступной из web директории,
     * в которой будет размещаться директория для хранения файлов [[$storageDir]]
     * @var string
     */
    public $storagePath = '@webroot';

    /**
     * Наименование директории для хранения файлов
     * @var string
     */
    public $storageDir = 'upload';
    
    /**
     * Если заданы права, 
     * то после создания файла они будут принудительно назначены
     * @var number|null
     */
    public $filemode;


    /**
     * Файл, для которого производится вычисление путей
     * @var BaseFile|null
     */
    protected $_file;

    /**
     * При инициализации проверяем необходимые конфигурационные переменные
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (is_null($this->storageBaseUrl) || ($this->storageBaseUrl !== false && trim($this->storageBaseUrl) === '')) {
            throw new InvalidConfigException("Параметр 'storageBaseUrl' имеет неверное значение");
        }

        if (!$this->storagePath) {
            throw new InvalidConfigException("Параметр 'storagePath' должен быть указан");
        }

        if (!$this->storageDir) {
            throw new InvalidConfigException("Параметр 'storageDir' должен быть указан");
        }
    }
    
    /**
     * Устанавливает файл для вычислений
     * @param BaseFile $file
     * @return \sem\filestorage\FileStorage
     */
    public function setFile(BaseFile $file)
    {
        $this->_file = $file;
        
        return $this;
    }
    
    /**
     * Возвращает файл для производства вычислений путей
     * @throws ErrorException в случае, если файл не был задан
     */
    protected function getFile()
    {
        if (is_null($this->_file)) {
            throw new ErrorException("Не задан файл для операций");
        }
        
        return $this->_file;
    }

    /**
     * Возвращает абсолютный путь к директории хранения файлов определенного типа
     * 
     * @return string
     */
    public function getUploadPath()
    {
        $path = $this->storagePath . DIRECTORY_SEPARATOR . $this->storageDir . DIRECTORY_SEPARATOR . $this->file->group_code;

        if ($this->file->object_id) {
            $path .= DIRECTORY_SEPARATOR . $this->file->object_id;
        }

        return FileHelper::normalizePath(Yii::getAlias($path));
    }
    
    /**
     * Возвращает полный путь к файлу в файловой системе
     * @return string
     */
    public function getFilePath()
    {
        return $this->uploadPath . DIRECTORY_SEPARATOR . $this->file->sys_file;
    }

    /**
     * Возвращает URL-адрес до директории нахождения файлов определенного типа
     * 
     * @param bool $isAbsolute возвращать абсолютный (полный) URL
     * @return string
     */
    public function getUploadUrl($isAbsolute = false)
    {

        $url = '/' . $this->storageDir . '/' . $this->file->group_code;

        if ($this->file->object_id) {
            $url .= '/' . $this->file->object_id;
        }

        if ($this->storageBaseUrl !== false) {

            $url = Url::to($this->storageBaseUrl . $url, true);
            
        } else {

            if ($isAbsolute) {
                $url = Url::base(true) . $url;
            }
        }

        return $url;
    }
    
    /**
     * Возвращает абсолютный или относительный URL-адрес к файлу
     * 
     * @param bool $isAbsolute возвращать абсолютный (полный) URL
     * @return string
     */
    public function getFileUrl($isAbsolute = false)
    {
        return $this->getUploadUrl($isAbsolute) . '/' . $this->file->sys_file;
    }

    /**
     * Проверяет существование директории загрузок и если она не существует, то создает ее
     * 
     * @return boolean
     */
    public function touchUploadDir()
    {
        $path = $this->uploadPath;

        if (!file_exists($path)) {
            return FileHelper::createDirectory($path);
        } else {
            return true;
        }

        return false;
    }

    /**
     * Проверяет существование директории загрузок кеша и если она не существует, то создает ее
     * 
     * @return boolean
     */
    public function touchUploadCacheDir()
    {
        $path = $this->uploadCachePath;

        if (!file_exists($path)) {
            return FileHelper::createDirectory($path);
        } else {
            return true;
        }

        return false;
    }

    /**
     * Возвращает абсолютный путь к директории хранения файлов кеша определенного типа
     * 
     * @return string
     */
    public function getUploadCachePath()
    {
        return $this->uploadPath . DIRECTORY_SEPARATOR . self::CACHE_DIR_NAME;
    }

    /**
     * Возвращает URL-адрес файлу кеша определенного типа
     *
     * @param bool $isAbsolute возвращать абсолютный (полный) URL
     * @return string
     */
    public function getUploadCacheUrl($isAbsolute = false)
    {
        return $this->getUploadUrl($isAbsolute) . '/' . self::CACHE_DIR_NAME;
    }

    /**
     * Генерирует имя кеш-файла оригинала
     * 
     * @param string $function функция преобразования оригинала
     * @param string $params массив значений для примеси в имя
     * @return string
     */
    public function generateCacheFilename($function, $params)
    {
        return mb_substr($function, 0, 3, 'UTF-8') . '_' . implode('_', $params) . '_' . $this->file->sys_file;
    }
    
    /**
     * Производит удаление директории кеша файлов определеного типа
     * 
     * @return bool
     */
    public function flushUploadDirCache()
    {
        try {
            FileHelper::removeDirectory($this->uploadCachePath);
        } catch (ErrorException $exc) {
            return false;
        }
        
        return true;
    }

    /**
     * Производит удаление файлов кеша оригинального файла
     * 
     * @return boolean
     */
    public function flushFileCache()
    {
        if (!file_exists($this->uploadCachePath)) {
            return true;
        }
        
        foreach (FileHelper::findFiles($this->uploadCachePath, [
            'recursive' => false,
            'only' => [
                '*_' . $this->file->sys_file,
            ],
            'caseSensitive' => false
        ]) as $file) {
            
            if (!@unlink($file)) {
                return false;
            }
            
        }
        
        return true;
    }
}
