<?php


namespace orating;


class SQLite3Engine extends DBEngine
{

    /** @var SQLite3  */
    private static $db;

    public function __construct()
    {
        self::$db = new \SQLite3(self::DB_NAME);
    }


    public function init()
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

    public function getEvent(array $params)
    {

    }

    public function addEvent(array $params)
    {

    }

    public function updateEvent(array $params)
    {

    }

    public function addAthlete(array $params)
    {

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

}