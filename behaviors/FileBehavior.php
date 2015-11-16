<?php
namespace file\behaviors;

use file\FileModuleTrait;
use file\models\File;
use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\helpers\FileHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\Application;
use yii\web\UploadedFile;

/**
 * Class FileBehavior
 * @property ActiveRecord $owner
 * @package file\behaviors
 */
class FileBehavior extends Behavior
{
    use FileModuleTrait;

    public function events()
    {
        $events = [
            ActiveRecord::EVENT_AFTER_DELETE => 'deleteUploads'
        ];

        if (Yii::$app instanceof Application) {
            $events[ActiveRecord::EVENT_AFTER_INSERT] = 'saveUploads';
            $events[ActiveRecord::EVENT_AFTER_UPDATE] = 'saveUploads';
        }

        return $events;
    }

    public function saveUploads($event)
    {
        $files = UploadedFile::getInstancesByName('file');

        if (!empty($files)) {
            foreach ($files as $file) {
                if (!$file->saveAs($this->getModule()->getUserDirPath() . $file->name)) {
                    throw new \Exception(\Yii::t('yii', 'File upload failed.'));
                }
            }
        }

        $userTempDir = $this->getModule()->getUserDirPath();
        foreach (FileHelper::findFiles($userTempDir) as $file) {
            if (!$this->getModule()->attachFile($file, $this->owner)) {
                throw new \Exception(\Yii::t('yii', 'File upload failed.'));
            }
        }
        rmdir($userTempDir);
    }

    public function deleteUploads($event)
    {
        foreach ($this->getFiles() as $file) {
            $this->getModule()->detachFile($file->id);
        }
    }

    /**
     * @param string $andWhere
     * @return array|File[]
     * @throws \Exception
     */
    public function getFiles($andWhere = '')
    {
        $fileQuery = File::find()
            ->where(
                [
                    'itemId' => $this->owner->getAttribute('id'),
                    'model' => $this->getModule()->getShortClass($this->owner)
                ]
            );
        $fileQuery->orderBy('is_main DESC, sort DESC');

        if($andWhere) {
            $fileQuery->andWhere($andWhere);
        }

        return $fileQuery->all();
    }

    /**
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getGalleryFiles()
    {
        $files = File::find()
            ->where([
                'itemId' => $this->owner->getAttribute('id'),
                'model' => $this->getModule()->getShortClass($this->owner)
            ])
            ->orderBy('is_main DESC, sort DESC')
            ->all();
        if( count( $files ) > 0 ) {
            array_shift( $files );
        }
        return $files;
    }

    public function getInitialPreview()
    {
        $initialPreview = [];

        $userTempDir = $this->getModule()->getUserDirPath();
        foreach (FileHelper::findFiles($userTempDir) as $file) {
            if (substr(FileHelper::getMimeType($file), 0, 5) === 'image') {
                $initialPreview[] = Html::img(
                    ['/file/file/download-temp', 'filename' => basename($file)],
                    ['class' => 'file-preview-image']
                );
            } else {
                $initialPreview[] = Html::beginTag('div', ['class' => 'file-preview-other']) .
                    Html::beginTag('h2') .
                    Html::tag('i', '', ['class' => 'glyphicon glyphicon-file']) .
                    Html::endTag('h2') .
                    Html::endTag('div');
            }
        }

        foreach ($this->getFiles() as $file) {
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
                'url' => Url::to(
                    ['/file/file/delete-temp',
                        'filename' => $filename
                    ]
                ),
            ];
        }

        foreach ($this->getFiles() as $index => $file) {
            $initialPreviewConfig[] = [
                'caption' => $file->name,
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

    public function getMainFile()
    {
        $file = File::find()
            ->where([
                'itemId' => $this->owner->getAttribute('id'),
                'model' => $this->getModule()->getShortClass($this->owner)
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
                'model' => $this->getModule()->getShortClass($this->owner)
            ])
            ->count();
        return (int)$count;
    }

}