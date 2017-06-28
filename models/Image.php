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
 * {@inheritdoc}
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

                    if (false === ($imageInfo = getimagesize($this->_file->tempName))) {
                        $this->addError($attribute, "Загружаемый файл не является изображением!");
                    }
                    
                }
                
            }]
        ]);
    }
}