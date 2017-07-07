<?php
/**
 * @author Самсонов Владимир <samsonov.sem@gmail.com>
 * @copyright Copyright &copy; S.E.M. 2017-
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */
namespace sem\filestorage\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\web\UploadedFile;
use sem\helpers\FileHelper;
use sem\filestorage\FileStorage;

/**
 * AR-модель для хранения и доступа к файлам разных типов
 *
 * @property integer $id
 * @property string $group_code
 * @property string $object_id
 * @property string $ori_name
 * @property string $ori_extension
 * @property string $sys_file
 * @property string $mime
 * @property inetegr $size
 * @property integer $created_at
 * @property integer $updated_at
 * 
 * @property string $url
 * @property string $path
 * @property string $name
 * 
 * @property-read \sem\filestorage\FileStorage $storageComponent
 */
abstract class BaseFile extends \yii\db\ActiveRecord
{

    /**
     * Имя компоненнта для работы с загружаемыми файлами
     * [[\sem\filestorage\FileStorage]]
     * @var string
     */
    public $storageComponentName = 'filestorage';

    /**
     * Загруженный файл
     * @var \yii\web\UploadedFile
     */
    protected $_file;

    /**
     * Относительный URL-адрес к файлу
     * @var string
     */
    protected $_url;

    /**
     * Абсолютный URL-адрес к файлу
     * @var string
     */
    protected $_absoluteUrl;

    /**
     * Абсолютный путь к файлу
     * @var string
     */
    protected $_path;

    /**
     * Перечень разрешенных расширений файлов к сохранению
     * @var array|null
     */
    public $allowedExtensions;

    /**
     * Список возможных ошибок при загрузке файлов
     * @var array 
     */
    protected static $_fileErrorsList = [
        UPLOAD_ERR_INI_SIZE => "Размер принятого файла превысил максимально допустимый размер",
        UPLOAD_ERR_FORM_SIZE => "Размер принятого файла превысил максимально допустимый размер",
        UPLOAD_ERR_PARTIAL => "Загружаемый файл был получен только частично",
        UPLOAD_ERR_NO_FILE => "Файл не был загружен",
        UPLOAD_ERR_NO_TMP_DIR => "Отсутствует временная папка",
        UPLOAD_ERR_CANT_WRITE => "Не удалось записать файл на диск",
        UPLOAD_ERR_EXTENSION => "PHP-расширение остановило загрузку файла"
    ];

