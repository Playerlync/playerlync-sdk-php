<?php


namespace PlMigration\Client;

use PlMigration\Exceptions\ClientException;
use PlMigration\Helper\PlapiClient;

class PlapiHttpClient extends RemoteClient
{
    private $config;

    private $cache = [];

    /**
     * @var PlapiClient
     */
    protected $protocol;

    /**
     * Create a new instance of the playerlync API to be server to hold files for import and exports
     * PlapiHttpClient constructor.
     * @param array $config
     * @param bool $allowOverwrite
     */
    public function __construct($config, $allowOverwrite = false)
    {
        $this->config = $config;
        $this->allowOverwrite = (bool)$allowOverwrite;
    }

    /**
     * Check if the file exists
     * throws exception if the directory does not exist
     * @param $file
     * @return bool
     * @throws ClientException
     */
    protected function fileExists($file)
    {
        return !empty($this->getApiRecord($file));
    }

    /**
     * Delete a file using the v3 DELETE file service
     * @param $file
     * @return mixed
     * @throws ClientException
     */
    protected function deleteFile($file)
    {
        $record = $this->getApiRecord($file);
        $this->protocol->delete('file/'.$record->file_id, []);
    }

    /**
     * @param $file
     * @param $destination
     * @return mixed
     * @throws ClientException
     */
    protected function moveFile($file, $destination)
    {
        throw new ClientException('Not implemented at this moment');
    }

    /**
     * Retrieve all files that are inside the directory using the GET files service.
     * @param string $directory
     * @param bool $includeDirectories
     * @return array
     * @throws ClientException
     */
    protected function getChildren($directory, $includeDirectories = false)
    {
        $records = $this->protocol->get('/files', ['filter'=>'file_path|eq|'.$directory]);

        $results = [];
        foreach($records as $record)
        {
            $this->cache[$record->file_path.'/'.$record->file_name] = $record;
            $results[]=$record->file_name;
        }
        return $results;
    }

    /**
     * Create a connection to the playerlync server with the information provided
     * @throws ClientException
     */
    public function connect()
    {
        if($this->protocol === null)
            $this->protocol = new PlapiClient($this->config);
    }

    /**
     * Close the connection to the playerlync server
     */
    public function close()
    {
        $this->protocol = null;
    }

    /**
     * Download a file to the local system using the securefiles GET service to download the file
     * @param $localDestination
     * @param $remoteFile
     * @return mixed
     * @throws ClientException
     */
    protected function downloadFile($localDestination, $remoteFile)
    {
        $record = $this->getApiRecord($remoteFile);
        $handler = $this->protocol->getFileClient()->getSecureFile($record->file_id);

        if(!file_put_contents($localDestination, $handler))
        {
            throw new ClientException('Unable to download file into the desired destination');
        }
        return true;
    }

    /**
     * Upload a file to the playerlync file system using the v3 plapi POST file service
     * @param $remoteLocation
     * @param $localFile
     * @return mixed
     */
    protected function uploadFile($remoteLocation, $localFile)
    {
        $fileInfo = pathinfo($localFile);
        $fileProperties = [
            [
                'name' => 'file_name',
                'contents' => $fileInfo['basename']
            ],
            [
                'name' => 'file_path',
                'contents' => pathinfo($remoteLocation,PATHINFO_DIRNAME)
            ],
            [
                'name' => 'file_ext',
                'contents' => $fileInfo['extension']
            ],
            [
                'name' => 'hidden',
                'contents' => 1
            ],
            [
                'name' => 'org_id',
                'contents' => $this->protocol->getPrimaryOrgId()
            ],
            [
                'name' => 'selected_org_id',
                'contents' => $this->protocol->getPrimaryOrgId()
            ],
            [
                'name' => 'file',
                'contents' => fopen($localFile, 'rb'),
                'filename' => $fileInfo['basename']
            ]
            ];
        $this->protocol->post('file', ['multipart'=> $fileProperties]);
        return true;
    }

    /**
     * @param $file
     * @return mixed|null
     * @throws ClientException
     */
    private function getApiRecord($file)
    {
        if(!array_key_exists($file, $this->cache))
        {
            $fileInfo = pathinfo($file);
            $records = $this->protocol->get('/files', ['filter' => 'file_name|eq|' . $fileInfo['filename'] . ',file_path|eq|' . $fileInfo['dirname']]);

            if (count($records) > 0) {
                $this->cache[$file] = $records[0];
            }
        }

        return $this->cache[$file] ?? null;
    }
}