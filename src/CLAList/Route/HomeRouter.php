<?php

namespace CLAList\Route;

use CLAList\Router;

class HomeRouter extends Router {
	public function register() {
		$this->klein->respond('GET', '/', [$this, 'render']);
	}

	public function render(\Klein\Request $request, \Klein\Response $response, \Klein\ServiceProvider $service, \Klein\App $app) {
		return $this->twig->render("MissionList.twig", []);
	}

}