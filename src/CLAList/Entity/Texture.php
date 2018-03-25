<?php
namespace CLAList\Entity;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Column;

/**
 * @Entity
 * @Table(name="uxwba_textures")
 */
class Texture extends AbstractGameEntity {

	public function __construct($gamePath) {
		parent::__construct($gamePath);
	}

	/**
	 * Find a texture if it exists, or if it exists in any parent directory
	 * @param string $base
	 * @param string $texture
	 * @return null|string
	 */
	static function resolve($base, $texture) {
		$test = $base . "/" . $texture;

		//Test a whole bunch of image types
		if (is_file("{$test}.jpg")) {
			$image = "{$test}.jpg";
		} else if (is_file("{$test}.jpeg")) {
			$image = "{$test}.jpeg";
		} else if (is_file("{$test}.png")) {
			$image = "{$test}.png";
		} else if (is_file("{$test}.bmp")) {
			$image = "{$test}.bmp";
		} else {
			//Try to recurse
			$sub = pathinfo($base, PATHINFO_DIRNAME);
			if ($sub === BASE_DIR || $sub === "" || $sub === "/" || $sub === ".")
				return null;
			$image = self::resolve($sub, $texture);
		}
		return $image;
	}

}
