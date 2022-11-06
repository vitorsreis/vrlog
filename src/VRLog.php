<?php
/**
 * This file is part of VRLog - PHP Full AccessLog
 *
 *  @author    Vitor Reis <vitor@d5w.com.br>
 *  @copyright 2022 D5W Group. All rights reserved.
 */

namespace VRLog;

use Exception;
use VRLog\Adaptor\ElasticSearch;
use VRLog\Adaptor\File;
use VRLog\Utils\DotEnv;
use VRLog\Utils\IAdaptor;

/**
 * Class VRLog
 *
 * @package VRLog
 * @author  Vitor Reis <vitor@d5w.com.br>
 */
class VRLog
{
    /**
     * @var IAdaptor Log adaptor
     */
    private static $adaptor;

    /**
     * @var string Doc ID
     */
    private static $docId;

    /**
     * @var float Start time
     */
    private static $startTime;

    /**
     * @var float|false Tolerance for skip full log if response is less than time
     */
    private static $tolerance;

    /**
     * @var array Errors
     */
    private static $error = [];

    /**
     * @var array Extra data
     */
    private static $extra = [];

    /**
     * Method for handle log
     *
     * @param  string|null $docId Doc ID
     * @return void
     * @throws Exception
     */
    public static function bootstrap($docId = null)
    {
        # TRY .env LOAD IF ADAPTOR KEY NOT EXISTS
        if (!DotEnv::has('VRLOG_ADAPTOR')) {
            DotEnv::bootstrap(is_file($env = __DIR__ . '/../.env') ? $env :"$env.development");
        }

        # SET SKIP ULL LOG
        self::setTolerance(intval(DotEnv::get('VRLOG_TOLERANCE')) ?: false);

        # GET START TIME
        if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
            $startTime = $_SERVER['REQUEST_TIME_FLOAT']; # APACHE START TIME
        } else {
            $startTime = microtime(true);
        }
        self::setStartTime($startTime);

        # GET DOC ID
        if (!$docId) {
            if (!empty($_SERVER['UNIQUE_ID'])) { # APACHE UNIQUE ID
                $docId = sha1($_SERVER['UNIQUE_ID']);
            } else {
                $docId = sha1(uniqid());
            }
        }
        self::setDocId($docId);

        # GET ADAPTOR
        switch ($adaptor = DotEnv::get('VRLOG_ADAPTOR')) {
            case 'elasticsearch':
                self::$adaptor = new ElasticSearch();
                break;

            case 'file':
                self::$adaptor = new File();
                break;

            default:
                self::ex('Require .env[VRLOG_ADAPTOR]');
                return;
        }

        $instance = self::$adaptor;
        if (!($instance::bootstrap($docId))) {
            error_log('[' . date('Y-m-d H:i:s') . "] VRLog: Failed $adaptor bootstrap" . PHP_EOL);
            return;
        }

        # SAVE REQUEST
        self::saveRequest();

