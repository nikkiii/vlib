<?php namespace VLib\Buffer;

use OutOfBoundsException;

/**
 * A simple buffer for reading standard data types in PHP
 *
 * @author Nikki
 *
 */
class ResourceBuffer extends Buffer {

	/**
	 * The data object (String in this case)
	 */
	private $stream;

	/**
	 * Construct a new buffer.
	 *
	 * @param string $data
	 */
	public function __construct($stream) {
		$this->stream = $stream;
		$this->stat = fstat($stream);
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

		return fread($this->stream, $len);
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
		$pos = $this->position();
		$data = fread($this->stream, $len);
		fseek($this->stream, $pos);
		return $data;
	}

	/**
	 * Get the number of remaining bytes.
	 *
	 * @return int
	 */
	public function remaining() {
		return $this->limit() - $this->position();
	}

	/**
	 * Get the length of the data in this buffer.
	 *
	 * @return int
	 */
	public function limit() {
		return $this->stat['size'];
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

		fseek($this->stream, $this->position() + $len);
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
		fseek($this->stream, $pos);
	}

	/**
	 * Get the current reader position.
	 *
	 * @return int
	 */
	public function position() {
		return ftell($this->stream);
	}

	/**
	 * Reset the buffer reader position.
	 */
	public function reset() {
		rewind($this->stream);
	}

	/**
	 * Attempt to free the buffer data.
	 */
	public function close() {
		fclose($this->stream);
	}
}