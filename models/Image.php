<?php
/**
 * @author Самсонов Владимир <samsonov.sem@gmail.com>
 * @copyright Copyright &copy; S.E.M. 2017-
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */
namespace sem\filestorage\models;

use Yii;
use yii\helpers\ArrayHelper;
use yii\base\Exception;
use sem\filestorage\models\File;
use Intervention\Image\ImageManager;

/**
 * Реализует логику работы с файлами изображений
 * 
 * {@inheritdoc}
 * @property-read boolean $isImage
 * @property-read integer $width
 * @property-read inetegr $height
 * 
 */
class Image extends File
{
    
    /**
     * Позиционирование от левого верхнего края
     */
    const POSITION_TOP_LEFT = 'top-left';
    
    /**
     * Позиционирование от верха
     */
    const POSITION_TOP = 'top';
    
    /**
     * Позиционирование от верхнего правого края
     */
    const POSITION_TOP_RIGHT = 'top-right';
    
    /**
     * Позиционирование от левого края
     */
    const POSITION_LEFT = 'left';
    
    /**
     * Позиционирование от ценрта (по-умолчанию)
     */
    const POSITION_CENTER = 'center';
    
    /**
     * Позиционирование от правого края
     */
    const POSITION_RIGHT = 'right';
    
    /**
     * Позиционирование от нижнего левого края
     */
    const POSITION_BOTTOM_LEFT = 'bottom-left';
    
    /**
     * Позиционирование от нижнего края
     */
    const POSITION_BOTTOM = 'bottom';
    
    /**
     * Позиционирование от нижнего правого края
     */
    const POSITION_BOTTOM_RIGHT = 'bottom-right';

    /**
     * Имя файла кеша
     * @var string
     */
    protected $_cacheFileName;
    
    /**
     * Полный путь к файлу кеша
     * @var string
     */
    protected $_cacheFilePath;
    
