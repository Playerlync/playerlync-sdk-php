<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 2019-02-26
 * Time: 10:11
 */

namespace PlMigration;

use PlMigration\Connectors\IConnector;
use PlMigration\Helper\MappedImportTrait;
use PlMigration\Model\ImportModel;
use PlMigration\Reader\IReader;

/**
 * Imported created for playerlync data types that can't be run with PlayerlyncImport class.
 * NOT RECOMMENDED FOR USE UNLESS NECESSARY
 * CONTAINS PERFORMANCE ISSUES ON BIG DATA SETS
 * Class PlayerlyncImportPostPut
 * @package PlMigration
 */
class PlayerlyncMappedImport extends PlayerlyncImport
{
    use MappedImportTrait;

    /**
     * PlayerlyncMappedImport constructor.
     * @param IConnector $connector
     * @param IReader $reader
     * @param ImportModel $model
     * @param array $options
     */
    public function __construct(IConnector $connector, IReader $reader, ImportModel $model, array $options = [])
    {
        parent::__construct($connector, $reader, $model, $options);

        $this->setMappedData($options);
    }

    /**
     * Override to add grabbing server records of the importing type to be able to do data mapping
     */
    public function import()
    {
        $this->getServerRecords();
        parent::import();
    }
}