<?php
if ( ! defined('ABSPATH') ) exit;

class ASI_Deactivator {
    public static function deactivate() {
        wp_clear_scheduled_hook('asi_cron_purge');
    }
}

