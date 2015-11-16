<?php

namespace file\controllers;

use file\models\File;
use file\models\UploadForm;
use file\FileModuleTrait;
use yii\helpers\FileHelper;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UploadedFile;
use Yii;

class FileController extends Controller
{
    use FileModuleTrait;

    public function actionUpload()
    {
        $model = new UploadForm();
        $model->file = UploadedFile::getInstances($model, 'file');

        if ($model->rules()[0]['maxFiles'] == 1) {
            $model->file = UploadedFile::getInstances($model, 'file')[0];
        }

        if ($model->file && $model->validate()) {
            $result['uploadedFiles'] = [];
            if (is_array($model->file)) {
                foreach ($model->file as $file) {
                    /** @var UploadedFile $file */
                    $path = $this->getModule()->getUserDirPath() . DIRECTORY_SEPARATOR . $file->name;
                    $file->saveAs($path);
                    $result['uploadedFiles'][] = $file->name;
                }
            } else {
                $path = $this->getModule()->getUserDirPath() . DIRECTORY_SEPARATOR . $model->file->name;
                $model->file->saveAs($path);
            }
            return json_encode($result);
        } else {
            return json_encode(
                [
                    'error' => $model->errors['file']
                ]
            );
        }
    }

    public function actionDownload($id)
    {
        /** @var File $file */
        $file = File::findOne(['id' => $id]);
        $filePath = $this->getModule()->getFilesDirPath(
                $file->hash
            ) . DIRECTORY_SEPARATOR . $file->hash . '.' . $file->type;

        return Yii::$app->response->sendFile($filePath, "$file->hash.$file->type");
    }

    public function actionDelete($id)
    {
        $this->getModule()->detachFile($id);

        if (Yii::$app->request->isAjax) {
            return json_encode([]);
        } else {
            return $this->redirect(Url::previous());
        }
    }

    public function actionDownloadTemp($filename)
    {
        $filePath = $this->getModule()->getUserDirPath() . DIRECTORY_SEPARATOR . $filename;

        return Yii::$app->response->sendFile($filePath, $filename);
    }

    public function actionDeleteTemp($filename)
    {
        $userTempDir = $this->getModule()->getUserDirPath();
        $filePath = $userTempDir . DIRECTORY_SEPARATOR . $filename;
        unlink($filePath);
        if (!count(FileHelper::findFiles($userTempDir))) {
            rmdir($userTempDir);
        }

        if (Yii::$app->request->isAjax) {
            return json_encode([]);
        } else {
            return $this->redirect(Url::previous());
        }
    }

    public function actionSetMain()
    {
        /** @var File $file */
        Yii::$app->response->format = Response::FORMAT_JSON;
        $file = File::findOne(Yii::$app->request->post('id', 0));
        $result = [];
        if($file) {
            $status = Yii::$app->request->post('value', 'false');
            if($status === 'true') {
                File::updateAll(['is_main' => 0], ['model' => $file->model, 'itemId' => $file->itemId]);
                $file->is_main = File::MAIN;
            } else {
                $file->is_main = File::NOT_MAIN;
            }
            if($file->save() && $file->is_main){
                $result = ['id' => $file->id];
            }
        }
        return $result;
    }

    public function actionGetMain($id, $model)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        /** @var File $file */
        $file = File::find()->where([
            'model' => $model,
            'itemId' => $id,
            'is_main' => File::MAIN,
        ])->one();
        if($file) {
            return $file->id;
        }
        return 0;
    }

    public function actionRename()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $file = File::findOne(Yii::$app->request->post('id'));
        /** @var File $file */
        if($file) {
            $file->name = Yii::$app->request->post('name');
            $file->save();
        }
        return [];
    }

    public function actionSetOrder()
    {
        $order = array_reverse(Yii::$app->request->post('order', []));
        foreach($order as $sort => $fileId) {
            $file = File::findOne($fileId);
            /** @var File $file */
            $file->sort = $sort + 1;
            $file->save();
        }
    }
}
