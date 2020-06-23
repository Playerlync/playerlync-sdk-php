<?php


namespace PlMigration\Service;


use PlMigration\Exceptions\ClientException;
use PlMigration\Helper\ApiClient;

interface IService
{
    /**
     * @param ApiClient $apiConnection
     * @param array $options
     * @return mixed
     * @throws ClientException
     */
    public function execute(ApiClient $apiConnection, $options = []);

    public function getStructure(ApiClient $apiClient);

    public function __toString();
}
