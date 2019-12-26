<?php


namespace PlMigration\Helper;

/**
 * Interface that executes things after the import or export processes have finished
 */
interface TearDownAction
{
    /**
     * Actions to take as the export/import process is exiting
     * @param null $logger
     */
    public function tearDown($logger = null);
}