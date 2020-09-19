<?php
 
namespace Netopia\Netcard\Model\Config\Backend;
 
class CustomFileType extends \Magento\Config\Model\Config\Backend\File
{
	const UPLOAD_DIR = '/var/www/html/app/code/Netopia/Netcard/etc/certificates/'; // Folder save file

	/**
     * Overwirte Method - Save uploaded file before saving config value
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        $file = $this->getFileData();
        if (!empty($file)) {
            $uploadDir = $this->_getUploadDir();
            try {
                /** @var Uploader $uploader */
                $uploader = $this->_uploaderFactory->create(['fileId' => $file]);
                $uploader->setAllowedExtensions($this->_getAllowedExtensions());
                $uploader->setAllowRenameFiles(true);
                $uploader->addValidateCallback('size', $this, 'validateMaxSize');
                $result = $uploader->save($uploadDir);
            } catch (\Exception $e) {
                throw new \Magento\Framework\Exception\LocalizedException(__('%1', $e->getMessage()));
            }

            $filename = $result['file'];
            if ($filename) {
                if ($this->_addWhetherScopeInfo()) {
                    $filename = $this->_prependScopeInfo($filename);
                }
                if(isset($value['value']) && !is_null($value['value']))
                	$this->_DeleteKey($value['value']);
                $this->setValue($filename);
            }
        } else {
            if (is_array($value) && !empty($value['delete'])) {
            	$this->_DeleteKey($value['value']);
                $this->setValue('');
            } elseif (is_array($value) && !empty($value['value'])) {
                $this->setValue($value['value']);
            } else {
                $this->unsValue();
            }
        }

        return $this;
    }

	protected function _getUploadDir()
    {
        // return $this->getAbsolutePath($this->_appendScopeInfo(self::UPLOAD_DIR));
        return self::UPLOAD_DIR;
    }

	protected function _addWhetherScopeInfo()
    {
        return false;
    }

    protected function _prependScopeInfo($path)
    {
    	return false;
    }

    /**
     * @return string[]
     */
    public function _getAllowedExtensions() {
        return ['cer', 'key'];
    }

    public function _DeleteKey($keyName) {
    	$keyFileName = self::UPLOAD_DIR.$keyName; 
        if(file_exists($keyFileName))
        	unlink($keyFileName);
        return true;
    }
}