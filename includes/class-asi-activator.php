<?php
if ( ! defined('ABSPATH') ) exit;

class ASI_Activator {

    // incrémente si tu modifies le schéma
    const DB_VERSION = '1.1';

    public static function activate() {
        // Options par défaut
        if ( false === get_option(ASI_OPTION_KEY) ) {
            add_option(ASI_OPTION_KEY, asi_get_settings(), '', false);
        }

        // Créer / mettre à niveau la table logs
        self::create_or_update_logs_table();

        // Mémoriser la version de schéma
        update_option('asi_db_version', self::DB_VERSION, false);
    }

    /**
     * Crée ou met à jour la table des logs via dbDelta.
     * - v1.0: created_at, ip, user_agent, request_uri, hits
     * - v1.1: + type, + meta
     */
    private static function create_or_update_logs_table() {
        global $wpdb;

        $table = $wpdb->prefix . 'asi_logs';
        $charset_collate = $wpdb->get_charset_collate();

        // Schéma cible (dbDelta mettra à jour si colonnes manquent)
        $sql = "CREATE TABLE {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            created_at DATETIME NOT NULL,
            type VARCHAR(20) NOT NULL,
            ip VARCHAR(64) NOT NULL,
            user_agent TEXT NOT NULL,
            request_uri MEDIUMTEXT NOT NULL,
            hits INT UNSIGNED NOT NULL DEFAULT 1,
            meta TEXT NULL,
            PRIMARY KEY  (id),
            KEY idx_created_at (created_at),
            KEY idx_type (type),
            KEY idx_ip (ip(32))
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
