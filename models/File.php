<?php
/**
 * @author Самсонов Владимир <samsonov.sem@gmail.com>
 * @copyright Copyright &copy; S.E.M. 2017-
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace sem\filestorage\models;

use Yii;
use sem\filestorage\models\BaseFile;
use sem\helpers\FileHelper;

/**
 * @inheritdoc
 */
class File extends BaseFile
{

    /**
     * @inheritdoc
     */
    protected function saveFile()
    {

        if ($this->_file) {
            
            // Проверка готовности директории загрузок
            if (!$this->getStorageComponent()->touchUploadDir($this->group_code, $this->object_id)) {
                return false;
            }

            // Сохранение файла
            return $this->_file->saveAs(
                $this->getStorageComponent()->getUploadPath($this->group_code, $this->object_id)
                . DIRECTORY_SEPARATOR
                . $this->sys_file
            );
            
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    protected function removeFile()
    {
        return @unlink($this->getPath());
    }
}
