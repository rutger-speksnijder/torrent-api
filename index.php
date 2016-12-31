<?php
// Composer autoloader
require 'vendor/autoload.php';
require 'TorrentApi.php';

// Create the router
$router = new \SimpleRoute\Router(
    strtolower($_SERVER['REQUEST_METHOD']),
    isset($_GET['l']) ? $_GET['l'] : ''
);

// Create the torrent api handler
$torrentApi = new TorrentApi();
$router = $torrentApi->addRoutes($router);

// Execute the router
$router->execute();
