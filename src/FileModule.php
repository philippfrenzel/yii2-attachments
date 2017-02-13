<?php

namespace file;

use file\models\File;
use yii\base\Module;
use yii\base\Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\i18n\PhpMessageSource;
use yii\web\Controller;

class FileModule extends Module
{
    /**
     * Папка, в которую прокинут линк для прямого доступа по http
     * @var string
     */
    public $webDir = 'files';

    public $controllerNamespace = 'file\controllers';

    public $storePath = '@app/uploads/store';

    public $tempPath = '@app/uploads/temp';

    public $rules = [];

    public $tableName = 'attach_file';

    public $config = [];

    public function init()
    {
        parent::init();

        if (empty($this->storePath) || empty($this->tempPath)) {
            throw new Exception('Setup {storePath} and {tempPath} in module properties');
        }

        //Configuration
        $config = require(__DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php');
        \Yii::configure($this, ArrayHelper::merge($config, $this));

        $this->rules = ArrayHelper::merge(['maxFiles' => 3], $this->rules);
        $this->defaultRoute = 'file';
        $this->registerTranslations();
    }

    public function registerTranslations()
    {
        \Yii::$app->i18n->translations['file/*'] = [
            'class' => PhpMessageSource::className(),
            'sourceLanguage' => 'en-US',
            'basePath' => '@file/messages',
            'fileMap' => [
                'file/attachments' => 'attachments.php'
            ],
        ];
    }

    public static function t($category, $message, $params = [], $language = null)
    {
        return \Yii::t('file/' . $category, $message, $params, $language);
    }

    public function getStorePath()
    {
        return \Yii::getAlias($this->storePath);
    }

    public function getTempPath()
    {
        return \Yii::getAlias($this->tempPath);
    }

    /**
     * @param $fileHash
     * @param $useStorePath
     * @return string
     */
    public function getFilesDirPath($fileHash, $useStorePath = true)
    {
        if($useStorePath){
            $path = $this->getStorePath() . DIRECTORY_SEPARATOR . $this->getSubDirs($fileHash);
        } else {
            $path = DIRECTORY_SEPARATOR . $this->getSubDirs($fileHash);
        }

        FileHelper::createDirectory($path);

        return $path;
    }

    public function getSubDirs($fileHash, $depth = 3)
    {
        $depth = min($depth, 9);
        $path = '';

        for ($i = 0; $i < $depth; $i++) {
            $folder = substr($fileHash, $i * 3, 2);
            $path .= $folder;
            if ($i != $depth - 1) $path .= DIRECTORY_SEPARATOR;
        }

        return $path;
    }

    public function getUserDirPath($suffix = '')
    {
        \Yii::$app->session->open();

        $userDirPath = $this->getTempPath() . DIRECTORY_SEPARATOR . \Yii::$app->session->id . $suffix;
        FileHelper::createDirectory($userDirPath);

        \Yii::$app->session->close();

        return $userDirPath . DIRECTORY_SEPARATOR;
    }

    public function getClass($obj)
    {
        $className = $obj::className();

        return $className;
    }

    public function getShortClass($obj)
    {
        $className = get_class($obj);
        if (preg_match('@\\\\([\w]+)$@', $className, $matches)) {
            $className = $matches[1];
        }
        return $className;
    }

    /**
     * @param $filePath string
     * @param $owner
     * @param $attribute
     * @return bool|File
     * @throws \Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function attachFile($filePath, $owner, $attribute='file')
    {
        if (!$owner->id) {
            throw new \Exception('Owner must have id when you attach file');
        }

        if (!file_exists($filePath)) {
            throw new \Exception('File not exist :' . $filePath);
        }

        $fileHash = md5(microtime(true) . $filePath);
        $fileType = pathinfo($filePath, PATHINFO_EXTENSION);
        $fileName = pathinfo($filePath, PATHINFO_FILENAME);
        $newFileName = $fileHash . '.' . $fileType;
        $fileDirPath = $this->getFilesDirPath($fileHash);

        $newFilePath = $fileDirPath . DIRECTORY_SEPARATOR . $newFileName;

        copy($filePath, $newFilePath);

        if (!file_exists($filePath)) {
            throw new \Exception('Cannot copy file! ' . $filePath . ' to ' . $newFilePath);
        }

        $file = new File();

        $file->name = $fileName;
        $file->model = $this->getClass($owner);
        $file->itemId = $owner->id;
        $file->hash = $fileHash;
        $file->size = filesize($filePath);
        $file->type = $fileType;
        $file->mime = FileHelper::getMimeType($filePath);
        $file->attribute = $attribute;

        if ($file->save()) {
            unlink($filePath);
            return $file;
        } else {
            if (count($file->getErrors()) > 0) {
                $errors = $file->getErrors();
                $ar = array_shift($errors);

                unlink($newFilePath);
                throw new \Exception(array_shift($ar));
            }
            return false;
        }
    }

    public function detachFile($id)
    {
        /** @var File $file */
        $file = File::findOne(['id' => $id]);
        $filePath = $this->getFilesDirPath($file->hash) . DIRECTORY_SEPARATOR . $file->hash . '.' . $file->type;

        if(file_exists($filePath))
            unlink($filePath);

        $file->delete();
    }

    /**
     * @param File $file
     * @return string
     */
    public function getWebPath(File $file)
    {
        $fileName = $file->hash . '.' . $file->type;
        $webPath = '/' . $this->webDir . '/' . $this->getSubDirs($file->hash) . '/' . $fileName;
        return $webPath;
    }
}
