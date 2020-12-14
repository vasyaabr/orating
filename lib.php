<?php

namespace lib;

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\DomCrawler\Crawler;

function query(\SQLite3 $db, string $query, array $params = []) {
    $statement = $db->prepare($query);
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

function extractValues(Crawler $crawler, string $nodeName)
{

    return $crawler->filter($nodeName)->each(function (Crawler $node, $i) {
        return $node->text();
    });

}
