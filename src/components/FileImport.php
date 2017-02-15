<?php
namespace file\components;

use yii\base\Component;
use yii\web\UploadedFile;

class FileImport extends Component
{
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

            $path = $this->getModule()->getUserDirPath($attribute) . DIRECTORY_SEPARATOR . $model->file->name;
            $model->file->saveAs($path);
            $result['uploadedFiles'][] = $model->file->name;

            Yii::$app->response->format = Response::FORMAT_JSON;
            return $result;
        } else {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'error' => $model->getErrors(),
                'ioca' => $model->getErrors()
            ];
        }
    }
}