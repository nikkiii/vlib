<?php namespace VLib\VPK;

use League\Flysystem\FilesystemInterface;

/**
 * Represents a VPK File.
 *
 * @package VLib\VPK
 */
class VPKEntry {
	/**
	 * The Flysystem interface.
	 * @var FilesystemInterface
	 */
	private $filesystem;

	/**
	 * The file to read from.
	 * @var string
	 */
	private $file;

	/**
	 * The file type (extension).
	 * @var string
	 */
	private $type;

	/**
	 * The file name.
	 * @var string
	 */
	private $name;

	/**
	 * The file directory.
	 * @var string
	 */
	private $dir;

	/**
	 * The file crc32 checksum.
	 * @var int
	 */
	private $crc32;

	/**
	 * The offset of the file in the vpk archive.
	 * @var integer
	 */
	private $offset;

	/**
	 * The file size.
	 * @var integer
	 */
	private $size;

	/**
	 * The preloaded data.
	 * @var string
	 */
	private $preload;

	/**
	 * The preloaded data length.
	 * @var integer
	 */
	private $preloadLen;

	/**
	 * Construct a new VPK entry.
	 *
	 * @param FilesystemInterface $filesystem
	 * @param $file
	 */
	public function __construct(FilesystemInterface $filesystem, $file) {
		$this->filesystem = $filesystem;
		$this->file = $file;
	}

	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @param string $type
	 */
	public function setType($type) {
		$this->type = $type;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getDir() {
		return $this->dir;
	}

	/**
	 * @param string $dir
	 */
	public function setDir($dir) {
		$this->dir = $dir;
	}

	/**
	 * @return int
	 */
	public function getCrc32() {
		return $this->crc32;
	}

	/**
	 * @param int $crc32
	 */
	public function setCrc32($crc32) {
		$this->crc32 = $crc32;
	}

	/**
	 * @return int
	 */
	public function getOffset() {
		return $this->offset;
	}

	/**
	 * @param int $offset
	 */
	public function setOffset($offset) {
		$this->offset = $offset;
	}

	/**
	 * @return int
	 */
	public function getSize() {
		return $this->size;
	}

	/**
	 * @param int $size
	 */
	public function setSize($size) {
		$this->size = $size;
	}

	/**
	 * @return string
	 */
	public function getPreload() {
		return $this->preload;
	}

	/**
	 * @param string $preload
	 */
	public function setPreload($preload) {
		$this->preload = $preload;
		$this->preloadLen = strlen($preload);
	}

	/**
	 * Get the full path of this file by joining the directory and name, using the type as the extension.
	 * @return string
	 */
	public function getPath() {
		return $this->dir . '/' . $this->name . '.' . $this->type;
	}

	/**
	 * Get the file data using stream_get_contents.
	 * @return bool|string
	 */
	public function getData() {
		$stream = $this->stream();

		if (!$stream) {
			return false;
		}

		$contents = stream_get_contents($stream);
		fclose($stream);
		return $contents;
	}

	/**
	 * Get the file's data as a stream. This uses php://temp which will write to file if the file is over a certain size, otherwise it will use memory.
	 * @return bool|resource
	 */
	public function stream() {
		if ($this->size == 0 && $this->preloadLen == 0) {
			return false;
		}

		// temp has a memory limit, so it'll let us use bigger files a lot better.
		$stream = fopen('php://temp','r+');

		if ($this->size == 0 && $this->preloadLen > 0) {
			fwrite($stream, $this->payload);
		} else if ($this->size > 0 && $this->preloadLen > 0) {
			// concat preloaded and external data
			$fh = $this->filesystem->readStream($this->file);

			fseek($fh, $this->offset);

			// Write to our temp stream
			fwrite($stream, $this->payload);

			$remaining = $this->size;

			while ($remaining > 0) {
				$chunk = fread($fh, $remaining > 1024 ? 1024 : $remaining);

				fwrite($stream, $chunk);

				$remaining -= strlen($chunk);
			}

			fclose($fh);
		} else {
			$fh = $this->filesystem->readStream($this->file);

			fseek($fh, $this->offset);

			$read = 0;

			while ($read < $this->size) {
				$remaining = $this->size - $read;

				$chunk = fread($fh, $remaining > 8192 ? 8192 : $remaining);

				fwrite($stream, $chunk);

				$read += strlen($chunk);
			}

			fclose($fh);
		}

		rewind($stream);

		return $stream;
	}
}