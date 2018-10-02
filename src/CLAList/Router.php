<?php


namespace CLAList;

use Klein\Klein;
use Twig_Environment;

abstract class Router {
	/**
	 * @var Twig_Environment $twig
	 */
	protected $twig;
	/**
	 * @var Klein $klein
	 */
	protected $klein;

	/**
	 * MissionRoute constructor.
	 * @param Twig_Environment $twig
	 * @param Klein            $klein
	 */
	public function __construct(Twig_Environment $twig, Klein $klein) {
		$this->twig = $twig;
		$this->klein = $klein;
	}

	public abstract function register();

	/**
	 * @param \Klein\Request         $request
	 * @param \Klein\Response        $response
	 * @param \Klein\ServiceProvider $service
	 * @param \Klein\App             $app
	 */
	public abstract function render(\Klein\Request $request, \Klein\Response $response, \Klein\ServiceProvider $service, \Klein\App $app);
}