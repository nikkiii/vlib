<?php namespace VLib\Buffer;

/**
 * A Buffer implementation to read simple data types in PHP.
 *
 * @see ByteBuffer
 * @see ResourceBuffer
 * @package VLib\Buffer
 */
abstract class Buffer {
	/**
	 * Read a specified amount of data from the buffer.
	 *
	 * @param $len
	 * @return string
	 * @throws BufferUnderflowException
	 */
	public abstract function read($len);

	/**
	 * Peek ahead $len bytes.
	 *
	 * @param $len
	 * @return string
	 * @throws BufferUnderflowException
	 */
	public abstract function peek($len);

	/**
	 * Get the number of remaining bytes.
	 *
	 * @return int
	 */
	public abstract function remaining();

	/**
	 * Get the length of the data in this buffer.
	 *
	 * @return int
	 */
	public abstract function limit();

	/**
	 * Skip ahead $len bytes.
	 *
	 * @param $len
	 * @throws BufferUnderflowException
	 */
	public abstract function skip($len);

	/**
	 * Seek to $pos in the buffer, starting from the beginning.
	 *
	 * @param $pos
	 * @return mixed
	 * @throws OutOfBoundsException
	 */
	public abstract function seek($pos);

	/**
	 * Get the current reader position.
	 *
	 * @return int
	 */
	public abstract function position();

	/**
	 * Reset the buffer reader position.
	 */
	public abstract function reset();

	/**
	 * Close/free the buffer.
	 */
	public abstract function close();

	/**
	 * Get a single byte from the buffer.
	 *
	 * @return int
	 */
	public function getByte() {
		return ord($this->read(1));
	}

	/**
	 * Get a short (16 bits) from the buffer.
	 *
	 * @return int
	 */
	public function getShort() {
		$lo = $this->getByte();
		$hi = $this->getByte();
		$short = ($hi << 8) | $lo;
		return $short;
	}

	/**
	 * Get an integer (32 bits) from the buffer.
	 *
	 * @return int
	 */
	public function getInteger() {
		$lo = $this->getShort();
		$hi = $this->getShort();
		$long = ($hi << 16) | $lo;
		return $long;
	}

	/**
	 * Get a float (32 bits) from the buffer.
	 *
	 * @return float
	 */
	public function getFloat() {
		$f = @unpack("f1float", $this->read(4));
		return $f['float'];
	}

	/**
	 * Get a null-terminated string from the buffer.
	 *
	 * @return string
	 */
	public function getString() {
		$str = '';
		while (($b = $this->read(1)) != "\x00") {
			$str .= $b;
		}
		return $str;
	}

	/**
	 * Return a new ByteBuffer containing `len` bytes.
	 *
	 * @param $len
	 * @return ByteBuffer
	 */
	public function slice($len) {
		return new ByteBuffer($this->read($len));
	}
}