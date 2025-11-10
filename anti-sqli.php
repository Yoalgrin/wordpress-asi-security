<?php
/**
 * Plugin Name: ASI Security
 * Description: Protection contre SQLi, XSS (optionnel) et CSRF côté admin.
 * Version: 1.0.0
 * Author: GVG
 * Text Domain: asi-security
 */
if ( ! defined('ABSPATH') ) exit;

define('ASI_VERSION',      '1.0.0');
define('ASI_PLUGIN_FILE',   __FILE__);
define('ASI_PLUGIN_DIR',    plugin_dir_path(__FILE__));
define('ASI_PLUGIN_URL',    plugin_dir_url(__FILE__));
define('ASI_OPTION_KEY',   'asi_settings');
define('ASI_CSRF_OPTION_KEY', 'asi_csrf_settings');

// Charge UNIQUEMENT les helpers ici
require_once ASI_PLUGIN_DIR . 'includes/helpers.php';



// Démarrage : charge les classes au bon endroit (évite les includes côté front)
add_action('plugins_loaded', function () {
    // Front/commun : Guard
    require_once ASI_PLUGIN_DIR . 'includes/class-asi-guard.php';
    (new ASI_Guard())->init();

    // Charge les handlers (admin_post, ajax, etc.)
    require_once ASI_PLUGIN_DIR . 'includes/handlers.php';

    // Admin : charge la classe admin uniquement en back-office
    if ( is_admin() ) {
        require_once ASI_PLUGIN_DIR . 'admin/class-asi-admin.php';
        (new ASI_Admin())->init();
    }
});
add_action('plugins_loaded', function(){
    $dir = plugin_dir_path(__FILE__);
    $hash = '';
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $f) {
        if ($f->isFile() && substr($f->getFilename(), -4) === '.php') {
            $hash = hash_hmac('sha256', $hash . sha1_file($f->getPathname()), AUTH_SALT);
        }
    }
    // Compare à une valeur stockée à l’activation (option 'asi_sig')
    $prev = get_option('asi_sig');
    if ($prev && $prev !== $hash) {
        // log l’écart
        // (évite de bloquer l’exécution, juste alerte)
    }
});
// Vérification d'intégrité des fichiers du plugin
add_action('plugins_loaded', function(){
    $dir = plugin_dir_path(__FILE__);
    $hash = '';
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $f) {
        if ($f->isFile() && substr($f->getFilename(), -4) === '.php') {
            $hash = hash_hmac('sha256', $hash . sha1_file($f->getPathname()), AUTH_SALT);
        }
    }
    $prev = get_option('asi_sig');
    if ($prev && $prev !== $hash) {
        // Ici : log l’écart dans la table asi_logs
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix.'asi_logs',
            [
                'created_at'=>current_time('mysql'),
                'type'=>'tampering',
                'ip'=>'local',
                'user_agent'=>'',
                'request_uri'=>'plugin integrity mismatch',
                'hits'=>1,
                'meta'=>null,
            ],
            ['%s','%s','%s','%s','%s','%d','%s']
        );
    }
});
