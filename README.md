# ASI Security

ASI Security est un plugin WordPress d’apprentissage dédié à l’exploration de mécanismes de sécurité Web : détection de patterns SQLi, journalisation en base, vérification CSRF côté admin et gestion optionnelle d’un mode XSS.

## Contexte 
J’ai commencé ce plugin comme exercice pour apprendre WordPress et la sécurité basique : détecter des patterns SQLi et bloquer.
En testant, j’ai constaté que la détection par regex seule n’est pas suffisante (faux positifs et contournements possibles).
J’ai donc étendu le plugin pour ajouter : logs détaillés, whitelist IP/UA, vérifications CSRF côté admin, mode *log only* pour éviter d’interrompre des utilisateurs légitimes, et une page d’administration pour gérer ces réglages.  
**Remarque** — Ce plugin est pédagogique et n’est pas un remplaçant d’un WAF ni d’une logique de sécurité côté base de données (`prepare()`, ORM) ou d’un reverse proxy type ModSecurity.

## 🚀 Installation (développeur·euse)

1. **Cloner** le dépôt dans `wp-content/plugins/asi-security` :
   ```bash
   cd wp-content/plugins
   git clone <URL_DU_DEPOT> asi-security
   ```
2. Aller dans l’admin WordPress → **Extensions** → **Activer “ASI Security”**.

## 🧪 En local (WAMP/MAMP/Laragon/Docker)

- Place le dossier du plugin dans `wp-content/plugins/` de ton WordPress local.
- Active le plugin depuis l’admin.
- Regarde le fichier principal : `asi-security-plugin/anti-sqli/anti-sqli.php`

## 📁 Contenu du dépôt

```
asi-security-plugin/
    anti-sqli/
        anti-sqli.php
        doc.text
        uninstall.php
        admin/
            class-asi-admin.php
            index.php
            views/
                csrf-page.php
                index.php
                settings-page.php
        includes/
            class-asi-activator.php
            class-asi-deactivator.php
            class-asi-guard.php
            handlers.php
            helpers.php
            index.php
```

## ⚙️ Compatibilité

- WordPress 6.x
- PHP 8.x

## 📝 Développement

- Code simple, lisible, sans dépendances.
- N’hésite pas à ouvrir une **issue** pour signaler un bug ou proposer une amélioration.

## 🧩 TODO / Améliorations prévues

Pistes d’amélioration et correctifs identifiés lors d’un audit rapide.
J’ajoute pour chaque item une courte note sur la priorité (H = haute, M = moyenne, L = faible).

- [ ] **Corriger la whitelist** (`helpers.php`) : incohérence entre clés `ip_whitelist` / `whitelist_ips`.
- [ ] **Unifier le Text Domain** (`asi-security`) et charger via `load_plugin_textdomain()`.
- [ ] **Sécuriser les actions admin** avec `current_user_can()` + `check_admin_referer()`.
- [ ] **Ajouter un mode "Log only"** (enregistrer sans bloquer).
- [ ] **Ajouter un niveau de sensibilité** (Off / Log / Block / Paranoid).
- [ ] **Exporter les logs en CSV** depuis la page d’admin.
- [ ] **Ajouter des nonces sur tous les formulaires** pour éviter le CSRF.
- [ ] **Mettre à jour la documentation TESTS.md** avec les cas d’essai (SQLi, CSRF, whitelist).
- [ ] **Captures d’écran** : interface admin + page des logs.
- [ ] **Option “Settings” sur la ligne du plugin** dans l’admin WordPress.
- [ ] **Refactor regex SQLi** pour réduire les faux positifs.


## 📄 Licence

MIT — fais-en bon usage avec attribution.

---

**Auteur :** GVG — v1.0.0
