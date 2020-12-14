<?php

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\DomCrawler\Crawler;
include_once 'lib.php';

$db = new SQLite3('rating.db');

$events = lib\query($db, "SELECT * FROM events WHERE processed=0");

while ($event = $events->fetchArray(SQLITE3_ASSOC)) {

    echo "Processing {$event['title']}\n";

    $url = $event['url'];
    $crawler = new Crawler(file_get_contents($url));

    $classes = lib\extractValues($crawler, 'h2');

    $athletes = $crawler->filter('table.rezult')->each(
        function (Crawler $node, $i) {
            $list = new Crawler($node->html());
            $rows = $list->filter('tr')->each(
                function (Crawler $node, $i) {
                    return $node->filter('td')->count() === 0
                        ? []
                        : lib\extractValues($node, 'td');
                }
            );
            return array_filter($rows);
        }
    );

    foreach ($classes as $key => $class) {
        $classBest = strtotime($athletes[$key][1][5]) - strtotime('TODAY');
        foreach ($athletes[$key] as $athlete) {
            $rawTimeArray = explode(':', $athlete[5]);
            if (count($rawTimeArray) !== 3) {
                continue;
            }
            $rawTime = strtotime($athlete[5]) - strtotime('TODAY');
            $score = 100 * $classBest / $rawTime;
            //echo "{$class} | {$athlete[2]} | {$athlete[3]} | {$athlete[4]} | {$athlete[5]} | {$rawTime} | {$athlete[6]} | {$score} \n";
            lib\query(
                $db,
                "INSERT INTO athletes (eventID, class, surname, name, team, `time`, score) VALUES (:eventID,:class,:surname,:name,:team,:time,:score)",
                [
                    'eventID' => $event['id'],
                    'class' => $class,
                    'surname' => $athlete[2],
                    'name' => $athlete[3],
                    'team' => $athlete[4],
                    'time' => $athlete[5],
                    'score' => $score,
                ]
            );
        }
    }

    lib\query($db, "UPDATE events SET processed=1 WHERE id=:id", ['id' => $event['id']]);
    echo "Completed\n";

}

echo "All events processed\n";
