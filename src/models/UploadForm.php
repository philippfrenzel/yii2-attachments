<?php

namespace file\models;

use file\FileModuleTrait;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;

class UploadForm extends Model
{
    use FileModuleTrait;

    /**
     * @var UploadedFile[]|UploadedFile file attribute
     */
    public $file;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            ArrayHelper::merge(['file', 'file'], $this->getModule()->rules)
        ];
    }
}