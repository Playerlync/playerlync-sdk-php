<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/11/18
 */

namespace PlMigration\Reader;


interface IReader
{
    public function getFile();

    public function getRecord();

    public function hasNext();

    public function next();
}