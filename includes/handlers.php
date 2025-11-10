<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Handler suppression d'un log (POST)
 * URL cible: /wp-admin/admin-post.php?action=asi_delete_log
 */
add_action('admin_post_asi_delete_log', function () {
    global $wpdb;
    $wpdb->insert($wpdb->prefix.'asi_logs', [
        'created_at'=>current_time('mysql'),
        'type'=>'CSRF','ip'=>$_SERVER['REMOTE_ADDR']??'',
        'user_agent'=>$_SERVER['HTTP_USER_AGENT']??'',
        'request_uri'=>$_SERVER['REQUEST_URI']??'',
        'hits'=>1,'meta'=>null,
    ], ['%s','%s','%s','%s','%s','%d','%s']);

    if ( ! current_user_can('manage_options') ) {
        asi_render_403('CSRF', 'Accès refusé.');
    }

    if ( empty($_POST['asi_delete_log_nonce']) || ! wp_verify_nonce($_POST['asi_delete_log_nonce'], 'asi_delete_log_action') ) {
        asi_render_403('CSRF'); // même page 403 que ton guard
    }

    $log_id = isset($_POST['log_id']) ? (int) $_POST['log_id'] : 0;
    if ( ! $log_id ) {
        wp_safe_redirect( admin_url('admin.php?page=asi_logs&asi_msg=not_deleted') );
        exit;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'asi_logs';
    $redirect = esc_url_raw( $_GET['redirect'] ?? '' );
    $allowed_hosts = [ parse_url( home_url(), PHP_URL_HOST ) ];
    $host = $redirect ? parse_url($redirect, PHP_URL_HOST) : '';

    if ( ! $redirect || ! in_array($host, $allowed_hosts, true) ) {
        $redirect = admin_url('admin.php?page=asi_logs&asi_msg=deleted');
    }

    wp_safe_redirect( $redirect );
    exit;

});
add_action('asi_cron_purge', function(){
    global $wpdb;
    $table = $wpdb->prefix.'asi_logs';
    $retention = max(1, absint( (get_option('asi_settings')['retention'] ?? 90) ));
    $wpdb->query( $wpdb->prepare(
        "DELETE FROM {$table} WHERE created_at < (NOW() - INTERVAL %d DAY)", $retention
    ));
});
