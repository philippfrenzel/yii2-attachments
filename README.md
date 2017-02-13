Yii2 attachments
----------------

[![Latest Stable Version](https://poser.pugx.org/badbreze/yii2-attachments/v/stable)](https://packagist.org/packages/badbreze/yii2-attachments)
[![Total Downloads](https://poser.pugx.org/badbreze/yii2-attachments/downloads)](https://packagist.org/packages/badbreze/yii2-attachments)
[![License](https://poser.pugx.org/badbreze/yii2-attachments/license)](https://packagist.org/packages/badbreze/yii2-attachments)

Extension for file uploading and attaching to the models

This fork has the aim to implement some missing features such as multiple fields and simplifying installation

Demo
----
You can see the demo on the [krajee](http://plugins.krajee.com/file-input/demo) website

Installation
------------

1. The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```bash
composer require badbreze/yii2-attachments
```

or add

```
"badbreze/yii2-attachments": ">=1.2.0"
```

to the require section of your `composer.json` file.

2.  Add module to your main config:
	
```php
<?php
'aliases' => [
    '@file' => dirname(__DIR__),
],
'modules' => [
    'file' => [
        'class' => 'file\FileModule',
        'webDir' => 'files',
        'tempPath' => '@common/uploads/temp',
        'storePath' => '@common/uploads/store',
        'tableName' => '{{%attach_file}}' // Optional, default to 'attach_file'
    ],
],
```

Also, add these lines to your console config:
	
```php
<?php
'controllerMap' => [
    'file' => [
        'class' => 'yii\console\controllers\MigrateController',
        'migrationPath' => '@file/migrations'
    ],
],
```

3. Apply migrations

```bash
php yii migrate/up --migrationPath=@vendor/badbreze/yii2-attachments/src/migrations
```

4. Attach behavior to your model (be sure that your model has "id" property)
	
```php
<?php
use yii\helpers\ArrayHelper;

/**
 * Declare file fields
 */
public $my_field_multiple_files;
public $my_field_single_file;

/**
 * Adding the file behavior
 */
public function behaviors()
{
    return ArrayHelper::merge(parent::behaviors(), [
        'fileBehavior' => [
            'class' => \file\behaviors\FileBehavior::className()
        ]
    ]);
}

/**
 * Add the new fields to the file behavior
 */
public function rules()
{
    return ArrayHelper::merge(parent::rules(), [
        [['my_field_multiple_files', 'my_field_single_file'], 'file'],
    ]);
}
```
	
5. Make sure that you have added `'enctype' => 'multipart/form-data'` to the ActiveForm options
	
6. Make sure that you specified `maxFiles` in module rules and `maxFileCount` on `AttachmentsInput` to the number that you want

7. Youre ready to use, [See How](https://badbreze.github.io/yii2-attachments/docs/)
