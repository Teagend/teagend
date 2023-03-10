<?php

/**
 * This file contains code used to notify people that a new event has been
 * added to the calendar - but only when no topic has been created.
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

/**
 * Class EventNew_Notify_Background
 */
class EventNew_Notify_Background extends SMF_BackgroundTask
{
	/**
	 * This executes the task: loads up the info, puts the email in the queue
	 * and inserts any alerts as needed.
	 *
	 * @return bool Always returns true
	 */
	public function execute()
	{
		global $sourcedir, $smcFunc, $user_profile;

		// Get everyone who could be notified - those are the people who can see the calendar.
		require_once($sourcedir . '/Subs-Members.php');
		$members = membersAllowedTo('calendar_view');

		// Don't alert the event creator
		if (!empty($this->_details['sender_id']))
			$members = array_diff($members, array($this->_details['sender_id']));

		// Having successfully figured this out, now let's get the preferences of everyone.
		require_once($sourcedir . '/Subs-Notify.php');
		$prefs = getNotifyPrefs($members, 'event_new', true);

		// Just before we go any further, we may not have the sender's name. Let's just quickly fix that.
		// If a guest creates the event, we wouldn't be capturing a username or anything.
		if (!empty($this->_details['sender_id']) && empty($this->_details['sender_name']))
		{
			loadMemberData($this->_details['sender_id'], false, 'minimal');
			if (!empty($user_profile[$this->_details['sender_id']]))
				$this->_details['sender_name'] = $user_profile[$this->_details['sender_id']]['real_name'];
			else
				$this->_details['sender_id'] = 0;
		}

		// So now we find out who wants what.
		$alert_bits = array(
			'alert' => self::RECEIVE_NOTIFY_ALERT,
			'email' => self::RECEIVE_NOTIFY_EMAIL,
		);
		$notifies = array();

		foreach ($prefs as $member => $pref_option)
		{
			foreach ($alert_bits as $type => $bitvalue)
				if ($pref_option['event_new'] & $bitvalue)
					$notifies[$type][] = $member;
		}

		// Firstly, anyone who wants alerts.
		if (!empty($notifies['alert']))
		{
			// Alerts are relatively easy.
			$insert_rows = array();
			foreach ($notifies['alert'] as $member)
			{
				$insert_rows[] = array(
					'alert_time' => $this->_details['time'],
					'id_member' => $member,
					'id_member_started' => $this->_details['sender_id'],
					'member_name' => $this->_details['sender_name'],
					'content_type' => 'event',
					'content_id' => $this->_details['event_id'],
					'content_action' => empty($this->_details['sender_id']) ? 'new_guest' : 'new',
					'is_read' => 0,
					'extra' => $smcFunc['json_encode'](
						array(
							"event_id" => $this->_details['event_id'],
							"event_title" => $this->_details['event_title']
						)
					),
				);
			}

			$smcFunc['db_insert']('insert',
				'{db_prefix}user_alerts',
				array('alert_time' => 'int', 'id_member' => 'int', 'id_member_started' => 'int',
					'member_name' => 'string', 'content_type' => 'string', 'content_id' => 'int',
					'content_action' => 'string', 'is_read' => 'int', 'extra' => 'string'),
				$insert_rows,
				array('id_alert')
			);

			// And update the count of alerts for those people.
			updateMemberData($notifies['alert'], array('alerts' => '+'));
		}

		return true;
	}
}

?>