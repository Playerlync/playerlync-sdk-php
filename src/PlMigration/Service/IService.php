<?php


namespace PlMigration\Service;


use PlMigration\Helper\ApiClient;

interface IService
{
    /**
     * @param ApiClient $apiConnection
     * @param array $options
     * @return mixed
     */
    public function execute(ApiClient $apiConnection, $options = []);

    public function __toString();
}