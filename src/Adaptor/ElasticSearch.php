<?php
/**
 * This file is part of VRLog - PHP Full AccessLog
 *
 *  @author    Vitor Reis <vitor@d5w.com.br>
 *  @copyright 2022 D5W Group. All rights reserved.
 */

namespace VRLog\Adaptor;

use VRLog\Utils\IAdaptor;
use VRLog\VRLog;

/**
 * Class ElasticSearch
 *
 * @package VRLog\Adaptor
 * @author  Vitor Reis <vitor@d5w.com.br>
 * @since   Class available since Release: 1.0.0
 */
class ElasticSearch implements IAdaptor
{
    /**
     * @var   array Request date
     * @since Property available since Release: 1.0.0
     */
    private static $req;
    
    /**
     * Method for bootstrap adaptor
     *
     * @param  string $docId Doc ID
     * @return bool Return "true" if success, else "false"
     * @since  Method available since Release: 1.0.0
     */
    public static function bootstrap($docId)
    {
        return true;
    }

    /**
     * Method for save request data
     *
     * @param  string $docId Doc ID
     * @param  array{
     *   start_date:string,
     *   start_time:float,
     *   method:string,
     *   url:array{
     *     scheme:string,
     *     host:string,
     *     uri:string,
     *   },
     *   ip:string,
     *   referer:string|null,
     *   useragent:string|null,
     *   get:array|null,
     *   post:array|null,
     *   rawpost:string|null,
     *   files:array|null,
     *   cookies:array|null,
     *   server:array|null
     * } $data Request data
     * @return bool Return "true" if success, else "false"
     * @since  Method available since Release: 1.0.0
     */
    public static function request($docId, $data)
    {
        $data['time'] = -1;
        return self::exec($docId, self::$req = $data);
    }

    /**
     * Method for save response data
     *
     * @param  string $docId Doc ID
     * @param  array{
     *   end_date:string,
     *   end_time:float,
     *   time:float,
     *   http_code:int,
     *   headers:array|null,
     *   error:array|null,
     *   extra:array|null,
     *   inc_files:array|null
     * } $data Response data
     * @return bool Return "true" if success, else "false"
     * @since  Method available since Release: 1.0.0
     */
    public static function response($docId, $data)
    {
        return self::exec($docId, array_merge(self::$req, $data));
    }

    /**
     * Method for send data to elasticsearch
     *
     * @param  string $docId  Doc id
     * @param  array  $source Source data
     * @return bool
     * @since  Method available since Release: 1.0.0
     */
    private static function exec($docId, $source)
    {
        $headers = [
            'Content-Type: application/json; charset=utf-8'
        ];
        if ($apikey = getenv('VRLOG_ELK_APIKEY')) {
            $headers[] = "Authorization: ApiKey $apikey";
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => getenv('VRLOG_ELK_SERVER') . "/_doc/$docId",
            CURLOPT_CUSTOMREQUEST  => 'PUT',
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => json_encode($source),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => getenv('VRLOG_ELK_TIMEOUT') ?: 5
        ]);
        curl_exec($ch);
        $httpCode = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
        curl_close($ch);

        if ($httpCode !== 200 && $httpCode !== 201) {
            error_log(
                '[' . date('Y-m-d H:i:s') . "] Failed elasticsearch save [$httpCode]" . PHP_EOL,
                3,
                VRLog::getTempDir() . '/error.log'
            );
            return false;
        }
        return true;
    }
}
