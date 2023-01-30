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

/**
 * This is the manage pages home.
 */
function template_page_edit()
{
	global $scripturl, $context, $txt;

	echo '
		<form method="post" action="', $scripturl, '?action=admin;area=pages;sa=', !empty($context['page']['id_page']) ? 'edit_page' : 'add_page', '">
			<div class="cat_bar">
				<h3 class="catbg">', $context['page_title'], '</h3>
			</div>
			<div class="errorbox"', empty($context['page_errors']) ? ' style="display: none"' : '', ' id="errors">
				<dl>
					<dt>
						<strong id="error_serious">', $txt['error_while_submitting'], '</strong>
					</dt>
					<dd class="error" id="error_list">
						', !empty($context['page_errors']) ? implode('<br>', $context['page_errors']) : '', '
					</dd>
				</dl>
			</div>
			<div id="box_preview"', empty($context['page_preview']) ? ' style="display:none"' : '', '>
				<div class="title_bar">
					<h3 class="titlebg">', $txt['preview'], '</h3>
				</div>
				<div class="roundframe">
					<div id="page_preview">', $context['page_preview'] ?? '', '</div>
				</div>
			</div>
			<div class="windowbg">
				<dl class="settings">
					<dt>', $txt['page_name'], ':</dt>
					<dd>', $scripturl, '?action=pages;page= <input type="text" name="page_name" value="', $context['page']['page_name'], '" required></dd>
					<dt>', $txt['page_title'], ':</dt>
					<dd><input type="text" name="page_title" value="', $context['page']['page_title'], '" required></dd>
				</dl>
				', template_control_richedit('message', 'smileyBox_message', 'bbcBox_message'), '
				<br>

				<dl class="settings">
					<dt>', $txt['page_access_desc'], '</dt>
					<dd>
						<dl>
							<dt>
								<span class="perms"><strong>', $txt['permissions_option_on'], '</strong></span>
								<span class="perms"><strong>', $txt['permissions_option_off'], '</strong></span>
								<span class="perms red"><strong>', $txt['permissions_option_deny'], '</strong></span>
							</dt>
							<dd>
							</dd>';
	foreach ($context['page']['groups']['account'] as $group_id => $group)
	{
		echo '
							<dt>
								<span class="perms"><input type="radio" name="access[', $group_id, ']" value="a"', $group['access'] == 'a' ? ' checked' : '', !empty($group['frozen']) ? ' disabled' : '', '></span>
								<span class="perms"><input type="radio" name="access[', $group_id, ']" value="x"', $group['access'] == 'x' ? ' checked' : '', !empty($group['frozen']) ? ' disabled' : '', '></span>
								<span class="perms"><input type="radio" name="access[', $group_id, ']" value="d"', $group['access'] == 'd' ? ' checked' : '', !empty($group['frozen']) ? ' disabled' : '', '></span>
							</dt>
							<dd>
								<span>', $group['name'], '</span>
							</dd>';
	}

	echo '
						</dl>
					</dd>
				</dl>

				<dl class="settings">
					<dt>', $txt['page_show_help'], ':</dt>
					<dd><input type="checkbox" name="show_help" value="1"', !empty($context['page']['show_help']) ? ' checked' : '', '></dd>
				</dl>';

	if (!empty($context['page']['fields']['account']) || !empty($context['page']['fields']['character']))
	{
		echo '
				<dl class="settings">
					<dt>', $txt['page_display_custom_field'], ':</dt>
					<dd>
						<select name="show_custom_field">
							<option value="0">', $txt['page_custom_field_none'], '</option>';
		if (!empty($context['page']['fields']['account']))
		{
			echo '
							<optgroup label="' . $txt['page_custom_field_ooc'] . '">';
			foreach ($context['page']['fields']['account'] as $field_id => $field_name)
				echo '
								<option value="', $field_id, '"', $field_id == $context['page']['show_custom_field'] ? ' selected' : '', '>', $field_name, '</option>';
			echo '
							</optgroup>';
		}

		if (!empty($context['page']['fields']['character']))
		{
			echo '
							<optgroup label="' . $txt['page_custom_field_ic'] . '">';
			foreach ($context['page']['fields']['character'] as $field_id => $field_name)
				echo '
								<option value="', $field_id, '"', $field_id == $context['page']['show_custom_field'] ? ' selected' : '', '>', $field_name, '</option>';
			echo '
							</optgroup>';
		}

		echo '
						</select>
					</dd>
					<dt>', $txt['txt.page_custom_field_post_age'], ':</dt>
					<dd>
						<select name="custom_field_filter">
							<option value="0"', $context['page']['custom_field_filter'] == 0 ? ' selected' : '', '>', $txt['page_custom_field_post_age_any'], '</option>
							<option value="1"', $context['page']['custom_field_filter'] == 1 ? ' selected' : '', '>', $txt['page_custom_field_post_age_1month'], '</option>
							<option value="2"', $context['page']['custom_field_filter'] == 2 ? ' selected' : '', '>', $txt['page_custom_field_post_age_3months'], '</option>
							<option value="3"', $context['page']['custom_field_filter'] == 3 ? ' selected' : '', '>', $txt['page_custom_field_post_age_6months'], '</option>
							<option value="4"', $context['page']['custom_field_filter'] == 4 ? ' selected' : '', '>', $txt['page_custom_field_post_age_ever'], '</option>
						</select>
					</dd>
				</dl>';
	}
	else
	{
		echo '
				<input type="hidden" name="show_custom_field" value="0">
				<input type="hidden" name="custom_field_filter" value="0">';
	}

	echo '
				<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
				<input type="hidden" name="pid" value="', $context['page']['id_page'], '">
				<div class="buttonlist righttext">
					<input type="submit" class="active" name="save" value="', $txt['save'], '">
					<input type="submit" name="preview" value="', $txt['preview'], '" id="preview_page">';

	if (!empty($context['page']['id_page']))
		echo '
					<input type="submit" name="delete" class="you_sure" value="', $txt['delete'], '">';

	echo '
				</div>
			</div>
		</form>';
}

?>