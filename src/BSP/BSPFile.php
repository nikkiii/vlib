<?php namespace VLib\BSP;

define('CHUNK_SIZE', 1024);

use VLib\Buffer\ByteBuffer;
use VLib\BSP\Lumps\PakFile;

class BSPFile {
	/**
	 * The path to the lzma binary.
	 * @var string
	 */
	static $lzma_path = 'lzma';

	/**
	 * A list of the lump names.
	 * @var array
	 */
	static $lumps = [
		'entities' => 0,
		'pakfile' => 40
	];

	/**
	 * The file stream we're reading from.
	 * @var resource
	 */
	private $stream;

	/**
	 * An array of options. Currently only supports lzma path.
	 * @var array
	 */
	private $options;

	/**
	 * The BSP version.
	 * @var int
	 */
	public $version;

	/**
	 * The lump headers.
	 * @var array
	 */
	private $lumpHeader;

	/**
	 * @param resource $stream
	 * @throws \Exception
	 */
	public function __construct($stream, $options = []) {
		$this->stream = $stream;
		$this->options = $options;

		$this->readHeader();
	}

	/**
	 * Read the file header and lumps.
	 *
	 * @throws \Exception
	 */
	private function readHeader() {
		// size = 4 (header) + 4 (version) + (64 * 32)
		$buffer = new ResourceBuffer(fread($this->stream, 8 + (64 * 32)));

		$header = $buffer->read(4);

		if ($header != 'VBSP') {
			throw new \Exception("Invalid header: $header");
		}

		$this->version = $buffer->getInteger();

		$this->lumpHeader = [];

		for ($i = 0; $i < 64; $i++) {
			$this->lumpHeader[] = [
				'fileofs' => $buffer->getInteger(),
				'filelen' => $buffer->getInteger(),
				'version' => $buffer->getInteger(),
				'fourCC' => $buffer->read(4)
			];
		}
	}

	/**
	 * Read a specific lump. Currently only supports entities.
	 *
	 * @param $index
	 * @throws \Exception
	 */
	private function readLump($index) {
		$info = $this->lumpHeader[$index];

		fseek($this->stream, $info['fileofs']);

		if ($index === 0) {
			$buffer = new ByteBuffer(fread($this->stream, $info['filelen']));

			$hdr = $buffer->peek(4);

			if ($hdr == 'LZMA') { // Team Fortress 2
				// Decompress
				$buffer = $this->decompressLZMALump($buffer);
			}

			$parts = explode("}", $buffer->getData());

			$lump = [];

			foreach ($parts as $data) {
				$data = trim($data);
				if (strlen($data) < 1 || $data[0] != '{') {
					continue;
				}
				$lump[] = KeyValues::decode("\"Entity\"\n" . trim($data) . "\n}")['Entity'];
			}
		} else if ($index === 40) {
			$file = tempnam(sys_get_temp_dir(), 'zip');
			$tmp = fopen($file, 'w');

			$remaining = $info['filelen'];
			while ($remaining > 0) {
				$chunk = fread($tmp, $remaining > CHUNK_SIZE ? CHUNK_SIZE : $remaining);

				$remaining -= strlen($chunk);
			}

			$lump = new PakFile($file);
		}

		return $lump;
	}

	/**
	 * Decompress an LZMA-encoded lump.
	 *
	 * @param ByteBuffer $buffer
	 * @return ByteBuffer
	 */
	private function decompressLZMALump(ByteBuffer &$buffer) {
		$id = $buffer->getInteger();
		$actualSize = $buffer->getInteger();
		$lzmaSize = $buffer->getInteger(); // We already know this.
		$properties = $buffer->read(5);

		if ($id !== 1095588428) { // = (('A'<<24)|('M'<<16)|('Z'<<8)|('L'))
			throw new \Exception('Lump is not in LZMA format!');
		}

		if ($lzmaSize > $buffer->remaining()) {
			throw new \Exception('Not enough data in the buffer');
		}

		$descriptorspec = array(
			0 => [ 'pipe', 'r' ],
			1 => [ 'pipe', 'w' ],
			2 => [ 'pipe', 'w' ]
		);

		$encodedSize = pack('N', $actualSize << 32) . pack('N', $actualSize);

		$proc = proc_open(array_get($this->options, 'lzma_path', static::$lzma_path) . ' d -si -so', $descriptorspec, $pipes);

		// Valve uses the header to store this info on their own, so we have to append it back on our own.
		fwrite($pipes[0], $properties);
		fwrite($pipes[0], $encodedSize);
		fwrite($pipes[0], $buffer->getData());
		fclose($pipes[0]);

		$buf = '';

		while ($data = fread($pipes[1], 1024)) {
			$buf .= $data;
		}

		$stderr = '';

		while ($data = fread($pipes[2], 1024)) {
			$stderr .= $data;
		}

		proc_close($proc);

		return new ByteBuffer($buf);
	}

	/**
	 * Magic method for getters. Allows us to lazy load information.
	 * @param $name
	 */
	public function __get($name) {
		if (array_key_exists($name, static::$lumps)) {
			return $this->{$name} = $this->readLump(static::$lumps[$name]);
		}
	}
}