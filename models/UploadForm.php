<?php

namespace file\models;

use file\FileModuleTrait;
use yii\base\Model;
use yii\web\UploadedFile;

class UploadForm extends Model
{
    use FileModuleTrait;

    /**
     * @var UploadedFile[] file attribute
     */
    public $file;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            array_replace([['file'], 'file'], $this->getModule()->rules)
        ];
    }
}