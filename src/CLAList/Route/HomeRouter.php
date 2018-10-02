<?php

namespace CLAList\Route;

use CLAList\Router;

class HomeRouter extends Router {
	public function register() {
		$this->klein->respond('GET', '/', [$this, 'render']);
	}

	public function render(\Klein\Request $request, \Klein\Response $response, \Klein\ServiceProvider $service, \Klein\App $app) {
		$em = GetEntityManager();
		$builder = $em->createQueryBuilder();
		$query = $builder
			->select('m.id')
			->from('CLAList\Model\Entity\Mission', 'm')
			->orderBy('RAND()', 'ASC')
			->getQuery()
			->setMaxResults(1)
		;
		$random = $query->getSingleScalarResult();

	    return $this->twig->render("Index.twig", [
	        "random" => $random
	    ]);
	}

}