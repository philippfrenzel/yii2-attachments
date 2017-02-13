<?php

namespace file\models;

use file\FileModuleTrait;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\helpers\Url;

/**
 * This is the model class for table "file".
 *
 * @property integer $id
 * @property string $name
 * @property string $model
 * @property integer $itemId
 * @property string $hash
 * @property integer $size
 * @property string $type
 * @property string $mime
 * @property integer $is_main
 * @property integer $date_upload
 * @property integer $sort
 */
class File extends ActiveRecord
{
    use FileModuleTrait;

    const MAIN = 1;
    const NOT_MAIN = 0;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return \Yii::$app->getModule('file')->tableName;
    }

    public function behaviors() {
        return [
            [
                'class'              => TimestampBehavior::className(),
                'createdAtAttribute' => 'date_upload',
                'updatedAtAttribute' => false,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['model', 'attribute', 'itemId', 'hash', 'size', 'type', 'mime'], 'required'],
            [['itemId', 'size', 'is_main', 'date_upload', 'sort'], 'integer'],
            [['name', 'model', 'hash', 'type', 'mime'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'model' => 'Model',
            'attribute' => 'Attribute',
            'itemId' => 'Item ID',
            'hash' => 'Hash',
            'size' => 'Size',
            'type' => 'Type',
            'mime' => 'Mime',
            'is_main' => 'Is main',
            'date_upload' => 'Date upload',
            'sort' => 'Sort',
        ];
    }

    public function getUrl($size = 'original')
    {
        return Url::to(['/file/file/download', 'id' => $this->id, 'hash' => $this->hash, 'size' => $size]);
    }

    public function getWebUrl()
    {
        return str_replace('@webroot', '', Yii::$app->modules['file']->storePath . "/" . \Yii::$app->modules['file']->getSubDirs($this->hash) . DIRECTORY_SEPARATOR . $this->hash . '.' . $this->type);
    }

    public function getPath()
    {
        return $this->getModule()->getFilesDirPath($this->hash) . DIRECTORY_SEPARATOR . $this->hash . '.' . $this->type;
    }
}
