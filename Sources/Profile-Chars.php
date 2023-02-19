<?php

/**
 * Teagend
 *
 * @package Teagend
 * @author Teagend https://teagend.com/
 * @copyright 2023 Teagend and individual contributors (see contributors.txt)
 * @license https://www.simplemachines.org/about/smf/license.php BSD
 *
 * @version 2.1.3
 */

if (!defined('SMF'))
	die('No direct access...');

function character_create($memID)
{
	global $context, $smcFunc, $txt, $sourcedir, $user_info, $modSettings;

	$context['page_title'] = $txt['create_character'];
	
	$context['character'] = [
		'character_name' => '',
	];

	$context['form_errors'] = [];

	// See if they're saving.
	if (isset($_POST['create_char']))
	{
		checkSession();
		$context['character']['character_name'] = !empty($_POST['char_name']) ? $smcFunc['htmlspecialchars'](trim($_POST['char_name']), ENT_QUOTES) : '';

		if ($context['character']['character_name'] == '')
			$context['form_errors'][] = $txt['char_error_character_must_have_name'];
		else
		{
			// Check if the name already exists.
			$result = $smcFunc['db_query']('', '
				SELECT COUNT(*)
				FROM {db_prefix}characters
				WHERE character_name LIKE {string:new_name}',
				array(
					'new_name' => $context['character']['character_name'],
				)
			);
			list ($matching_names) = $smcFunc['db_fetch_row']($result);
			$smcFunc['db_free_result']($result);

			if ($matching_names)
				$context['form_errors'][] = $txt['char_error_duplicate_character_name'];
		}

		if (empty($context['form_errors']))
		{
			// So no errors, we can save this new character, yay!
			$smcFunc['db_insert']('insert',
				'{db_prefix}characters',
				array(
					'id_member' => 'int', 'character_name' => 'string', 'default_avatar' => 'int',
					'avatar_rotation' => 'string', 'default_signature' => 'int', 'id_theme' => 'int',
					'posts' => 'int', 'date_created' => 'int', 'last_active' => 'int',
					'is_main' => 'int', 'main_char_group' => 'int', 'char_groups' => 'string',
					'char_sheet' => 'int', 'char_topic' => 'int', 'retired' => 'int', 'is_npc' => 'int'),
				array(
					$context['id_member'], $context['character']['character_name'], 0,
					'', 0, 0,
					0, time(), time(),
					0, 0, '',
					0, 0, 0, 0,
				),
				['id_character']
			);
			$context['character']['id_character'] = $smcFunc['db_insert_id']('{db_prefix}characters', 'id_character');

			redirectexit('action=profile;u=' . $context['id_member'] . ';area=characters;char=' . $context['character']['id_character']);
		}
	}
}

