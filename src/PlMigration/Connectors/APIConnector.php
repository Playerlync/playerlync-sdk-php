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
            $this->api = new PlayerLync($config);
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

        $fields = $this->getStructure();

        foreach($fields as $field => $struct)
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
     * @throws ConnectorException
     */
    private function getStructure()
    {
        try
        {
            $response = $this->api->get($this->service, ['structure' => 1]);
        }
        catch (PlayerLyncSDKException $e)
        {
            throw new ConnectorException($e->getMessage());
        }

        return $response->getData()['structure'];
    }

    /**
     * @return bool
     */
    public function hasNext()
    {
        return $this->hasNext;
    }

    /**
     * @param $data
     * @throws ConnectorException
     */
    public function insertRecord($data)
    {
        try
        {
            $this->api->post($this->service, ['upsert' => 1, 'body' => $data]);
        }
        catch (PlayerLyncSDKException $e)
        {
            throw new ConnectorException($e->getMessage());
        }
    }
}