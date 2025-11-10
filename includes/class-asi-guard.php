<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Intercepte les requêtes publiques et bloque SQLi et (optionnellement) XSS.
 */
class ASI_Guard {

    // Pattern SQLi robuste (espaces/commentaires/encodage)
    private $pattern = '/(?:\bunion\b(?:\/\*.*?\*\/|\s)+select\b|\bor\b\s*1\s*=\s*1\b|\bor\b\s*\'1\'\s*=\s*\'1\'\b|\bsleep\(\s*\d+\s*\)|--|#|\/\*)/i';

    public function init() {
        // Multi-hooks pour garantir l’exécution
        add_action('init',              [$this, 'maybe_block'], 0);
        add_action('template_redirect', [$this, 'maybe_block'], 0);


    }

    public function maybe_block() {
        // Front only
        if ( is_admin() || (defined('DOING_CRON') && DOING_CRON) || (php_sapi_name() === 'cli') ) return;

        $settings = asi_get_settings();
        if ( empty($settings['enabled']) ) return;

        $ua  = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip  = $_SERVER['REMOTE_ADDR'] ?? '';
        $uri = $_SERVER['REQUEST_URI'] ?? '';

        // Whitelist IP/UA (Pour forcer le test, commenter la ligne suivante)
        if ( asi_is_ip_whitelisted($ip, $settings) || asi_is_ua_whitelisted($ua, $settings) ) return;

        // Concatène URI + GET + POST
        $raw = $uri;
        foreach (['_GET', '_POST'] as $super) {
            foreach ($GLOBALS[$super] ?? [] as $k => $v) {
                if (is_array($v)) $v = wp_json_encode($v);
                $raw .= ' ' . $k . '=' . (string) $v;
            }
        }
        if ($raw === '') return;

        // Normalise/décode avant détection
        $hay = $this->normalize_input($raw);

        // 1) SQLi (regex)
        if ( preg_match($this->pattern, $hay) ) {
            $this->block_and_log('SQLi', $ip, $ua, $uri);
        }

        // 1.b) filet de sécurité
        if ( strpos($hay, ' or 1=1') !== false || strpos($hay, ' union select ') !== false ) {
            $this->block_and_log('SQLi', $ip, $ua, $uri);
        }

        // 2) XSS
        if ( ! empty($settings['detect_xss']) && function_exists('asi_detect_xss') && asi_detect_xss($hay) ) {
            $this->block_and_log('XSS', $ip, $ua, $uri);
        }
    }

    private function block_and_log(string $type, $ip, $ua, $uri) {
        global $wpdb;

        // Rate-limit simple
        $key  = 'asi_hits_' . md5($ip);
        $hits = (int) get_transient($key);
        set_transient($key, $hits + 1, 15 * MINUTE_IN_SECONDS);

        // Log DB
        $table = $wpdb->prefix . 'asi_logs';
        $wpdb->insert($table, [
            'created_at'  => current_time('mysql'),
            'type'        => $type,
            'ip'          => $ip,
            'user_agent'  => (string) $ua,
            'request_uri' => (string) $uri,
            'hits'        => $hits + 1,
            'meta'        => null,
        ], ['%s','%s','%s','%s','%s','%d','%s']);

        // 403 + noindex éventuel
        status_header(403);
        $settings = asi_get_settings();
        if ( ! headers_sent() && ! empty($settings['add_noindex']) ) {
            header('X-Robots-Tag: noindex');
        }

        $message = $settings['custom_message'] ?: '<h1>403</h1><p>Accès refusé.</p>';
        $label   = ($type === 'XSS') ? 'Requête bloquée (XSS suspectée)' : 'Requête bloquée (SQLi suspectée)';
        $label = 'Requête bloquée (' . strtoupper($type) . ' suspectée)';
        // Remplacement simple de placeholders dans le message personnalisé
        $replacements = [
            '{TYPE}' => strtoupper($type),
            '{CSRF}' => 'CSRF',   // alias si tu as écrit {CSRF}
            '{SQLI}' => 'SQLi',
            '{XSS}'  => 'XSS',
            '{IP}'   => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
            '{UA}'   => (string)($_SERVER['HTTP_USER_AGENT'] ?? ''),
            '{URI}'  => (string)($_SERVER['REQUEST_URI'] ?? ''),
            '{DATE}' => current_time('mysql'),
        ];
        if ($message) {
            $message = strtr($message, $replacements);
        }


        $html  = '<!doctype html><html lang="fr"><head><meta charset="utf-8">';
        $html .= '<meta name="viewport" content="width=device-width,initial-scale=1">';
        if ( ! empty($settings['add_noindex']) ) $html .= '<meta name="robots" content="noindex">';
        $html .= '<title>403 — Accès refusé</title></head><body style="font-family:system-ui;max-width:800px;margin:6rem auto;padding:0 1rem">';
        $html .= '<div><strong>' . esc_html($label) . '</strong></div>';
        if ( function_exists('asi_allowed_html') ) $html .= wp_kses($message, asi_allowed_html());
        else $html .= esc_html($message);
        $html .= '<p style="margin-top:1.25rem"><a href="' . esc_url(home_url('/')) . '">Retour à l’accueil</a></p>';
        $html .= '</body></html>';

        echo $html;
        exit;
    }

    // Normalisation : %xx, +, commentaires, espaces, casse
    private function normalize_input($s) {
        $s = (string) $s;
        $s = str_replace('+', '%20', $s);
        for ($i = 0; $i < 3; $i++) {
            $decoded = rawurldecode($s);
            if ($decoded === $s) break;
            $s = $decoded;
        }
        $s = preg_replace('/\/\*.*?\*\//s', ' ', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        return strtolower($s);
    }
}
