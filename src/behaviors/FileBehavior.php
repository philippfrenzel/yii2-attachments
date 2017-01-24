<?php
namespace file\behaviors;

use file\models\File;
use file\FileModuleTrait;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\helpers\FileHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\Application;
use yii\web\UploadedFile;
use file\models\UploadForm;

/**
 * Class FileBehavior
 * @property ActiveRecord $owner
 * @package file\behaviors
 */
class FileBehavior extends Behavior
{
    use FileModuleTrait;

    var $permissions = [];

    var $rules = [];

    public function events()
    {
        $events = [
            ActiveRecord::EVENT_BEFORE_VALIDATE => 'validateUploads',
            ActiveRecord::EVENT_AFTER_DELETE => 'deleteUploads',
            ActiveRecord::EVENT_AFTER_INSERT => 'saveUploads',
            ActiveRecord::EVENT_AFTER_UPDATE => 'saveUploads'
        ];

        return $events;
    }
	/**
	* Accrocchio da storia per il require
	***/
	public function validateUploads() {
        $attributes = $this->getFileAttributes();

        if (!empty($attributes)) {
            foreach ($attributes as $attribute) {
				/**
				 * @var $modelSpecific ActiveRecord
				 */
				$modelSpecific = new $this->owner;

				$model = new UploadForm([
					'modelSpecific' => $modelSpecific,
					'attributeSpecific' => $attribute
				]);

				$model->file = UploadedFile::getInstances($modelSpecific, $attribute);
//pr($model,'MOSPE');
//die;
				//Attribute Validations
				$attributeValidation = $modelSpecific->getActiveValidators($attribute);

				//File validator
				/*$modelFileValidator = reset($attributeValidation);

				if ($modelFileValidator->maxFiles == 1) {
					$fileInstance = UploadedFile::getInstances($modelSpecific, $attribute);

					$model->file = reset($fileInstance);
				}*/
				//pr($model);
				if ($model->file && $model->validate()) {
					$result['uploadedFiles'] = [];
					if (is_array($model->file)) {
						foreach ($model->file as $file) {
							$path = $this->getModule()->getUserDirPath($attribute) . DIRECTORY_SEPARATOR . $file->name;
							$file->saveAs($path);
							$result['uploadedFiles'][] = $file->name;
						}

                        $this->owner->{$attribute} = true;
                    } else {
						$path = $this->getModule()->getUserDirPath($attribute) . DIRECTORY_SEPARATOR . $model->file->name;
						$model->file->saveAs($path);
						$result['uploadedFiles'][] = $model->file->name;

                        $this->owner->{$attribute} = true;
                    }

					//Yii::$app->response->format = Response::FORMAT_JSON;
				} else {
					//Yii::$app->response->format = Response::FORMAT_JSON;
					return [
						'error' => $model->getErrors(),
						'data' => $model->toArray()
					];
				}
				/*
				$oldItems = $this->owner->__get($attribute);
			
				$this->owner->{$attribute} = $oldItems;
			
				
				$userTempDir = $this->getModule()->getUserDirPath($attribute);
				
				if (!empty($userTempDir)) {
					$filesFound = FileHelper::findFiles($userTempDir);
					
					if (!empty($filesFound)) {
						$this->owner->{$attribute} = true; 
					}
				}*/
			}
		}
	}


    public function saveUploads($event)
    {
        $attributes = $this->getFileAttributes();

        if (!empty($attributes)) {
            foreach ($attributes as $attribute) {
                $this->saveAttributeUploads($attribute);
            }
        }
    }

    /**
     * Return array of attributes which may contain f
     * @return array
     */
    public function getFileAttributes() {
        $validators = $this->owner->getValidators();

        //Array of attributes
        $fileAttributes = [];

        //has file validator?
        $fileValidator = $this->getFileValidator($validators);

        if (!empty($fileValidator)) {
            foreach ($fileValidator->attributes as $attribute) {
                $fileAttributes[] = $attribute;
            }
        }

        return $fileAttributes;
    }

    /**
     * Check if owner model has file validator
     * @param ArrayObject|\yii\validators\Validator[]
     * @return \yii\validators\Validator|null
     */
    public function getFileValidator($validators) {
        foreach($validators as $validator) {
            $classname = $validator::className();

            if ($classname == 'yii\validators\FileValidator') {
                return $validator;
            }
        }

        return null;
    }

