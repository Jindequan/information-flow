<?php

namespace App\Services\RSS;

use App\Services\Base;

class XML extends Base {
    private $articles;

    public function __construct(array $articles) {
        $this->articles = $articles;
    }
    public function handle($params) {
        $outputPath = $params['output_path'];
        $xmlTitle = $params['xml_title'] || '';

        $content = "<rss version=\"2.0\" xmlns:content=\"http://purl.org/rss/1.0/modules/content/\" xmlns:media=\"http://search.yahoo.com/mrss/\">\n<channel><title>$xmlTitle</title>\n";
    
        foreach ($this->articles as $article) {
            $content .= "  <item>\n";
            $content .= "    <title>" . htmlspecialchars($article['title']) . "</title>\n";
            $content .= "    <link>" . htmlspecialchars($article['link']) . "</link>\n";
            $content .= "    <pubDate>" . htmlspecialchars($article['pubDate']) . "</pubDate>\n";
            $content .= "    <description>" . htmlspecialchars($article['description']) . "</description>\n";
            if (!empty($article['content'])) {
                $content .= "    <content:encoded>" . htmlspecialchars($article['content']) . "</content:encoded>\n";
            }
            foreach ($article['media'] as $mediaUrl) {
                $content .= "    <media:content url=\"" . htmlspecialchars($mediaUrl) . "\" />\n";
            }
            $content .= "  </item>\n";
        }
    
        $content .= "</channel>\n</rss>";
    
        file_put_contents($outputPath, $content);
    }
}