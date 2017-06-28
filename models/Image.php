<?php
/**
 * @author Самсонов Владимир <samsonov.sem@gmail.com>
 * @copyright Copyright &copy; S.E.M. 2017-
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace sem\filestorage\models;

use Yii;
use sem\filestorage\models\File;
use yii\helpers\ArrayHelper;

/**
 * Реализует логику работы с файлами изображений
 * 
 * {@inheritdoc}
 * @property-read boolean $isImage
 */
class Image extends File
{
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
}