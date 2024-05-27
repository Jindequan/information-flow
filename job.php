<?php

require 'vendor/autoload.php';

set_exception_handler(['App\Core\ErrorHandler', 'handleException']);

use App\Exceptions\ConsoleException;

define('ROOT_PATH', __DIR__);
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/configs');
define('RESOURCE_PATH', ROOT_PATH . '/resources');
define('RSS_STORAGE_PATH', RESOURCE_PATH . '/rss');


$scripts = [
    'wxofficial_sync' => App\Scripts\WechatOfficialSync::class
];

array_shift($argv);

$scriptAlias = array_shift($argv);

if (empty($scriptAlias) || !isset($scripts[$scriptAlias])) {
    throw new ConsoleException('No script specified!');
}

$params = [];
foreach (array_chunk($argv, 2) as $input) {
    if (count($input) !== 2) {
        $allowedParams = (new $scripts[$scriptAlias])->showParams();

        $paramTips = '';
        foreach ($allowedParams as $key => $description) {
            $paramTips .= "    $key: $description\n";
        }
        throw new ConsoleException("Params error, name and value must appear in pairs, eg: -name1 value1 -name2 value2\n" . $paramTips);
    }
    $key = ltrim($input[0], '-');
    $params[$key] = $input[1];
}

try {
    (new $scripts[$scriptAlias])->run($params);
} catch (\Exception $e) {
    throw new ConsoleException("", -1, $e);
}
