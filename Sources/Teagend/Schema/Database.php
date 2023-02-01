<?php

/**
 * This class handles installing or updating the DB schema for StoryBB
 *
 * @package Teagend
 * @author Teagend https://teagend.com/
 * @copyright 2023 Teagend and individual contributors (see contributors.txt)
 * @license https://www.simplemachines.org/about/smf/license.php BSD
 *
 * @version 2.1.3
 */

namespace Teagend\Schema;

use Teagend\Container;
use Teagend\Database\DatabaseAdapter;
use Teagend\Schema\Schema;

/**
 * This class handles the main database changes for StoryBB.
 */
class Database
{
	/**
	 * Go through the defined schema, see if tables need creating or updating, and action those.
	 */
	public static function update_schema(): void
	{
		global $smcFunc;

		$queries = [];
		$schema = Schema::get_tables();
		foreach ($schema as $table)
		{
			if ($table->exists())
			{
				$existing_table = $smcFunc['db_get_table']('{db_prefix}' . $table->get_table_name());
				$existing_table->update_to($table);
			}
			else
			{
				$table->create();
			}
		}
	}

	/**
	 * Go through the defined schema, see if tables need creating or updating, and return that SQL.
	 */
	public static function get_outstanding_schema_changes(): array
	{
		global $smcFunc;

		$queries = [];
		$schema = Schema::get_tables();
		foreach ($schema as $table)
		{
			if ($table->exists())
			{
				$existing_table = $smcFunc['db_get_table']('{db_prefix}' . $table->get_table_name());
				$queries[] = $existing_table->update_to($table, true);
			}
			else
			{
				$queries[] = $table->create(true);
			}
		}

		return $queries;
	}

	/**
	 * Get the available engines supported by the database system in use.
	 *
	 * @return array A list of engines supported by the underlying DB (currently MySQL only)
	 */
	public static function get_engines(): array
	{
		global $smcFunc;
		static $engines = null;

		if ($engines === null)
		{
			// Figure out which engines we have
			$engines = [];
			$get_engines = $smcFunc['db_query']('', 'SHOW ENGINES', []);

			while ($row = $smcFunc['db_fetch_assoc']($get_engines))
			{
				if ($row['Support'] == 'YES' || $row['Support'] == 'DEFAULT')
					$engines[] = $row['Engine'];
			}

			$smcFunc['db_free_result']($get_engines);
		}

		return $engines;
	}
}
