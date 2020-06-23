<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 2019-04-10
 * Time: 09:15
 */

namespace PlMigration\Helper;

use GuzzleHttp\Psr7\Response;
use PlMigration\Exceptions\ClientException;

/**
 * Interface for all API clients that can be created for the use of exporting or importing data from.
 *
 * @package PlMigration\Helper
 */
interface ApiClient
{
    /**
     * Perform an API request with the information provided
     * @param string $method
     * @param string $servicePath
     * @param array $options
     * @return Response
     * @throws ClientException
     */
    public function request($method, $servicePath, $options = []);

    /**
     * @param $requests
     * @param int $concurrency
     * @return mixed
     */
    public function batchRequests($requests, $concurrency = 1);

    /**
     * Validate that the response returns valid data.
     * If the API returns an error, throw a ClientException
     * @param Response $response
     * @return mixed
     * @throws ClientException
     */
    public function validateResponse($response);
}