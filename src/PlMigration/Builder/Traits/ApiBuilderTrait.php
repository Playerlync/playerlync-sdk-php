<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/11/18
 */

namespace PlMigration\Builder\Traits;

use PlMigration\Connectors\APIv3Connector;
use PlMigration\Exceptions\BuilderException;
use PlMigration\Exceptions\ConnectorException;

/**
 * Trait containing configuration & settings methods to be used to connect to the Playerlync API
 * @package PlMigration\Builder\Traits
 */
trait ApiBuilderTrait
{
    /**
     * Array that holds the api settings
     * @var array
     */
    private $hostSettings = [];

    /**
     * Playerlync API path of a POST service to use for importing records
     * @var string
     */
    private $postService;

    /**
     * Playerlync API path of a GET service to use for retrieving records
     * @var string
     */
    private $getService;

    /**
     * Array has holds the query parameters to be used when performing GET services
     * @var array
     */
    protected $queryParams;

    /**
     * Set the client_id to be used to connect to the playerlync API
     * @param string $clientId
     * @return $this
     */
    public function clientId($clientId)
    {
        $this->hostSettings['client_id'] = $clientId;
        return $this;
    }

    /**
     * Set the client_secret to be used to connect to the playerlync API
     * @param string $clientSecret
     * @return $this
     */
    public function clientSecret($clientSecret)
    {
        $this->hostSettings['client_secret'] = $clientSecret;
        return $this;
    }

    /**
     * Set the username to be authenticated to connect to the playerlync API
     * @param string $username
     * @return $this
     */
    public function username($username)
    {
        $this->hostSettings['username'] = $username;
        return $this;
    }

    /**
     * Set the password for the username provided.
     * @param string $password
     * @return $this
     */
    public function password($password)
    {
        $this->hostSettings['password'] = $password;
        return $this;
    }

    /**
     * Set the host to connect to. Include the port number if not 443
     * @param string $host
     * @return $this
     */
    public function host($host)
    {
        $this->hostSettings['host'] = $host;
        return $this;
    }

    /**
     * Set the primary org id that the records will be added to.
     * This helps prevents errors in the playerlync api as the primary organization affected does not always belong to the
     * username provided.
     * @param string $primaryOrgId
     * @return $this
     */
    public function primaryOrgId($primaryOrgId)
    {
        $this->hostSettings['primary_org_id'] = $primaryOrgId;
        return $this;
    }

    /**
     * Set the POST service to be used by the API connection for inserting data.
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
     * Set the GET service to be used by the API connection for retrieving data.
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
     * @param string $filter
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
     * @param string $order
     * @return $this
     */
    public function orderBy($order)
    {
        $this->queryParams['orderBy'] = $order;
        return $this;
    }

    /**
     * Toggle to turn on/off async bulk requests, if applicable. (This only affects importing).
     * By default, async bulk requests are enabled.
     * It is not recommended to disable this as it will make the process slower.
     *
     * @param bool $supportBatch
     * @return $this
     */
    public function supportBatch($supportBatch)
    {
        $this->hostSettings['support_batch'] = $supportBatch;
        return $this;
    }

    /**
     * Build the playerlync api connection with the desired settings.
     * @param string|null $logger
     * @return APIv3Connector
     * @throws BuilderException
     */
    private function buildApi($logger = null)
    {
        $this->hostSettings['logger'] = $logger;
        try
        {
            return new APIv3Connector($this->getService, $this->queryParams, $this->postService, $this->hostSettings);
        }
        catch (ConnectorException $e)
        {
            throw new BuilderException('Unable to connect to the API: '. $e->getMessage());
        }
    }
}