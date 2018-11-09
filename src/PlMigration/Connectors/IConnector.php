<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/8/18
 */

namespace PlMigration\Connectors;

use PlMigration\Exceptions\ConnectorException;

interface IConnector
{
    /**
     * @return array
     * @throws ConnectorException
     */
    public function getRecords();

    /**
     * @return bool
     */
    public function hasNext();

    /**
     * @param $data
     * @return mixed
     * @throws ConnectorException
     */
    public function insertRecord($data);

    /**
     * @param $requests
     * @param bool $force
     * @return mixed
     */
    public function insertRecords($requests, $force = false);

    /**
     * @param $data
     * @return mixed
     * @throws ConnectorException
     */
    public function insertActivityRecord($data);

    /**
     * Return boolean whether this implementation supports bulk data insertions.
     * @return bool
     */
    public function supportBatch();
}