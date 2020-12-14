<?php

namespace orating;

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\DomCrawler\Crawler;

class Scanner
{

    private const DB_NAME = 'rating.db';
    public const SOURCE_URL = 'http://www.orientpskov.ru/news';

    /** @var SQLite3  */
    private static $db;

    /** @var DBEngine  */
    private static $dbEngine;

    public function __construct()
    {
        self::$db = new \SQLite3(self::DB_NAME);
    }

    public function init(): void
    {

        self::query("CREATE TABLE IF NOT EXISTS events (
            id INTEGER PRIMARY KEY AUTOINCREMENT, 
            title VARCHAR(50), 
            url VARCHAR(200),
            processed INTEGER,
            hash VARCHAR(50))");

        self::query("CREATE TABLE IF NOT EXISTS athletes 
            (eventID INTEGER, 
            class VARCHAR(20), 
            surname VARCHAR(50), 
            name VARCHAR(50), 
            team VARCHAR(30), 
            `time` VARCHAR(8), 
            score REAL, 
            PRIMARY KEY (eventID,class,surname,name))");

    }

    public function process()
    {
        $this->init();
        $this->crawl(self::SOURCE_URL);
        $this->scan();
    }

    public function crawl(string $sourceUrl)
    {
        $crawler = new Crawler(file_get_contents($sourceUrl));

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

            try {
                $content = file_get_contents($link);
            } catch (\Throwable $e) {
                $content = null;
                self::log("Request error: {$e->getMessage()}");
                continue;
            }

            if (!empty($content) && strpos($content, 'SFR event centre') !== false) {

                self::log("{$link}");
                $hash = md5($content);
                $event = self::query(
                    "SELECT * FROM events WHERE url=:url AND hash=:hash",
                    ['url' => $link, 'hash' => $hash]
                )->fetchArray(SQLITE3_ASSOC);

                if (empty($event)) {
                    $crawler = new Crawler($content);
                    $title = $crawler->filter('h1')->text();
                    self::query(
                        "INSERT INTO events (title, url , processed, hash) VALUES (:title,:url,0,:hash)",
                        ['title' => $title,'url' => $link, 'hash' => $hash]
                    );
                    self::log("Event created");
                }

            }

        }

        self::log("Crawl finished");

    }

    public function scan()
    {

        $events = self::query("SELECT * FROM events WHERE processed=0");

        while ($event = $events->fetchArray(SQLITE3_ASSOC)) {

            self::log("Processing {$event['title']}");

            $url = $event['url'];
            $crawler = new Crawler(file_get_contents($url));

            $classes = self::extractValues($crawler, 'h2');

            $athletes = $crawler->filter('table.rezult')->each(
                function (Crawler $node, $i) {
                    $list = new Crawler($node->html());
                    $rows = $list->filter('tr')->each(
                        function (Crawler $node, $i) {
                            return $node->filter('td')->count() === 0
                                ? []
                                : self::extractValues($node, 'td');
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
                    self::query(
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

            self::query("UPDATE events SET processed=1 WHERE id=:id", ['id' => $event['id']]);
            self::log("Completed");

        }

        self::log("All events processed");

    }

    public function calc()
    {

    }

    private static function log(string $message): void
    {
        echo "{$message}\n";
    }

    private static function query(string $query, array $params = []) {

        $statement = self::$db->prepare($query);

        if ($statement === false) {
            throw new \Exception("Failed to prepare query: {$query}");
        }

        foreach ($params as $name => $value) {
            $bindResult = $statement->bindValue($name, $value);
            if ($bindResult === false) {
                throw new \Exception("Failed to bind {$name}");
            }
        }

        $result = $statement->execute();

        if ($result === false) {
            throw new \Exception("Failed to execute query: {$statement->getSQL()}");
        }

        return $result;

    }

    private static function extractValues(Crawler $crawler, string $nodeName)
    {

        return $crawler->filter($nodeName)->each(function (Crawler $node, $i) {
            return $node->text();
        });

    }

}
