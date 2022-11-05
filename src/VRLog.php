<?php
/**
 * This file is part of VRLog - PHP Full AccessLog
 *
 *  @author    Vitor Reis <vitor@d5w.com.br>
 *  @copyright 2022 D5W Group. All rights reserved.
 */

namespace VRLog;

use VRLog\Adaptor\ElasticSearch;
use VRLog\Adaptor\File;
use VRLog\Utils\DotEnv;
use VRLog\Utils\IAdaptor;

/**
 * Class VRLog
 *
 * @package VRLog
 * @author  Vitor Reis <vitor@d5w.com.br>
 * @since   Class available since Release: 1.0.0
 */
class VRLog
{
    /**
     * @var   IAdaptor Log adaptor
     * @since Property available since Release: 1.0.0
     */
    private static $adaptor;

    /**
     * @var   string Temp directory
     * @since Property available since Release: 1.0.0
     */
    private static $tempDir;

    /**
     * @var   string Doc ID
     * @since Property available since Release: 1.0.0
     */
    private static $docId;

    /**
     * @var   float Start time
     * @since Property available since Release: 1.0.0
     */
    private static $startTime;

    /**
     * @var   float|false Tolerance for skip full log if response is less than time
     * @since Property available since Release: 1.0.0
     */
    private static $tolerance;

    /**
     * @var   array Errors
     * @since Property available since Release: 1.0.0
     */
    private static $error = [];

    /**
     * @var   array Extra data
     * @since Property available since Release: 1.0.0
     */
    private static $extra = [];

    /**
     * Method for handle log
     *
     * @param  string|null $docId Doc ID
     * @return void
     * @since  Method available since Release: 1.0.0
     */
    public static function bootstrap($docId = null)
    {
        # LOAD .env
        new DotEnv(__DIR__ . '/../.env');

        # SET TEMP DIR
        if (getenv('VRLOG_TMP')) {
            self::setTempDir(getenv('VRLOG_TMP'));
        } else {
            self::setTempDir(__DIR__ . '/../.tmp');
        }

        # SET SKIP ULL LOG
        self::setTolerance(getenv('VRLOG_TOLERANCE'));

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
        switch ($adaptor = getenv('VRLOG_ADAPTOR')) {
            case 'elasticsearch':
                self::$adaptor = new ElasticSearch();
                break;

            default:
                self::$adaptor = new File();
                break;
        }
        if (!((self::$adaptor)::bootstrap($docId))) {
            error_log(
                '[' . date('Y-m-d H:i:s') . "] Failed $adaptor bootstrap" . PHP_EOL,
                3,
                self::getTempDir() . '/error.log'
            );
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
    }

    /**
     * Method for get doc id
     *
     * @return string Return doc id
     * @since  Method available since Release: 1.0.0
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
     * @since  Method available since Release: 1.0.0
     */
    private static function setDocId($docId)
    {
        self::$docId = $docId;
    }

    /**
     * Method for get start time
     *
     * @return float Start time
     * @since  Method available since Release: 1.0.0
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
     * @since  Method available since Release: 1.0.0
     */
    private static function setStartTime($startTime)
    {
        self::$startTime = $startTime;
    }

    /**
     * Method for set tolerance for skip full log time
     *
     * @return float|false Skip full log time or false
     * @since  Method available since Release: 1.0.0
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
     * @since  Method available since Release: 1.0.0
     */
    public static function setTolerance($tolerance)
    {
        self::$tolerance = $tolerance;
    }

    /**
     * Method for get temp directory
     *
     * @return string
     * @since  Method available since Release: 1.0.0
     */
    public static function getTempDir()
    {
        return self::$tempDir;
    }

    /**
     * Method for set temp directory
     *
     * @param  string $dir Temp directory
     * @return void
     * @since  Method available since Release: 1.0.0
     */
    public static function setTempDir($dir)
    {
        self::$tempDir = $dir;

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * Method for set extra data
     *
     * @param  string $key    Extra key
     * @param  string $value  Extra value
     * @param  string $append if "true" append value in array, else clear array before append vale
     * @return void
     * @since  Method available since Release: 1.0.0
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
     * @since  Method available since Release: 1.0.0
     */
    private static function errorEvent($errno, $errstr, $errfile, $errline)
    {
        self::$error[] = [ $errno, $errstr, $errfile, $errline ];
    }

    /**
     * Method for log request (start connection)
     *
     * @return void
     * @since  Method available since Release: 1.0.0
     */
    private static function saveRequest()
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
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

        (self::$adaptor)::request(
            self::$docId,
            array_filter([
                'start_date' => date('c\Z', (int)self::$startTime),
                'start_time' => self::$startTime,
                'method'     => $_SERVER['REQUEST_METHOD'],
                'url'        => [
                    'scheme' => $_SERVER['REQUEST_SCHEME'],
                    'host'   => $_SERVER['HTTP_HOST'],
                    'uri'    => $_SERVER['REQUEST_URI']
                ],
                'ip'         => $ip,
                'referer'    => $referer,
                'useragent'  => $userAgent,
                'get'        => $_GET ?: null,
                'post'       => $_POST ?: null,
                'rawpost'    => file_get_contents('php://input') ?: null,
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
     * @since  Method available since Release: 1.0.0
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

        (self::$adaptor)::response(
            self::$docId,
            array_filter([
                'end_date' => date('c\Z', (int)$endTime),
                'end_time'  => $endTime,
                'time'      => $endTime - self::$startTime,
                'http_code' => http_response_code() ?: '0',
                'headers'   => $headers,
                'error'     => self::$error ?: null,
                'extra'     => $extra,
                'inc_files' => $incFiles
            ])
        );
    }
}