    /**
     * Инициализируем подгруженный файл
     * @param UploadedFile $file
     * @param array $config
     * @inheritdoc
     */
    public function __construct(UploadedFile $file = null, $config = [])
    {
        if ($file instanceof UploadedFile) {
            $this->_file = $file;

            $this->mime = $this->_file->type;
            $this->ori_extension = $this->_file->extension;
            $this->ori_name = $this->_file->baseName;
            $this->sys_file = uniqid() . '.' . $this->_file->extension;
            $this->size = $this->_file->size;
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function transactions()
    {
        return [
            'default' => self::OP_ALL,
        ];
    }

    /**
     * Выполняем проверку на существование компонента
     * [[\sem\filestorageFileStorage]]
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        parent::init();
        if (
            (!isset(Yii::$app->{$this->storageComponentName})) ||
            (!$this->getStorageComponent() instanceof FileStorage)
        ) {
            throw new \yii\base\InvalidConfigException("Компонент для работы с загружаемыми файлами не подключен");
        }
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%file}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['ori_name'], function ($attribute, $params) {
                if (!$this->hasErrors($attribute) && $this->_file && $this->_file->hasError) {
                    $this->addError($attribute, self::getErrorDescription($this->_file->error));
                }
            }, 'skipOnEmpty' => false],
            [['group_code', 'ori_name', 'ori_extension', 'sys_file', 'mime'], 'required'],
            [['created_at', 'updated_at', 'size'], 'integer'],
            [['group_code', 'ori_extension'], 'string', 'max' => 16],
            [['object_id'], 'string', 'max' => 11],
            [['ori_name', 'sys_file', 'mime'], 'string', 'max' => 255],
            [['sys_file'], 'unique'],
            [[
                'group_code',
                'object_id',
                'ori_name',
                'ori_extension',
                'sys_file',
                'mime',
                ],
                'filter',
                'filter' => '\yii\helpers\Html::encode'
            ],
            [['ori_extension'], function ($attribute, $params) {
                if (!$this->hasErrors($attribute) && $this->_file && !empty($this->allowedExtensions)) {

                    $extension = mb_strtolower($this->_file->extension, 'UTF-8');

                    if (!in_array($extension, $this->allowedExtensions, true)) {
                        $this->addError($attribute, "Файл с расширением {$this->_file->extension} не допустим к загрузке!");
                    }
                }
            }, 'skipOnEmpty' => false]
        ];
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    self::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    self::EVENT_BEFORE_UPDATE => ['updated_at']
                ],
            ]
        ];
    }

    /**
     * После успешного сохранения, сохраняем файлы
     * @inheritdoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if (!$this->saveFile()) {
            throw new \yii\base\Exception("Не удалось сохранить файл!");
        }
        
        if ($this->storageComponent->filemode) {
            if (!chmod($this->storageComponent->filePath, $this->storageComponent->filemode)) {
                throw new \yii\base\Exception("Не удалось изменить права на созданный файл на {$this->storageComponent->filemode}!");
            }
        }
    }

    /**
     * Перед удалением, удаляем физически файл
     * @return boolean
     */
    public function beforeDelete()
    {
        if (parent::beforeDelete()) {

            return $this->removeFile();
        }
        return false;
    }

    /**
     * Выполняет проверку является ли текущий файл изображением
     * @return boolean
     */
    public function getIsImage()
    {
        if ($this->_file) {

            $filePath = $this->_file->tempName;
            
        } else {

            $filePath = $this->path;
        }

        if (false === getimagesize($filePath)) {
            return false;
        }

        return true;
    }

    /**
     * Возвращает компонент для работы с загружаемыми файлами пользователя
     * @return \sem\filestorage\FileStorage
     */
    protected function getStorageComponent()
    {
        return Yii::$app->{$this->storageComponentName}->setFile($this);
    }

    /**
     * Возвращает оригинальное имя файла вместе с расширением
     * @return string
     */
    public function getName()
    {
        return $this->ori_name . '.' . $this->ori_extension;
    }

    /**
     * Возвращает абсолютный путь к файлу
     * @return string|false
     */
    public function getPath()
    {
        if (!$this->isNewRecord) {

            if (is_null($this->_path)) {
                $this->_path = $this->storageComponent->filePath;
            }

            return $this->_path;
        }

        return false;
    }

    /**
     * Возвращает URL-адрес к файлу относительно домена
     * 
     * @param bool $isAbsolute абсолютный или относительный
     * @return string|false
     */
    public function getUrl($isAbsolute = false)
    {
        if (!$this->isNewRecord) {

            $url = $this->storageComponent->getFileUrl($isAbsolute);
            
            if (!$isAbsolute) {

                if (is_null($this->_url)) {
                    $this->_url = $url;
                }

                return $this->_url;
                
            } else {

                if (is_null($this->_absoluteUrl)) {
                    $this->_absoluteUrl = $url;
                }

                return $this->_absoluteUrl;
            }
        }

        return false;
    }

    /**
     * Файл не был загружен
     * 
     * @param integer $code
     * @return string
     */
    protected static function getErrorDescription($code)
    {
        return isset(self::$_fileErrorsList[$code]) ? self::$_fileErrorsList[$code] : self::$_fileErrorsList[UPLOAD_ERR_NO_FILE];
    }

    /**
     * Сбрасывает значения путей к файлу в NULL
     */
    protected function resetPathes()
    {
        $this->_url = null;
        $this->_absoluteUrl = null;
        $this->_path = null;
    }

    /**
     * Производит сохранение файла в файловую систему
     * @return bool
     */
    abstract protected function saveFile();

    /**
     * Производит удаление файла из файловой системы
     * @return bool
     */
    abstract protected function removeFile();
}
