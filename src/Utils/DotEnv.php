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
 * @since   Class available since Release: 1.0.0
 */

class DotEnv
{
    /**
     * @var   string[] .env values
     * @since Property available since Release: 1.0.0
     */
    private $list = [];

    /**
     * @var   bool Status adaptor for put in $_SERVER
     * @since Property available since Release: 1.0.0
     */
    public static $adaptorSuperGlobalServer = true;

    /**
     * @var   bool Status adaptor for put in $_ENV
     * @since Property available since Release: 1.0.0
     */
    public static $adaptorSuperGlobalEnv = true;

    /**
     * @var   bool Status adaptor for put with function "apache_setenv"
     * @since Property available since Release: 1.0.0
     */
    public static $adaptorApache = true;

    /**
     * @var   bool Status adaptor for put with function "putenv"
     * @since Property available since Release: 1.0.0
     */
    public static $adaptorPutenv = true;

    /**
     * @var   bool Status adaptor for create constant with name if not exists
     * @since Property available since Release: 1.0.0
     */
    public static $adaptorConstant = false;

    /**
     * @param string|string[] $filename       File or files for load
     * @param bool            $readFromMemory Read super global $_ENV
     */
    public function __construct($filename, $readFromMemory = true)
    {
        if ($readFromMemory) {
            foreach ($_ENV as $name => $value) {
                $this->put($name, $value);
            }
        }

        if (is_string($filename)) {
            $filename = [ $filename ];
        }

        foreach ($filename as $i) {
            $this->load($i);
        }
    }

    /**
     * Method for get instance putted value
     *
     * @param  string $name .env name
     * @return string|null
     * @since  Method available since Release: 1.0.0
     */
    public function get($name)
    {
        return isset($this->list[$name]) ? $this->list[$name] : null;
    }

    /**
     * Method for put name and value in .env memory
     *
     * @param  string $name
     * @param  string $value
     * @return void
     * @since  Method available since Release: 1.0.0
     */
    public function put($name, $value)
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
            # WRITE APACHE
            putenv("$name=$value");
        }

        # WRITE CURRENT INSTANCE
        $this->list[$name] = $value;
    }

    /**
     * Method for load .env file
     *
     * @param  string $filename DotEnv Filename
     * @return bool
     * @since  Method available since Release: 1.0.0
     */
    public function load($filename)
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

            $this->put(trim($split[0]), trim($split[1]));
        }
        return true;
    }
}
