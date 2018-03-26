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

	function __construct($gamePath, $realPath = null) {
		parent::__construct($gamePath, $realPath);
	}

	/**
	 * Find a texture if it exists, or if it exists in any parent directory
	 * @param string $base
	 * @param string $texture
	 * @return null|string
	 */
	public static function resolve($base, $texture) {
		$test = $base . "/" . $texture;

		$candidates = self::getCandidates($base, $texture);
		foreach ($candidates as $candidate) {
			if (is_file($candidate)) {
				return $candidate;
			}
		}
		return null;
	}

	/**
	 * Get candidate texture paths
	 * @param string $base
	 * @param string $texture
	 * @param bool $recursive
	 * @return null|array
	 */
	public static function getCandidates($base, $texture, $recursive = true) {
		if ($base === BASE_DIR || $base === "" || $base === "/" || $base === ".")
			return [];

		$test = $base . "/" . $texture;

		//Test a whole bunch of image types
		$candidates = [];
		$extensions = [".jpg", ".jpeg", ".png", ".bmp", ".dds"];
		foreach ($extensions as $extension) {
			$candidates[] = "{$test}{$extension}";
		}

		if (!$recursive) {
			return $candidates;
		}
		//Try to recurse
		$sub = pathinfo($base, PATHINFO_DIRNAME);
		$subs = self::getCandidates($sub, $texture);
		return array_merge($candidates, $subs);
	}

	public static function getQualityCandidates($texture) {
		$dir = pathinfo($texture, PATHINFO_DIRNAME);
		$base = pathinfo($texture, PATHINFO_FILENAME);
		$ext = pathinfo($texture, PATHINFO_EXTENSION);
		$candidates = [];
		$qualities = ["", ".hi", ".med", ".low"];
		foreach ($qualities as $quality) {
			$candidates[] = "{$dir}/{$base}{$quality}.{$ext}";
		}
		return $candidates;
	}

}
