<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/15/18
 */

namespace PlMigration\Builder;

use PlMigration\Transfer\FtpTransfer;

trait TransferTrait
{
    public function ftpConnect($host, $username, $password, $port = 21)
    {
        $this->options['transfer'] = new FtpTransfer($host, $username, $password, $port);
        return $this;
    }

    public function sendTo($destination)
    {
        $this->options['remoteFileLocation'] = $destination;
        return $this;
    }

}