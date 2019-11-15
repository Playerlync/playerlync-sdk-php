<?php


namespace PlMigration\Helper;


interface IActivityTrackable
{
    /**
     * log an activity into the API system
     * @param array $data
     * @return mixed
     */
    public function logActivity($data);
}
