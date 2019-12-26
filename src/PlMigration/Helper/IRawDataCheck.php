<?php


namespace PlMigration\Helper;

/**
 * Interface that has access to the raw data in the import and export processes.
 */
interface IRawDataCheck
{
    /**
     * Method that reads the raw data for its purposes. If the raw data should not be used for the import/export,
     * have the method return false to skip it from transfer and loading into the output
     * @param $data
     * @param $logger
     * @return bool
     */
    public function checkRawData($data, $logger = null): bool;
}