<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2015 PlayerLync
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace PlayerLync\Url;

/**
 * Class PlayerLyncUrlManipulator
 *
 * @package PlayerLync\Url
 */
class PlayerLyncUrlManipulator
{
    /**
     * Remove params from a URL.
     *
     * @param string $url            The URL to filter.
     * @param array  $paramsToFilter The params to filter from the URL.
     *
     * @return string The URL with the params removed.
     */
    public static function removeParamsFromUrl($url, array $paramsToFilter)
    {
        $parts = parse_url($url);

        $query = '';
        if (isset($parts['query']))
        {
            $params = [];
            parse_str($parts['query'], $params);

            // Remove query params
            foreach ($paramsToFilter as $paramName)
            {
                unset($params[$paramName]);
            }

            if (count($params) > 0)
            {
                $query = '?' . http_build_query($params, null, '&');
            }
        }

        $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
        $host = isset($parts['host']) ? $parts['host'] : '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = isset($parts['path']) ? $parts['path'] : '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        return $scheme . $host . $port . $path . $query . $fragment;
    }

    /**
     * Adds the params of the first URL to the second URL.
     *
     * Any params that already exist in the second URL will go untouched.
     *
     * @param string $urlToStealFrom The URL harvest the params from.
     * @param string $urlToAddTo     The URL that will receive the new params.
     *
     * @return string The $urlToAddTo with any new params from $urlToStealFrom.
     */
    public static function mergeUrlParams($urlToStealFrom, $urlToAddTo)
    {
        $newParams = static::getParamsAsArray($urlToStealFrom);
        // Nothing new to add, return as-is
        if (!$newParams)
        {
            return $urlToAddTo;
        }

        return static::appendParamsToUrl($urlToAddTo, $newParams);
    }

    /**
     * Returns the params from a URL in the form of an array.
     *
     * @param string $url The URL to parse the params from.
     *
     * @return array
     */
    public static function getParamsAsArray($url)
    {
        $query = parse_url($url, PHP_URL_QUERY);
        if (!$query)
        {
            return [];
        }
        $params = [];
        parse_str($query, $params);

        return $params;
    }

    /**
     * Gracefully appends params to the URL.
     *
     * @param string $url       The URL that will receive the params.
     * @param array  $newParams The params to append to the URL.
     *
     * @return string
     */
    public static function appendParamsToUrl($url, array $newParams = [])
    {
        if (!$newParams)
        {
            return $url;
        }

        if (strpos($url, '?') === false)
        {
            return $url . '?' . http_build_query($newParams, null, '&');
        }

        list($path, $query) = explode('?', $url, 2);
        $existingParams = [];
        parse_str($query, $existingParams);

        // Favor params from the original URL over $newParams
        $newParams = array_merge($newParams, $existingParams);

        // Sort for a predicable order
        ksort($newParams);

        return $path . '?' . http_build_query($newParams, null, '&');
    }

    /**
     * Check for a "/" prefix and prepend it if not exists.
     *
     * @param string|null $string
     *
     * @return string|null
     */
    public static function forceSlashPrefix($string)
    {
        if (!$string)
        {
            return $string;
        }

        return strpos($string, '/') === 0 ? $string : '/' . $string;
    }

    /**
     * Check for a "/<version>/plapi" postfix and append it if not exists.
     *
     * @param string|null $string
     *
     * @return string|null
     */
    public static function forcePLAPIPrefix($string)
    {
        if (!$string)
        {
            return $string;
        }

        return strpos($string, '/plapi') === 0 ? $string : '/plapi' . $string;
    }

    /**
     * Trims off the hostname and API version from a URL.
     *
     * @param string $urlToTrim The URL the needs the surgery.
     *
     * @return string The $urlToTrim with the hostname and API version removed.
     */
    public static function baseAPIUrlEndpoint($urlToTrim)
    {
        return '/' . preg_replace('/^https:\/\/.+\.playerlync\.com(\/v.+?)?\//', '', $urlToTrim);
    }
}
