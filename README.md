Yii2 attachments
================
Extension for file uploading and attaching to the models

This fork has been made by me and by company Elitmaster.

Demo
----
You can see the demo on the [krajee](http://plugins.krajee.com/file-input/demo) website

Installation
------------

1. The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

	Either run
	
	```
	composer require axelpal/yii2-attachments "dev-master"
	```
	
	or add
	
	```
	"axelpal/yii2-attachments": "dev-master"
	```
	
	to the require section of your `composer.json` file.

2.  Add module to your main config:
	
	```php
	'aliases' => [
            '@file' => dirname(__DIR__),
        ],
        'modules' => [
            'file' => [
                'class' => 'file\FileModule',
                'webDir' => 'files',
                'tempPath' => '@common/uploads/temp',
                'storePath' => '@common/uploads/store',
                'rules' => [ // Правила для FileValidator
                    'maxFiles' => 20,
                    'maxSize' => 1024 * 1024 * 20 // 20 MB
                ],
            ],
        ],
	```
	
	Also, add these lines to your console config:
	
	```php
	'controllerMap' => [
            'file' => [
                'class' => 'yii\console\controllers\MigrateController',
                'migrationPath' => '@file/migrations'
            ],
        ],
    ```

3. Apply migrations

	```
	php yii migrate/up --migrationPath=@vendor/axelpal/yii2-attachments/migrations
	```

4. Attach behavior to your model (be sure that your model has "id" property)
	
	```php
	public function behaviors()
	{
		return [
			...
			'fileBehavior' => [
				'class' => \file\behaviors\FileBehavior::className()
			]
			...
		];
	}
	```
	
5. Make sure that you have added `'enctype' => 'multipart/form-data'` to the ActiveForm options
	
6. Make sure that you specified `maxFiles` in module rules and `maxFileCount` on `AttachmentsInput` to the number that you want

Usage
-----

1. In the `form.php` of your model add file input
	
	```php
	<?= \file\components\AttachmentsInput::widget([
		'id' => 'file-input', // Optional
		'model' => $model,
		'options' => [ // Options of the Kartik's FileInput widget
			'multiple' => true, // If you want to allow multiple upload, default to false
		],
		'pluginOptions' => [ // Plugin options of the Kartik's FileInput widget 
			'maxFileCount' => 10 // Client max files
		]
	]) ?>
	```

2. Use widget to show all attachments of the model in the `view.php`
	
	```php
	<?= \file\components\AttachmentsTable::widget(['model' => $model]) ?>
	```

3. (Deprecated) Add onclick action to your submit button that uploads all files before submitting form
	
	```php
	<?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', [
		'class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary',
		'onclick' => "$('#file-input').fileinput('upload');"
	]) ?>
	```
	
4. You can get all attached files by calling ```$model->files```, for example:

	```php
	foreach ($model->files as $file) {
        echo $file->path;
    }
    ```
