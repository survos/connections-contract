<?php

declare(strict_types=1);

$autoload = is_file(dirname(__DIR__).'/vendor/autoload.php')
    ? dirname(__DIR__).'/vendor/autoload.php'
    : dirname(__DIR__, 3).'/vendor/autoload.php';
$loader = require $autoload;
$loader->addPsr4('Survos\\ConnectionsContract\\', dirname(__DIR__).'/src');
