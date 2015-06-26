<?php

namespace nemmo\attachments\controllers;

use Yii;
use nemmo\attachments\models\File;
use nemmo\attachments\models\UploadForm;
use nemmo\attachments\ModuleTrait;
use yii\helpers\FileHelper;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\UploadedFile;
use yii\filters\AccessControl;

class FileController extends Controller
{
    use ModuleTrait;
    
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
                        'matchCallback' => function ($rule, $action){
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
        
        $model = new UploadForm();
        $model->file = UploadedFile::getInstances($model, 'file');

        if ($model->rules()[0]['maxFiles'] == 1) {
            $model->file = UploadedFile::getInstances($model, 'file')[0];
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
            }
            
            return json_encode($result);
        } else {
            return json_encode([
                'error' => $model->errors['file']
            ]);
        }
    }

    public function actionDownload($id)
    {
        $file = File::findOne(['id' => $id]);
        $filePath = $this->getModule()->getFilesDirPath($file->hash) . DIRECTORY_SEPARATOR . $file->hash . '.' . $file->type;

        return \Yii::$app->response->sendFile($filePath, "$file->name.$file->type");
    }

    public function actionDelete($id)
    {
        $this->getModule()->detachFile($id);

        if (\Yii::$app->request->isAjax) {
            return json_encode([]);
        } else {
            return $this->redirect(Url::previous());
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
        if (!sizeof(FileHelper::findFiles($userTempDir))) {
            rmdir($userTempDir);
        }

        if (\Yii::$app->request->isAjax) {
            return json_encode([]);
        } else {
            return $this->redirect(Url::previous());
        }
    }
    
    protected function checkAccess() 
    {
        
        $access = true;
        
        // ACL filter
        $id = Yii::$app->request->get('id') ? Yii::$app->request->get('id') : Yii::$app->request->post('id');
        
        // check access
        $file = File::findOne(['id' => $id]);
        $modelClass = '\app\models\\' . $file->model;
        $model = new $modelClass();
        $behaviours = $model->behaviors();
        $fileBehaviour = $behaviours['fileBehavior'];
        $permission = $fileBehaviour['permissions'][$file->attribute];
        if(!empty($permission)) {
            if(!Yii::$app->user->can($permission)) {
                $access = false;
            }
        }
        
        return $access;
        
    }
    
}
