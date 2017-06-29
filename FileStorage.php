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

/**
 * Компонент-конфигуратор механизма работы с загружаемыми файлами пользователя
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
     * Возвращает абсолютный путь к директории хранения файлов определенного типа
     * 
     * @param string $groupCode группа файлов
     * @param integer|string|null $objectId идентификатор объекта
     * @return string
     */
    public function getUploadPath($groupCode, $objectId = null)
    {
        $path = $this->storagePath . DIRECTORY_SEPARATOR . $this->storageDir . DIRECTORY_SEPARATOR . $groupCode;

        if ($objectId) {
            $path .= DIRECTORY_SEPARATOR . $objectId;
        }

        return FileHelper::normalizePath(Yii::getAlias($path));
    }

    /**
     * Возвращает URL-адрес до директории нахождения файлов определенного типа
     * 
     * @param string $groupCode группа файлов
     * @param integer|string|null $objectId идентификатор объекта
     * @param bool $isAbsolute возвращать абсолютный (полный) URL
     * @return string
     */
    public function getUploadUrl($groupCode, $objectId = null, $isAbsolute = false)
    {

        $url = '/' . $this->storageDir . '/' . $groupCode;

        if ($objectId) {
            $url .= '/' . $objectId;
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
     * Проверяет существование директории загрузок и если она не существует, то создает ее
     * 
     * @param string $groupCode группа файлов
     * @param integer|string|null $objectId идентификатор объекта
     * @return boolean
     */
    public function touchUploadDir($groupCode, $objectId = null)
    {
        $path = $this->getUploadPath($groupCode, $objectId);

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
     * @param string $groupCode группа файлов
     * @param integer|string|null $objectId идентификатор объекта
     * @return boolean
     */
    public function touchUploadCacheDir($groupCode, $objectId = null)
    {
        $path = $this->getUploadPath($groupCode, $objectId) . DIRECTORY_SEPARATOR . self::CACHE_DIR_NAME;

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
     * @param string $groupCode группа файлов
     * @param integer|string|null $objectId идентификатор объекта
     * @return string
     */
    public function getUploadCachePath($groupCode, $objectId = null)
    {
        return $this->getUploadPath($groupCode, $objectId) . DIRECTORY_SEPARATOR . self::CACHE_DIR_NAME;
    }

    /**
     * Возвращает URL-адрес файлу кеша определенного типа
     * 
     * @param string $groupCode группа файлов
     * @param integer|string|null $objectId идентификатор объекта
     * @param bool $isAbsolute возвращать абсолютный (полный) URL
     * @return string
     */
    public function getUploadCacheUrl($groupCode, $objectId = null, $isAbsolute = false)
    {
        return $this->getUploadUrl($groupCode, $objectId, $isAbsolute) . '/' . self::CACHE_DIR_NAME;
    }

    /**
     * Генерирует имя кеш-файла оригинала
     * 
     * @param string $function функция преобразования оригинала
     * @param string $sys_file имя оригинального файла в ФС с расширением
     * @param string $params массив значений для примеси в имя
     * @return string
     */
    public function getCacheFilename($function, $sys_file, $params)
    {
        return mb_substr($function, 0, 3, 'UTF-8') . '_' . implode('_', $params) . '_' . $sys_file;
    }
    
    /**
     * Производит удаление директории кеша файлов определеного типа
     * 
     * @param string $groupCode группа файлов
     * @param integer|string|null $objectId идентификатор объекта
     * @return bool
     */
    public function flushUploadDirCache($groupCode, $objectId = null)
    {
        try {
            FileHelper::removeDirectory($this->getUploadCachePath($groupCode, $objectId));
        } catch (ErrorException $exc) {
            return false;
        }
        
        return true;
    }

    /**
     * Производит удаление файлов кеша оригинального файла
     * 
     * @param string $sysFile имя системного оригинального файла с расширением
     * @param string $groupCode группа файлов
     * @param integer|string|null $objectId идентификатор объекта
     * @return boolean
     */
    public function flushFileCache($sysFile, $groupCode, $objectId = null)
    {
        foreach (FileHelper::findFiles($this->getUploadCachePath($groupCode, $objectId), [
            'recursive' => false,
            'only' => [
                '*_' . $sysFile,
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
