<?php
/**
 * This file is part of VRLog - PHP Full AccessLog
 *
 *  @author    Vitor Reis <vitor@d5w.com.br>
 *  @copyright 2022 D5W Group. All rights reserved.
 */

namespace VRLog\Utils;

/**
 * Interface IAdaptor
 *
 * @package VRLog\Utils
 * @author  Vitor Reis <vitor@d5w.com.br>
 */
interface IAdaptor
{
    /**
     * Method for bootstrap adaptor
     *
     * @param  string $docId Doc ID
     * @return bool Return "true" if success, else "false"
     */
    public static function bootstrap($docId);

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
     */
    public static function request($docId, $data);

    /**
     * Method for save response data
     *
     * @param  string $docId Doc ID
     * @param  array{
     *   end_date:string,
     *   end_time:float,
     *   time:float,
     *   http_code:int,
     *   length:int,
     *   headers:array|null,
     *   error:array|null,
     *   extra:array|null,
     *   inc_files:array|null
     * } $data Response data
     * @return bool Return "true" if success, else "false"
     */
    public static function response($docId, $data);
}
