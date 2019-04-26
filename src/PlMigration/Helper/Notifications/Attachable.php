<?php


namespace PlMigration\Helper\Notifications;


interface Attachable
{
    const LOG_FILE = 'log';
    const INPUT_FILE = 'input';
    const OUTPUT_FILE = 'output';
    const TRANSACTION_FILE = 'transaction';

    /**
     * Add an attachment file with the correspondent type that it is categorized from the list provided of
     * constants in the Attachable interface
     * @param string $file the file path of the file
     * @param string $attachmentType optional: the file type that is being attached.
     * @return mixed
     */
    public function addAttachment($file, $attachmentType = null);
}