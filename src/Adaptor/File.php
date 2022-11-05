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
 * Class File
 *
 * @package VRLog\Adaptor
 * @author  Vitor Reis <vitor@d5w.com.br>
 */
class File implements IAdaptor
{
    /**
     * @var string Data directory
     */
    private static $dir;

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
            VRLog::ex('Require .env[VRLOG_FILE_DIR] for file adaptor');
            return false;
        }

        # SET DATA DIRECTORY
        self::$dir = DotEnv::get('VRLOG_FILE_DIR') . "/$docId/";

        # CREATE DIRECTORY IF NOT EXISTS
        return is_dir(self::$dir) || mkdir(self::$dir, 0755, true);
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
     */
    public static function request($docId, $data)
    {
        return error_log(json_encode($data), 3, self::$dir . 'req.log');
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
     */
    public static function response($docId, $data)
    {
        return error_log(json_encode($data), 3, self::$dir . 'res.log');
    }
}
