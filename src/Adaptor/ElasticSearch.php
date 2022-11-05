<?php
/**
 * This file is part of VRLog - PHP Full AccessLog
 *
 *  @author    Vitor Reis <vitor@d5w.com.br>
 *  @copyright 2022 D5W Group. All rights reserved.
 */

namespace VRLog\Adaptor;

use Exception;
use VRLog\Utils\DotEnv;
use VRLog\Utils\IAdaptor;
use VRLog\VRLog;

/**
 * Class ElasticSearch
 *
 * @package VRLog\Adaptor
 * @author  Vitor Reis <vitor@d5w.com.br>
 */
class ElasticSearch implements IAdaptor
{
    /**
     * @var array Request date
     */
    private static $req;

    /**
     * Method for bootstrap adaptor
     *
     * @param  string $docId Doc ID
     * @return bool Return "true" if success, else "false"
     * @throws Exception
     */
    public static function bootstrap($docId)
    {
        if (!DotEnv::get('VRLOG_ELK_SERVER')) {
            VRLog::ex('Require .env[VRLOG_ELK_SERVER] for file adaptor');
            return false;
        }

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
     * } $data  Request data
     * @return bool Return "true" if success, else "false"
     * @throws Exception
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
     * }               $data  Response data
     * @return bool Return "true" if success, else "false"
     * @throws Exception
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
     * @throws Exception
     */
    private static function exec($docId, $source)
    {
        $headers = [
            'Content-Type: application/json; charset=utf-8'
        ];
        if ($apikey = DotEnv::get('VRLOG_ELK_APIKEY')) {
            $headers[] = "Authorization: ApiKey $apikey";
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => DotEnv::get('VRLOG_ELK_SERVER') . "/_doc/$docId",
            CURLOPT_CUSTOMREQUEST  => 'PUT',
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => json_encode($source),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => DotEnv::get('VRLOG_ELK_TIMEOUT') ?: 5
        ]);
        curl_exec($ch);
        $httpCode = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
        curl_close($ch);

        if ($httpCode !== 200 && $httpCode !== 201) {
            VRLog::ex("Failed elasticsearch save [$httpCode]");
            return false;
        }
        return true;
    }
}