        # SET HANDLE ERROR/EXCEPTION
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            self::errorEvent($errno, $errstr, $errfile, $errline);
        }, E_ALL);

        set_exception_handler(function ($exception) {
            self::errorEvent(
                $exception->getCode(),
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            );
        });

        # SET HANDLE SHUTDOWN
        register_shutdown_function(function () {
            self::saveResponse();
        });

        # START OB FOR GET LENGTH
        ob_start();
    }

    /**
     * Method for get doc id
     *
     * @return string Return doc id
     */
    public static function getDocId()
    {
        return self::$docId;
    }

    /**
     * Method for set doc id
     *
     * @param  string $docId Doc id
     * @return void
     */
    private static function setDocId($docId)
    {
        self::$docId = $docId;
    }

    /**
     * Method for get start time
     *
     * @return float Start time
     */
    public static function getStartTime()
    {
        return self::$startTime;
    }

    /**
     * Method for set start time
     *
     * @param  float $startTime Start time
     * @return void
     */
    private static function setStartTime($startTime)
    {
        self::$startTime = $startTime;
    }

    /**
     * Method for set tolerance for skip full log time
     *
     * @return float|false Skip full log time or false
     */
    public static function getTolerance()
    {
        return self::$tolerance;
    }

    /**
     * Method for set tolerance for skip full log time
     *
     * @param  float|false $tolerance Skip full log time
     * @return void
     */
    public static function setTolerance($tolerance)
    {
        self::$tolerance = $tolerance;
    }

    /**
     * Method for set extra data
     *
     * @param  string $key    Extra key
     * @param  string $value  Extra value
     * @param  string $append if "true" append value in array, else clear array before append vale
     * @return void
     */
    public static function extra($key, $value, $append = true)
    {
        if (!isset(self::$extra[$key]) || !$append) {
            self::$extra[$key] = [];
        }

        self::$extra[$key][] = $value;
    }

    /**
     * Method for handle error events
     *
     * @param  int    $errno
     * @param  string $errstr
     * @param  string $errfile
     * @param  int    $errline
     * @return void
     */
    private static function errorEvent($errno, $errstr, $errfile, $errline)
    {
        self::$error[] = [ $errno, $errstr, $errfile, $errline ];
    }

    /**
     * Method for log request (start connection)
     *
     * @return void
     */
    private static function saveRequest()
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = null;
        }

        if (!empty($_SERVER['HTTP_REFERER'])) {
            $referer = $_SERVER['HTTP_REFERER'];
        } else {
            $referer = null;
        }

        if (!empty($_SERVER['HTTP_USER_AGENT'])) {
            $userAgent = $_SERVER['HTTP_USER_AGENT'];
        } else {
            $userAgent = null;
        }

        if (!empty($_SERVER['REQUEST_METHOD'])) {
            $method = $_SERVER['REQUEST_METHOD'];
        } else {
            $method = null;
        }

        if (!empty($_SERVER['REQUEST_SCHEME'])) {
            $scheme = $_SERVER['REQUEST_SCHEME'];
        } else {
            $scheme = null;
        }

        if (!empty($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
        } else {
            $host = null;
        }

        if (!empty($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];
        } else {
            $uri = null;
        }

        $instance = self::$adaptor;
        $instance::request(
            self::$docId,
            array_filter([
                'start_date' => date('c\Z', (int)self::$startTime),
                'start_time' => self::$startTime,
                'method'     => $method,
                'url'        => [
                    'scheme' => $scheme,
                    'host'   => $host,
                    'uri'    => $uri
                ],
                'ip'         => $ip,
                'referer'    => $referer,
                'useragent'  => $userAgent,
                'get'        => $_GET ?: null,
                'post'       => $_POST ?: null,
                'rawpost'    => is_file('php://input') ? file_get_contents('php://input') : null,
                'files'      => $_FILES ?: null,
                'cookies'    => $_COOKIE ?: null,
                'server'     => $_SERVER ?: null
            ])
        );
    }

    /**
     * Method for log response (end connection)
     *
     * @return void
     */
    private static function saveResponse()
    {
        $endTime = microtime(true);

        if ($endTime < self::$tolerance) {
            $headers = null;
            $extra = null;
            $incFiles = null;
        } else {
            $headers = headers_list();
            $extra = self::$extra;
            $incFiles = get_included_files();
        }

        $instance = self::$adaptor;
        $instance::response(
            self::$docId,
            array_filter([
                'end_date'    => date('c\Z', (int)$endTime),
                'end_time'    => $endTime,
                'time'        => $endTime - self::$startTime,
                'http_code'   => http_response_code() ?: '0',
                'length'      => ob_get_length() ?: '0',
                'headers'     => $headers,
                'error'       => self::$error ?: null,
                'extra'       => $extra,
                'inc_files'   => $incFiles,
                'memory'      => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true)
            ])
        );
        ob_end_flush();
    }

    /**
     * Method for error alert
     *
     * @param  string $err
     * @return void
     * @throws Exception
     */
    public static function ex($err)
    {
        if (filter_var(DotEnv::get('VRLOG_ELK_SERVER'), FILTER_VALIDATE_BOOLEAN)) {
            throw new Exception("VRLog: $err", E_ERROR);
        } else {
            error_log('[' . date('Y-m-d H:i:s') . "] VRLog: $err" . PHP_EOL);
        }
    }
}
