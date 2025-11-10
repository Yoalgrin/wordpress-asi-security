<?php
if ( ! defined('ABSPATH') ) exit;

class ASI_Admin {

    public function init() {
        add_action('admin_menu',  [$this, 'menu']);
        add_action('admin_menu',  [$this, 'rename_primary_submenu'], 999);
        add_action('admin_init',  [$this, 'settings']);
        add_action('admin_init',  [$this, 'maybe_handle_export']);
    }

    public function menu() {
        $parent_slug = 'asi_settings';

        add_menu_page(
            __('ASI Security', 'anti-sqli'),
            __('ASI Security', 'anti-sqli'),
            'manage_options',
            $parent_slug,
            [$this, 'render_settings'],
            'dashicons-shield',
            3
        );

        // Sous-menu : CSRF
        add_submenu_page(
            $parent_slug,
            __('Protection CSRF', 'anti-sqli'),
            __('CSRF', 'anti-sqli'),
            'manage_options',
            'asi_csrf',
            [$this, 'render_csrf_page']
        );

        // Sous-menu : Logs
        add_submenu_page(
            $parent_slug,
            __('Tentatives bloquées', 'anti-sqli'),
            __('Logs', 'anti-sqli'),
            'manage_options',
            'asi_logs',
            [$this, 'render_logs_page']
        );
    }

