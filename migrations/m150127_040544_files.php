<?php

use yii\db\Migration;
use yii\db\Schema;

class m150127_040544_files extends Migration
{
    use \file\FileModuleTrait;

    public function up()
    {
        $tableName = $this->getModule()->tableName ?: '{{%file}}';
        $this->createTable(
            $tableName,
            [
                'id' => Schema::TYPE_PK,
                'name' => Schema::TYPE_STRING . ' NOT NULL',
                'model' => Schema::TYPE_STRING . ' NOT NULL',
                'itemId' => Schema::TYPE_INTEGER . ' NOT NULL',
                'hash' => Schema::TYPE_STRING . ' NOT NULL',
                'size' => Schema::TYPE_INTEGER . ' NOT NULL',
                'type' => Schema::TYPE_STRING . ' NOT NULL',
                'mime' => Schema::TYPE_STRING . ' NOT NULL',
                'is_main' => Schema::TYPE_BOOLEAN . ' DEFAULT 0',
                'date_upload' => Schema::TYPE_INTEGER . ' DEFAULT NULL',
                'sort' => Schema::TYPE_INTEGER . ' NOT NULL DEFAULT 1'
            ]
        );

        $this->createIndex('file_model', $tableName, 'model');
        $this->createIndex('file_item_id', $tableName, 'itemId');
    }

    public function down()
    {
        $this->dropTable('file');
    }
}