    protected function saveAttributeUploads($attribute)
    {

        $debugArr = [];

        $files = UploadedFile::getInstancesByName($attribute);
        if (!empty($files)) {
            foreach ($files as $file) {
                if (!$file->saveAs($this->getModule()->getUserDirPath($attribute) . $file->name)) {
                    throw new \Exception(\Yii::t('yii', 'File upload failed.'));
                }
            }
        }

        $userTempDir = $this->getModule()->getUserDirPath($attribute);
        foreach (FileHelper::findFiles($userTempDir) as $file) {
            $debugArr[] = $file;
            if (!$this->getModule()->attachFile($file, $this->owner, $attribute)) {
                throw new \Exception(\Yii::t('yii', 'File upload failed.'));
            }
        }
        rmdir($userTempDir);

//        $debug = print_r($debugArr, true);
//        file_put_contents('/tmp/files.txt', $debug);

    }

    public function deleteUploads($event)
    {
        $files = $this->getFiles();

        foreach ($files as $file) {
            $this->getModule()->detachFile($file->id);
        }
    }

    /**
     * @param string $andWhere
     * @return array|File[]
     * @return \yii\db\ActiveQuery
     */
    public function getFilesQuery($andWhere = '')
    {
        $fileQuery = File::find()
            ->where(
                [
                    'itemId' => $this->owner->getAttribute('id'),
                    'model' => $this->getModule()->getClass($this->owner)
                ]
            );
        $fileQuery->orderBy('is_main DESC, sort DESC');

        if ($andWhere) {
            $fileQuery->andWhere($andWhere);
        }

        return $fileQuery;
    }

    /**
     * @param string $andWhere
     * @return File[]
     */
    public function getFiles($andWhere = '')
    {
        return $this->getFilesQuery()->all();
    }

    /**
     * @param string $attribute
     * @param string $sort
     * @return \yii\db\ActiveQuery[]
     */
    public function hasMultipleFiles($attribute = 'file', $sort = 'id')
    {
        $fileQuery = File::find()
            ->where([
                'itemId' => $this->owner->id,
                'model' => $this->getModule()->getClass($this->owner),
                'attribute' => $attribute,
            ]);

        $fileQuery->orderBy([$sort => SORT_ASC]);

        //Single result mode
        $fileQuery->multiple = true;

        return $fileQuery;
    }

    /**
     * DEPRECATED
     */
    public function getFilesByAttributeName($attribute = 'file', $sort = 'id')
    {
        return $this->hasMultipleFiles($attribute, $sort);
    }

    /**
     * @param string $attribute
     * @param string $sort
     * @return \yii\db\ActiveQuery[]
     */
    public function hasOneFile($attribute = 'file', $sort = 'id') {
        $query = $this->hasMultipleFiles($attribute, $sort);

        //Single result mode
        $query->multiple = false;

        return $query;
    }

    /**
     * DEPRECATED
     */
    public function getSingleFileByAttributeName($attribute = 'file', $sort = 'id') {
        $query = $this->getFilesByAttributeName($attribute, $sort);

        //Single result mode
        $query->multiple = false;

        return $this->hasOneFile($attribute, $sort);
    }

