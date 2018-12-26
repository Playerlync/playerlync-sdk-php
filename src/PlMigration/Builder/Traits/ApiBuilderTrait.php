<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/11/18
 */

namespace PlMigration\Builder\Traits;

use PlMigration\Connectors\APIConnector;
use PlMigration\Exceptions\BuilderException;
use PlMigration\Exceptions\ConnectorException;

trait ApiBuilderTrait
{
    /**
     * Array that holds the api settings
     * @var array
     */
    private $hostSettings = [];

    /**
     * @var string
     */
    private $postService;

    /**
     * @var string
     */
    private $getService;

    /**
     * @var array
     */
    protected $queryParams;

    private $source;

    /**
     * Set the client_id to be used to connect to the playerlync API
     * @param $clientId
     * @return $this
     */
    public function clientId($clientId)
    {
        $this->hostSettings['client_id'] = $clientId;
        return $this;
    }

    /**
     * Set the client_secret to be used to connect to the playerlync API
     * @param $clientSecret
     * @return $this
     */
    public function clientSecret($clientSecret)
    {
        $this->hostSettings['client_secret'] = $clientSecret;
        return $this;
    }

    /**
     * Set the username to be authenticated to connect to the playerlync API
     * @param $username
     * @return $this
     */
    public function username($username)
    {
        $this->hostSettings['username'] = $username;
        return $this;
    }

    /**
     * Set the password for the username provided.
     * @param $password
     * @return $this
     */
    public function password($password)
    {
        $this->hostSettings['password'] = $password;
        return $this;
    }

    /**
     * Set the host to connect to. Include the port number if not 443
     * @param $host
     * @return $this
     */
    public function host($host)
    {
        $this->hostSettings['host'] = $host;
        return $this;
    }

    /**
     * Set the api version to use
     * @param $version
     * @return $this
     */
    public function apiVersion($version)
    {
        $this->hostSettings['default_api_version'] = $version;
        return $this;
    }

    /**
     * Set the primary org id that the records will be added to.
     * This helps prevents errors in the playerlync api as the primary organization affected does not always belong to the
     * username provided.
     * @param $primaryOrgId
     * @return $this
     */
    public function primaryOrgId($primaryOrgId)
    {
        $this->hostSettings['primary_org_id'] = $primaryOrgId;
        return $this;
    }

    /**
     * Set the service to be used by the API connection for inserting data.
     * Refer to the API docs for information on services available
     * @param string $servicePath
     * @return $this
     */
    public function postService($servicePath)
    {
        $this->postService = $servicePath;
        return $this;
    }

    /**
     * Set the service to be used by the API connection for retrieving data.
     * Refer to the API docs for information on services available
     * @param string $servicePath
     * @return $this
     */
    public function getService($servicePath)
    {
        $this->getService = $servicePath;
        return $this;
    }

    /**
     * Set the filter query parameter as it is recognized by the playerlync api.
     * Refer to the playerlync API docs for syntax information
     * @param $filter
     * @return $this
     */
    public function filter($filter)
    {
        $this->queryParams['filter'] = $filter;
        return $this;
    }

    /**
     * Set the orderby query parameter as it is recognized by the playerlync api.
     * Refer to the Playerlync API docs for syntax information.
     *
     * @param $order
     * @return $this
     */
    public function orderBy($order)
    {
        $this->queryParams['orderBy'] = $order;
        return $this;
    }

    /**
     * Toggle to turn off async bulk requests, if applicable. (This only affects importing)
     *
     * @param $supportBatch
     * @return $this
     */
    public function supportBatch($supportBatch)
    {
        $this->hostSettings['support_batch'] = $supportBatch;
        return $this;
    }

    protected function source($source)
    {
        $this->source = $source;
        return $this;
    }

    /**
     * Build the playerlync api connection with the desired settings.
     * @param null $logger
     * @return APIConnector
     * @throws BuilderException
     */
    private function buildApi($logger = null)
    {
        $this->hostSettings['logger'] = $logger;
        try
        {
            return new APIConnector($this->getService, $this->queryParams, $this->postService, $this->hostSettings, $this->source);
        }
        catch (ConnectorException $e)
        {
            throw new BuilderException('Unable to connect to the API: '. $e->getMessage());
        }
    }
}