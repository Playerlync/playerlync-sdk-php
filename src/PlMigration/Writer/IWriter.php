<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/5/18
 */

namespace PlMigration\Writer;


interface IWriter
{
    /**
     * Write a record into the desired location
     * @param $record
     * @return mixed
     */
    public function writeRecord($record);

    /**
     * Get the file that the writer is pointing to
     * @return mixed
     */
    public function getFile();

    /**
     * Returns true when appending to the end of an existing file
     * @return bool
     */
    public function isAppend();
}