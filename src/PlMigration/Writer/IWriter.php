<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/5/18
 */

namespace PlMigration\Writer;


interface IWriter
{
    public function writeRecord($record);

    public function getFile();
}