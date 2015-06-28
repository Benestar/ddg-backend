<?php

require_once __DIR__ . '/../vendor/autoload.php';

$backend = new DDGWikidata\Backend( $_GET );
$backend->execute();
