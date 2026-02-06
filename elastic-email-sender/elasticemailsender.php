<?php

if (!function_exists('is_plugin_active')) {
	include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

/*
 * Plugin Name: Elastic Email Sender
 * Version: 1.2.22
 * Plugin URI: https://wordpress.org/plugins/elastic-email-sender/
 * Description: This plugin reconfigures the <code>wp_mail()</code> function to send email using API (via Elastic Email) instead of SMTP and creates an options page that allows you to specify various options.
 * Author: Elastic Email Inc.
 * Author URI: https://elasticemail.com
 * Text Domain: elasticemailsender
 * Domain Path: /languages
 * License: GPLv2 or later
 */

/**
 * @author    Elastic Email Inc.
 * @copyright Elastic Email, 2025, All Rights Reserved
 * This code is released under the GPL licence version 3 or later, available here
 * https://www.gnu.org/licenses/gpl.txt
 */

/* Version check */
global $wp_version;
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variable is local to this file
$exit_msg = 'ElasticEmail Sender requires WordPress 5.0 or newer. <a href="' . esc_url('http://codex.wordpress.org/Upgrading_WordPress') . '">Please update!</a>';


if (version_compare($wp_version, "5.0", "<")) {
	wp_die(wp_kses_post($exit_msg));
}

if (!class_exists('eemail')) {
	require_once('defaults/function.reset_pass.php');
	require_once('class/ees_mail.php');
	eemail::on_load(__DIR__);
}
update_option('ees_plugin_dir_name', plugin_basename(__DIR__));

/* ----------- ADMIN ----------- */
if (is_admin()) {

	register_activation_hook(__FILE__, 'elasticemailsender_activate');
	register_uninstall_hook(__FILE__, 'elasticemailsender_uninstall');

	add_action('wp_ajax_sender_send_test', 'eeSenderTestMsg');
	add_action('wp_ajax_clean_error_log', 'eeCleanErrorLog');

	require_once 'class/ees_admin.php';
	new eeadmin5120420526(__DIR__);

}

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Function name matches plugin name for activation hook
function elasticemailsender_activate()
{
	update_option('daterangeselect', 'last-7d');
	update_option('ee_mimetype', 'auto');
	create_elasticemail_log_table();
}

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Function is used only internally for plugin activation
function create_elasticemail_log_table()
{
	global $wpdb;
	$table = $wpdb->prefix . 'elasticemail_log';
	$charset_collate = $wpdb->get_charset_collate();

	$table = esc_sql($table);
	$query = "CREATE TABLE IF NOT EXISTS {$table} (
		id INT(11) AUTO_INCREMENT,
		date TEXT(120),
		error TEXT(255),
		PRIMARY KEY(id)
	) {$charset_collate};";

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared
	// Direct query is necessary during plugin activation to create database table. wpdb->prepare() cannot be used for DDL statements like CREATE TABLE.
	$wpdb->query($query);
	// phpcs:enable
}

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Function is used only internally for plugin deactivation
function drop_elasticemail_log_table()
{
	global $wpdb;
	$table = $wpdb->prefix . 'elasticemail_log';
	$table = esc_sql($table);
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	// Direct query is necessary during plugin deactivation to drop database table. wpdb->prepare() cannot be used for DDL statements like DROP TABLE.
	$wpdb->query("DROP TABLE IF EXISTS {$table}");
	// phpcs:enable
}

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Function is used only internally
function clean_elasticemail_log_table()
{
	global $wpdb;
	$table = $wpdb->prefix . 'elasticemail_log';
	$table = esc_sql($table);
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	// Direct query is necessary to truncate error log table. wpdb->prepare() cannot be used for DDL statements like TRUNCATE TABLE.
	$wpdb->query("TRUNCATE TABLE {$table}");
	// phpcs:enable
}

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Function name matches plugin name for uninstall hook
function elasticemailsender_uninstall()
{
	drop_elasticemail_log_table();

	$optionsList = [
		'ees-connecting-status',
		'ee_options',
		'ee_send-email-type',
		'ees_plugin_dir_name',
		'ee_config_from_name',
		'ee_config_from_email',
		'ee_from_email',
		'ee_channel_name',
		'daterangeselect',
		'ee_config_override_wooCommerce',
		'ee_config_woocommerce_original_email',
		'ee_config_woocommerce_original_name',
		'ee_is_created_channels',
		'ee_mimetype'
	];

	foreach ($optionsList as $option) {
		delete_option($option);
	}
}

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Function name is used for AJAX hook registration
function eeCleanErrorLog()
{
	// Check nonce
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- wp_verify_nonce() validates the nonce directly, sanitization is not needed
	if (!isset($_POST['nonce']) || !wp_verify_nonce(wp_unslash($_POST['nonce']), 'ee_clean_error_log')) {
		wp_send_json_error(['message' => esc_html__('Security check failed.', 'elasticemailsender')], 403);
		return;
	}

	// Check user capabilities
	if (!current_user_can('manage_options')) {
		wp_send_json_error(['message' => esc_html__('You do not have permission to perform this action.', 'elasticemailsender')], 403);
		return;
	}

	clean_elasticemail_log_table();
	wp_send_json_success(['message' => esc_html__('Error log cleared successfully.', 'elasticemailsender')]);
}
