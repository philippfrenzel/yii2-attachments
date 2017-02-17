<?php

namespace file\components;

use himiklab\colorbox\Colorbox;
use file\behaviors\FileBehavior;
use file\FileModuleTrait;
use Yii;
use yii\bootstrap\Widget;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Created by PhpStorm.
 * User: Алимжан
 * Date: 28.01.2015
 * Time: 19:10
 */
class AttachmentsTableWithPreview extends Widget
{
    use FileModuleTrait;

    /** @var ActiveRecord */
    public $model;

    public $attribute;

    public $tableOptions = ['class' => 'table table-striped table-bordered table-condensed'];

    public function init()
    {
        parent::init();
    }

    public function run()
    {
        if (!$this->model) {
            return Html::tag('div',
                Html::tag('b',
                    Yii::t('yii', 'Error')) . ': ' . $this->getModule()->t('attachments', 'The model cannot be empty.'
                ),
                [
                    'class' => 'alert alert-danger'
                ]
            );
        }

        $hasFileBehavior = false;
        foreach ($this->model->getBehaviors() as $behavior) {
            if ($behavior instanceof FileBehavior) {
                $hasFileBehavior = true;
                break;
            }
        }
        if (!$hasFileBehavior) {
            return Html::tag('div',
                Html::tag('b',
                    Yii::t('yii', 'Error')) . ': ' . $this->getModule()->t('attachments', 'The behavior FileBehavior has not been attached to the model.'
                ),
                [
                    'class' => 'alert alert-danger'
                ]
            );
        }

        Url::remember(Url::current());

        if (!empty($this->attribute)) {
            return $this->drawWidget($this->attribute);
        } else {
            $widgets = null;
            $attributes = $this->model->getFileAttributes();

            if (!empty($attributes)) {
                foreach ($attributes as $attribute) {
                    $widgets .= $this->drawWidget($attribute);
                }
            }

            return $widgets;
        }
    }

    public function drawWidget($attribute = null)
    {
        if (!$attribute) {
            return null;
        }

        $files = $this->model->hasMultipleFiles($attribute);

        return GridView::widget([
                'dataProvider' => new ActiveDataProvider(['query' => $files]),
                'layout' => '{items}',
                'tableOptions' => $this->tableOptions,
                'columns' => [
                    [
                        'class' => 'yii\grid\SerialColumn'
                    ],
                    [
                        'label' => $this->getModule()->t('attachments', 'File Preview'),
                        'format' => 'raw',
                        'value' => function ($model) {
                            return Html::img($model->getUrl('square_small'), [
                                'class' => ' group' . $model->itemId
                            ]);
                        }
                    ],
                    [
                        'label' => $this->getModule()->t('attachments', 'File name'),
                        'format' => 'raw',
                        'value' => function ($model) {
                            return Html::a("$model->name.$model->type", $model->getUrl(), [
                                'class' => ' group' . $model->itemId,
                                'onclick' => 'return false;',
                            ]);
                        }
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'template' => '{delete}',
                        'buttons' => [
                            'delete' => function ($url, $model, $key) {
                                return Html::a('<span class="glyphicon glyphicon-trash"></span>',
                                    [
                                        '/file/file/delete',
                                        'id' => $model->id
                                    ],
                                    [
                                        'title' => Yii::t('yii', 'Delete'),
                                        'data-confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
                                        'data-method' => 'post',
                                    ]
                                );
                            }
                        ]
                    ],
                ],
            ]) .
            Colorbox::widget([
                'targets' => [
                    '.group' . $this->model->id => [
                        'rel' => '.group' . $this->model->id,
                        'photo' => true,
                        'scalePhotos' => true,
                        'width' => '100%',
                        'height' => '100%',
                        'maxWidth' => 800,
                        'maxHeight' => 600,
                    ],
                ],
                'coreStyle' => 4,

            ]);
    }
}
