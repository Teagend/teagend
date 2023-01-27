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
 * Displays custom pages.
 */
function template_page()
{
	global $context;

	echo '
	<div class="cat_bar">
		<h3 class="catbg">', $context['page']['page_title'], '</h3>
	</div>';

	if (!empty($context['page']['page_content']))
		echo '
	<div class="windowbg">
		', $context['page']['page_content'], '
	</div>
	<br>';

	if (!empty($context['page']['custom_fields']))
	{

		foreach ($context['page']['custom_fields'] as $letter => $field_group)
		{
			echo '
		<div class="title_bar">
			<h3 class="titlebg">', $context['page']['field_name'], $letter != ' ' ? ' - ' . $letter : '', '</h3>
		</div>';

			foreach ($field_group as $field)
			{
				echo '
		<div class="up_contain">
			<div class="lastpost">
				<div class="last_poster">
					', $field['avatar']['image'], '
				</div>';

				if (!empty($context['page']['in_character']))
				{
					echo '
				<p><a href="', $field['character_link'], '"">', $field['character_name'], '</a><br>
					(<a href="', $field['account_link'], '">', $field['real_name'], '</a>)</p>';
				}
				else
				{
					echo '
				<p><a href="', $field['account_link'], '">', $field['real_name'], '</a></p>';
				}
			}

			echo '
			</div>
			<div class="info">
				', $field['value'], '
			</div>
		</div>';

		}
	}
}

?>