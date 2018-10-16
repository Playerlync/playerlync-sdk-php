<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/9/18
 */

namespace PlMigration\Transfer;


interface ITransfer
{
    public function put($localFile, $remoteDestination);

    public function get($remoteFile, $localDestination);

    public function connect();

    public function close();
}