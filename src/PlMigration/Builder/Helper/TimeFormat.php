<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/9/18
 */

namespace PlMigration\Builder\Helper;

class TimeFormat
{
    /**
     *  Day of the month, 2 digits with leading zeros (01 to 31)
     */
    const DAY = 'd';
    /**
     *  Numeric representation of a month, with leading zeros (01 to 12)
     */
    const MONTH = 'm';
    /**
     *  A full numeric representation of a year, 4 digits (Examples: 1999 or 2003)
     */
    const YEAR = 'Y';
    /**
     *  A two digit representation of a year (Examples: 99 or 03)
     */
    const TWO_DIGIT_YEAR = 'y';
    /**
     *  24-hour format of an hour with leading zeros (00 to 23)
     */
    const HOUR_24 = 'H';
    /**
     *  12-hour format of an hour with leading zeros (01 to 12)
     */
    const HOUR_12 = 'h';
    /**
     *  minutes with leading zeros (00 to 59)
     */
    const MINUTES = 'i';
    /**
     *  seconds with leading zeros (00 to 59)
     */
    const SECONDS = 's';
    /**
     *  Ante meridian and Post meridian (AM or PM)
     */
    const MERIDIAN = 'A';
    /**
     *  Add the timezone to the date (Example: UTC, GMT, Atlantic/Azores)
     */
    const TIMEZONE = 'e';
    /**
     *  Format to an RFC 2822 date (Example: Thu, 21 Dec 2000 16:01:07 +0200)
     */
    const RFC_DATE = 'r';
    /**
     *  Format to an ISO 8601 date (Example: 2004-02-12T15:19:21+00:00)
     */
    const ISO_DATE = 'c';
}