<?php
/**
 * Created by PhpStorm.
 * User: Alimzhan
 * Date: 2/6/2016
 * Time: 10:29 PM
 */

use tests\models\Comment;

/** @var $model Comment */

echo \file\components\AttachmentsTable::widget([
    'model' => $model
]);
