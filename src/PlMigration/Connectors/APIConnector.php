<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/8/18
 */

namespace PlMigration\Connectors;

use PlayerLync\Exceptions\PlayerLyncSDKException;
use PlayerLync\PlayerLync;
use PlayerLync\PlayerLyncResponse;
use PlMigration\Exceptions\ConnectorException;

class APIConnector implements IConnector
{
    private $api;

    private $service;

    private $page = 1;

    private $hasNext = true;

    private $queryParams;

    /**
     * APIConnector constructor.
     * @param array $config
     * @param string $service
     * @param array $query
     * @throws ConnectorException
     */
    public function __construct($service, $query, array $config = [])
    {
        try
        {
            $this->api = new PlayerLync([
                'host' => $config['host'],
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'username' => $config['username'],
                'password' => $config['password'],
                'default_api_version' => 'v3'
            ]);
        }
        catch (PlayerLyncSDKException $e)
        {
            throw new ConnectorException($e->getMessage());
        }

        $this->service = $service;
        $this->queryParams = $query;
    }

    /**
     * @return array
     * @throws ConnectorException
     */
    public function getRecords()
    {
        $this->queryParams['page'] = $this->page;
        try
        {
            $response = $this->api->get($this->service, $this->queryParams);
        }
        catch (PlayerLyncSDKException $e)
        {
            throw new ConnectorException($e->getMessage());
        }
        $this->hasNext = $this->moreRecordsExist($response);
        $this->page++;
        return ($response->getData() !== null) ? $response->getData() : [];
    }

    /**
     * @throws ConnectorException
     */
    public function getTimeFields()
    {
        $timeFields = [];
        try
        {
            $response = $this->api->get($this->service, ['structure' => 1]);
        }
        catch (PlayerLyncSDKException $e)
        {
            throw new ConnectorException($e->getMessage());
        }

        $fields = $response->getData();

        foreach($fields['structure'] as $field => $struct)
        {
            if(strpos($struct['type'], 'timestamp') !== false)
                $timeFields[] = $field;
        }

        return $timeFields;
    }

    /**
     * @param PlayerLyncResponse $response
     * @return bool
     */
    private function moreRecordsExist(PlayerLyncResponse $response)
    {
        return $response->getPage() < $response->getTotalPages();
    }

    /**
     * @return bool
     */
    public function hasNext()
    {
        return $this->hasNext;
    }
}