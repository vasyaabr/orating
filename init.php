<?php

require __DIR__ . '/vendor/autoload.php';

include_once 'lib.php';

$db = new SQLite3('rating.db');

lib\query($db, "CREATE TABLE IF NOT EXISTS events (
    id INTEGER PRIMARY KEY AUTOINCREMENT, 
    title VARCHAR(50), 
    url VARCHAR(200),
    processed INTEGER)");

lib\query($db, "CREATE TABLE IF NOT EXISTS athletes 
    (eventID INTEGER, 
    class VARCHAR(20), 
    surname VARCHAR(50), 
    name VARCHAR(50), 
    team VARCHAR(30), 
    `time` VARCHAR(8), 
    score REAL, 
    PRIMARY KEY (eventID,class,surname,name))");

echo "Finished\n";