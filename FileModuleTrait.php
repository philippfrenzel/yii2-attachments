<?php

namespace file;

trait FileModuleTrait
{
    /**
     * @var null|FileModule
     */
    private $_module = null;

    /**
     * @return null|FileModule
     * @throws \Exception
     */
    protected function getModule()
    {
        if ($this->_module == null) {
            $this->_module = \Yii::$app->getModule('file');
        }

        if (!$this->_module) {
            throw new \Exception("Yii2 attachment module not found, may be you didn't add it to your config?");
        }

        return $this->_module;
    }
}