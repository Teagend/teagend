<?php

/**
 * This file handles character administration tasks.
 *
 * Teagend
 *
 * @package Teagend
 * @author Teagend https://teagend.com/
 * @copyright 2023 Teagend and individual contributors (see contributors.txt)
 * @license https://www.simplemachines.org/about/smf/license.php BSD
 *
 * @version 2.1.0
 */

if (!defined('SMF'))
	die('No direct access...');

/**
 * Displaying/saving the character immersion settings page.
 *
 * @param bool $return_config If true, return the configuration rather than processing the page.
 * @return array Returns the configuration definition if $return_config is set to true, otherwise does a page render/redirect
 */
function CharacterSettings($return_config = false)
{
	global $txt, $scripturl, $context, $sourcedir;

	loadLanguage('Help');
	loadLanguage('ManageCharacters');
	require_once($sourcedir . '/ManageServer.php');

	$config_vars = [
			['select', 'characters_ic_may_post', [
				'ic' => $txt['ic_boards_only'],
				'icooc' => $txt['ic_and_ooc_boards'],
			]],
			['select', 'characters_ooc_may_post', [
				'ooc' => $txt['ooc_boards_only'],
				'icooc' => $txt['ic_and_ooc_boards'],
			]],
		'',
			['check', 'characters_ic_require_sheet'],
		'',
			['select', 'characters_online_view', [
				'ooc' => $txt['characters_online_ooc'],
				'ic' => $txt['characters_online_ic'],
			]],
	];

	call_integration_hook('integrate_character_settings', [&$config_vars]);

	if ($return_config)
		return $config_vars;

	// Saving?
	if (isset($_GET['save']))
	{
		checkSession();

		call_integration_hook('integrate_save_character_settings');

		saveDBSettings($config_vars);
		session_flash('success', $txt['settings_saved']);

		writeLog();
		redirectexit('action=admin;area=charactersettings');
	}

	$context['post_url'] = $scripturl . '?action=admin;area=charactersettings;save';
	$context['settings_title'] = $txt['admin_character_settings'];
	$context['page_title'] = $txt['admin_character_settings'];

	prepareDBSettingContext($config_vars);
}
