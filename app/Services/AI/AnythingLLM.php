<?php
namespace App\Services\AI;

use App\Core\Config;
use App\Exceptions\ErrorException;

class AnythingLLM {
    private $config;
    private $role;

    private $slug;

    public function __construct($role){
        $this->role = $role;

        $this->config = Config::config()['services']['anythingllm'];
        $slug = $this->config['slug'] ?? $this->config['slugs'][$this->role];
        if (empty($slug)) {
            throw new \Exception('AnythingLLM slug cannot be empty.');
        } 
        $this->slug = $slug;
    }

    public function handle($params) {
        $url = $this->config["url"] . "/api/v1/workspace/%s/chat";
        $key = $this->config["key"];

        $url = sprintf($url, $this->slug);

        $content = $params['content'];
        $role = $this->role;
        switch ($role) {
            case 'translate':
                $rolePromote = str_replace(
                    ['[source language]', '[target language]'], 
                    [$params['from'], $params['to']],
                    $this->config[$role]
                ); 
                break;
            case 'summerize':
                $rolePromote = $this->config[$role];
                break;
        }

        $rolePromote = str_replace(['[content]'],  [$params['content']], $rolePromote);

        $data = ["mode" => 'chat', "message" => $content];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', "Accept: application/json", "Authorization: Bearer $key"));
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));

       $response = curl_exec($curl);
       
       curl_close($curl);

       $response = json_decode($response, true);
        if (!empty($response['error'])) {
            throw new ErrorException('AnythingLLM Api Error: '. $response['error']);
        }
       return $response["textResponse"];
    }
}