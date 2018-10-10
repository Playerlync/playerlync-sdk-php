<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/5/18
 */

namespace PlMigration\Builder;

use Keboola\Csv\Exception;
use PlMigration\Connectors\APIConnector;
use PlMigration\Exceptions\ConnectorException;
use PlMigration\Exceptions\BuilderException;
use PlMigration\Model\ExportModel;
use PlMigration\Model\Field;
use PlMigration\PlayerlyncExport;
use PlMigration\Writer\CsvWriter;

class APIExportBuilder
{
    /**
     * @var string
     */
    private $outputFile;

    /**
     * Delimiter used by the csv writer with a default value of a comma
     * @var string
     */
    private $delimiter = ',';

    /**
     * Enclosure used by the csv write with a default value of a double quote
     * @var string
     */
    private $enclosure = '"';

    /**
     * @var string
     */
    private $service;

    /**
     * @var array
     */
    private $queryParams;

    /**
     * boolean to decide whether to include the headers names for the records in the output file. By default, the
     * headers will not be printed into the file.
     *
     * @var bool
     */
    private $includeHeaders = false;

    /**
     * Fields to be retrieved from the data provider
     * @var array
     */
    private $fields = [];

    /**
     * Array has holds the host settings
     * @var array
     */
    private $hostSettings = [];

    private $format;

    /**
     * ExportBuilder constructor.
     * @param $destination
     * @param $host
     */
    public function __construct($destination, $host)
    {
        $this->outputFile = $destination;
        $this->hostSettings['host'] = $host;
    }

    public function delimiter($delimiter)
    {
        $this->delimiter = $delimiter;
        return $this;
    }

    public function enclosure($enclosure)
    {
        $this->enclosure = $enclosure;
        return $this;
    }

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
     * @param $apiField
     * @param null $headerName
     * @param string $fieldType
     * @return $this
     * @throws BuilderException
     */
    public function addField($apiField, $headerName = null, $fieldType = Field::VARIABLE)
    {
        if($apiField === null && $headerName === null)
        {
            throw new BuilderException('All arguments of addField cannot be null.');
        }
        $headerName = $headerName ?: $apiField;

        if(array_key_exists($headerName, $this->fields))
        {
            throw new BuilderException('Attempted to insert duplicate header: '.$headerName);
        }
        $this->fields[$headerName] = new Field($apiField, $fieldType);
        return $this;
    }

    public function includeHeaders()
    {
        $this->includeHeaders = true;
        return $this;
    }

    /**
     * @param mixed ...$format
     * @return $this
     */
    public function timeFormat(...$format)
    {
        $this->format['time'] = implode('', $format);
        return $this;
    }

    /**
     * @return PlayerlyncExport
     * @throws BuilderException
     */
    public function build()
    {
        $model = new ExportModel($this->fields, $this->format);

        try
        {
            $writer = new CsvWriter($this->outputFile, $this->delimiter, $this->enclosure);
        }
        catch (Exception $e)
        {
            throw new BuilderException($e->getMessage());
        }

        try
        {
            $api = new APIConnector($this->service, $this->queryParams, $this->hostSettings);
            $model->setTimeFields($api->getTimeFields());
        }
        catch (ConnectorException $e)
        {
            throw new BuilderException($e->getMessage());
        }

        return new PlayerlyncExport($api, $writer, $model, $this->includeHeaders);
    }
}