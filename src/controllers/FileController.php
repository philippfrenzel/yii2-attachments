<?php

namespace file\controllers;

use file\models\File;
use file\models\UploadForm;
use file\FileModuleTrait;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\Url;
use yii\image\drivers\Image_GD;
use yii\image\drivers\Image_Imagick;
use yii\image\ImageDriver;
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

        $model->file = UploadedFile::getInstances($modelSpecific, $attribute);
        //pr($model->file, 'filemodel');
        //Attribute Validations
        $attributeValidation = $modelSpecific->getActiveValidators($attribute);

        //File validator
        $modelFileValidator = reset($attributeValidation);

        if ($modelFileValidator->maxFiles == 1) {
            $fileInstance = UploadedFile::getInstances($modelSpecific, $attribute);

            $model->file = reset($fileInstance);
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
                'error' => $model->getErrors(),
                'ioca' => $model->getErrors()
            ];
        }
    }

    public function actionDownload($id, $hash, $size = 'original')
    {
        /** @var File $file */
        $file = File::findOne(['id' => $id, 'hash' => $hash]);

        $filePath = $this->getModule()->getFilesDirPath($file->hash) . DIRECTORY_SEPARATOR . $file->hash . '.' . $file->type;

        if (file_exists($filePath)) {
            if ($size == 'original' || !in_array($file->type, ['jpg', 'jpeg', 'png', 'gif'])) {
                return \Yii::$app->response->sendFile($filePath, "$file->name.$file->type");
            } else {
                $moduleConfig = Yii::$app->getModule('file')->config;
                $crops = $moduleConfig['crops'] ?: [];

                if (array_key_exists($size, $crops)) {
                    return $this->getCroppedImage($file, $crops[$size]);
                } else {
                    throw new \Exception('Size not found - ' . $size);
                }
            }
        } else
            return false;
    }

    public function getCroppedImage($file, $cropSettings)
    {
        $fileDir = $this->getModule()->getFilesDirPath($file->hash) . DIRECTORY_SEPARATOR;
        $filePath = $fileDir . $file->hash . '.' . $file->type;
        $cropPath = $fileDir . $file->hash . '.' . $cropSettings['width'] . '.' . $cropSettings['height'] . '.' . $file->type;

        if (file_exists($cropPath)) {
            //return \Yii::$app->response->sendFile($cropPath, "$file->name.$file->type");
        }

        //Crop and return
        $cropper = new ImageDriver();

        $configStack = [
            'width' => null,
            'height' => null,
            'master' => null,
            'crop_width' => null,
            'crop_height' => null,
            'crop_width' => null,
            'crop_height' => null,
            'crop_offset_x' => null,
            'crop_offset_y' => null,
            'rotate_degrees' => null,
            'rotate_degrees' => null,
            'refrect_height' => null,
            'refrect_opacity' => null,
            'refrect_fade_in' => null,
            'flip_direction' => null,
            'flip_direction' => null,
            'bg_color' => null,
            'bg_color' => null,
            'bg_opacity' => null,
            'quality' => null
        ];

        $cropConfig = ArrayHelper::merge($configStack, $cropSettings);

        /**
         * Extract All settings
         * Eg.
         * $cr_width
         * $cr_height
         * $cr_quality
         */
        extract($cropConfig, EXTR_PREFIX_ALL, 'cr');

        /**
         * @var $image Image_GD | Image_Imagick
         */
        $image = $cropper->load($filePath);

        $image->resize($cr_width, $cr_height, $cr_master);

        if ($cr_crop_width && $cr_crop_height)
            $image->crop($cr_crop_width, $cr_crop_height, $cr_crop_offset_x, $cr_crop_offset_y);

        if ($cr_rotate_degrees)
            $image->rotate($cr_rotate_degrees);

        if ($cr_refrect_height)
            $image->reflection($cr_refrect_height, $cr_refrect_opacity, $cr_refrect_fade_in);

        if ($cr_flip_direction)
            $image->flip($cr_flip_direction);

        if ($cr_bg_color)
            $image->background($cr_bg_color, $cr_bg_opacity);

        $image->save($cropPath, $cr_quality);

        //Return the new image
        return \Yii::$app->response->sendFile($cropPath, "$file->name.$file->type");
    }

    public function actionDelete($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if ($this->getModule()->detachFile($id)) {
            if (Yii::$app->request->isAjax) {
                return true;
            } else {
                return $this->goBack((!empty(Yii::$app->request->referrer) ? Yii::$app->request->referrer : null));
            }
        } else {
            if (Yii::$app->request->isAjax) {
                return false;
            } else {
                return $this->goBack((!empty(Yii::$app->request->referrer) ? Yii::$app->request->referrer : null));
            }
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

        if (!empty($fileBehaviour['permissions'])) {
            $permission = $fileBehaviour['permissions'][$file->attribute];

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
