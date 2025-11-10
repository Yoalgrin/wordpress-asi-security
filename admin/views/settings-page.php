<?php if ( ! defined('ABSPATH') ) exit; ?>
<div class="wrap">
    <h1 class="wp-heading-inline">ASI Security</h1>
    <hr class="wp-header-end">

    <form method="post" action="options.php">
        <?php
        // Groupe et sections de la page Anti-SQLi UNIQUEMENT
        settings_fields('asi_group');
        do_settings_sections('asi_settings');
        submit_button(__('Enregistrer', 'anti-sqli'));
        ?>
    </form>

    <hr>
    <h2><?php esc_html_e('Test rapide', 'anti-sqli'); ?></h2>
    <p><?php esc_html_e('Essayez une URL avec un morceau suspect (ex: ?id=1%20UNION%20SELECT ou ?q=<script>alert(1)</script>) pour voir le 403 et les logs.', 'anti-sqli'); ?></p>
</div>
