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
use yii\helpers\Url;
use sem\helpers\FileHelper;

/**
 * Компонент-конфигуратор механизма работы с загружаемыми файлами пользователя
 */
class FileStorage extends Component
{

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
     * @param bool $isAbsolute
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
        $path = $this->getStorageComponent()->getUploadPath($groupCode, $objectId);

        if (!file_exists($path)) {
            return FileHelper::createDirectory($path);
        } else {
            return true;
        }
        
        return false;
    }
}
