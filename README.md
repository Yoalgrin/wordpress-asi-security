# ASI Security

Protection contre SQLi, XSS (optionnel) et CSRF côté admin.

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

## 📄 Licence

MIT — fais-en bon usage avec attribution.

---

**Auteur :** GVG — v1.0.0
