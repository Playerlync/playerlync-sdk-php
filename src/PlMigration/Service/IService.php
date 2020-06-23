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

    /**
     * @param ApiClient $apiClient
     * @throws ClientException
     * @return mixed
     */
    public function getStructure(ApiClient $apiClient);

    /**
     * @return string
     */
    public function __toString();
}