    public function settings() {
        // === Settings ASI principal ===
        register_setting('asi_group', ASI_OPTION_KEY, [
            'type'              => 'array',
            'sanitize_callback' => [$this, 'sanitize_settings'],
            'default'           => asi_get_settings(),
        ]);

        add_settings_section('asi_section_main', __('Paramètres', 'anti-sqli'), function() {
            echo '<p>'.esc_html__('Configurer le comportement du bloqueur.', 'anti-sqli').'</p>';
        }, 'asi_settings');

        add_settings_field('enabled', __('Activer le bloqueur', 'anti-sqli'), function () {
            $s = asi_get_settings();
            $val = ! empty($s['enabled']) ? 1 : 0;
            printf(
                '<label><input type="checkbox" name="%1$s[enabled]" value="1" %2$s> %3$s</label>',
                esc_attr(ASI_OPTION_KEY),
                checked(1, $val, false),
                esc_html__('Activer', 'anti-sqli')
            );
        }, 'asi_settings', 'asi_section_main');

        add_settings_field('whitelist_ips', __('Liste blanche IP', 'anti-sqli'), function () {
            $s = asi_get_settings();
            $val = $s['whitelist_ips'] ?? '';
            printf(
                '<textarea name="%1$s[whitelist_ips]" rows="4" class="large-text code" placeholder="%2$s">%3$s</textarea>',
                esc_attr(ASI_OPTION_KEY),
                esc_attr('127.0.0.1' . PHP_EOL . '192.168.0.1'),
                esc_textarea($val)
            );
            echo '<p class="description">'.esc_html__('Une IP par ligne.', 'anti-sqli').'</p>';
        }, 'asi_settings', 'asi_section_main');

        add_settings_field('whitelist_uas', __('Liste blanche User-Agent', 'anti-sqli'), function () {
            $s = asi_get_settings();
            $val = $s['whitelist_uas'] ?? '';
            printf(
                '<textarea name="%1$s[whitelist_uas]" rows="4" class="large-text code" placeholder="%2$s">%3$s</textarea>',
                esc_attr(ASI_OPTION_KEY),
                esc_attr('Googlebot' . PHP_EOL . 'Bingbot'),
                esc_textarea($val)
            );
            echo '<p class="description">'.esc_html__('Chaque ligne est recherchée dans le User-Agent (match partiel, insensible à la casse).', 'anti-sqli').'</p>';
        }, 'asi_settings', 'asi_section_main');

        add_settings_field('add_noindex', __('Noindex sur 403', 'anti-sqli'), function () {
            $s = asi_get_settings();
            $val = ! empty($s['add_noindex']) ? 1 : 0;
            printf(
                '<label><input type="checkbox" name="%1$s[add_noindex]" value="1" %2$s> %3$s</label>',
                esc_attr(ASI_OPTION_KEY),
                checked(1, $val, false),
                esc_html__('Ajouter noindex (SEO safe)', 'anti-sqli')
            );
        }, 'asi_settings', 'asi_section_main');

        add_settings_field('custom_message', __('Message 403 (HTML)', 'anti-sqli'), function () {
            $s = asi_get_settings();
            $val = $s['custom_message'] ?? '';
            printf(
                '<textarea name="%1$s[custom_message]" rows="6" class="large-text code">%2$s</textarea>',
                esc_attr(ASI_OPTION_KEY),
                esc_textarea($val)
            );
            echo '<p class="description">'.esc_html__('Accepté : HTML sûr (nettoyé).', 'anti-sqli').'</p>';
        }, 'asi_settings', 'asi_section_main');

        add_settings_field('detect_xss', __('Détection XSS (optionnelle)', 'anti-sqli'), function () {
            $s = asi_get_settings();
            $val = ! empty($s['detect_xss']) ? 1 : 0;
            printf(
                '<label><input type="checkbox" name="%1$s[detect_xss]" value="1" %2$s> %3$s</label>',
                esc_attr(ASI_OPTION_KEY),
                checked(1, $val, false),
                esc_html__('Activer la détection XSS (peut créer des faux positifs)', 'anti-sqli')
            );
        }, 'asi_settings', 'asi_section_main');

        // === Réglages CSRF (séparés, page slug 'asi_csrf') ===
        register_setting('asi_csrf_group', ASI_CSRF_OPTION_KEY, [
            'type'              => 'array',
            'default'           => asi_get_csrf_settings(),
            'sanitize_callback' => [$this, 'sanitize_csrf_settings'],
        ]);

        add_settings_section('asi_csrf_main', __('Paramètres CSRF', 'anti-sqli'), function () {
            echo '<p>'.esc_html__('Active un garde-fou CSRF pour tes actions admin_post et recommande l’usage de nonces.', 'anti-sqli').'</p>';
        }, 'asi_csrf');

        add_settings_field('enable_guard', __('Activer le garde-fou', 'anti-sqli'), function () {
            $s = asi_get_csrf_settings();
            $val = ! empty($s['enable_guard']) ? 1 : 0;
            printf(
                '<label><input type="checkbox" name="%1$s[enable_guard]" value="1" %2$s> %3$s</label>',
                esc_attr(ASI_CSRF_OPTION_KEY),
                checked(1, $val, false),
                esc_html__('Bloquer les POST d’actions listées ci-dessous si le nonce est absent.', 'anti-sqli')
            );
        }, 'asi_csrf', 'asi_csrf_main');

        add_settings_field('guard_actions', __('Actions protégées', 'anti-sqli'), function () {
            $s = asi_get_csrf_settings();
            $val = $s['guard_actions'] ?? '';
            printf(
                '<textarea name="%1$s[guard_actions]" rows="4" class="large-text code" placeholder="%2$s">%3$s</textarea>',
                esc_attr(ASI_CSRF_OPTION_KEY),
                esc_attr("asi_export_csv\nasi_delete_log"),
                esc_textarea($val)
            );
            echo '<p class="description">'.esc_html__('Une action admin_post par ligne (ex: asi_export_csv).', 'anti-sqli').'</p>';
        }, 'asi_csrf', 'asi_csrf_main');

        add_settings_field('enforce_ajax', __('AJAX sécurisé', 'anti-sqli'), function () {
            $s = asi_get_csrf_settings();
            $val = ! empty($s['enforce_ajax']) ? 1 : 0;
            printf(
                '<label><input type="checkbox" name="%1$s[enforce_ajax]" value="1" %2$s> %3$s</label>',
                esc_attr(ASI_CSRF_OPTION_KEY),
                checked(1, $val, false),
                esc_html__('Rappeler l’utilisation de check_ajax_referer côté handlers.', 'anti-sqli')
            );
        }, 'asi_csrf', 'asi_csrf_main');

        add_settings_field('short_nonce_life', __('Durée du nonce réduite', 'anti-sqli'), function () {
            $s = asi_get_csrf_settings();
            $val = ! empty($s['short_nonce_life']) ? 1 : 0;
            printf(
                '<label><input type="checkbox" name="%1$s[short_nonce_life]" value="1" %2$s> %3$s</label>',
                esc_attr(ASI_CSRF_OPTION_KEY),
                checked(1, $val, false),
                esc_html__('Limiter la durée des nonces à ~2h sur les pages ASI.', 'anti-sqli')
            );
        }, 'asi_csrf', 'asi_csrf_main');

        add_settings_field('error_message', __('Message d’erreur', 'anti-sqli'), function () {
            $s = asi_get_csrf_settings();
            $val = $s['error_message'] ?? '';
            printf(
                '<textarea name="%1$s[error_message]" rows="3" class="large-text">%2$s</textarea>',
                esc_attr(ASI_CSRF_OPTION_KEY),
                esc_textarea($val)
            );
        }, 'asi_csrf', 'asi_csrf_main');
    }

