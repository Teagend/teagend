<?php

/**
 * CLI command for reporting on outstanding schema changes.
 *
 * @package Teagend
 * @author Teagend https://teagend.com/
 * @copyright 2023 Teagend and individual contributors (see contributors.txt)
 * @license https://www.simplemachines.org/about/smf/license.php BSD
 *
 * @version 2.1.3
 */

namespace Teagend\Cli\Command\Schema;

use Teagend\Discoverable;
use Teagend\DiscoverableType;
use Teagend\Schema\Database;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[Discoverable(DiscoverableType::CliCommand)]
class UpdateSQL extends Command
{
	public function configure()
	{
		$this->setName('schema:updatesql')
			->setDescription('Provides SQL to update the schema to the current version.')
			->setHelp('This command exports the necessary SQL to upgrade whatever is currently in the database, to the current database schema.');
	}

	public function execute(InputInterface $input, OutputInterface $output)
	{
		$results = Database::get_outstanding_schema_changes();

		foreach ($results as $resultid => $result)
		{
			if (empty($result))
			{
				unset($results[$resultid]);
				continue;
			}
			$result = rtrim($result);
			if (substr($result, -1) !== ';')
			{
				$results[$resultid] = $result . ";\n";
			}
		}

		if (empty($results))
		{
			$output->writeln('Schema is up to date.');
			return 0;
		}

		$output->writeln("The following queries would be run in non-safe mode:");
		$output->write($results);
		$output->writeln('');

		return 0;
	}
}
