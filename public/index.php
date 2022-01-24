<?php

use App\Kernel;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Dotenv\Dotenv;

$loader = require __DIR__.'/../vendor/autoload.php';
// auto-load annotations
AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

date_default_timezone_set("Europe/Helsinki");

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/../.env');

$kernel = new Kernel($_ENV['APP_ENV'], $_ENV['APP_DEBUG']);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
