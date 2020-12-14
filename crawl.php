<?php

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\DomCrawler\Crawler;
include_once 'lib.php';

$db = new SQLite3('rating.db');

$url = 'http://www.orientpskov.ru/news';
$crawler = new Crawler(file_get_contents($url));

$links = array_filter(
    $crawler->filter('a')->each(
        function (Crawler $node, $i) {
            return $node->attr('href');
        }
    ),
    function ($v) {
        return strpos($v, 'orientpskov.ru') !== false;
    }
);

foreach ($links as $link) {
    $content = file_get_contents($link);
    if (strpos($content, 'SFR event centre') !== false) {
        echo "{$link}\n";
        $event = lib\query($db, "SELECT * FROM events WHERE url=:url", ['url' => $link])->fetchArray(SQLITE3_ASSOC);
        if (empty($event)) {
            $crawler = new Crawler($content);
            $title = $crawler->filter('h1')->text();
            lib\query(
                $db,
                "INSERT INTO events (title, url , processed) VALUES (:title,:url,0)",
                ['title' => $title,'url' => $link]
            );
            echo "Event created\n";
        }

    }
}
