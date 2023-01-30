<?php

/**
 * This file displays custom pages.
 *
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

function Pages()
{
	global $context, $txt, $smcFunc, $scripturl;

	$page_name = isset($_GET['page']) && preg_match('~^[a-z0-9_-]+$~i', $_GET['page']) ? $_GET['page'] : '';
	$request = $smcFunc['db_query']('', '
		SELECT p.id_page, p.page_name, p.page_title, p.page_content, p.show_help, p.show_custom_field, p.custom_field_filter
		FROM {db_prefix}pages AS p
		WHERE page_name = {string:page_name}',
		[
			'page_name' => $page_name,
		]
	);
	$row = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);

	if (empty($row))
	{
		fatal_lang_error('no_access', false);
	}

	$context['page'] = $row;

	assertPageVisible((int) $context['page']['id_page']);

	$context['linktree'][] = [
		'url' => $scripturl . '?action=pages;page=' . $context['page']['page_name'],
		'name' => $context['page']['page_title'],
	];

	$context['current_subaction'] = $context['page']['page_name'];

	$context['page']['page_content'] = parse_bbc($context['page']['page_content'], true, 'page-' . $page_name);

	if (!empty($context['page']['page_content']))
	{
		$context['meta_description'] = shorten_subject(strip_tags(preg_replace('/<br ?\/?>/i', "\n", $context['page']['page_content'])), 500);
	}

	if (!empty($context['page']['field_name']))
	{
		$context['page']['custom_fields'] = [];
		$characters_loaded = [];

		$request = $smcFunc['db_query']('', '
			SELECT cfv.value, cfv.id_character, chars.character_name, mem.id_member, mem.real_name, chars.avatar AS avatar_url, a.filename AS avatar_filename
			FROM {db_prefix}custom_field_values AS cfv
				INNER JOIN {db_prefix}characters AS chars ON (cfv.id_character = chars.id_character)
				INNER JOIN {db_prefix}members AS mem ON (chars.id_member = mem.id_member)
				LEFT JOIN {db_prefix}attachments AS a ON (a.id_character = cfv.id_character AND a.attachment_type = 1)
			WHERE cfv.id_field = {int:field}
			ORDER BY cfv.value',
			[
				'field' => $context['page']['show_custom_field'],
			]
		);
		while ($row = $smcFunc['db']->fetch_assoc($request))
		{
			$row['value'] = trim($row['value']);
			if (empty($row['value']))
			{
				continue;
			}
			if ($context['page']['cf_bbc'])
			{
				$row['value'] = Parser::parse_bbc($row['value']);
			}
			$field = html_entity_decode(strip_tags($row['value']));
			preg_match('/([a-z0-9])/i', $field, $matches);
			$index = !empty($matches[1]) ? StringLibrary::toUpper($matches[1]) : ' ';

			$row['avatar'] = set_avatar_data([
				'avatar' => $row['avatar_url'],
				'filename' => $row['avatar_filename'],
			]);

			$row['account_link'] = $scripturl . '?action=profile;u=' . $row['id_member'];
			$row['character_link'] = $scripturl . '?action=profile;u=' . $row['id_member'] . ';area=characters;char=' . $row['id_character'];

			$characters_loaded[$row['id_character']] = 0;

			$context['page']['custom_fields'][$index][$row['id_character']] = $row;
		}
		$smcFunc['db']->free_result($request);

		if (!empty($characters_loaded) && !empty($context['page']['custom_field_filter']))
		{
			// Values correspond to:
			// 0 = 'No checks; always display'
			// 1 = 'Must have posted in the last month'
			// 2 = 'Must have posted in the last three months'
			// 3 = 'Must have posted in the last six months'
			// 4 = 'Must have posted at least once'
			// Now we need to find, of the characters in question, which had their last posts when.
			$request = $smcFunc['db_query']('', '
				SELECT id_character, MAX(poster_time) AS most_recent
				FROM {db_prefix}messages
				WHERE id_character IN ({array_int:characters})
				GROUP BY id_character',
				[
					'characters' => array_keys($characters_loaded)
				]
			);
			while ($row = $smcFunc['db']->fetch_assoc($request))
			{
				$characters_loaded[$row['id_character']] = (int) $row['most_recent'];
			}
			$smcFunc['db']->free_result($request);

			$removals = [];
			$min_age = [
				1 => strtotime('-1 month'),
				2 => strtotime('-3 months'),
				3 => strtotime('-6 months'),
				4 => 1, // Just assert non-zero.
			];

			foreach ($characters_loaded as $id_character => $most_recent)
			{
				if ($most_recent < $min_age[$context['page']['custom_field_filter']])
				{
					$removals[$id_character] = $id_character;
				}
			}

			if (!empty($removals))
			{
				foreach ($context['page']['custom_fields'] as $index => $characters)
				{
					foreach (array_keys($characters) as $id_character)
					{
						if (isset($removals[$id_character]))
						{
							unset ($context['page']['custom_fields'][$index][$id_character]);
						}
					}
				}

				foreach ($context['page']['custom_fields'] as $index => $characters)
				{
					if (empty($characters))
					{
						unset ($context['page']['custom_fields'][$index]);
					}
				}
			}

			if (!empty($context['page']['custom_fields']))
			{
				ksort($context['page']['custom_fields']);
			}
		}
	}

	loadTemplate('Pages');
	$context['page_title'] = $context['page']['page_title'];
	$context['sub_template'] = 'page';
}

function assertPageVisible(int $page_id): void
{
	global $user_info, $smcFunc;

	if (allowedTo('admin_forum'))
	{
		return;
	}

	if (empty($user_info['groups']))
	{
		fatal_lang_error('no_access');
	}

	$access = 'x';
	$request = $smcFunc['db_query']('', '
		SELECT id_group, allow_deny
		FROM {db_prefix}pages_access
		WHERE id_page = {int:id_page}
			AND id_group IN ({array_int:groups})',
		[
			'id_page' => $page_id,
			'groups' => $user_info['groups'],
		]
	);
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		if ($row['allow_deny'])
		{
			// If this is true, the result is a deny.
			$access = 'd';
		}
		elseif ($access != 'd')
		{
			// If we're here, we got an allow - but only if we haven't already had a deny.
			$access = 'a';
		}
	}
	$smcFunc['db_free_result']($request);

	if ($access != 'a')
	{
		is_not_guest(); // It might improve if you are logged in, perhaps. But we're not going to confirm that for you.
		fatal_lang_error('no_access', false);
	}
}

?>