<?php namespace VLib\Buffer;

/**
 * A simple buffer for reading standard data types in PHP
 *
 * @author Nikki
 *
 */
class ByteBuffer extends Buffer {

	/**
	 * The data object (String in this case)
	 *
	 * @var string
	 */
	private $data;

	/**
	 * The length of the data.
	 *
	 * @var int
	 */
	private $length;

	/**
	 * The data pointer position.
	 *
	 * @var int
	 */
	private $position;

	/**
	 * Construct a new buffer.
	 *
	 * @param string $data
	 */
	public function __construct($data) {
		$this->data = $data;
		$this->length = strlen($data);
	}

	/**
	 * Get the data in this buffer.
	 *
	 * @return string
	 */
	public function getBuffer() {
		return $this->data;
	}

	/**
	 * Get the remaining data in this buffer.
	 *
	 * @return string
	 */
	public function getData() {
		return substr($this->data, $this->position);
	}

	/**
	 * Read a specified amount of data from the buffer.
	 *
	 * @param $len
	 * @return string
	 * @throws BufferUnderflowException
	 */
	public function read($len) {
		if ($len > $this->remaining()) {
			throw new BufferUnderflowException();
		}

		$data = substr($this->data, $this->position, $len);
		$this->skip($len);
		return $data;
	}

	/**
	 * Peek ahead $len bytes.
	 *
	 * @param $len
	 * @return string
	 * @throws BufferUnderflowException
	 */
	public function peek($len) {
		if ($len > $this->remaining()) {
			throw new BufferUnderflowException();
		}
		return substr($this->data, $this->position, $len);
	}

	/**
	 * Get the number of remaining bytes.
	 *
	 * @return int
	 */
	public function remaining() {
		return $this->length - $this->position;
	}

	/**
	 * Get the length of the data in this buffer.
	 *
	 * @return int
	 */
	public function limit() {
		return $this->length;
	}

	/**
	 * Skip ahead $len bytes.
	 *
	 * @param $len
	 * @throws BufferUnderflowException
	 */
	public function skip($len) {
		if ($len > $this->remaining()) {
			throw new BufferUnderflowException();
		}

		$this->position += $len;
	}

	/**
	 * Seek to $pos in the buffer, starting from the beginning.
	 *
	 * @param $pos
	 * @return mixed
	 * @throws OutOfBoundsException
	 */
	public function seek($pos) {
		if ($pos < 0 || $pos > $this->limit()) {
			throw new OutOfBoundsException();
		}
		$this->position = $pos;
	}

	/**
	 * Get the current reader position.
	 *
	 * @return int
	 */
	public function position() {
		return $this->position;
	}

	/**
	 * Reset the buffer reader position.
	 */
	public function reset() {
		$this->position = 0;
	}

	/**
	 * Close/free the buffer.
	 */
	public function close() {
		unset($this->data);
	}
}