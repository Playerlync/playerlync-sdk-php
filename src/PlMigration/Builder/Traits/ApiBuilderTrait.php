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
     * Array has holds the api connection settings
     * @var array
     */
    private $hostSettings = [];

    /**
     * @var string
     */
    private $service;

    /**
     * @var array
     */
    private $queryParams;

    public function clientId($clientId)
    {
        $this->hostSettings['client_id'] = $clientId;
        return $this;
    }

    public function clientSecret($clientSecret)
    {
        $this->hostSettings['client_secret'] = $clientSecret;
        return $this;
    }

    public function username($username)
    {
        $this->hostSettings['username'] = $username;
        return $this;
    }
    public function password($password)
    {
        $this->hostSettings['password'] = $password;
        return $this;
    }

    public function host($host)
    {
        $this->hostSettings['host'] = $host;
        return $this;
    }

    public function apiVersion($version)
    {
        $this->hostSettings['default_api_version'] = $version;
        return $this;
    }

    public function primaryOrgId($primaryOrgId)
    {
        $this->hostSettings['primary_org_id'] = $primaryOrgId;
        return $this;
    }

    /**
     * @param string $servicePath
     * @return $this
     */
    public function serviceEndpoint($servicePath)
    {
        if('/' !== substr($servicePath,0,1))
            $servicePath = '/'.$servicePath;
        $this->service = $servicePath;
        return $this;
    }

    public function filter($filter)
    {
        $this->queryParams['filter'] = $filter;
        return $this;
    }

    public function orderBy($order)
    {
        $this->queryParams['orderBy'] = $order;
        return $this;
    }

    /**
     * @throws BuilderException
     */
    private function buildApi()
    {
        try
        {
            return new APIConnector($this->service, $this->queryParams, $this->hostSettings);
        }
        catch (ConnectorException $e)
        {
            throw new BuilderException($e->getMessage());
        }
    }
}