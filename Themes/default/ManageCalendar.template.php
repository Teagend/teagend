<?php
/**
 * Teagend
 *
 * @package Teagend
 * @author Teagend https://teagend.com/
 * @copyright 2023 Teagend and individual contributors (see contributors.txt)
 * @license https://www.simplemachines.org/about/smf/license.php BSD
 *
 * @version 2.1.0
 */

/**
 * Editing or adding holidays.
 */
function template_edit_holiday()
{
	global $context, $scripturl, $txt, $modSettings;

	// Show a form for all the holiday information.
	echo '
		<form action="', $scripturl, '?action=admin;area=managecalendar;sa=editholiday" method="post" accept-charset="', $context['character_set'], '">
			<div class="cat_bar">
				<h3 class="catbg">', $context['page_title'], '</h3>
			</div>
			<div class="windowbg">
				<dl class="settings">
					<dt>
						<strong>', $txt['holidays_title_label'], ':</strong>
					</dt>
					<dd>
						<input type="text" name="title" value="', $context['holiday']['title'], '" size="55" maxlength="60">
					</dd>
					<dt>
						<strong>', $txt['calendar_year'], '</strong>
					</dt>
					<dd>
						<select name="year" id="year" onchange="generateDays();">
							<option value="0000"', $context['holiday']['year'] == '0000' ? ' selected' : '', '>', $txt['every_year'], '</option>';

	// Show a list of all the years we allow...
	for ($year = $modSettings['cal_minyear']; $year <= $modSettings['cal_maxyear']; $year++)
		echo '
							<option value="', $year, '"', $year == $context['holiday']['year'] ? ' selected' : '', '>', $year, '</option>';

	echo '
						</select>
						<label for="month">', $txt['calendar_month'], '</label>
						<select name="month" id="month" onchange="generateDays();">';

	// There are 12 months per year - ensure that they all get listed.
	for ($month = 1; $month <= 12; $month++)
		echo '
							<option value="', $month, '"', $month == $context['holiday']['month'] ? ' selected' : '', '>', $txt['months'][$month], '</option>';

	echo '
						</select>
						<label for="day">', $txt['calendar_day'], '</label>
						<select name="day" id="day" onchange="generateDays();">';

	// This prints out all the days in the current month - this changes dynamically as we switch months.
	for ($day = 1; $day <= $context['holiday']['last_day']; $day++)
		echo '
							<option value="', $day, '"', $day == $context['holiday']['day'] ? ' selected' : '', '>', $day, '</option>';

	echo '
						</select>
					</dd>
				</dl>';

	if ($context['is_new'])
		echo '
				<input type="submit" value="', $txt['holidays_button_add'], '" class="button">';
	else
		echo '
				<input type="submit" name="edit" value="', $txt['holidays_button_edit'], '" class="button">
				<input type="submit" name="delete" value="', $txt['holidays_button_remove'], '" class="button">
				<input type="hidden" name="holiday" value="', $context['holiday']['id'], '">';
	echo '
				<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
				<input type="hidden" name="' . $context['admin-eh_token_var'] . '" value="' . $context['admin-eh_token'] . '">
			</div><!-- .windowbg -->
		</form>';
}

?>