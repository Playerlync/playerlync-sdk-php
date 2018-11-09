<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/8/18
 */

namespace PlMigration\Connectors;

use PlMigration\Exceptions\ClientException;
use PlMigration\Exceptions\ConnectorException;
use PlMigration\Helper\LoggerTrait;
use PlMigration\Helper\PlapiClient;
use Psr\Http\Message\ResponseInterface;

class APIConnector implements IConnector
{
    use LoggerTrait;
    /**
     *
     * @var PlapiClient
     */
    private $api;

    /**
     *
     * @var string
     */
    private $service;

    /**
     *
     * @var integer
     */
    private $page = 1;

    /**
     *
     * @var bool
     */
    private $hasNext = true;

    /**
     *
     * @var string
     */
    private $queryParams;

    /**
     *
     * @var object
     */
    private $structure;

    /**
     *
     * @var bool
     */
    private $supportBatch = true;

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
            $this->api = new PlapiClient($config);

        }
        catch (ClientException $e)
        {
            throw new ConnectorException($e->getMessage());
        }

        $this->service = $service;
        $this->queryParams = $query;
        if(isset($config['support_batch']) && is_bool($config['support_batch']))
        {
            $this->supportBatch = $config['support_batch'];
        }

        if(isset($config['logger']))
        {
            $this->setLogger($config['logger']);
        }
    }

    /**
     * @return array
     * @throws ConnectorException
     */
    public function getRecords()
    {
        $this->queryParams['page'] = $this->page;

        $response = $this->get($this->service, $this->queryParams);

        if($this->page === 1)
            $this->debug($response->totalitems. ' records returned');

        $this->hasNext = $this->moreRecordsExist($response);
        $this->page++;
        return ($response->data !== null) ? $response->data : [];
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
            if(strpos($struct->type, 'timestamp') !== false)
            {
                $timeFields[] = $field;
            }
        }

        return $timeFields;
    }

    /**
     * @param object $response
     * @return bool
     */
    private function moreRecordsExist($response)
    {
        return $response->page < $response->totalpages;
    }

    /**
     * @throws ConnectorException
     */
    public function getStructure()
    {
        if(!$this->structure)
        {
            $response = $this->get($this->service, ['structure'=> 1]);

            $this->structure = $response->data->structure;
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
     * @return object
     * @throws ConnectorException
     */
    public function insertRecord($data)
    {
        $query = ['upsert' => 1];
        return $this->post($this->service, $query, $data);
    }

    public function setQueryParams($params)
    {
        return $this->queryParams = $params;
    }

    /**
     * @param array $data
     * @return object
     * @throws ConnectorException
     */
    public function insertActivityRecord($data)
    {
        $data['activity_id'] = self::createGUID();
        $data['primary_org_id'] = $this->api->getPrimaryOrgId();
        $data['member_id'] = $this->api->getMemberId();
        return $this->post('/log/activities', [], $data);
    }

    /**
     * @param $records
     * @param bool $force
     * @return mixed|null
     */
    public function insertRecords($records, $force = false)
    {
        if(!$force && count($records) < 30)
        {
            return null;
        }

        $requests = [];
        foreach($records as $i => $record)
        {
            $requests[$i] = [
                'method' => 'POST',
                'path' => $this->service,
                'body' => $record
            ];
        }

        $responses = $this->api->poolRequests($requests);

        $recordIndexes = array_keys($records);
        /**
         * @var int $index
         * @var ResponseInterface $response
         */
        foreach($responses as $index => $response)
        {
            $requestIndex = $recordIndexes[$index];
            if($response->getStatusCode() !== 200)
            {
                $records[$requestIndex] = new ClientException('Response returned invalid status code ' .$response->getStatusCode());
            }
            else
            {
                $responseBody = json_decode($response->getBody());
                if($responseBody === null && json_last_error())
                {
                    $records[$requestIndex] = new ClientException('Malformed JSON response '.json_last_error_msg());
                }
                elseif ($responseBody->status === 'INVALID_REQUEST')
                {
                    $records[$requestIndex] = new ClientException($responseBody->errors[0]->message);
                }
                else
                {
                    $records[$requestIndex] = true;
                }
            }
        }

        return $records;
    }

    /**
     * @return string
     */
    private static function createGUID()
    {
        $guid = null;
        if (function_exists('com_create_guid'))
        {
            $guid = str_replace(array('}', '{'), '', com_create_guid());
        }
        else
        {
            mt_srand((double)microtime() * 10000);
            $charid = strtoupper(md5(uniqid(mt_rand(), true)));
            $hyphen = chr(45); // "-"
            $guid = substr($charid, 0, 8) . $hyphen
                . substr($charid, 8, 4) . $hyphen
                . substr($charid, 12, 4) . $hyphen . '4'
                . substr($charid, 16, 3) . $hyphen
                . substr($charid, 20, 12);
        }
        return strtolower($guid);
    }

    /**
     * @param $path
     * @param $query
     * @param $body
     * @return object
     * @throws ConnectorException
     */
    private function post($path, $query, $body)
    {
        $params = [
            'query' => $query,
            'json' => $body
        ];
        return $this->request('post', $path, $params);
    }

    /**
     * @param $path
     * @param $params
     * @return object
     * @throws ConnectorException
     */
    private function get($path,$params)
    {
        return $this->request('get', $path, $params);
    }

    /**
     * @param $method
     * @param $path
     * @param $params
     * @return mixed
     * @throws ConnectorException
     */
    private function request($method, $path, $params)
    {
        try
        {
            return $this->api->$method($path, $params);
        }
        catch (ClientException $e)
        {
            throw new ConnectorException('API returned error: '.$e->getMessage());
        }
    }

    /**
     * @return bool
     */
    public function supportBatch()
    {
        return $this->supportBatch;
    }
}