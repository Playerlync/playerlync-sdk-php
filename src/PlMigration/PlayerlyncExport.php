<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/5/18
 */

namespace PlMigration;

use PlMigration\Connectors\IConnector;
use PlMigration\Exceptions\ExportException;
use PlMigration\Exceptions\TransferException;
use PlMigration\Model\ExportModel;
use PlMigration\Transfer\ITransfer;
use PlMigration\Transfer\LocalTransfer;
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

    /**
     * Transfer object to send information to another destination after exporting the data
     * @var ITransfer
     */
    private $transfer;

    /**
     *
     * @var string
     */
    private $remoteFileLocation;

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

        $this->transfer = isset($options['transfer']) ? $options['transfer'] : new LocalTransfer();
        $this->remoteFileLocation = $options['remoteFileLocation'];

        if(isset($options['include_headers']) && $options['include_headers'] === true)
        {
            $this->writer->writeRecord($model->getHeaders());
        }
    }

    /**
     * @throws ExportException
     */
    public function export()
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

        $this->send();
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

    /**
     * @throws ExportException
     */
    public function send()
    {
        if($this->transfer === null)
        {
            return;
        }

        try
        {
            $this->transfer->connect();
            $this->transfer->put($this->writer->getFile(), $this->remoteFileLocation);
            $this->transfer->close();
        }
        catch(TransferException $e)
        {
            throw new ExportException('file transfer failed, '.$e->getMessage());
        }
    }
}