<?php if ( ! defined('ABSPATH') ) exit; ?>
<div class="wrap">
    <h1 class="wp-heading-inline">ASI Security — CSRF</h1>
    <hr class="wp-header-end">

    <form method="post" action="options.php">
        <?php
        settings_fields('asi_csrf_group');   // le groupe enregistré
        do_settings_sections('asi_csrf');    // la page/slug des sections/champs
        submit_button(__('Enregistrer les réglages CSRF', 'anti-sqli'));
        ?>
    </form>

    <hr>
    <h2><?php esc_html_e('Aide rapide', 'anti-sqli'); ?></h2>
    <ul style="list-style:disc;margin-left:20px">
        <li>Formulaires : <code>wp_nonce_field('mon_action','mon_nonce')</code></li>
        <li>Handlers : <code>check_admin_referer('mon_action','mon_nonce')</code></li>
        <li>Liens GET : <code>wp_nonce_url(..., 'mon_action', '_wpnonce')</code></li>
        <li>AJAX admin : <code>check_ajax_referer('asi_ajax_nonce','security')</code></li>
    </ul>
</div>
