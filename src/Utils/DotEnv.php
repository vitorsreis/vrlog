<?php
/**
 * This file is part of VRLog - PHP Full AccessLog
 *
 *  @author    Vitor Reis <vitor@d5w.com.br>
 *  @copyright 2022 D5W Group. All rights reserved.
 */

namespace VRLog\Utils;

/**
 * Class DotEnv
 *
 * @package VRLog\Utils
 * @author  Vitor Reis <vitor@d5w.com.br>
 */

class DotEnv
{
    /**
     * @var string[] Env values
     */
    private static $memory = [];

    /**
     * @var bool Status adaptor for put in $_SERVER
     */
    public static $adaptorSuperGlobalServer = false;

    /**
     * @var bool Status adaptor for put in $_ENV
     */
    public static $adaptorSuperGlobalEnv = false;

    /**
     * @var bool Status adaptor for put with function "apache_setenv"
     */
    public static $adaptorApache = false;

    /**
     * @var bool Status adaptor for put with function "putenv"
     */
    public static $adaptorPutenv = false;

    /**
     * @var bool Status adaptor for create constant with name if not exists
     */
    public static $adaptorConstant = false;

    /**
     * Method for bootstrap .env
     *
     * @param  string|string[] $filename       File or files for load
     * @param  bool            $readFromMemory Read super global $_ENV
     * @return void
     */
    public static function bootstrap($filename, $readFromMemory = true)
    {
        if ($readFromMemory) {
            foreach ($_ENV as $name => $value) {
                self::put($name, $value);
            }
        }

        if (is_string($filename)) {
            $filename = [ $filename ];
        }

        foreach ($filename as $i) {
            self::load($i);
        }
    }

    /**
     * Method for get instance putted value
     *
     * @param  string $name .env name
     * @return string|null
     */
    public static function get($name)
    {
        return isset(self::$memory[$name]) ? self::$memory[$name] : null;
    }

    /**
     * Method for put name and value in .env memory
     *
     * @param  string $name
     * @param  string $value
     * @return void
     */
    public static function put($name, $value)
    {
        if (self::$adaptorSuperGlobalServer && isset($_SERVER)) {
            # WRITE SUPER GLOBAL $_SERVER
            $_SERVER[$name] = $value;
        }

        if (self::$adaptorSuperGlobalEnv && isset($_ENV)) {
            # WRITE SUPER GLOBAL $_ENV
            $_ENV[$name] = $value;
        }

        if (self::$adaptorApache && function_exists('apache_setenv')) {
            # WRITE APACHE
            apache_setenv($name, $value);
        }

        if (self::$adaptorPutenv && function_exists('putenv')) {
            # WRITE ENV
            putenv("$name=$value");
        }

        if (self::$adaptorConstant && function_exists('putenv')) {
            # WRITE CONSTANT
            define($name, $value);
        }

        # WRITE CURRENT INSTANCE
        self::$memory[$name] = $value;
    }

    /**
     * Method for load .env file
     *
     * @param  string $filename DotEnv Filename
     * @return bool
     */
    public static function load($filename)
    {
        if (!is_file($filename)) {
            return false;
        }

        # READ .ENV FILE
        foreach (file($filename) as $line) {
            $line = trim($line);

            if (empty($line) || $line[0] === '#') {
                # IGNORE EMPTY LINES OR COMMENTARIES
                continue;
            }

            $split = explode('=', $line, 2);

            if (count($split) < 2) {
                # IGNORE INCORRECT LINES
                continue;
            }

            self::put(trim($split[0]), trim($split[1]));
        }
        return true;
    }
}
