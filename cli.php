#!/usr/bin/env php
<?php	

/**	
 * Runs command line functions.
 *	
 * @package Teagend
 * @author Teagend https://teagend.com/
 * @copyright 2023 Teagend and individual contributors (see contributors.txt)
 * @license https://www.simplemachines.org/about/smf/license.php BSD
 *
 * @version 2.1.3
 */	

use Teagend\ClassManager;
use Teagend\DiscoverableType;
use Symfony\Component\Console;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

define('SMF', 'BACKGROUND');
define('SMF_VERSION', '2.1.3');
define('SMF_FULL_VERSION', 'SMF ' . SMF_VERSION);
define('SMF_SOFTWARE_YEAR', '2023');

define('FROM_CLI', empty($_SERVER['REQUEST_METHOD']));

define('JQUERY_VERSION', '3.6.0');
define('SMF_USER_AGENT', 'Mozilla/5.0 (' . php_uname('s') . ' ' . php_uname('m') . ') AppleWebKit/605.1.15 (KHTML, like Gecko)  SMF/' . strtr(SMF_VERSION, ' ', '.'));

// We're going to want a few globals... these are all set later.
global $maintenance, $msubject, $mmessage, $mbname, $language;
global $boardurl, $boarddir, $sourcedir, $webmaster_email;
global $db_server, $db_name, $db_user, $db_prefix, $db_persist, $db_error_send, $db_last_error;
global $db_connection, $modSettings, $context, $sc, $user_info, $txt;
global $smcFunc, $ssi_db_user, $scripturl, $db_passwd, $cachedir, $admin_cli_password;

if (!defined('TIME_START'))
	define('TIME_START', microtime(true));

// Just being safe...
foreach (array('db_character_set', 'cachedir') as $variable)
	if (isset($GLOBALS[$variable]))
		unset($GLOBALS[$variable]);

// Get the forum's settings for database and file paths.
require_once(dirname(__FILE__) . '/Settings.php');

// Make absolutely sure the cache directory is defined and writable.
if (empty($cachedir) || !is_dir($cachedir) || !is_writable($cachedir))
{
	if (is_dir($boarddir . '/cache') && is_writable($boarddir . '/cache'))
		$cachedir = $boarddir . '/cache';
	else
	{
		$cachedir = sys_get_temp_dir() . '/smf_cache_' . md5($boarddir);
		@mkdir($cachedir, 0750);
	}
}

// Don't do john didley if the forum's been shut down completely.
if ($maintenance == 2)
	die($mmessage);

// Fix for using the current directory as a path.
if (substr($sourcedir, 0, 1) == '.' && substr($sourcedir, 1, 1) != '.')
	$sourcedir = dirname(__FILE__) . substr($sourcedir, 1);

$cli_input = null;
$cli_output = null;

if (!FROM_CLI)	
{	
	if (!isset($admin_cli_password) || !isset($_GET['secret']) || $_GET['secret'] !== $admin_cli_password)	
	{	
		die('Script can only be run from command line or with administrative CLI password');
	}

	if (!isset($_GET['command']))
	{
		die('Script must have a command set.');
	}

	$params = $_GET;
	unset($params['secret']);

	$cli_input = new ArrayInput($params);
	$cli_output = new BufferedOutput();
}
else
{
	$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.0';
}

// Load the most important includes. In general, a background should be loading its own dependencies.
require_once($sourcedir . '/Errors.php');
require_once($sourcedir . '/Load.php');
require_once($sourcedir . '/Security.php');
require_once($sourcedir . '/Subs.php');

// Ensure we don't trip over disabled internal functions
if (version_compare(PHP_VERSION, '8.0.0', '>='))
	require_once($sourcedir . '/Subs-Compat.php');

// Create a variable to store some SMF specific functions in.
$smcFunc = array();

// This is our general bootstrap, a la SSI.php but with a few differences.
unset ($db_show_debug);
loadDatabase();
reloadSettings();

require_once($boarddir . '/vendor/autoload.php');

require_once($sourcedir . '/Errors.php');
require_once($sourcedir . '/Load.php');
require_once($sourcedir . '/Subs.php');

loadLanguage('Index+Modifications');

$app = new Console\Application;
foreach (ClassManager::get_classes_implementing(DiscoverableType::CliCommand) as $command)
{
	$app->add(new $command);
}
$app->run($cli_input, $cli_output);

if (!empty($cli_output))
{
	echo $cli_output->fetch();
}
