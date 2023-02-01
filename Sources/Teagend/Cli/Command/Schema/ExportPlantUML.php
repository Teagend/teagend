<?php

/**
 * CLI command for exporting the schema UML into a file for PlantUML.
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
use Teagend\Schema\Exporter\PlantUML;
use Teagend\Schema\Schema as TeagendSchema;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[Discoverable(DiscoverableType::CliCommand)]
class ExportPlantUML extends Command
{
	
	public function configure()
	{
		$this->setName('schema:exportuml')
			->setDescription('Exports Teagend schema as UML for documentation.')
			->setHelp('This command exports the Teagend schema to a specified destination path in PlantUML format.')
			->addArgument('path', InputArgument::REQUIRED, 'The destination path to write to');
	}
	public function execute(InputInterface $input, OutputInterface $output)
	{
		$path = $input->getArgument('path');
		if (substr($path, 0, 2) == './')
		{
			$path = getcwd() . substr($path, 1);
		}

		$exporter = new PlantUML(new TeagendSchema);
		$uml = $exporter->output();
		if (@file_put_contents($path, $uml))
		{
			$output->writeln('Successfully written PlantUML to ' . $path);
			return 0;
		}

		$output->writeln('Could not write to ' . $path);
		return 1;
	}
}
