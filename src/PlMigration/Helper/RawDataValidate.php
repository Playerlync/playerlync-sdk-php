<?php


namespace PlMigration\Helper;

use Closure;

class RawDataValidate implements IRawDataCheck
{
    private $validate;

    public function __construct(Closure $validate)
    {
        $this->validate = $validate;
    }

    /**
     * @inheritDoc
     */
    public function checkRawData($data, $logger = null): bool
    {
        return $this->validate->__invoke($data, $logger);
    }
}