<?php namespace VLib\DEM;

use VLib\Buffer\ResourceBuffer;

/**
 * A class to read basic demo file info.
 *
 * Doesn't support reading frames/packets, but will soon.
 *
 * @package VLib\DEM
 */
class DEMFile {

	/**
	 * List of frames -> names. Currently unused.
	 *
	 * @var array
	 */
	static $frameIds = [
		1 => 'dem_signon',
		2 => 'dem_packet',
		3 => 'dem_synctick',
		4 => 'dem_consolecmd',
		5 => 'dem_usercmd',
		6 => 'dem_datatables',
		7 => 'dem_stop',
		8 => 'dem_stringtables'
	];

	private $fh;

	/**
	 * Demo protocol version
	 * @var int
	 */
	public $dem_proto;

	/**
	 * Network protocol version
	 * @var int
	 */
	public $net_proto;

	/**
	 * Hostname (Server name if SourceTV or IP Address if local)
	 * @var string
	 */
	public $host_name;

	/**
	 * Client name (SourceTV or client name if local)
	 * @var string
	 */
	public $client_name;

	/**
	 * Map name
	 * @var string
	 */
	public $map_name;

	/**
	 * Game directory
	 * @var string
	 */
	public $gamedir;

	/**
	 * Demo length in seconds
	 * @var float
	 */
	public $time;

	/**
	 * Demo length in ticks
	 * @var int
	 */
	public $ticks;

	/**
	 * Demo frames
	 * @var int
	 */
	public $frames;

	/**
	 * Signon packet/frame length
	 * @var int
	 */
	public $signon_length;

	/**
	 * Construct a new demo file.
	 *
	 * @param resource|string $file
	 * @param bool $parse
	 * @throws DEMException
	 */
	public function __construct($file, $parse = false) {
		if (is_resource($file)) {
			$this->fh = $file;
		} else {
			$this->fh = fopen($file, 'r');
		}

		if (!$this->fh) {
			throw new DEMException('Unable to open demo file.');
		}

		$this->buffer = new ResourceBuffer($file);

		$this->readHeader($parse);
	}

	private function readHeader($parse = false) {
		$buffer = $this->buffer;

		if ($buffer->getString() !== 'HL2DEMO') {
			throw new DEMException('Invalid demo');
		}

		$this->dem_proto = $buffer->getInteger();
		$this->net_proto = $buffer->getInteger();
		$this->host_name = trim($buffer->read(260));
		$this->client_name = trim($buffer->read(260));
		$this->map_name = trim($buffer->read(260));
		$this->gamedir = trim($buffer->read(260));
		$this->time = $buffer->getFloat();
		$this->ticks = $buffer->getInteger();
		$this->frames = $buffer->getInteger();
		$this->signon_length = $buffer->getInteger();

		if ($parse) {
			$this->readFrames();
		}
	}

	/**
	 * Read through the frames in this demo.
	 * @throws \VLib\Buffer\BufferUnderflowException
	 */
	private function readFrames() {
		while (($type = $this->buffer->getByte()) !== 7) {
			$tick = $this->buffer->getInteger();

			if ($type == 3) {
				// No data
				continue;
			} else if ($type == 1 || $type == 2) {
				$this->buffer->skip(0x54);
			} else if ($type == 5) {
				$this->buffer->skip(0x4);
			}

			$this->buffer->skip($this->buffer->getInteger());
		}
	}
}