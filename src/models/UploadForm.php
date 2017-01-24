<?php

namespace file\models;

use file\FileModuleTrait;
use yii\base\Model;
use yii\db\ActiveRecord;
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
     * @var ActiveRecord
     */
    public $modelSpecific;

    /**
     * @var string
     */
    public $attributeSpecific;

    /**
     * @return bool
     *
    public function beforeValidate()
    {
        $attributeValidators = $this->modelSpecific->getActiveValidators($this->attributeSpecific);

        foreach($attributeValidators as $validator) {
            $validator->attributes = ['file'];
            $this->validators->append($validator);
            //$this->activeValidators[] = $validator;
        }

        return parent::beforeValidate();
    }*/
}