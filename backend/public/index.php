<?php

require_once __DIR__ . '/../src/Controller/ApiController.php';

$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Simple router
$controller = new ApiController();
$controller->handleRequest($method, $uri);
