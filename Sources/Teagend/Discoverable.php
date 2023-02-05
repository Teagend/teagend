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

namespace Teagend;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Discoverable
{
	public $type;

	public function __construct(DiscoverableType $type)
	{
		$this->type = $type;
	}
}
