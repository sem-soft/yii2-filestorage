<?php
/**
 * @author Самсонов Владимир <samsonov.sem@gmail.com>
 * @copyright Copyright &copy; S.E.M. 2017-
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */
namespace sem\filestorage\models;

use Yii;
use sem\helpers\FileHelper;

/**
 * @inheritdoc
 */
class File extends \sem\filestorage\models\BaseFile
{

    /**
     * @inheritdoc
     */
    protected function saveFile()
    {

        if ($this->_file) {

            $path = $this->getStorageComponent()->getUploadPath($this->group_code, $this->object_id);

            if (!file_exists($path)) {
                FileHelper::createDirectory($path);
            }

            return $this->_file->saveAs($path . DIRECTORY_SEPARATOR . $this->sys_file);
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    protected function removeFile()
    {
        return !@unlink($this->getPath());
    }

    /**
     * @inheritdoc
     */
    public function getUrl($isAbsolute = false)
    {
        if (!$this->isNewRecord) {
            return $this->getStorageComponent()->getUploadUrl($this->group_code, $this->object_id, $isAbsolute) . '/' . $this->sys_file;
        }

        return false;
    }
}
