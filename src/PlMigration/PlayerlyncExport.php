<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/5/18
 */

namespace PlMigration;

use PlMigration\Connectors\IConnector;
use PlMigration\Exceptions\ConnectorException;
use PlMigration\Exceptions\ExportException;
use PlMigration\Model\ExportModel;
use PlMigration\Writer\IWriter;
use PlMigration\Helper\LoggerTrait;

class PlayerlyncExport
{
    use LoggerTrait;
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
     * @throws ExportException
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
                    $this->writeRow((array)$record);
                }
            }
            while($hasNext);
        }
        catch(\Exception $e)
        {
            $this->error($e->getMessage());
            throw new ExportException($e->getMessage());
        }

        return $this->writer->getFile();
    }

    /**
     * @param bool $hasNext
     * @return array
     * @throws ConnectorException
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
}