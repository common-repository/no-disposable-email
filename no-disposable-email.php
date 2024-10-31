<?php

/*
 *
 *	Plugin Name: No Disposable Email
 *	Plugin URI: http://www.joeswebtools.com/wordpress-plugins/no-disposable-email/
 *	Description: This plugin prevent people from registering with a disposable email addresses like the one provided by mailinator. Go to <a href="options-general.php?page=no-disposable-email/no-disposable-email.php">Settings &rarr; No Disposable Emails</a> after activating the plugin to access the options.
 *	Version: 2.5.1
 *	Author: Joe's Web Tools
 *	Author URI: http://www.joeswebtools.com/
 *
 *	Copyright (c) 2009 Joe's Web Tools. All Rights Reserved.
 *
 *	This program is free software; you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation; either version 2 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program; if not, write to the Free Software
 *	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 *
 *	If you are unable to comply with the terms of this license,
 *	contact the copyright holder for a commercial license.
 *
 *	We kindly ask that you keep links to Joe's Web Tools so
 *	other people can find out about this plugin.
 *
 */





/*
 *
 *	no_disposable_email
 *
 */

function no_disposable_email($errors, $sanitized_user_login, $user_email) {

	global $wpdb;

	// Create the table name
	$table_name = $wpdb->prefix . 'no_disposable_email';

	// Get the domain name and clean it
	list($username, $domain) = split('@', $user_email); 
	$domain = $wpdb->escape(strtolower(trim($domain)));

	// Check the blacklist
	$blacklist_domain = $wpdb->get_results("SELECT * FROM $table_name WHERE domain='$domain'");
	if(!empty($blacklist_domain)) {
		$errors->add('invalid_email', get_option('no_disposable_email_message'));
	}

	// Create the log entry
	$log_string = date('Y-m-d H:i:s');
	$log_string .= ' ';
	$log_string .= $domain;
	$log_string .= ' -> ';
	if(empty($blacklist_domain)) {
		$log_string .= 'not found';
	} else {
		$log_string .= 'found';
	}
	$log_string .= "\r\n";

	// Write the log
	$log_file = dirname(__FILE__) . '/no_disposable_email.log';
	$log_file_handle = fopen($log_file, 'a');
	fwrite($log_file_handle, $log_string);
	fclose($log_file_handle);
	
	return $errors;
}

add_filter('registration_errors', 'no_disposable_email', 20, 3);





/*
 *
 *	no_disposable_email_page
 *
 */

