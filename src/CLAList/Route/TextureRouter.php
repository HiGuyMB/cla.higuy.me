<?php


namespace CLAList\Route;


use CLAList\Model\Entity\Texture;
use CLAList\Router;

class TextureRouter extends Router {

	public function register() {
		$this->klein->respond('GET', '/api/v1/textures', function(\Klein\Request $request, \Klein\Response $response, \Klein\ServiceProvider $service, \Klein\App $app) {
			$response->json($this->allTextures());
		});
		$this->klein->respond('GET', '/api/v1/textures/[:id]', function(\Klein\Request $request, \Klein\Response $response, \Klein\ServiceProvider $service, \Klein\App $app) {
			$this->renderTexture($request, $response, $service, $app);
		});
		$this->klein->respond('GET', '/textures', function(\Klein\Request $request, \Klein\Response $response, \Klein\ServiceProvider $service, \Klein\App $app) {
			return $this->twig->render('TextureSearch.twig');
		});
	}

	private function allTextures() {
		$em = GetEntityManager();

		$repo = $em->getRepository('CLAList\Model\Entity\Texture');
		$all = $repo->findAll();

		$all = array_map(function(Texture $texture) {
			return [
				"id" => $texture->getId(),
				"baseName" => $texture->getBaseName(),
				"gamePath" => $texture->getGamePath(),
				"hash" => $texture->getHash(),
				"official" => $texture->getOfficial(),
			];
		}, $all);

		return $all;
	}

	private function renderTexture(\Klein\Request $request, \Klein\Response $response, \Klein\ServiceProvider $service, \Klein\App $app) {
		$service->validateParam('id')->notNull();
		$id = $request->param('id');

		/* @var Texture $texture */
		$texture = Texture::find(["id" => $id]);
		if ($texture === null) {
			$response->code(404);
			return;
		}
//		echo($texture->getRealPath());
		$response->file($texture->getRealPath());
	}
}