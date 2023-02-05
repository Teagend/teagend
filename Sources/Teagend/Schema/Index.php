<?php

/**
 * This class handles indexes.
 *
 * @package Teagend
 * @author Teagend https://teagend.com/
 * @copyright 2023 Teagend and individual contributors (see contributors.txt)
 * @license https://www.simplemachines.org/about/smf/license.php BSD
 *
 * @version 2.1.3
 */

namespace Teagend\Schema;

use Teagend\Schema\InvalidIndexException;
use Teagend\Schema\InvalidIndexTypeException;

/**
 * This class handles indexes.
 */
class Index
{
	/** @var $index The properties of the index this object represents */
	private $index;

	/**
	 * Creates an instance representing the index.
	 *
	 * @param array $columns Columns in internal format (a la parse_columns)
	 * @param string $type Type of index to be produced
	 * @param array $raw_columns The names of columns upon which this index is based
	 * @return instance
	 */
	private function __construct(array $columns, string $type, array $raw_columns)
	{
		if (empty($columns))
		{
			throw new InvalidIndexException('An index must contain columns');
		}
		if (!in_array($type, ['primary', 'unique', 'index', 'fulltext']))
		{
			throw new InvalidIndexTypeException('Unsupported index type: ' . $type);
		}

		$this->index = [
			'columns' => $columns,
			'type' => $type,
			'raw_columns' => $raw_columns,
		];
	}

	/**
	 * Converts the syntax used externally for columns (incl. prefix) into internal format.
	 * e.g. ['myfield, 'myfield2' => 15] -> ['myfield', 'myfield2(15)']
	 *
	 * @param array $columns An array of columns where values are columns, or key/values are partial columns.
	 * @param bool $raw Whether to return these as just raw column names or parsed format.
	 * @return array An array in SQL format
	 */
	protected static function parse_columns(array $columns, bool $raw = false): array
	{
		$sqlcols = [];
		$rawcols = [];
		foreach ($columns as $id => $column)
		{
			if (is_numeric($id) && !is_numeric($column))
			{
				// This came in as a regular column for the array.
				$sqlcols[] = $column;
				$rawcols[] = $column;
			}
			else
			{
				$sqlcols[] = $id . '(' . $column . ')';
				$rawcols[] = $id;
			}
		}

		return $raw ? $rawcols : $sqlcols;
	}

	/**
	 * Factory function to create a primary key index.
	 *
	 * @param array Columns to index as part of the primary key
	 * @return Index instance
	 */
	public static function primary(array $columns)
	{
		return new self(self::parse_columns($columns), 'primary', self::parse_columns($columns, true));
	}

	/**
	 * Factory function to create a simple index.
	 *
	 * @param array Columns to index as part of the key
	 * @return Index instance
	 */
	public static function key(array $columns)
	{
		return new self(self::parse_columns($columns), 'index', self::parse_columns($columns, true));
	}

	/**
	 * Factory function to create a unique index.
	 *
	 * @param array Columns to index as part of the unique key
	 * @return Index instance
	 */
	public static function unique(array $columns)
	{
		return new self(self::parse_columns($columns), 'unique', self::parse_columns($columns, true));
	}

	public static function fulltext(array $columns)
	{
		return new self(self::parse_columns($columns), 'fulltext', self::parse_columns($columns, true));
	}

	/**
	 * Returns an array suitable for DatabaseAdapter::create_table to create the indexes on that table.
	 *
	 * @return array Data for DatabaseAdapter::create_table
	 */
	public function create_data()
	{
		return [
			'columns' => $this->index['columns'],
			'type' => $this->index['type'],
		];
	}

	/**
	 * Returns an array of raw columns this column is based off.
	 *
	 * @return array An array of raw column names without length parameters.
	 */
	public function get_raw_columns()
	{
		return $this->index['raw_columns'];
	}
}
