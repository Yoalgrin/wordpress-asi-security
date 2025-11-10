# TESTS.md — Tests manuels

## 1) SQLi en GET
- Ouvre : `http://localhost/?q=' OR '1'='1`
- Attendu :
  - Mode *Log only* : une ligne apparaît dans les logs, pas de blocage.
  - Mode *Block* : page 403 affichée.

## 2) SQLi en POST
- Envoie un POST avec `name=Robert'); DROP TABLE users; --`
- Attendu : log ou 403 selon le mode.

## 3) Whitelist IP
- Ajoute ton IP dans `whitelist_ips` (une IP par ligne).
- Refais 1) et 2) depuis cette IP.
- Attendu : pas de log / pas de blocage.

## 4) Whitelist User-Agent
- Ajoute un pattern dans `whitelist_uas` (ex: `^Googlebot`).
- Requête avec cet UA.
- Attendu : pas de log / pas de blocage.

## 5) Export CSV des logs
- Page Logs → Export CSV.
- Attendu : fichier CSV lisible (en-têtes ok).
