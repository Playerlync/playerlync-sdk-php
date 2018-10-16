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
    use ApiBuilderTrait;
    use CsvBuilderTrait;
    use TransferTrait;

    /**
     * Full path to the output file created
     * @var string
     */
    private $outputFile;

    /**
     * Specific format to be used in the output file (ie. time)
     * @var array
     */
    private $format = [];

    /**
     * array holding additional settings that will be used by the export
     * @var bool
     */
    private $options = [];

    /**
     * ExportBuilder constructor.
     */
    public function __construct()
    {
        $this->apiVersion('v3');
    }

    public function outputFilename($file)
    {
        $this->outputFile = $file;
        return $this;
    }

    /**
     * Set the date format to be output on the exported file
     * @param mixed ...$format
     * @return $this
     */
    public function timeFormat(...$format)
    {
        $this->format['time'] = implode('', $format);
        return $this;
    }

    public function includeHeaders()
    {
        $this->options['include_headers'] = true;
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

        return new PlayerlyncExport($api, $writer, $model, $this->options);
    }
}