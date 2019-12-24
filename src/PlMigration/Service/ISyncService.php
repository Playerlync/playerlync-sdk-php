<?php


namespace PlMigration\Service;


interface ISyncService
{
    /**
     * @param $data
     * @return mixed
     */
    public function addRecord($data);

    /**
     * Execute actions to update the resources
     * @return mixed
     */
    public function sendUpdate();
}