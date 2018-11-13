<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/11/18
 */

namespace PlMigration\Reader;


interface IReader
{
    /**
     * Get a single record
     * @return mixed
     */
    public function getRecord();

    /**
     * Returns true when the pointer is valid and a record can be retrieved
     * @return bool
     */
    public function valid();

    /**
     * Move the pointer to the next record
     */
    public function next();
}