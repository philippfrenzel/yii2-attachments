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
        $module = Yii::$app->getModule('file');

        //Se non esiste salto
        if(!file_exists($filePath)) {
            return false;
        }

        $file = [];
        $file['name'] = basename($filePath);
        $file['tempName'] = $filePath;
        $file['type'] = mime_content_type($filePath);
        $file['size'] = filesize($filePath);

        if ($module->attachFile($filePath, $modelSpecific, $attribute)) {
            $result['uploadedFiles'] = [$filePath];

            return $result;
        } else {
            return [
                'error' => $model->getErrors(),
                'ioca' => $model->getErrors()
            ];
        }
    }
}