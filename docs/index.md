Basic Usage
-----------

In the form.php of your model add file input

```php
<?= $form->field($model, 'myFieldMultipleFiles')->widget(\file\components\AttachmentsInput::classname(), [
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

Use widget to show all attachments of the model in the view.php

```php
<?= \file\components\AttachmentsTable::widget(['model' => $model]) ?>
```

(Deprecated) Add onclick action to your submit button that uploads all files before submitting form

```php
<?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', [
    'class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary',
    'onclick' => "$('#file-input').fileinput('upload');"
]) ?>
```

You can get all attached files by calling $model->files, for example:

```php
<?php
foreach ($model->files as $file) {
    echo $file->path;
}
```

Advanced Usage
--------------

### Custom Getters by Attribute

`From version 1.2.4`

You can replace public properties with custom getters to easily get images related to your attributes

```php
<?php
/**
 * @var $myFieldMultipleFiles \file\models\File[]
 * @var $myFieldSingleFile \file\models\File
 */

//RREMOVE OLD ATTRIBUTES
//public $myFieldMultipleFiles;
//public $myFieldSingleFile;

//Add getters

/**
 * Getter for $this->myFieldMultipleFiles;
 * @return \yii\db\ActiveQuery
 */
public function getMyFieldMultipleFiles() {
    return $this->hasMultipleFiles('myFieldMultipleFiles');
}

/**
 * Getter for $this->myFieldSingleFile;
 * @return \yii\db\ActiveQuery
 */
public function getMyFieldSingleFile() {
    return $this->hasOneFile('myFieldSingleFile');
}
?>
```

### Allow only images / Custom Validators

`From version 1.2.3`

It is possible to validate the files with any validator like image validator you only need to add the right rules to the file fields, eg ad in your model:

```php
<?php
/**
 * Implement Image validation
 */
public function rules()
{
    return ArrayHelper::merge(parent::rules(), [
        [['myFieldMultipleFiles', 'myFieldSingleFile'], 'file'],
        [['myFieldMultipleFiles', 'myFieldSingleFile'], 'image'],
    ]);
}
```