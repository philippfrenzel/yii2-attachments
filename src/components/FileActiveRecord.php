<?php

namespace File\components;

use file\models\File;
use yii\db\ActiveRecord;

/**
 * Class FileActiveRecord
 * @package File\components
 * @property File[] files()
 * @method getInitialPreview()
 * @method getInitialPreviewConfig()
 * @method File[] getFiles()
 */
abstract class FileActiveRecord extends ActiveRecord
{

}