<?php namespace VLib\VPK;

use League\Flysystem\FilesystemInterface;
use VLib\Buffer\ResourceBuffer;

/**
 * A VPK Archive class.
 *
 * @package VLib\VPK
 */
class VPKArchive {
	/**
	 * The Flysystem filesystem instance.
	 * @var FilesystemInterface
	 */
	private $filesystem;

	/**
	 * The directory of the main file.
	 * @var string
	 */
	private $dir;

	/**
	 * The path of the file.
	 * @var string
	 */
	private $path;

	/**
	 * Flag for whether this file is a multi-chunk (vpk_dir, vpk_000, etc)
	 * @var bool
	 */
	private $multiChunk;

	/**
	 * Version of the file.
	 * @var int
	 */
	public $version;

	/**
	 * Dictionary size.
	 * @var int
	 */
	public $dictSize;

	/**
	 * The entries in this filesystem, by their name.
	 * @var array
	 */
	public $pathEntries;

	/**
	 * Construct the archive and load it.
	 *
	 * @param FilesystemInterface $filesystem
	 * @param $path
	 * @throws VPKException
	 * @throws \Exception
	 */
	public function __construct(FilesystemInterface $filesystem, $path) {
		$this->filesystem = $filesystem;
		$this->dir = pathinfo($path, PATHINFO_DIRNAME);
		$this->path = $path;

		$this->vpkName = substr(pathinfo($path, PATHINFO_FILENAME), 0, -4);

		$this->load();
	}

	/**
	 * Load the archive.
	 *
	 * @throws VPKException
	 * @throws \Exception
	 * @throws \VLib\Buffer\BufferUnderflowException
	 */
	private function load() {
		$this->multiChunk = strpos($this->path, '_dir') !== false;

		// Load it from our filesystem. This uses Flysystem to support remote files.
		$fh = $this->filesystem->readStream($this->path);

		if (!$fh) {
			throw new VPKException('Unable to open path ' . $this->path);
		}

		/**
		 * Use a temporary buffer for the header data.
		 */
		$buffer = new ResourceBuffer($fh);

		$sig = $buffer->getInteger();

		if ($sig !== 1437209140) {
			throw new VPKException('Invalid file signature.');
		}

		$this->version = $buffer->getInteger();

		switch ($this->version) {
		case 1:
			$headerSize = 12;
			break;
		case 2:
			$headerSize = 28;

			$buffer->skip(16);
			break;
		default:
			throw new VPKException('Invalid file version.');
		}

		$this->dictSize = $buffer->getInteger();

		$this->pathEntries = [];

		while ($type = $buffer->getString()) {
			while ($dir = $buffer->getString()) {
				while ($name = $buffer->getString()) {
					$crc32 = $buffer->getInteger();
					$preloadSize = $buffer->getShort();
					$chunkIndex = $buffer->getShort();
					$offset = $buffer->getInteger();
					$size = $buffer->getInteger();

					$term = $buffer->getShort();

					if ($term !== 0xffff) {
						throw new VPKException('Unexpected termination character.');
					}

					$preload = '';

					if ($preloadSize > 0) {
						$preload = $buffer->read($preloadSize);
					}

					if ($this->multiChunk) {
						$entryName = sprintf('%s_%03d.vpk', $this->vpkName, $chunkIndex);

						$entryName = $this->dir . '/' . $entryName;
                    } else {
						$entryName = $this->file;
						if ($this->version == 1) {
							$offset += $headerSize + $this->dictSize;
						}
					}

					$entry = new VPKEntry($this->filesystem, $entryName);
					$entry->setType($type);
					$entry->setName($name);
					$entry->setDir($dir);
					$entry->setCRC32($crc32);
					$entry->setOffset($offset);
					$entry->setSize($size);
					$entry->setPreload($preload);

					$this->pathEntries[$entry->getPath()] = $entry;
				}
			}
		}
	}

	/**
	 * Get a file from the archive.
	 *
	 * @param $path
	 * @return \VLib\VPK\VPKEntry
	 */
	public function get($path) {
		return $this->pathEntries[$path];
	}
}