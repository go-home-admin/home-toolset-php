<?php


namespace App;


class Go
{
    private static $module;

    public static function getModule()
    {
        if (!self::$module){
            $modFile = HOME_PATH."/go.mod";
            $module  = "";
            $lines   = file($modFile);
            foreach ($lines as $line) {
                $line = rtrim($line);
                if ($line && strpos($line, 'module ') === 0) {
                    $arr    = explode(" ", $line);
                    $module = end($arr);
                }
            }
            self::$module = $module;
        }

        return self::$module;
    }
}