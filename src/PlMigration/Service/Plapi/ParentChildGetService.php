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
    private $prereqIds = [];

    /**
     * Class that gets parent service to be able to fill the second service.
     * For example: to be able to get group members from playerlync API, we need a GET groups to get all groups,
     * followed by a GET group/group_id/members to get the group members in each group.
     * ParentChildService constructor.
     * @param $service
     * @param $prereqService
     */
    public function __construct($service, $prereqService)
    {
        $this->method = 'GET';
        $this->service = $this->cleanupPath($service);
        $this->prereqService = $this->cleanupPath($prereqService);
        $this->parseKeysInPath();
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
        $parentRecords = $this->getAll($apiConnection, $this->prereqService, $options);

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
     * extract all strings inside curly brackets as they will be used as the key to match with the parent service
     */
    protected function parseKeysInPath()
    {
        $results = preg_match_all('/\/{(.*?)}/', $this->service, $matches);
        if($results && isset($matches[1]))
        {
            foreach($matches[1] as $id)
            {
                $this->prereqIds[] = $id;
            }
        }
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
}