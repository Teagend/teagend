<?php

/**
 * A library for handling class discovery.
 *
 * @package Teagend
 * @author Teagend https://teagend.com/
 * @copyright 2023 Teagend and individual contributors (see contributors.txt)
 * @license https://www.simplemachines.org/about/smf/license.php BSD
 *
 * @version 2.1.3
 */

namespace Teagend;

use Teagend\Discoverable;
use ReflectionClass;

class ClassManager
{
	/**
	 * Given the name of an interface, return the classes which implement that interface.
	 * The interface in question must be an extension of the Discoverable interface.
	 *
	 * e.g. StoryBB\Helper\Autocomplete\Completable is a valid interface.
	 *
	 * @param string $interface The interface name from root namespace
	 * @return array An array of class names that can be autoloaded
	 */
	public static function get_classes_implementing(DiscoverableType $interface): array
	{
		$cache = self::get_cache();
		return isset($cache[$interface->value]) ? $cache[$interface->value] : [];
	}

	/**
	 * Fetches the data required for the classes. Loads from cache where possible.
	 *
	 * @return array An array of interface -> implementing classes (where interface implements Discoverable)
	 */
	protected static function get_cache(): array
	{
		global $cachedir;
		if (!file_exists($cachedir . '/class_cache.php'))
		{
			$class_cache = self::rebuild_cache();
		}
		else
		{
			$class_cache = [];
			include($cachedir . '/class_cache.php');
		}

		return $class_cache;
	}

	/**
	 * Forces a rebuild of the cache of interfaces/classes. Also returns the list so the run
	 * that generates the build gets it too.
	 *
	 * @return An array of interface -> implementing classes (where interface implements Discoverable)
	 */
	public static function rebuild_cache(): array
	{
		global $cachedir, $sourcedir;

		$class_cache = [];

		$classes = self::get_oo_from_basepath($sourcedir);
		foreach ($classes as $class)
		{
			$reflection = new ReflectionClass($class);
			$attributes = $reflection->getAttributes(Discoverable::class);
			foreach ($attributes as $attribute) {
				foreach ($attribute->getArguments() as $argument)
				{
					$class_cache[$argument->value][] = $class;
				}
			}
		}

		$cachefile = '<?php if (!defined(\'SMF\')) die; $class_cache = ' . var_export($class_cache, true) . ';';
		file_put_contents($cachedir . '/class_cache.php', $cachefile);
		if (function_exists('opcache_invalidate'))
		{
			opcache_invalidate($cachedir . '/class_cache.php', true);
		}

		return $class_cache;
	}

	/**
	 * Finds all the things that look like classes that live in a given folder/its subfolders.
	 *
	 * @param string $path The absolute path to start from
	 * @return array An array of classes found in the path
	 */ 
	protected static function get_oo_from_basepath(string $path): array
	{
		$pathiterator = new \RecursiveDirectoryIterator($path);
		$fileiterator = new \RecursiveIteratorIterator($pathiterator);
		$filteriterator = new \RegexIterator($fileiterator, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);

		$current_classes = get_declared_classes();
		$current_interfaces = get_declared_interfaces();

		foreach ($filteriterator as $file)
		{
			// Match the filename part of the PHP file. We're going to need that part.
			$match = basename($file[0]);
			if (strtolower(substr($match, -4)) === '.php') {
				$match = substr($match, 0, -4);
			}

			$filecontent = file_get_contents($file[0]);

			// Does this file contain a class or interface?
			if (strpos($filecontent, "\nclass " . $match) !== false)
			{
				try
				{
					include_once($file[0]);
				}
				catch (\Throwable $e)
				{
					// We don't really care if this happens.
					continue;
				}
			}
		}

		return array_diff(get_declared_classes(), $current_classes);
	}
}