    /**
     * @see [[getImager()]]
     * @var \Intervention\Image\Image
     */
    protected $_imager;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
                [['ori_extension'], function ($attribute, $params) {

                    if (!$this->hasErrors($attribute) && $this->_file) {

                        if (!$this->isImage) {
                            $this->addError($attribute, "Загружаемый файл не является изображением!");
                        }
                    }
                }]
        ]);
    }
    
    /**
     * @inheritdoc
     */
    public function beforeDelete()
    {
        if (parent::beforeDelete() && $this->clearCache()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            
            if (!$insert) {
                $this->clearCache();
            }
            
            return true;
        }
        
        return false;
    }

        /**
     * @inheritDoc
     */
    protected function removeFile()
    {
        // Чтобы заново усановить путь к оригинальному изображению
        $this->resetPathes();
        
        return @unlink($this->getPath());
    }
    
    /**
     * Компонент для работы с изображениями
     * @var \Intervention\Image\Image 
     */
    protected function getImager()
    {
        if (is_null($this->_imager)) {
            $this->_imager = new ImageManager(); 
        }
        
        return $this->_imager->make($this->path);
    }

    /**
     * Псевдособытие, вызываемое в начале тела функции операции над изображением
     * 
     * @param string $operation наименование операции
     * @param array $func_args принимает на вход массив значений аргументов функции операции
     * @throws Exception
     */
    protected function beforeOperation($operation, $func_args)
    {
        if (!$this->isImage) {
            throw new Exception("Файл не является изображением");
        }
        $this->_cacheFileName = $this->storageComponent
            ->generateCacheFilename($operation, $func_args);
        
        $this->_cacheFilePath = $this->storageComponent
            ->uploadCachePath . DIRECTORY_SEPARATOR . $this->_cacheFileName;
        
        // Чтобы все преобразования происходили над исходным оригинальный изображением
        $this->resetPathes();
    }
    
    /**
     * Псевдособытие, вызываемое в конце тела функции операции над изображением
     */
    protected function afterOperation()
    {
        $this->_path = $this->_cacheFilePath;
        
        $this->_url = $this->storageComponent
            ->getUploadCacheUrl(false) . '/' . $this->_cacheFileName;
        
        $this->_absoluteUrl = $this->storageComponent
            ->getUploadCacheUrl(true) . '/' . $this->_cacheFileName;
    }
    
    /**
     * Возвращает ширину текущего изображения
     * @return integer
     */
    public function getWidth()
    {
        return $this->imager->width();
    }
    
    /**
     * Возвращает высоту текущего изображения
     * @return integer
     */
    public function getHeight()
    {
        return $this->imager->height();
    }

    /**
     * Масштабирование по высоте без обрезки краев
     * 
     * @param integer $height новая высота в пикселях
     * @param integer $quality качество после сохранения
     * @param bool $upsize не превышать размер оригинального изображения
     * @return \sem\filestorage\models\Image
     */
    public function heighten($height, $quality = 80, $upsize = true)
    {

        $this->beforeOperation(__FUNCTION__, [
            $height,
            $quality,
            $upsize
        ]);
        
        if (!file_exists($this->_cacheFilePath)) {

            $this->storageComponent->touchUploadCacheDir();

            $callback = function ($constraint) use ($upsize) {
                if ($upsize) {
                    $constraint->upsize();
                }
            };
            
            $this->imager->heighten(
                $height,
                $callback
            )->save($this->_cacheFilePath, $quality);
        }

        $this->afterOperation();

        return $this;
    }

    /**
     * Масштабирование по ширине без обрезки краев
     * 
     * @param integer $width новая ширина изоражения
     * @param integer $quality качество после сохранения
     * @param bool $upsize не превышать размер оригинального изображения
     * @return \sem\filestorage\models\Image
     */
    public function widen($width, $quality = 80, $upsize = true)
    {
        $this->beforeOperation(__FUNCTION__, [
            $width,
            $quality,
            $upsize
        ]);

        if (!file_exists($this->_cacheFilePath)) {

            $this->storageComponent->touchUploadCacheDir();

            $callback = function ($constraint) use ($upsize) {
                if ($upsize) {
                    $constraint->upsize();
                }
            };
            
            $this->imager->widen(
                $width,
                $callback
            )->save($this->_cacheFilePath, $quality);
            
        }

        $this->afterOperation();

        return $this;
    }
    
    /**
     * Вписывание изображения в область путем пропорционального масштабирования без обрезки.
     * 
     * @param integer $width новая ширина в пикселях
     * @param integer $height новая высота в пикселях
     * @param integer $quality качество после сохранения
     * @param bool $upsize не превышать размер оригинального изображения
     * @return \sem\filestorage\models\Image
     */
    public function contain($width, $height, $quality = 80, $upsize = true)
    {
        $this->beforeOperation(__FUNCTION__, [
            $width,
            $height,
            $quality,
            $upsize
        ]);
        
        if (!file_exists($this->_cacheFilePath)) {

            $this->storageComponent->touchUploadCacheDir();
            
            $callback = function ($constraint) use ($upsize) {
                if ($upsize) {
                    $constraint->upsize();
                }
                $constraint->aspectRatio();
            };
            
            $this->imager->resize(
                $width,
                $height,
                $callback
            )->save($this->_cacheFilePath, $quality);
        }

        $this->afterOperation();

        return $this;
    }
    
    /**
     * Заполнение обаласти частью изображения с обрезкой исходного,
     * отталкиваясь от точки позиционировани. 
     * 
     * @param integer $width новая ширина в пикселях
     * @param integer $height новая высота в пикселях
     * @param string $position точка позиционирования
     * @param integer $quality качество после сохранения
     * @param bool $upsize не превышать размер оригинального изображения
     * @return \sem\filestorage\models\Image
     */
    public function cover($width, $height, $position = self::POSITION_CENTER, $quality = 80, $upsize = true)
    {
        $this->beforeOperation(__FUNCTION__, [
            $width,
            $height,
            $position,
            $quality,
            $upsize
        ]);
        
        if (!file_exists($this->_cacheFilePath)) {

            $this->storageComponent->touchUploadCacheDir();
            
            $callback = function($constraint) use ($upsize) {
                if ($upsize) {
                    $constraint->upsize();
                }
            };
            
            $this->imager->fit(
                $width,
                $height,
                $callback
            )->save($this->_cacheFilePath, $quality);

        }
        
        $this->afterOperation();

        return $this;
    }
    
    /**
     * Производит удаление кеша файла
     * @return bool
     */
    public function clearCache()
    {
        return $this->storageComponent->
            flushFileCache();
    }
}
