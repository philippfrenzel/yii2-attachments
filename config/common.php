<?php

return [
    'aliases' => [
        '@file' => dirname(__DIR__),
    ],
    'modules' => [
        'file' => [
            'class' => 'file\FileModule',
            'webDir' => 'files',
            'storeDirMask' => 'Y/m/d',
            'tempPath' => '@common/uploads/temp',
            'storePath' => '@common/uploads/store',
            'rules' => [ // Правила для FileValidator
                'maxFiles' => 20,
                'maxSize' => 1024 * 1024 * 20 // 20 MB
            ],
        ],
    ],
];