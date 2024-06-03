<?php

namespace App\Scripts;

use App\Core\Config;
use App\Scripts\Base;

use App\Services\RSS\Image;
use App\Services\ServiceCenter;
use App\Services\RSS\Filter;
use App\Services\RSS\Html;
use App\Services\RSS\RSSLoader;
use App\Services\Platform\WXOfficialAPI;

class WechatOfficialSync extends Base {
    private $startDate = null;
    private $endDate = null;
    private $language = null;
    private $saveAsImage = false;
    private $dateDir = null;
    private $styleFile = null;
    private $coverFile = null;
    private $rssLoader = null;
    private $wxofficialApi = null;
    private $imageGenerator = null;

    public function showParams() {
        return [
            'sd(or start_date)' => 'start date (used when filter info list)',
            'ed(or end_date)' => 'end date (used when filter info list)',
            'lang(or language)' => 'translate to language',
            'sai(or save_as_image)' => 'if you want to save html to image, you can set true'
        ];
    }
    public function run($params) {
        // prepare all
        $this->prepare($params);

        // file dir
        $this->dateDir = $this->dirName($this->startDate, $this->endDate);

        // fetch data to simple xml element list
        $xmls = $this->rssLoader->handle([]);

        // filter by params and keep group struct
        $articles = [];
        foreach ($xmls as $groupName => $groupXmls) {
            foreach ($groupXmls as $sourceName => $xml) {
                $filter = new Filter($xml);
                $filteredArticles = $filter->handle(['start_date' => $this->startDate,'end_date'=> $this->endDate]);

                if (empty($filteredArticles)) continue;

                $articles[$groupName][$sourceName] = $filteredArticles;
            }
        }
        // save html
        $htmls = $this->saveArticlesToHtmls($articles);

        // save image
        $this->saveAsImage($htmls);

        // save wxoffcial articles
        $this->saveArticlesToWX($articles, $htmls);
    }

    private function saveArticlesToHtmls($articles) {
        // save to html by group
        $htmlList = [
            'sources' => [],
            'group' => []
        ];
        foreach ($articles as $groupName => $groupArticles) {
            $htmlDir = RSS_STORAGE_PATH .'/'. $groupName .'/' . $this->dateDir;
            if (!file_exists($htmlDir)) {
                mkdir($htmlDir,0, true);
            }
            // save to html by source
            foreach ($groupArticles as $sourceName => $sourceArticles) {
                // generate html file for each rss
                $htmlFile = $htmlDir . "/$sourceName.html";
                $html = new Html($sourceArticles);
                $html->handle([
                    'output_path' => $htmlFile, 
                    'style_file' => $this->styleFile, 
                    'html_title' => $this->dateDir . " $sourceName"
                ]);

                $htmlList['sources'][$sourceName] = $htmlFile;
            }

            // save to html by group
            $allGroupArticles = array_merge(...array_values($groupArticles));

            if (empty($allGroupArticles)) continue;

            // generate html file for each rss
            $htmlFile = $htmlDir . "/$groupName.html";

            $html = new Html($allGroupArticles);
            $html->handle([
                'output_path' => $htmlFile, 
                'style_file' => $this->styleFile, 
                'html_title' => $this->dateDir . " $groupName"
            ]);

            $htmlList['group'][$groupName] = $htmlFile;
        }

        return $htmlList;
    }