    /**
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getGalleryFiles()
    {
        $files = File::find()
            ->where([
                'itemId' => $this->owner->getAttribute('id'),
                'model' => $this->getModule()->getClass($this->owner)
            ])
            ->orderBy('is_main DESC, sort DESC')
            ->all();
        if (count($files) > 0) {
            array_shift($files);
        }
        return $files;
    }

    public function getInitialPreview()
    {
        $initialPreview = [];

        $userTempDir = $this->getModule()->getUserDirPath();
        foreach (FileHelper::findFiles($userTempDir) as $file) {
            if (substr(FileHelper::getMimeType($file), 0, 5) === 'image') {
                $initialPreview[] = Html::img(['/file/file/download-temp', 'filename' => basename($file)], ['class' => 'file-preview-image']);
            } else {
                $initialPreview[] = Html::beginTag('div', ['class' => 'file-preview-other']) .
                    Html::beginTag('h2') .
                    Html::tag('i', '', ['class' => 'glyphicon glyphicon-file']) .
                    Html::endTag('h2') .
                    Html::endTag('div');
            }
        }

        $files = $this->getFiles();

        foreach ($files as $file) {
            if (substr($file->mime, 0, 5) === 'image') {
                $initialPreview[] = Html::img($file->getUrl(), ['class' => 'file-preview-image']);
            } else {
                $initialPreview[] = Html::beginTag('div', ['class' => 'file-preview-other']) .
                    Html::beginTag('h2') .
                    Html::tag('i', '', ['class' => 'glyphicon glyphicon-file']) .
                    Html::endTag('h2') .
                    Html::endTag('div');
            }
        }

        return $initialPreview;
    }

    public function getInitialPreviewByAttributeName($attribute = 'file')
    {
        $initialPreview = [];

        $userTempDir = $this->getModule()->getUserDirPath($attribute);
        foreach (FileHelper::findFiles($userTempDir) as $file) {
            if (substr(FileHelper::getMimeType($file), 0, 5) === 'image') {
                $initialPreview[] = Html::img(['/file/file/download-temp', 'filename' => basename($file)], ['class' => 'file-preview-image']);
            } else {
                $initialPreview[] = Html::beginTag('div', ['class' => 'file-preview-other']) .
                    Html::beginTag('h2') .
                    Html::tag('i', '', ['class' => 'glyphicon glyphicon-file']) .
                    Html::endTag('h2') .
                    Html::endTag('div');
            }
        }

        $files = $this->getFilesByAttributeName($attribute)->all();

        foreach ($files as $file) {
            if (substr($file->mime, 0, 5) === 'image') {
                $initialPreview[] = Html::img($file->getUrl(), ['class' => 'file-preview-image']);
            } else {
                $initialPreview[] = Html::beginTag('div', ['class' => 'file-preview-other']) .
                    Html::beginTag('h2') .
                    Html::tag('i', '', ['class' => 'glyphicon glyphicon-file']) .
                    Html::endTag('h2') .
                    Html::endTag('div');
            }
        }

        return $initialPreview;
    }

    public function getInitialPreviewConfig()
    {
        $initialPreviewConfig = [];

        $userTempDir = $this->getModule()->getUserDirPath();
        foreach (FileHelper::findFiles($userTempDir) as $file) {
            $filename = basename($file);
            $initialPreviewConfig[] = [
                'caption' => $filename,
                'size' => $file->size,
                'url' => Url::to(
                    ['/file/file/delete-temp',
                        'filename' => $filename
                    ]
                ),
            ];
        }

        $files = $this->getFiles();

        foreach ($files as $index => $file) {
            $initialPreviewConfig[] = [
                'caption' => $file->name,
                'size' => $file->size,
                'url' => Url::toRoute(
                    ['/file/file/delete',
                        'id' => $file->id
                    ]
                ),
                'key' => $file->id,
            ];
        }

        return $initialPreviewConfig;
    }

    public function getInitialPreviewConfigByAttributeName($attribute = 'file')
    {
        $initialPreviewConfig = [];

        $userTempDir = $this->getModule()->getUserDirPath($attribute);
        foreach (FileHelper::findFiles($userTempDir) as $file) {
            $filename = basename($file);
            $initialPreviewConfig[] = [
                'caption' => $filename,
                'size' => $file->size,
                'url' => Url::to(['/file/file/delete-temp',
                    'filename' => $filename
                ]),
            ];
        }

        $files = $this->getFilesByAttributeName($attribute)->all();

        foreach ($files as $index => $file) {
            $initialPreviewConfig[] = [
                'caption' => "$file->name.$file->type",
                'size' => $file->size,
                'url' => Url::toRoute(['/file/file/delete',
                    'id' => $file->id
                ]),
            ];
        }

        return $initialPreviewConfig;
    }

    public function getMainFile()
    {
        $file = File::find()
            ->where([
                'itemId' => $this->owner->getAttribute('id'),
                'model' => $this->getModule()->getClass($this->owner)
            ])
            ->orderBy('is_main DESC')
            ->limit(1)
            ->one();
        return $file;
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function getFileCount()
    {
        $count = File::find()
            ->where([
                'itemId' => $this->owner->getAttribute('id'),
                'model' => $this->getModule()->getClass($this->owner)
            ])
            ->count();
        return (int)$count;
    }

}