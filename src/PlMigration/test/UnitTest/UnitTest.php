<?php

namespace PlMigration\test\UnitTest;

use PHPUnit\Framework\TestCase;

class UnitTest extends TestCase
{

    protected function getConfigData()
    {
        $json = json_decode(file_get_contents(__DIR__ . '/config.cfg'));

        if($json === null)
            echo 'Bad config.cfg json';

        return $json;
    }
}