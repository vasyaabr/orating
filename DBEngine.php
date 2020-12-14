<?php


namespace orating;


interface DBEngine
{
    public function init();
    public function getEvent(array $params);
    public function addEvent(array $params);
    public function updateEvent(array $params);
    public function addAthlete(array $params);
}