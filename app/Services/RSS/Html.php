<?php

namespace App\Services\RSS;

use App\Services\Base;

class Html extends Base {
    private $articles;

    private $styles = [];

    public function __construct(array $articles) {
        $this->articles = $articles;
    }
    public function handle($params) {
        $styleFile = $params['style_file'];
        $outputPath = $params['output_path'];
        $htmlTitle = $params['html_title'] ?? '';

        $this->parseStyle($styleFile);

        $needReplaceClass = [];
        $replaceStyle = [];
        foreach ($this->styles as $key => $value) {
            $needReplaceClass[] = "class='$key'";
            $replaceStyle[] = "style='$value'";
        }

        $content = "<!DOCTYPE html>\n<html>\n<head><meta charset='UTF-8'>\n<title>{$htmlTitle}</title>\n";
        // $content .= "<style>$style</style>\n";
        $content .= "</head>\n<body>\n";
        $content .= "<div class='container'>\n";
    
        foreach ($this->articles as $article) {
            $content .= "<div class='article'>\n";
            $content .= "<div class='title'>" . $article['title'] . "</div>\n";
            // $content .= "<div class ='origin-link'><a href='" . $article['link'] . "'>" . $article['link'] . "</a></div>\n";
            $content .= "<div class='pub-date'>" . $article['pubDate'] . "</div>\n";
            $content .= "<div class='description'>" . $article['description'] . "</div>\n";
            if (!empty($article['media'])) {
                $content .= "<div class='img-box'><img src='" . $article['media'] . "' alt='Media content'></div>\n";
            }
            if (!empty($article['content'])) {
                $content .= "<div class='content'>" . $article['content'] . "</div>\n";
            }
            $content .= "</div>\n<hr>\n";
        }

    
        $content .= "</div>\n";
        $content .= "</body>\n</html>";

        $content = str_replace($needReplaceClass, $replaceStyle, $content);
    
        file_put_contents($outputPath, $content);
    }

    private function parseStyle($file) {
        $style = file_get_contents($file);
        
        $struct = json_decode($style, true);

        $this->styles = $this->parse($struct);
    }

    private function parse($struct) {
        $struct = $this->flatten($struct);

        $styles = [];
        foreach ($struct as $key => $value) {
            $tmp = '';
            foreach ($value as $k => $v) {
                $tmp .= "$k: $v;";
            }

            $styles[$key] = $tmp;
        }


        return $styles;
    }

    private function flatten($array, $currentKey = null, $result = []) {
        [$hasArray, $noArray ] = $this->splitArray($array);

        foreach ($array as $key => $value) {
            if (array_key_exists($key, $hasArray)) {
                $result = $this->flatten($hasArray[$key], $key, $result);
            } else {
                $result[$currentKey][$key] = $value;
            }
        }

        return $result;
    }

    private function splitArray($array) {
        $hasArray = [];
        $noArray = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $hasArray[$key] = $value;
            } else {
                $noArray[$key] = $value;
            }
        }

        return [$hasArray, $noArray];
    }
}