function character_profile($memID)
{
	global $user_profile, $context, $scripturl, $modSettings, $smcFunc, $txt, $user_info;

	$char_id = isset($_GET['char']) ? (int) $_GET['char'] : 0;
	if (!isset($context['all_characters'][$char_id])) {
		// character doesn't exist... bye.
		redirectexit('action=profile;u=' . $memID);
	}

	$context['character'] = $context['all_characters'][$char_id];
	$context['character']['editable'] = $context['user']['is_owner'] || allowedTo('admin_forum');

	$context['character']['is_current_character'] = $context['user']['is_owner'] && $user_info['id_character'] == $context['character']['id_character'];

	$context['switch_eligible'] = $context['user']['is_owner'] && !$context['character']['is_current_character'] && !$context['character']['retired'];
	$context['sheet_eligible'] = ($context['user']['is_owner'] || allowedTo('admin_forum')) || (!empty($context['character']['char_sheet']));
	$context['settings_eligible'] = $context['character']['editable'];
	$context['retire_eligible'] = $context['character']['editable'] && !$context['character']['is_current_character'];
	$context['move_eligible'] = $context['character']['editable'] && !$context['character']['is_current_character'] && (($context['user']['is_owner'] && allowedTo('character_move_own')) || allowedTo('character_move_any'));
	$context['delete_eligible'] = $context['character']['editable'] && !$context['character']['is_current_character'] && $context['character']['posts'] == 0 && (($context['user']['is_owner'] && allowedTo('character_delete_own')) || allowedTo('character_delete_any'));

	$context['linktree'][] = array(
		'name' => $txt['characters'],
		'url' => $scripturl . '?action=profile;u=' . $context['id_member'] . '#user_char_list',
	);
	$context['linktree'][] = array(
		'name' => $context['character']['character_name'],
		'url' => $scripturl . '?action=profile;u=' . $context['id_member'] . ';area=characters;sa=profile;char=' . $char_id,
	);
	$subactions = array(
		// 'edit' => 'character_edit',
		// 'sheet' => 'character_sheet',
		'retire' => 'character_retire',
		// 'move_acct' => 'character_move_account',
		// 'sheet_edit' => 'character_sheet_edit',
		// 'sheet_approval' => 'character_sheet_approval',
		// 'sheet_approve' => 'character_sheet_approve',
		// 'sheet_reject' => 'character_sheet_reject',
		// 'sheet_compare' => 'character_sheet_compare',
		// 'sheet_history' => 'character_sheet_history',
		// 'delete' => 'character_delete',
		// 'posts' => 'character_posts',
		// 'topics' => 'character_topics',
	);
	if (isset($_GET['sa'], $subactions[$_GET['sa']])) {
		$func = $subactions[$_GET['sa']];
		return $func();
	}

	// Since we're not doing any of those things, let's do the character profile.

	$context['has_posts'] = $context['character']['posts'] > 0;
	$context['has_topics'] = false;
	$context['character']['topics'] = 0;

	$session = ';' . $context['session_var'] . '=' . $context['session_id'];

	$context['character_buttons'] = array(
		'switch' => array(
			'text' => 'switch_character',
			'test' => 'switch_eligible',
			'url' => $scripturl . '?action=profile;u=' . $context['id_member'] . ';area=char_switch;profile;char=' . $context['character']['id_character'] . $session,
		),

		'sheet' => array(
			'text' => 'character_sheet_button',
			'test' => 'sheet_eligible',
			'url' => $scripturl . '?action=profile;u=' . $context['id_member'] . ';area=characters;char=' . $context['character']['id_character'] . ';sa=sheet',
		),
		'settings' => array(
			'text' => 'character_settings_button',
			'test' => 'settings_eligible',
			'url' => $scripturl . '?action=profile;u=' . $context['id_member'] . ';area=characters;char=' . $context['character']['id_character'] . ';sa=edit',
		),
		'retire' => array(
			'text' => $context['character']['retired'] ? 'character_unretire_button' : 'character_retire_button',
			'test' => 'retire_eligible',
			'url' => $scripturl . '?action=profile;u=' . $context['id_member'] . ';area=characters;char=' . $context['character']['id_character'] . ';sa=retire' . $session,
		),
		'move' => array(
			'text' => 'character_move_button',
			'test' => 'move_eligible',
			'url' => $scripturl . '?action=profile;u=' . $context['id_member'] . ';area=characters;char=' . $context['character']['id_character'] . ';sa=move_acct' . $session,
		),
		'delete' => array(
			'text' => 'character_delete_button',
			'test' => 'delete_eligible',
			'url' => $scripturl . '?action=profile;u=' . $context['id_member'] . ';area=characters;char=' . $context['character']['id_character'] . ';sa=delete' . $session,
			'custom' => 'onclick="return confirm(' . JavaScriptEscape($txt['are_you_sure_delete_char']) . ');"',
		),
		'posts' => array(
			'text' => 'character_posts_button',
			'test' => 'has_posts',
			'url' => $scripturl . '?action=profile;u=' . $context['id_member'] . ';area=characters;char=' . $context['character']['id_character'] . ';sa=posts',
		),
		'topics' => array(
			'text' => 'character_topics_button',
			'test' => 'has_topics',
			'url' => $scripturl . '?action=profile;u=' . $context['id_member'] . ';area=characters;char=' . $context['character']['id_character'] . ';sa=topics',
		),
	);

	$context['character']['days_registered'] = (int) ((time() - $context['character']['date_created']) / (3600 * 24));
	$context['character']['posts_per_day'] = $context['character']['days_registered'] > 1 ? comma_format($context['character']['posts'] / $context['character']['days_registered'], 2) : '';

	if ($context['character']['posts'] > 0)
	{
		// If they've made some posts, have they made any topics?
		$result = $smcFunc['db_query']('', '
			SELECT COUNT(*)
			FROM {db_prefix}topics AS t
			INNER JOIN {db_prefix}messages AS m ON (t.id_first_msg = m.id_msg)
			WHERE m.id_character = {int:id_character}' . (!empty($modSettings['recycle_enable']) && $modSettings['recycle_board'] > 0 ? '
				AND t.id_board != {int:recycle_board}' : ''),
			[
				'id_character' => $context['character']['id_character'],
				'recycle_board' => $modSettings['recycle_board'] ?? 0,
			]
		);
		list ($context['character']['topics']) = $smcFunc['db_fetch_row']($result);
		$smcFunc['db_free_result']($result);

		$context['has_topics'] = $context['character']['topics'] > 0;
	}
}

function character_retire()
{
	global $context, $smcFunc, $txt, $user_info;

	checkSession('get');

	// If the character isn't eligible for retirement, goodbye.
	if (!$context['retire_eligible']) {
		redirectexit('action=profile;u=' . $context['id_member'] . ';area=characters;char=' . $context['character']['id_character']);
	}

	// This is really quite straightforward.
	$new_state = $context['character']['retired'] ? 0 : 1; // If currently retired, make them not, etc.
	updateCharacterData($context['character']['id_character'], ['retired' => $new_state]);

	$change_array = [
		'previous' => $context['character']['retired'] ? $txt['yes'] : $txt['no'],
		'new' => $new_state ? $txt['yes'] : $txt['no'],
		'applicator' => $context['user']['id'],
		'member_affected' => $context['id_member'],
		'id_character' => $context['character']['id_character'],
		'character_name' => $context['character']['character_name'],
	];
	$smcFunc['db_insert']('insert',
		'{db_prefix}log_actions',
		['id_log' => 'int', 'log_time' => 'int', 'id_member' => 'int',
			'ip' => 'inet', 'action' => 'string', 'id_board' => 'int',
			'id_topic' => 'int', 'id_msg' => 'int', 'extra' => 'string'],
		[2, time(), $context['id_member'],
			$user_info['ip'], 'char_retired', 0,
			0, 0, json_encode($change_array)],
		[]
	);

	// And back to the character.
	redirectexit('action=profile;u=' . $context['id_member'] . ';area=characters;char=' . $context['character']['id_character']);
}

?>