function no_disposable_email_page() {

	global $wpdb;

	// Load language file
	$current_locale = get_locale();
	if(!empty($current_locale)) {
		$mo_file = dirname(__FILE__) . '/languages/no-disposable-email-' . $current_locale . ".mo";
		if(@file_exists($mo_file) && is_readable($mo_file)) {
			load_textdomain('no-disposable-email', $mo_file);
		}
	}

	// Create the table name
	$table_name = $wpdb->prefix . 'no_disposable_email';

	// Update data
	if(isset($_POST['update'])) {

		// Update the message
		update_option('no_disposable_email_message', $_POST['message']);

		// Truncate table
		$results = $wpdb->query("TRUNCATE TABLE $table_name");

		// Update the Blacklist
		if($blacklist = $_POST['blacklist']) {
			$blacklist_array = explode("\n", $blacklist);
			sort($blacklist_array);
			foreach($blacklist_array as $blacklist_current) {
				$blacklist_current = strtolower(trim($blacklist_current));
				if($blacklist_current != '') {
					$wpdb->query("INSERT INTO $table_name(domain) VALUES('" . $wpdb->escape($blacklist_current) . "')");
				}
			}
		}
	}

	// Restore defaults
	if(isset($_POST['default'])) {

		// Set the default message
		update_option('no_disposable_email_message', __('<strong>ERROR</strong>: Please, do not use a disposable email to register.', 'no-disposable-email'));

		// Truncate table
		$results = $wpdb->query("TRUNCATE TABLE $table_name");

		// Get the blacklist
		$blacklist_array = file(dirname(__FILE__) . '/no-disposable-email.dat');
		$blacklist_size = sizeof($blacklist_array);

		// Import the blacklist
		for($i = 0; $i < $blacklist_size; $i++) {
			$blacklist_current = strtolower(trim($blacklist_array[$i]));
			$wpdb->query("INSERT INTO $table_name(domain) VALUES('" . $wpdb->escape($blacklist_current) . "')");
		}
	}

	// Page wrapper start
	echo '<div class="wrap">';

	// Title
	screen_icon();
	echo '<h2>No Disposable Emails</h2>';

	// Options
	echo	'<div id="poststuff" class="ui-sortable">';
	echo		'<div class="postbox opened">';
	echo			'<h3>' . __('Options', 'no-disposable-email') . '</h3>';
	echo			'<div class="inside">';
	echo				'<form method="post">';
	echo					'<table  class="form-table">';
	echo						'<tr>';
	echo							'<th scope="row" valign="top">';
	echo								'<b>' . __('Error message', 'no-disposable-email') . '</b>';
	echo							'</th>';
	echo							'<td>';
	echo								'<textarea name="message" rows="4" cols="40">';
	echo									get_option('no_disposable_email_message');
	echo								'</textarea>';
	echo							'</td>';
	echo						'</tr>';
	echo						'<tr>';
	echo							'<th scope="row" valign="top">';
	echo								'<b>' . __('Blacklisted Domains', 'no-disposable-email') . '</b>';
	echo							'</th>';
	echo							'<td>';
	echo								'<textarea name="blacklist" rows="10" cols="40">';
											$table_name = $wpdb->prefix . 'no_disposable_email';
											$record = $wpdb->get_results("SELECT * FROM $table_name");
											foreach($record as $record)
											{
												echo $record->domain . "\r\n";
											}
	echo								'</textarea>';
	echo							'</td>';
	echo						'</tr>';
	echo						'<tr>';
	echo							'<td colspan="2">';
	echo								'<input type="submit" class="button-primary"  name="update" value="' . __('Save Changes', 'no-disposable-email') . '" />';
	echo								'<input type="submit" class="button-primary"  name="default" value="' . __('Restore defaults', 'no-disposable-email') . '" />';
	echo							'</td>';
	echo						'</tr>';
	echo					'</table>';
	echo				'</form>';
	echo			'</div>';
	echo		'</div>';
	echo	'</div>';

	// About
	echo	'<div id="poststuff" class="ui-sortable">';
	echo		'<div class="postbox opened">';
	echo			'<h3>' . __('About', 'no-disposable-email') . '</h3>';
	echo			'<div class="inside">';
	echo				'<form method="post">';
	echo					'<table  class="form-table">';
	echo						'<tr>';
	echo							'<th scope="row" valign="top">';
	echo								'<b>' . __('Like this plugin?', 'no-disposable-email') . '</b>';
	echo							'</th>';
	echo							'<td>';
	echo								__('Developing, maintaining and supporting this plugin requires time. Why not do any of the following:', 'no-disposable-email') . '<br />';
	echo								'&nbsp;&bull;&nbsp;&nbsp;' . __('Check out our <a href="http://www.joeswebtools.com/wordpress-plugins/">other plugins</a>.', 'no-disposable-email') . '<br />';
	echo								'&nbsp;&bull;&nbsp;&nbsp;' . __('Link to the <a href="http://www.joeswebtools.com/wordpress-plugins/no-disposable-email/">plugin homepage</a>, so other folks can find out about it.', 'no-disposable-email') . '<br />';
	echo								'&nbsp;&bull;&nbsp;&nbsp;' . __('Give this plugin a good rating on <a href="http://wordpress.org/extend/plugins/no-disposable-email/">WordPress.org</a>.', 'no-disposable-email') . '<br />';
	echo								'&nbsp;&bull;&nbsp;&nbsp;' . __('Support further development with a <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=5162912">donation</a>.', 'no-disposable-email') . '<br />';
	echo							'</td>';
	echo						'</tr>';
	echo						'<tr>';
	echo							'<th scope="row" valign="top">';
	echo								'<b>' . __('Need support?', 'no-disposable-email') . '</b>';
	echo							'</th>';
	echo							'<td>';
	echo									__('If you have any problems or good ideas, please talk about them on the <a href="http://www.joeswebtools.com/wordpress-plugins/no-disposable-email/">plugin homepage</a>.', 'no-disposable-email') . '<br />';
	echo							'</td>';
	echo						'</tr>';
	echo						'<tr>';
	echo							'<th scope="row" valign="top">';
	echo								'<b>' . __('Credits', 'no-disposable-email') . '</b>';
	echo							'</th>';
	echo							'<td>';
	echo									__('<a href="http://www.joeswebtools.com/wordpress-plugins/no-disposable-email/">No Disposable Emails</a> is developped by Philippe Paquet for <a href="http://www.joeswebtools.com/">Joe\'s Web Tools</a>. This plugin is released under the GNU GPL version 2.', 'no-disposable-email') . '<br />';
	echo							'</td>';
	echo						'</tr>';
	echo					'</table>';
	echo				'</form>';
	echo			'</div>';
	echo		'</div>';
	echo	'</div>';

	// Page wrapper end
	echo '</div>';
}





/*
 *
 *	add_no_disposable_email_menu
 *
 */

function add_no_disposable_email_menu() {

	// Add the menu page
	add_submenu_page('options-general.php', 'No Disposable Emails', 'No Disposable Emails', 10, __FILE__, 'no_disposable_email_page');
}

add_action('admin_menu', 'add_no_disposable_email_menu');





/*
 *
 *	no_disposable_email_activate
 *
 */

function no_disposable_email_activate() {

	global $wpdb;

	// Load language file
	$current_locale = get_locale();
	if(!empty($current_locale)) {
		$mo_file = dirname(__FILE__) . '/languages/no-disposable-email-' . $current_locale . ".mo";
		if(@file_exists($mo_file) && is_readable($mo_file)) {
			load_textdomain('no-disposable-email', $mo_file);
		}
	}

	// Create the table name
	$table_name = $wpdb->prefix . 'no_disposable_email';

	// Create the table if it doesn't already exist
	$results = $wpdb->query("CREATE TABLE IF NOT EXISTS $table_name(id INT(11) NOT NULL AUTO_INCREMENT, domain VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id), KEY domain (domain));");

	// Get the blacklist
	$blacklist_array = file(dirname(__FILE__) . '/no-disposable-email.dat');
	$blacklist_size = sizeof($blacklist_array);

	// Import the blacklist
	for($i = 0; $i < $blacklist_size; $i++) {
		$blacklist_current = strtolower(trim($blacklist_array[$i]));
		if(NULL == $wpdb->get_var("SELECT domain FROM $table_name WHERE domain='" . $wpdb->escape($blacklist_current) . "'")) {
			$wpdb->query("INSERT INTO $table_name(domain) VALUES('" . $wpdb->escape($blacklist_current) . "')");
		}
	}

	// Set the default message
	$message = get_option('no_disposable_email_message');
	if(empty($message)) {
		update_option('no_disposable_email_message', __('<strong>ERROR</strong>: Please, do not use a disposable email to register.'));
	}
}

register_activation_hook(__FILE__, 'no_disposable_email_activate');

?>