<?php


namespace PlMigration\Service\Plapi;

use PlMigration\Exceptions\ClientException;
use PlMigration\Helper\ApiClient;
use PlMigration\Service\IService;

/**
 * Class ParentChildGetService
 * @package PlMigration\Service\Plapi
 */
class ParentChildGetService implements IService
{
    use PlapiService;

    /**
     * @var string
     */
    private $prereqService;
    /**
     * @var array
     */
    private $prereqIds;

    private $prereqOptions;

    /**
     * Class that gets parent service to be able to fill the second service.
     * For example: to be able to get group members from playerlync API, we need a GET groups to get all groups,
     * followed by a GET group/group_id/members to get the group members in each group.
     * ParentChildService constructor.
     * @param string $service
     * @param string $prereqService
     * @param array $prereqServiceOptions
     */
    public function __construct($service, $prereqService, $prereqServiceOptions = [])
    {
        $this->method = 'GET';
        $this->service = $this->cleanupPath($service);
        $this->prereqService = $this->cleanupPath($prereqService);
        $this->prereqIds = $this->parseKeysInPath($this->service);
        if(is_array($prereqServiceOptions))
            $this->prereqOptions = $prereqServiceOptions;
    }

    /**
     * @param ApiClient $apiConnection
     * @param array $options
     * @return mixed|void
     * @throws ClientException
     */
    public function execute(ApiClient $apiConnection, $options = [])
    {
        $records = [];
        $parentRecords = $this->getAll($apiConnection, $this->prereqService, $this->prereqOptions);

        foreach($parentRecords as $parentRecord)
        {
            $childRecords = $this->getAll($apiConnection, $this->buildService($this->service, $parentRecord), $options);
            foreach($childRecords as $record)
            {
                $records[] = $record;
            }
        }
        return $records;
    }

    /**
     * @param ApiClient $apiConnection
     * @param string $servicePath
     * @param array $options
     * @return array
     * @throws ClientException
     */
    protected function getAll($apiConnection, $servicePath, $options)
    {
        $page = 1;
        $totalData = [];
        if(!array_key_exists('query', $options))
        {
            $options['query'] = [];
        }

        do{
            $options['query']['page'] = $page;

            $data = $apiConnection->validateResponse($apiConnection->request($this->method, $servicePath, $options));

            if(!empty($data->data))
            {
                if(is_array($data->data)) {
                    foreach($data->data as $record)
                    {
                        $totalData[] = $record;
                    }
                }
                else
                    return $data->data;
            }
            $valid = $data->page < $data->totalpages;
            $page++;
        }while($valid);

        return $totalData;
    }

    /**
     * Replace the primary keys in the service with actual values that are returned by the parent service results
     * @param string $serviceTemplate
     * @param object $data
     * @return string
     */
    protected function buildService($serviceTemplate, $data)
    {
        foreach($this->prereqIds as $id)
        {
            if(isset($data->$id))
                $serviceTemplate = str_replace('{'.$id.'}', $data->$id, $serviceTemplate);
        }
        return $serviceTemplate;
    }

    /**
     * @return array
     */
    public function getKeysInPath(): array
    {
        return $this->prereqIds;
    }

    /**
     * @param ApiClient $apiClient
     * @return mixed
     * @throws ClientException
     */
    public function getStructure(ApiClient $apiClient)
    {
        $prereqData = $apiClient->validateResponse($apiClient->request('GET', $this->prereqService, ['query' => [
            'limit' => 1
        ]]));

        if(empty($prereqData->data))
            throw new ClientException('Prerequisite service returned no data');

        $data = is_array($prereqData->data) ? $prereqData->data[0] : $prereqData->data;

        return $apiClient->validateResponse($apiClient->request($this->method, $this->buildService($this->service, $data), ['query' => [
            'structure' => 1
        ]]))->data->structure;
    }
}
