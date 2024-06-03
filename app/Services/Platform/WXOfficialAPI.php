<?php
namespace App\Services\Platform;
use App\Core\Config;
class WXOfficialAPI {
    private $config;
    private $cacheFile = RESOURCE_PATH . "/wxofficial/cache.json";
    private $mediaFile = RESOURCE_PATH . "/wxofficial/media.csv";
    private $articleFile = RESOURCE_PATH . "/wxofficial/article.csv";
    private $cache = null;
    private $media = [];
    private $articles = [];

    public function __construct() {
        $this->config = Config::config()['wxofficial'];

        $cache = file_get_contents($this->cacheFile);
        if (!empty($cache)) {
            $cache = json_decode($cache, true);
            $this->cache = $cache;
        }
        $this->token();

        $media = file_get_contents($this->mediaFile);
        $list = explode("\n", $media);
        for ($i = 1; $i < count($list); $i++) {
            $item = explode(",", $list[$i]);
            $this->media[$item[0]] = ["media_id" => $item[1], "url" => $item[2]];
        }

        $articles = file_get_contents($this->articleFile);
        $list = explode("\n", $articles);
        for ($i = 1; $i < count($list); $i++) {
            $item = explode(",", $list[$i]);
            $this->articles[$item[0] . '_' . $item[2]] = $item[1];
        }
    }

    public function addMedia($file, $type) {
        if (array_key_exists($file, $this->media)) {
            return $this->media[$file]['media_id'];
        }
        if (!in_array($type, ["image", "voice", "video"])) {
            die("media type error");
        }
        $path = explode("/", $file);
        $name = end($path);

        $url = "https://api.weixin.qq.com/cgi-bin/material/add_material?access_token=%s&type=%s";
        $url = sprintf($url, $this->token(), $type);

        $resp = $this->callAPI("POST", $url, ["media" => new \CURLFile($file), "filename" => $name], ['Content-Type: multipart/form-data; charset=utf-8'])["response"];
        $mediaId = $resp["media_id"];
        $mediaUrl = $resp["url"];
        if (!empty($mediaId)) {
            $this->media[$file] =  ["media_id" => $mediaId, "url" => $mediaUrl];
            file_put_contents($this->mediaFile, "\n$file,$mediaId,$mediaUrl", FILE_APPEND);
        }
        return $resp;
    }

    public function addDrafts($params) {
        $articles = [];
        foreach ($params as $param) {
            $articles[] = [
                "title" => $param['title'],
                "author" => $this->config['author'],
                "digest" => "",
                "content" => $param['content'],
                "content_source_url" => $param['content_source_url'] ?? '',
                "thumb_media_id" => $param['cover'],
                "need_open_comment" => 0,
                "only_fans_can_comment" =>0
            ];
        }

        $url = "https://api.weixin.qq.com/cgi-bin/draft/add?access_token=%s";
        $url = sprintf($url, $this->token());

        $data = ["articles"=> $articles];

        $resp = $this->callAPI("POST", $url, json_encode($data, JSON_UNESCAPED_UNICODE), ['Content-Type: application/json; charset=utf-8']);

        if (!empty($resp["response"]["media_id"])) {
            $mediaId = $resp["response"]["media_id"];
            $today=date('Y-m-d');
            file_put_contents($this->articleFile, "\n,$mediaId,$today", FILE_APPEND);
            return $mediaId;
        }
    }

    public function addDraft($title, $content, $thumbMediaId, $sourceUrl = "") {
        $today = date('Y-m-d');
        $key = implode('_', [$title, $today]);

        if (isset($this->articles[$key])) {
            return $this->articles[$key];
        }
        $cover = $this->media["cover"];
        $url = "https://api.weixin.qq.com/cgi-bin/draft/add?access_token=%s";
        $url = sprintf($url, $this->cache["token"]);
        $data = [
            "articles"=> [
                [
                    "title" => $title,
                    "author" => "Devin",
                    "digest" => "",
                    "content" => $content,
                    "content_source_url" => $sourceUrl,
                    "thumb_media_id" => $thumbMediaId,
                    "need_open_comment" => 0,
                    "only_fans_can_comment" =>0
                ]
            ]
        ];

        $resp = $this->callAPI("POST", $url, json_encode($data, JSON_UNESCAPED_UNICODE), ['Content-Type: application/json; charset=utf-8']);

        if (!empty($resp["response"]["media_id"])) {
            $mediaId = $resp["response"]["media_id"];
            file_put_contents($this->articleFile, "\n$title,$mediaId,$today", FILE_APPEND);
            return $mediaId;
        }
    }

    public function publish($mediaId) {
        $url = "https://api.weixin.qq.com/cgi-bin/freepublish/submit?access_token=%s";
        $url = sprintf($url, $this->cache["token"]);
        $data = ["media_id" => $mediaId];

        $this->callAPI("POST", $url, json_encode($data, JSON_UNESCAPED_UNICODE), ['Content-Type: application/json; charset=utf-8']);
    }

    private function token() {
        if (!empty($this->cache) && !empty($this->cache["token"]) && $this->cache["token_time"] + 3600 > time()) {
            return $this->cache["token"];
        }

        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s";
        $url = sprintf($url, $this->config['id'], $this->config['secret']);

        $token = $this->callAPI("GET", $url)["response"]["access_token"];
        $this->cache["token"] = $token;
        $this->cache["token_time"] = time();
        file_put_contents($this->cacheFile, json_encode($this->cache, JSON_UNESCAPED_UNICODE));

        return $token;
    }

    private function callAPI($method, $url, $data = false, $headers = []) {
        $curl = curl_init();
    
        switch (strtoupper($method)) {
            case "POST":
                curl_setopt($curl, CURLOPT_POST, 1);
                if ($data) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                }
                break;
            case "PUT":
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                if ($data) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                }
                break;
            case "DELETE":
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
                if ($data) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                }
                break;
            default: // GET
                if ($data) {
                    $url = sprintf("%s?%s", $url, http_build_query($data));
                }
                break;
        }
    
        // OPTIONS:
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  
        $result = curl_exec($curl);echo $result;

        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
            curl_close($curl);
            return ["error" => true, "message" => $error_msg];
        }
    
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
    
        return ["error" => false, "http_code" => $http_code, "response" => json_decode($result, true)];
    }
    
}