<?php
// Appelé quand on supprime le plugin (pas juste désactivation)
if ( ! defined('WP_UNINSTALL_PLUGIN') ) exit;

// Supprimer les options
delete_option('asi_settings');
delete_option('asi_db_version');



global $wpdb;
$table = $wpdb->prefix . 'asi_logs';
$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );

