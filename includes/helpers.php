<?php
if ( ! defined('ABSPATH') ) exit;

/** Réglages principal (SQLi/XSS) */
if ( ! function_exists('asi_get_settings') ) {
    function asi_get_settings() {
        $defaults = [
            'enabled'        => 1,
            'whitelist_ips'  => '',
            'whitelist_uas'  => '',
            'add_noindex'    => 1,
            'custom_message' => '',
            'detect_xss'     => 0,
        ];
        $opt = get_option(ASI_OPTION_KEY, []);
        return wp_parse_args(is_array($opt) ? $opt : [], $defaults);
    }
}

/** Whitelist IP */
if ( ! function_exists('asi_is_ip_whitelisted') ) {
    function asi_is_ip_whitelisted(string $ip, array $settings): bool {
        if (empty($settings['ip_whitelist'])) return false;
        $list = preg_split('/\r\n|\r|\n/', $settings['ip_whitelist']);
        return in_array($ip, array_map('trim', $list), true);
    }
}

/** Whitelist UA */
if ( ! function_exists('asi_is_ua_whitelisted') ) {
    function asi_is_ua_whitelisted(string $ua, array $settings): bool {
        if (empty($settings['ua_whitelist'])) return false;
        $patterns = preg_split('/\r\n|\r|\n/', $settings['ua_whitelist']);
        foreach ($patterns as $p) {
            $p = trim($p);
            if ($p && stripos($ua, $p) !== false) {
                return true;
            }
        }
        return false;
    }
}

/** HTML autorisé pour le message 403 perso */
if ( ! function_exists('asi_allowed_html') ) {
    function asi_allowed_html() {
        return [
            'a' => ['href'=>[], 'title'=>[], 'target'=>[], 'rel'=>[], 'class'=>[], 'style'=>[]],
            'div'=>['class'=>[], 'style'=>[]],
            'span'=>['class'=>[], 'style'=>[]],
            'p'=>['class'=>[], 'style'=>[]],
            'strong'=>[], 'b'=>[], 'em'=>[], 'i'=>[], 'br'=>[],
            'ul'=>[], 'ol'=>[], 'li'=>[],
            'button'=>['type'=>[], 'class'=>[], 'style'=>[], 'aria-label'=>[]],
        ];
    }
}

/** Détection XSS simple (optionnelle) */
if ( ! function_exists('asi_detect_xss') ) {
    function asi_detect_xss($s) {
        return (bool) preg_match('/<(script|img|svg|iframe)\b|onerror\s*=|onload\s*=|javascript:/i', (string)$s);
    }
    /** Réglages CSRF */
    if ( ! function_exists('asi_get_csrf_settings') ) {
        function asi_get_csrf_settings() {
            $defaults = [
                'enable_guard'     => 1,                                   // activer le garde-fou CSRF
                'guard_actions'    => "asi_export_csv\nasi_delete_log",    // actions admin_post protégées (une par ligne)
                'enforce_ajax'     => 1,                                   // rappel d'utiliser check_ajax_referer()
                'short_nonce_life' => 0,                                   // durée raccourcie des nonces (optionnel)
                'error_message'    => 'Requête non autorisée (CSRF).',      // message affiché si échec
            ];
            $opt = get_option(ASI_CSRF_OPTION_KEY, []);
            return wp_parse_args(is_array($opt) ? $opt : [], $defaults);
        }
    }
    if ( ! function_exists('asi_render_403') ) {
        function asi_render_403($type = 'CSRF', $message = null) {
            $s   = asi_get_settings();
            $ip  = sanitize_text_field( $_SERVER['REMOTE_ADDR']      ?? '' );
            $ua  = substr( sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ), 0, 255 );
            $uri = substr( esc_url_raw( $_SERVER['REQUEST_URI']      ?? '' ), 0, 2048 );

            $msg = $message ?: ($s['custom_message'] ?: '<h1>403</h1><p>Accès refusé.</p>');
            // placeholders {TYPE} {IP} {UA} {URI} {DATE}
            $msg = strtr($msg, [
                '{TYPE}' => $type,
                '{IP}'   => (string) $ip,
                '{UA}'   => (string) $ua,
                '{URI}'  => (string) $uri,
                '{DATE}' => current_time('mysql'),
            ]);

            status_header(403);
            nocache_headers();
            if ( ! headers_sent() && ! empty($s['add_noindex']) ) {
                header('X-Robots-Tag: noindex');
            }

            $label = ($type === 'XSS') ? 'Requête bloquée (XSS suspectée)'
                : ($type === 'SQLi' ? 'Requête bloquée (SQLi suspectée)' : 'Requête non autorisée (CSRF)');

            echo '<!doctype html><html lang="fr"><head><meta charset="utf-8">';
            echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
            if ( ! empty($s['add_noindex']) ) echo '<meta name="robots" content="noindex">';
            echo '<title>403 — Accès refusé</title></head>';
            echo '<body style="font-family:system-ui;max-width:800px;margin:6rem auto;padding:0 1rem">';
            echo '<div><strong>'.esc_html($label).'</strong></div>';
            if ( function_exists('asi_allowed_html') ) {
                echo wp_kses($msg, asi_allowed_html());
            } else {
                echo esc_html($msg);
            }
            echo '<p style="margin-top:1.25rem"><a href="'.esc_url(home_url('/')).'">Retour à l’accueil</a></p>';
            echo '</body></html>';
            exit;
        }
    }


}
