<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/8/18
 */

namespace PlMigration\Connectors;

use PlMigration\Exceptions\ClientException;
use PlMigration\Exceptions\ConnectorException;
use PlMigration\Helper\IActivityTrackable;
use PlMigration\Helper\LoggerTrait;
use PlMigration\Helper\PlapiClient;
use PlMigration\Service\IService;
use PlMigration\Service\Plapi\SimpleService;
use Psr\Http\Message\ResponseInterface;

class APIv3Connector implements IConnector
{
    use LoggerTrait;

    /**
     *
     * @var PlapiClient
     */
    private $api;

    /**
     * API path of the GET service to be used
     * @var IService
     */
    private $getService;

    /**
     * API path of the POST service to be used
     * @var IService
     */
    private $postService;

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
     * @var string
     */
    private $primaryKey;

    /**
     * APIv3Connector constructor.
     * @param $getService
     * @param array $query
     * @param $postService
     * @param array $config
     * @throws ConnectorException
     */
    public function __construct($getService, $query, $postService, array $config = [])
    {
        try
        {
            $this->api = new PlapiClient($config, $config['logger'] ?? null);
        }
        catch (ClientException $e)
        {
            throw new ConnectorException($e->getMessage());
        }

        $this->getService = $getService;
        $this->postService = $postService;
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
     * @param array $config
     * @return array
     * @throws ConnectorException
     */
    public function getRecords(array $config = [])
    {
        $queryParams = $this->queryParams;

        $queryParams['page'] = $this->page;
        if(isset($config['source']))
        {
            if(isset($queryParams['filter']))
                $queryParams['filter'] .= ',source|eq|'.$config['source'];
            else
                $queryParams['filter'] = 'source|eq|'.$config['source'];
        }

        $response = $this->getService->execute($this->getClient(), ['query' => $queryParams]);

        if(!($this->getService instanceof SimpleService))
        {
            $this->hasNext = false;
            $this->debug(count($response). ' records returned');
            return $response;
        }
        $this->hasNext = $this->moreRecordsExist($response);
        $this->page++;

        $this->debug($response->items. ' records returned');

        return $response->data ?? [];
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
            $this->structure = (array)$this->getService->getStructure($this->api);
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
        return $this->post((string)$this->postService, $query, $data);
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
        if($this->api instanceof IActivityTrackable)
        {
            try
            {
                return $this->api->logActivity($data);
            }
            catch (ClientException $e)
            {
                throw new ConnectorException('API returned error: '.$e->getMessage());
            }
        }
    }

    /**
     * @param $records
     * @return mixed
     */
    public function insertRecords($records)
    {
        $requests = [];
        foreach($records as $i => $record)
        {
            $requests[$i] = [
                'method' => 'POST',
                'path' => (string)$this->postService,
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
            if($response instanceof \RuntimeException)
            {
                $records[$requestIndex] = new ClientException('Network error: '.$response->getMessage());
            }
            elseif($response->getStatusCode() !== 200)
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

    /**
     * Overwrite settings needed
     * @param array $options
     */
    public function setOptions(array $options)
    {
        if(isset($options['page']))
        {
            $this->page = $options['page'];
        }

        if(isset($options['primary_key']))
        {
            $this->primaryKey = $options['primary_key'];
        }
    }

    public function getGetService()
    {
        return $this->getService;
    }

    public function getPostService()
    {
        return $this->postService;
    }

    /**
     * Update the data record into the system
     * @param array $data
     * @return mixed
     * @throws ConnectorException
     */
    public function updateRecord($data)
    {
        $params = [
            'json' => $data
        ];
        return $this->request('put', (string)$this->postService.'/'.$data[$this->primaryKey], $params);
    }

    /**
     * @param $data
     * @return mixed
     * @throws ConnectorException
     */
    public function deleteRecord($data)
    {
        return $this->request('delete', (string)$this->postService.'/'.$data[$this->primaryKey], []);
    }

    /**
     * @return PlapiClient
     */
    public function getClient()
    {
        return $this->api;
    }
}
