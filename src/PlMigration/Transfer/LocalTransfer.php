<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/16/18
 */

namespace PlMigration\Transfer;


use PlMigration\Exceptions\TransferException;

class LocalTransfer implements ITransfer
{

    /**
     * @param $localFile
     * @param $remoteDestination
     * @throws TransferException
     */
    public function put($localFile, $remoteDestination)
    {
        if(!rename($localFile, $remoteDestination.'/'.$localFile))
        {
            throw new TransferException('Unable to move file');
        }
    }

    public function get($remoteFile, $localDestination)
    {
    }

    public function connect()
    {
    }

    public function close()
    {
    }
}