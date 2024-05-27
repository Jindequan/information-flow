<?php

namespace App\Services\RSS;

use App\Services\Base;
use SimpleXMLElement;

class Filter extends Base {
    private $rssXml;

    public function __construct(SimpleXMLElement $rssXml) {
        $this->rssXml = $rssXml;
    }
    public function handle($params) {
        $sD = strtotime($params['start_date'] . ' 00:00:00');
        $eD = strtotime($params['end_date'] . ' 23:59:59');

        $filteredArticles = [];

        foreach ($this->rssXml->channel->item as $item) {
            $pubDate = strtotime($item->pubDate);
            if ($pubDate >= $sD && $pubDate <= $eD) {
                $filteredArticles[] = [
                    'title' => (string) $item->title,
                    'link' => (string) $item->link,
                    'pubDate' => date('Y-m-d', strtotime((string) $item->pubDate)),
                    'description' => (string) $item->description,
                    'content' => (string) $item->children('content', true)->encoded,
                    'media' => (string) $item->children('media', true)->content->attributes()->url,
                ];
            }
        }

        return $filteredArticles;
    }
}