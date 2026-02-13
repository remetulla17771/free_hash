<?php
// core/UploadFile.php

namespace app\helpers;

use app\App;

class UploadFile
{
    private $file;
    private $destination;
    private $allowedTypes;
    private $maxSize;

    public $extension;

    public function __construct($file, $destination, $allowedTypes = ['image/*', 'audio/*', 'image/gif'], $maxSize = 200 * 1024 * 1024)
    {
        $this->file = $file;
        $this->destination = App::getAlias('@uploads').$destination;
        $this->allowedTypes = $allowedTypes;
        $this->maxSize = $maxSize;
        $this->extension = $this->getExtension();
    }

    public function upload()
    {
        if ($this->validate()) {
            $filename = $this->generateFilename() . $this->getExtension();
            $filePath = $this->destination . DIRECTORY_SEPARATOR . $filename.$this->getExtension();

            if (move_uploaded_file($this->file['file']['tmp_name'], $filePath)) {
                return $filename;
            } else {
                throw new \Exception("Failed to move uploaded file.");
            }
        } else {
            throw new \Exception("File validation failed.");
        }
    }

    private function validate()
    {
        return $this->checkSize() && $this->checkError();
    }

    private function checkSize()
    {
        return $this->file['file']['size'] <= $this->maxSize;
    }

    private function checkType()
    {
        return in_array($this->file['type'], $this->allowedTypes);
    }

    private function checkError()
    {
        return $this->file['file']['error'] === UPLOAD_ERR_OK;
    }

    private function generateFilename()
    {
        $extension = pathinfo($this->file['file']['name'], PATHINFO_EXTENSION);
        return uniqid();
    }

    private function getExtension()
    {
        $ext = explode('.', $this->file['file']['name']);
        $ext = end($ext);
        return ".".$ext;
    }


}
