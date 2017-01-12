<?php

namespace file\controllers;

use file\models\File;
use file\models\UploadForm;
use file\FileModuleTrait;
use yii\db\ActiveRecord;
use yii\helpers\FileHelper;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UploadedFile;
use yii\filters\AccessControl;
use Yii;

class FileController extends Controller
{
    use FileModuleTrait;

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['download', 'delete'],
                'rules' => [
                    [
                        'actions' => ['download', 'delete'],
                        'allow' => true,
                        'matchCallback' => function ($rule, $action) {
                            return $this->checkAccess();
                        }
                    ],
                ],
            ],
        ];
    }

    public function actionUpload()
    {
        $getData = Yii::$app->request->get();
        $attribute = $getData['attribute'];
        $modelFrom = $getData['model'];

        /**
         * @var $modelSpecific ActiveRecord
         */
        $modelSpecific = new $modelFrom;

        $model = new UploadForm([
            'modelSpecific' => $modelSpecific,
            'attributeSpecific' => $attribute
        ]);

        $model->file = UploadedFile::getInstances($model, 'file');

        //Attribute Validations
        $attributeValidation = $modelSpecific->getActiveValidators($attribute);

        //File validator
        $modelFileValidator = reset($attributeValidation);

        if ($modelFileValidator->maxFiles == 1) {
            $model->file = reset(UploadedFile::getInstances($model, 'file'));
        }

        if ($model->file && $model->validate()) {
            $result['uploadedFiles'] = [];
            if (is_array($model->file)) {
                foreach ($model->file as $file) {
                    $path = $this->getModule()->getUserDirPath($attribute) . DIRECTORY_SEPARATOR . $file->name;
                    $file->saveAs($path);
                    $result['uploadedFiles'][] = $file->name;
                }
            } else {
                $path = $this->getModule()->getUserDirPath($attribute) . DIRECTORY_SEPARATOR . $model->file->name;
                $model->file->saveAs($path);
                $result['uploadedFiles'][] = $model->file->name;
            }

            Yii::$app->response->format = Response::FORMAT_JSON;
            return $result;
        } else {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'error' => $model->getErrors('file')
            ];
        }
    }

    public function actionDownload($id)
    {
        /** @var File $file */
        $file = File::findOne(['id' => $id]);

        $filePath = $this->getModule()->getFilesDirPath($file->hash) . DIRECTORY_SEPARATOR . $file->hash . '.' . $file->type;

        if (file_exists($filePath))
            return \Yii::$app->response->sendFile($filePath, "$file->hash.$file->type");
        else
            return false;
    }

    public function actionDelete($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if ($this->getModule()->detachFile($id)) {
            return true;
        } else {
            return false;
        }
    }

    public function actionDownloadTemp($filename)
    {
        $filePath = $this->getModule()->getUserDirPath() . DIRECTORY_SEPARATOR . $filename;

        return \Yii::$app->response->sendFile($filePath, $filename);
    }

    public function actionDeleteTemp($filename)
    {
        $userTempDir = $this->getModule()->getUserDirPath();
        $filePath = $userTempDir . DIRECTORY_SEPARATOR . $filename;
        unlink($filePath);
        if (!count(FileHelper::findFiles($userTempDir))) {
            rmdir($userTempDir);
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        return [];
    }

    protected function checkAccess()
    {

        $access = true;

        // ACL filter
        $id = Yii::$app->request->get('id') ? Yii::$app->request->get('id') : Yii::$app->request->post('id');

        // check access
        $file = File::findOne(['id' => $id]);
        $modelClass = $file->model;
        $model = new $modelClass();
        $behaviours = $model->behaviors();
        $fileBehaviour = $behaviours['fileBehavior'];
        $permission = $fileBehaviour['permissions'][$file->attribute];
        if (!empty($permission)) {
            if (!Yii::$app->user->can($permission)) {
                $access = false;
            }
        }

        return $access;
    }

    public function actionSetMain()
    {
        /** @var File $file */
        Yii::$app->response->format = Response::FORMAT_JSON;
        $file = File::findOne(Yii::$app->request->post('id', 0));
        $result = [];
        if ($file) {
            $status = Yii::$app->request->post('value', 'false');
            if ($status === 'true') {
                File::updateAll(['is_main' => 0], ['model' => $file->model, 'itemId' => $file->itemId]);
                $file->is_main = File::MAIN;
            } else {
                $file->is_main = File::NOT_MAIN;
            }
            if ($file->save() && $file->is_main) {
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
        if ($file) {
            return $file->id;
        }
        return 0;
    }

    public function actionRename()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $file = File::findOne(Yii::$app->request->post('id'));
        /** @var File $file */
        if ($file) {
            $file->name = Yii::$app->request->post('name');
            $file->save();
        }
        return [];
    }

    public function actionSetOrder()
    {
        $order = array_reverse(Yii::$app->request->post('order', []));
        foreach ($order as $sort => $fileId) {
            $file = File::findOne($fileId);
            /** @var File $file */
            $file->sort = $sort + 1;
            $file->save();
        }
    }
}
