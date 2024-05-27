<?php

namespace App\Services;
use App\Core\Config;
use App\Exceptions\ErrorException;
use App\Services\AI\Kimi;
use App\Services\AI\AnythingLLM;
use App\Services\Translator\Tencent;

class ServiceCenter {
    private static $services = [];
    private static $instances = [];

    public static function new($act) {
        $service = "";
        switch ($act) {
            case "summerize":
                $service = Config::config()['ai_service'];
                break;
            case "translate":
                $service = Config::config()["translate_service"];
                break;
            default:
                throw new ErrorException("Act not allowed.");
        }

        self::$services[$act] = $service;
        
        switch ($service) {
            case "kimi":
                self::$instances[$service] = new Kimi($act);
                break;
            case "anythingllm";
                self::$instances[$service] = new AnythingLLM($act);
                break;
            case "tencent_translate":
                self::$instances[$service] = new Tencent();
                break;
            default:
                throw new ErrorException("No service implemented for: $act");
        }

        return new self();
    }

    public static function handle($act, $params) {
        $service = self::$services[$act];
        $instance = self::$instances[$service];

        return $instance->handle($params);
    }
}