<?php
namespace App\Services\AI;

use App\Core\Config;
use App\Exceptions\ErrorException;

class Kimi {
    private $config;

    private $role;

    public function __construct($role){
        $this->role = $role;
    }

    public function handle($params) {
        $this->config = Config::config()['services']['kimi'];

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
        

        return $this->do($content, $rolePromote);
    }

    private function do($content, $role, $tryTimes = 0) {
        $url = $this->config["url"];
        $key = $this->config["key"];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', "Authorization: Bearer $key"));
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode([
            "model"=> "moonshot-v1-8k",
            "messages"=> [
                ["role"=>"system", "content"=> $role],
                ["role"=> "user", "content"=> $content],
            ],
            "temperature"=> 0.3,
            "use_search" => false
       ]));
    
        // Execute the cURL request and get the response
        $response = curl_exec($curl);
       
        // Check if the cURL request was successful
        if ($response === false) {
            echo "cURL Error: " . curl_error($curl);
            curl_close($curl);
            die;
        }
        curl_close($curl);
        
        $response = json_decode($response, true);
        if (!empty($response['error'])) {
            if ($tryTimes < 3) {
                sleep(3);
                return $this->do($content, $role, $tryTimes + 1);
            }

            throw new ErrorException('Kimi Api Error: '. $response['error']['type'] . "\n message: ". $response['error']['message']);
        }

        // Close the cURL session
        return $response["choices"][0]["message"]["content"];
    }
}