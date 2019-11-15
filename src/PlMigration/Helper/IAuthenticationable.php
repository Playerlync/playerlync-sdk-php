<?php
namespace PlMigration\Helper;

use PlMigration\Exceptions\ClientException;

interface IAuthenticationable
{
    /**
     * @throws ClientException
     */
    public function authenticate();

    /**
     * @throws ClientException
     */
    public function renew();
}
