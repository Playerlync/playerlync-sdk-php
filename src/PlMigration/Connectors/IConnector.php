<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/8/18
 */

namespace PlMigration\Connectors;


interface IConnector
{
    /**
     * @return array
     */
    public function getRecords();

    /**
     * @return bool
     */
    public function hasNext();

    public function insertRecord($data);
}