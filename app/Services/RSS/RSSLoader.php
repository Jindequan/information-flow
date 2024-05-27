<?php
namespace App\Services\RSS;

use App\Core\Config;

use App\Exceptions\RSSException;
use App\Services\Base;

class RSSLoader extends Base {
    private $groups = null;
    private static $xmls = [];

    public function __construct() {
        $groups = Config::rss()['groups'];

        foreach ($groups as $group) {
            $groupName = $group['name'];
            $collection = $group['collection'];
            foreach ($collection as $source) {
                $this->groups[$groupName][$source['name']] = $source;
            }
        }
    }

    public function getXmlLanguage($groupName, $sourceName) { 
        return $this->groups[$groupName][$sourceName]['language'];
    }

    public function handle($params) {
        if (!empty(self::$xmls)) {
            return self::$xmls;
        }

        foreach ($this->groups as $groupName => $collection) {
            $dir = implode('/', [RSS_STORAGE_PATH, $groupName]);
            if (!file_exists($dir)) {
                mkdir($dir);
            }
            foreach ($collection as $sourceName => $source) {
                $filePath = implode('/', [$dir, $sourceName . '.xml']);
                
                $xml = $this->loadRSS($source['url'], $filePath);

                self::$xmls[$groupName][$sourceName] = $xml;
            }
        }
        return self::$xmls;
    }

    private function loadRSS($url, $filePath) {
        if (file_exists($filePath)) {
            return simplexml_load_file($filePath);
        }

        $data = file_get_contents($url);

        if (empty($data)) {
            throw new RSSException('Cannot access rss source, please try again or check your url: ' . $url);
        }

        file_put_contents($filePath, $data);

        return simplexml_load_string($data);
    }
}