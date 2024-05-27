<?php

namespace App\Services\RSS;

use App\Services\Base;

class Markdown extends Base {
    private $articles;

    public function __construct(array $articles) {
        $this->articles = $articles;
    }
    public function handle($params) {
        $outputPath = $params['output_path'];
        $mdTitle = $params['md_title'] || '';

        $content = "# $mdTitle\n\n";
    
        foreach ($this->articles as $article) {
            $content .= "## " . htmlspecialchars($article['title']) . "\n\n";
            $content .= "**Published on:** " . htmlspecialchars($article['pubDate']) . "\n\n";
            $content .= "[Read more](" . htmlspecialchars($article['link']) . ")\n\n";
            $content .= htmlspecialchars($article['description']) . "\n\n";
            if (!empty($article['media'])) {
                $content .= "![Media](" . htmlspecialchars($article['media']) . ")\n\n";
            }
            if (!empty($article['content'])) {
                $content .= $article['content'] . "\n\n";
            }
            $content .= "---\n\n";
        }
    
        file_put_contents($outputPath, $content);
    }
}