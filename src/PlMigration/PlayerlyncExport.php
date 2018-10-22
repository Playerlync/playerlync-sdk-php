<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/5/18
 */

namespace PlMigration;

use Monolog\Logger;
use PlMigration\Connectors\IConnector;
use PlMigration\Model\ExportModel;
use PlMigration\Writer\IWriter;

class PlayerlyncExport
{
    /**
     * connection object to be used
     * @var IConnector
     */
    private $api;

    /**
     * Record write handler to output file
     * @var IWriter
     */
    private $writer;

    /**
     * Template for the output data to be used
     * @var ExportModel
     */
    private $model;

    /** @var Logger */
    private $logger;

    /**
     * Instantiate a new exporter.
     * @param IConnector $api
     * @param IWriter $writer
     * @param ExportModel $model
     * @param array $options
     */
    public function __construct(IConnector $api, IWriter $writer, ExportModel $model, array $options = [])
    {
        $this->api = $api;
        $this->writer = $writer;
        $this->model = $model;

        if(isset($options['include_headers']) && $options['include_headers'] === true)
        {
            $this->writer->writeRecord($model->getHeaders());
        }

        if(isset($options['logger']))
        {
            $this->setLogger($options['logger']);
        }
    }

    /**
     * @throws \Exception
     */
    public function export()
    {
        try
        {
            do
            {
                $records = $this->get($hasNext);

                foreach($records as $record)
                {
                    $this->writeRow($record);
                }
            }
            while($hasNext);
        }
        catch(\Exception $e)
        {
            $this->writeError($e->getMessage());
            throw $e;
        }

        return $this->writer->getFile();
    }

    /**
     * @param bool $hasNext
     * @return array
     */
    public function get(&$hasNext = false)
    {
        $response = $this->api->getRecords();

        $hasNext = $this->api->hasNext();

        return $response;
    }

    public function writeRow($record)
    {
        $row = $this->model->fillModel($record);

        $this->writer->writeRecord($row);
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function writeError($message)
    {
        if($this->logger !== null)
            $this->logger->error($message);
    }
}