    public function sanitize_settings($input) {
        $out = asi_get_settings();

        $out['enabled']        = empty($input['enabled']) ? 0 : 1;
        $out['add_noindex']    = empty($input['add_noindex']) ? 0 : 1;
        $out['whitelist_ips']  = isset($input['whitelist_ips']) ? sanitize_textarea_field($input['whitelist_ips']) : '';
        $out['whitelist_uas']  = isset($input['whitelist_uas']) ? sanitize_textarea_field($input['whitelist_uas']) : '';
        $out['detect_xss']     = empty($input['detect_xss']) ? 0 : 1;

        // HTML autorisé pour le message 403
        $allowed = [
            'a'    => ['href'=>[], 'title'=>[], 'target'=>[], 'rel'=>[], 'class'=>[], 'style'=>[]],
            'div'  => ['class'=>[], 'style'=>[]],
            'span' => ['class'=>[], 'style'=>[]],
            'p'    => ['class'=>[], 'style'=>[]],
            'h1'   => ['class'=>[], 'style'=>[]],
            'h2'   => ['class'=>[], 'style'=>[]],
            'h3'   => ['class'=>[], 'style'=>[]],
            'strong'=>[], 'b'=>[], 'em'=>[], 'i'=>[], 'br'=>[],
            'ul'   => [], 'ol' => [], 'li' => [],
            'button' => ['type'=>[], 'class'=>[], 'style'=>[], 'aria-label'=>[]],
        ];
        $out['custom_message'] = isset($input['custom_message']) ? wp_kses($input['custom_message'], $allowed) : '';

        return $out;
    }

    /**
     * Gère l'export CSV avant tout affichage (hook admin_init).
     * Permet d'envoyer des headers proprement sans erreur "headers already sent".
     */
    public function maybe_handle_export() {

        if ( empty($_GET['export_csv']) || ! check_admin_referer('asi_export_csv') ) {
            return;
        }

        // Vérifier page + capacité
        $page = trim($_GET['page'] ?? '');
        if ( $page !== 'asi_logs' ) {
            return;
        }
        if ( ! current_user_can('manage_options') ) {
            wp_die('Accès refusé.', 'Erreur', ['response' => 403]);
        }

        // Vérif CSRF manuelle (message personnalisé)
        if ( empty($_GET['_wpnonce']) || ! wp_verify_nonce($_GET['_wpnonce'], 'asi_export_csv') ) {
            asi_render_403('CSRF'); // ← même “fenêtre” que le SQLi
        }

        global $wpdb;
        $table = $wpdb->prefix . 'asi_logs';

        // Récupère les données (Possibilité de restreindre par date/type si nécessaire)
        $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC", ARRAY_A);

        // Expédition du CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=asi-logs-' . date('Y-m-d_Hi') . '.csv');

        $out = fopen('php://output', 'w');
        if ( $rows && is_array($rows) ) {
            // entête
            fputcsv($out, array_keys($rows[0]));
            foreach ($rows as $r) {
                // s'assurer que chaque valeur est une chaîne
                $row = array_map(function($v){ return (string) $v; }, $r);
                fputcsv($out, $row);
            }
        } else {
            // header row si aucune ligne (pour garder un fichier valide)
            fputcsv($out, ['id','created_at','type','ip','user_agent','request_uri','hits','meta']);
        }

        fclose($out);
        exit; // Important : stoppe WP après l'envoi du CSV
    }

    public function render_settings() {
        if ( ! current_user_can('manage_options') ) return;
        require ASI_PLUGIN_DIR . 'admin/views/settings-page.php';
    }

