<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/5/18
 */

namespace PlMigration;

use PlMigration\Connectors\IConnector;
use PlMigration\Exceptions\ExportException;
use PlMigration\Model\ExportModel;
use PlMigration\Transfer\ITransfer;
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
     * Instantiate a new exporter.
     * @param IConnector $api
     * @param IWriter $writer
     * @param ExportModel $model
     * @param bool $writeHeaders
     * @param ITransfer|null $transfer
     */
    public function __construct(IConnector $api, IWriter $writer, ExportModel $model, $writeHeaders = false, ITransfer $transfer = null)
    {
        $this->api = $api;
        $this->writer = $writer;
        $this->model = $model;
        $this->transfer = $transfer;
        if($writeHeaders)
        {
            $this->writer->writeRecord($model->getHeaders());
        }
    }

    public function export()
    {
        $hasNext = true;
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
            throw new ExportException('Transfer protocol has not been configured');
        }
        $file = $this->writer->getFile();

        $this->transfer->send($file);
    }
}