    private function saveArticlesToWX($articles, $htmls) {
        // save cover to wx
        $cover = $this->wxofficialApi->addMedia($this->coverFile, 'image');

        foreach ($articles as $groupName => $gArticles) {
            $index = 1;
            $aiNews = [];
            $drafts = [];
            foreach ($gArticles as $sourceName => $sourceArticles) {
                $fromLang = $this->rssLoader->getXmlLanguage($groupName, $sourceName);

                $htmlTitle = $this->dateDir ." $sourceName";
                
                $drafts[] = [
                    "title"=> $htmlTitle,
                    "cover" => $cover,
                    "content" => file_get_contents($htmls['sources'][$sourceName])
                ];

                foreach ($sourceArticles as $sourceArticle) {
                    $title = $fromLang == $this->language ? $sourceArticle['title'] : ServiceCenter::handle('translate', [
                        'content' => $sourceArticle['title'], 
                        'from' => $fromLang, 
                        'to' => $this->language
                    ]);
                    $description = $fromLang == $this->language ? $sourceArticle['description'] : ServiceCenter::handle('translate', [
                        'content' => $sourceArticle['description'], 
                        'from' => $fromLang, 
                        'to' => $this->language
                    ]);
                    $content = ServiceCenter::handle('summerize', [
                        'content' => $sourceArticle['link'],
                        'role' => 'summerize'
                    ]);
                    $content = $fromLang == $this->language ? $content : ServiceCenter::handle('translate', [
                        'content' => $content, 
                        'from' => $fromLang, 
                        'to' => $this->language
                    ]);
                    $aiNews[] = [
                        'title' => "$index. $title",
                        'link' => $sourceArticle['link'],
                        'pubDate' => $sourceArticle['pubDate'],
                        'description' => $description,
                        'content' => $content
                    ];
                    $index ++;
                }                 
            }

            $htmlDir = RSS_STORAGE_PATH .'/'. $groupName .'/' . $this->dateDir;
            if (!file_exists($htmlDir)) {
                mkdir($htmlDir,0, true);
            }

            $aiArticleFilePath = $htmlDir . '/ai.html';
            $aiArticleHtmlTitle = $this->dateDir . ' AI Infomation Summary.';

            $html = new Html($aiNews);
            $html->handle([
                'style_file' => $this->styleFile,
                'output_path' => $aiArticleFilePath,
                'html_title' => $aiArticleHtmlTitle
            ]);

            // create draft by source
            $aiDraft = [
                'title'=> $aiArticleHtmlTitle,
                'cover' => $cover,
                'content' => file_get_contents($aiArticleFilePath)
            ];

            $articleId = $this->wxofficialApi->addDrafts(array_merge([$aiDraft], $drafts));

            $this->wxofficialApi->publish($articleId);
        }
    }

    private function saveAsImage($htmls) {
        if (!$this->saveAsImage) {
            return;
        }

        $htmlFiles = [];
    
        array_walk_recursive($htmls, function($value) use (&$result) {
            $result[] = $value;
        });

        foreach ($htmlFiles as $file) {
            $imageName = str_replace('.html', '.png', $file);
            $this->imageGenerator->handle([
                'html' => $file,
                'image' => $imageName
            ]);
        }
    }

    private function prepare($params) {
        $startDate = $this->digValue($params, ["sd", "start_date"]);
        $endDate = $this->digValue($params, ["ed", "end_date"]);
        $language = $this->digValue($params, ["lang", "language"]);
        $saveAsImage = $this->digValue($params, ["sai", "save_as_image"]);

        if (empty($startDate)) {
            $startDate = date('Y-m-d');
        }

        if (empty($endDate)) {
            $endDate = $startDate;
        }

        if (empty($language)) {
            $language = 'zh';
        }

        // params
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->language = $language;
        $this->saveAsImage = $saveAsImage;
        // static file
        $this->styleFile = CONFIG_PATH .'/style.json';
        $this->coverFile = RESOURCE_PATH . '/' . Config::config()['wxofficial']['cover_img'];
        // init services
        $this->rssLoader = new RSSLoader();
        $this->imageGenerator = new Image();
        $this->wxofficialApi = new WXOfficialAPI();
        ServiceCenter::new('summerize');
        ServiceCenter::new('translate');
    }

    private function dirName($startDate, $endDate) {
        if ($startDate == $endDate) {
            return $startDate;
        } else {
            return implode("_", [$startDate, $endDate]);
        }
    }

    private function digValue($params, $keys) {
        if (empty($keys)) return null;

        $key = array_shift($keys);
        if (isset($params[$key])) {
            return $params[$key];
        }
        return $this->digValue($params, $keys);
    }
}