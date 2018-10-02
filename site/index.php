<?php

use CLAList\Route\HomeRouter;
use CLAList\Route\MissionRouter;
use CLAList\Router;

require_once dirname(__DIR__) . "/bootstrap.php";

$loader = new \Twig_Loader_Filesystem(BASE_DIR . '/templates');
$twig = new \Twig_Environment($loader, [
    "debug" => true
]);

$klein = new \Klein\Klein();
$routers = [
	new MissionRouter($twig, $klein),
	new HomeRouter($twig, $klein)
];
foreach ($routers as $router) {
	/** @var Router $router */
	$router->register();
}

$klein->dispatch();
