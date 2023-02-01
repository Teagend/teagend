<?php

/**
 * CLI command for updating the database to the current schema.
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
class Update extends Command
{
	public function configure()
	{
		$this->setName('schema:update')
			->setDescription('Update the schema to the current version.')
			->setHelp('This command updates the database.');
	}

	public function execute(InputInterface $input, OutputInterface $output)
	{
		Database::update_schema();

		return 0;
	}
}
