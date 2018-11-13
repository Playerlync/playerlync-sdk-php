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
     * Retrieve an array of records from the
     * @return array
     * @throws ConnectorException
     */
    public function getRecords();

    /**
     * Returns true when there are more records to be retrieved for another getRecords() call
     * @return bool
     */
    public function hasNext();

    /**
     * Insert the data into the system
     *
     * @param $data
     * @return mixed
     * @throws ConnectorException
     */
    public function insertRecord($data);

    /**
     * Insert an array of records into the system
     * @param $requests
     * @param bool $force
     * @return mixed
     */
    public function insertRecords($requests, $force = false);

    /**
     * Create a record tracking the actions that have happened for record keeping
     * @param $data
     * @return mixed
     * @throws ConnectorException
     */
    public function insertActivityRecord($data);

    /**
     * Return whether this implementation supports bulk data insertions to use the insertRecords function.
     * @return bool
     */
    public function supportBatch();
}