    public function render_logs_page() {
        if ( ! current_user_can('manage_options') ) return;

        global $wpdb;
        $table = $wpdb->prefix . 'asi_logs';

        $per_page = 20;
        $paged   = max( 1, absint($_GET['paged'] ?? 1) );
        $search  = sanitize_text_field( wp_unslash($_GET['s'] ?? '') );
        $order   = strtoupper( sanitize_key($_GET['order'] ?? 'DESC') );
        $order   = in_array($order, ['ASC','DESC'], true) ? $order : 'DESC';

        $offset  = ($paged - 1) * $per_page;

        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        $rows  = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} ORDER BY created_at {$order} LIMIT %d OFFSET %d",
            $per_page, $offset
        ));

        $export_url = wp_nonce_url(admin_url('admin.php?page=asi_logs&export_csv=1'), 'asi_export_csv');

        echo '<div class="wrap"><h1>Logs Anti SQLi/XSS</h1>';
        echo '<p><a class="button" href="'.esc_url($export_url).'">Exporter CSV</a></p>';

        echo '<table class="widefat fixed striped"><thead><tr>
            <th>Date</th><th>Type</th><th>IP</th><th>User-Agent</th><th>URI</th><th>Hits</th>
          </tr></thead><tbody>';

        if ($rows) {
            foreach ($rows as $r) {
                echo '<tr>';
                echo '<td>'.esc_html($r->created_at).'</td>';
                echo '<td>'.esc_html($r->type).'</td>';
                echo '<td>'.esc_html($r->ip).'</td>';
                echo '<td>'.esc_html(mb_strimwidth($r->user_agent, 0, 120, '…')).'</td>';
                echo '<td>'.esc_html(mb_strimwidth($r->request_uri, 0, 120, '…')).'</td>';
                echo '<td>'.esc_html($r->hits).'</td>';
                echo '<td>';
                echo '<form method="post" action="'.esc_url( admin_url('admin-post.php') ).'" onsubmit="return confirm(\'Supprimer ce log ?\');" style="display:inline">';
                echo '<input type="hidden" name="action" value="asi_delete_log">';
                echo '<input type="hidden" name="log_id" value="'.(int)$r->id.'">';
                wp_nonce_field('asi_delete_log_action', 'asi_delete_log_nonce');
                echo '<button class="button-link-delete" type="submit">Supprimer</button>';
                echo '</form>';
                echo '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="6">Aucun enregistrement.</td></tr>';
        }
        echo '</tbody></table>';

        // Pagination
        $total_pages = max(1, (int) ceil($total / $per_page));
        if ($total_pages > 1) {
            echo '<div class="tablenav"><div class="tablenav-pages">';
            echo paginate_links([
                'base'      => add_query_arg('paged','%#%', admin_url('admin.php?page=asi_logs')),
                'format'    => '',
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'total'     => $total_pages,
                'current'   => $paged,
                'type'      => 'plain',
            ]);
            echo '</div></div>';
        }
        echo '</div>';
    }

    public function rename_primary_submenu() {
        // Renomme le 1er sous-menu auto-généré par WP pour le parent 'asi_settings'
        $parent_slug = 'asi_settings';
        global $submenu;

        if ( ! isset($submenu[$parent_slug]) || ! is_array($submenu[$parent_slug]) ) {
            return;
        }

        // Par défaut, le premier item est celui auto-ajouté par WP
        if ( isset($submenu[$parent_slug][0][0]) ) {
            $submenu[$parent_slug][0][0] = __('Anti-SQLi', 'anti-sqli');
        }
    }

    public function render_csrf_page() {
        if ( ! current_user_can('manage_options') ) return;
        $view = ASI_PLUGIN_DIR . 'admin/views/csrf-page.php';
        if ( is_readable($view) ) {
            require $view;
        } else {
            echo '<div class="wrap"><h1>ASI Security — CSRF</h1><p>Fichier introuvable : admin/views/csrf-page.php</p></div>';
        }
    }

    public function sanitize_csrf_settings($input) {
        $out = asi_get_csrf_settings(); // helper défini dans includes/helpers.php
        $out['enable_guard']     = empty($input['enable_guard']) ? 0 : 1;
        $out['enforce_ajax']     = empty($input['enforce_ajax']) ? 0 : 1;
        $out['short_nonce_life'] = empty($input['short_nonce_life']) ? 0 : 1;
        $out['guard_actions']    = isset($input['guard_actions']) ? sanitize_textarea_field($input['guard_actions']) : '';
        $out['error_message']    = isset($input['error_message']) ? sanitize_textarea_field($input['error_message']) : 'Requête non autorisée (CSRF).';
        return $out;
    }
}
