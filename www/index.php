<?php

require_once __DIR__ . '/../vendor/autoload.php';

$backend = new DDGWikidata\Backend( $_GET, 'https://www.wikidata.org/w/api.php' );
$backend->execute();
