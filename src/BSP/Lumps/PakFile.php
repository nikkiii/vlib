<?php namespace VLib\BSP\Lumps;

use ZipArchive;

/**
 * An Uncompressed file which contains map resources.
 *
 * @package VLib\BSP\Lumps
 */
class PakFile {
	/**
	 * The file path.
	 * @var string
	 */
	private $file;

	/**
	 * Construct a new pak file.
	 * @param $file
	 */
	public function __construct($file) {
		$this->file = $file;
	}

	/**
	 * Return a new ZipArchive pointing to this file.
	 * @return ZipArchive
	 */
	public function openArchive() {
		$zip = new ZipArchive();
		$zip->open($this->file);
		return $zip;
	}
}