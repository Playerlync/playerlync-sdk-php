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

    private $structure;

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

        $response = $this->get($this->service, $this->queryParams);

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
    public function getStructure()
    {
        if(!$this->structure)
        {
            $response = $this->get($this->service, ['structure'=> 1]);

            $this->structure = $response->getData()['structure'];
        }
        return $this->structure;
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

    /**
     * @param $path
     * @param $params
     * @return PlayerLyncResponse
     * @throws ConnectorException
     */
    private function get($path,$params)
    {
        try
        {
            return $this->api->get($path, $params);
        }
        catch (PlayerLyncSDKException $e)
        {
            if(method_exists($e, 'getResponseData'))
            {
                throw new ConnectorException('API returned error: '.$e->getResponseData()['errors'][0]['message']);
            }
            else
            {
                throw new ConnectorException('API returned error: '.$e->getMessage());
            }
        }
    }

    public function setQueryParams($params)
    {
        return $this->queryParams = $params;
    }
}