<?php
/**
 * ONLY SINGLE FUNCTIONAL SERVICE CAN EXTEND THIS ABSTRACT CLASS.
 */
namespace App\Services;
abstract class Base {
    private static $instances = [];
    public static function instance() {
        $call = get_called_class();
        if (empty(self::$instances[$call])) {
            self::$instances[$call] = new $call();
        }

        return self::$instances[$call];
    }
    abstract public function handle(array $params);
}