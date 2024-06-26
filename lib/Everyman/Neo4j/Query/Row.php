<?php
namespace Everyman\Neo4j\Query;

use Everyman\Neo4j\Client;

/**
 * Represents a single result row from a query.
 * You can loop over this to get each value, or you can
 * use array access ($myRow['columnName'] or $myRow[0])
 * to get specific fields.
 */
class Row implements \Iterator, \Countable, \ArrayAccess
{
	protected $client = null;
	protected $raw = null;
	protected $data = null;
	protected $columns = null;
	protected $position = 0;

	/**
	 * Set the raw result data of this row
	 *
	 * @param Client $client
	 * @param array $columns
	 * @param array $rowData
	 */
	public function __construct(Client $client, $columns, $rowData)
	{
		$this->client = $client;
		$this->raw = $rowData;
		$this->data = array();
		$this->columns = $columns;
	}

	// ArrayAccess API
	#[\ReturnTypeWillChange]
	public function offsetExists($offset)
	{
		if (!is_integer($offset)) {

			$rawOffset = array_search($offset, $this->columns);

			if ($rawOffset === false) {
				return false;
			}

			return isset($this->raw[$rawOffset]);
		}

		return isset($this->raw[$offset]);
	}
	#[\ReturnTypeWillChange]
	public function offsetGet($offset)
	{
		if (!is_integer($offset)) {
			$offset = array_search($offset, $this->columns);
		}

		if (!isset($this->data[$offset])) {
			$raw = $this->raw[$offset];
			$data = $this->client->getEntityMapper()->getEntityFor($raw);
			if (is_array($data)) {
				$data = new Row($this->client, array_keys($raw), array_values($raw));
			}
			$this->data[$offset] = $data;
		}

		return $this->data[$offset];
	}
	#[\ReturnTypeWillChange]
	public function offsetSet($offset, $value)
	{
		throw new \BadMethodCallException("You cannot modify a result row.");
	}
	#[\ReturnTypeWillChange]
	public function offsetUnset($offset)
	{
		throw new \BadMethodCallException("You cannot modify a result row.");
	}


	// Countable API
	#[\ReturnTypeWillChange]
	public function count()
	{
		return count($this->raw);
	}


	// Iterator API
	#[\ReturnTypeWillChange]
	public function rewind()
	{
		$this->position = 0;
	}
	
	#[\ReturnTypeWillChange]
	public function current()
	{
		return $this[$this->position];
	}

	#[\ReturnTypeWillChange]
	public function key()
	{
		return $this->columns[$this->position];
	}

	#[\ReturnTypeWillChange]
	public function next()
	{
		++$this->position;
	}

	#[\ReturnTypeWillChange]
	public function valid()
	{
		return $this->position < count($this->raw);
	}
}
