<?php

/**
 * This class handles the main database schema for Teagend.
 *
 * @package Teagend
 * @author Teagend https://teagend.com/
 * @copyright 2023 Teagend and individual contributors (see contributors.txt)
 * @license https://www.simplemachines.org/about/smf/license.php BSD
 *
 * @version 2.1.3
 */

namespace Teagend\Schema;

use Teagend\Schema\Table;
use Teagend\Schema\Column;
use Teagend\Schema\Index;
use Teagend\Schema\TableGroup;

/**
 * This class handles the main database schema for Teagend.
 */
class Schema
{
	/**
	 * Returns a list of all tablegroups within the schema.
	 *
	 * @return array An array of the classes that are the tablegroups.
	 */
	public static function get_all_tablegroups(): array
	{
		return [
			TableGroup\Uncategorised::class,
		];
	}

	/**
	 * Returns all the tables in core StoryBB, without prefixes.
	 *
	 * @return array An array of Table instances representing the schema.
	 */
	public static function get_tables(): array
	{
		$schema = [];

		$tablegroups = static::get_all_tablegroups();
		foreach ($tablegroups as $tablegroup)
		{
			$schema = array_merge($schema, $tablegroup::return_tables());
		}

		return $schema;
	}
}
