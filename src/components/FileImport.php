<?php
namespace file\components;

use yii\base\Component;
use yii\db\ActiveRecord;
use yii\web\UploadedFile;
use file\models\UploadForm;
use yii\helpers\FileHelper;
use Yii;

class FileImport extends Component
{
    /**
     * Import single file on selected Model->attribute
     * @param $modelSpecific ActiveRecord The Record owner of the file
     * @param $attribute string The attribute Name
     * @param $filePath string Path on filesystem
     * @return array File format or Array with error
     */
    public function importFileForModel($modelSpecific, $attribute, $filePath)
    {
        $model = new UploadForm([
            'modelSpecific' => $modelSpecific,
            'attributeSpecific' => $attribute
        ]);

        $model->file = new UploadedFile();
        $model->file->name = basename($filePath);
        $model->file->tempName = $filePath;
        $model->file->type = mime_content_type($filePath);
        $model->file->size = filesize($filePath);

        if ($model->file && $model->validate()) {
            $result['uploadedFiles'] = [];

            $path = $this->getUserDirPath($attribute) . DIRECTORY_SEPARATOR . $model->file->name;
            $model->file->saveAs($path);
            $result['uploadedFiles'][] = $model->file->name;

            return $result;
        } else {
            return [
                'error' => $model->getErrors(),
                'ioca' => $model->getErrors()
            ];
        }
    }

    public function getUserDirPath($suffix = '')
    {
        $module = Yii::$app->getModule('file');

        $userDirPath = $module->getTempPath() . DIRECTORY_SEPARATOR . uniqid() . $suffix;
        FileHelper::createDirectory($userDirPath);

        return $userDirPath . DIRECTORY_SEPARATOR;
    }
}