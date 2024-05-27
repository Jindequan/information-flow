<?php

namespace App\Core;

class Config {

    private static $config = null;
    private static $rss = null;

    public static function config() {
        if (is_null(self::$config)) {
            self::$config = json_decode(file_get_contents(CONFIG_PATH . '/config.json'), true);
        }

        return self::$config;
    }

    public static function rss() {
        if (is_null(self::$rss)) {
            self::$rss = json_decode(file_get_contents(CONFIG_PATH . '/rss.json'), true);
        }

        return self::$rss;
    }
}