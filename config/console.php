<?php
return [
    'controllerMap' => [
        'file' => [
            'class' => 'yii\console\controllers\MigrateController',
            'migrationPath' => '@file/migrations'
        ],
    ],
];