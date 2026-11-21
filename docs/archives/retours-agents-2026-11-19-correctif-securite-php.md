# Correctif de sécurité PHP — 2026-11-19

## Constat

- Conteneur `ogamex-ogamex-app-1` avant correctif : **PHP 8.5.10**, vulnérable à PHP2Shell (corrigé en 8.5.12) et à la release du 2026-11-19 (corrigée en 8.5.13).
- PHP2Shell (CVE-2026-109618, CVE-2026-109619) : RCE sans authentification dans le runtime, CVSS v4 9.2, exploitation active signalée dès le 21 octobre. Correctifs : 8.5.12, 8.4.27, 8.3.36, 8.2.35.
- Release du 19 novembre : 18 CVE (session, moteur, PHP-FPM, Date, DOM, SOAP, Phar…). Correctifs : 8.5.13, 8.4.28, 8.3.37, 8.2.36.
- Sources : pages Aikido (PHP2Shell et release du 19 nov.), lues par un sous-agent ; Dark Reading et The Hacker News répondaient 403. Aucune source indépendante de l'éditeur n'a recoupé les numéros de version, à confirmer sur php.net (ChangeLog-8.php#8.5.13).

## Exposition de Kalendrath

- Sessions : le jeu utilise `SESSION_DRIVER=database` (Laravel), pas les sessions natives PHP, et aucun `session_start`/`ini_set` de sérialiseur dans `app/`, `routes/`, `config/`.
- PHP-FPM derrière nginx (`fastcgi_pass ogamex-app:9000`), sans `fastcgi_keep_conn` : le chemin de request smuggling (CVE-2026-129289) décrit n'est pas exploitable dans cette configuration. À revérifier si on ajoute un proxy qui garde les connexions FastCGI ouvertes.
- Le chemin PHP2Shell via `session.upload_progress` (activé par défaut) concernait en revanche toute installation servant des requêtes web.

## Corrections appliquées

1. Image reconstruite avec `docker compose build --pull` : PHP **8.5.13** (build du 2026-11-19), conteneurs relancés.
2. `php/local.ini` : `session.upload_progress.enabled=0` et `fatal_error_backtraces=0` (contournements recommandés, vérifiés dans le conteneur).
3. `composer update laravel/framework league/commonmark 'symfony/*' --with-dependencies` : `composer audit` passe de plusieurs avis (symfony/yaml, http-foundation, http-kernel, mime, routing, serializer, league/commonmark, laravel/framework) à **aucun avis**. Ancien lockfile sauvegardé hors dépôt.

## Tests

Suite complète après mise à jour : 1306 réussis, **2 échecs** :
- `ExpeditionItemLootTest` (l'objet n'apparaît pas dans le message) ;
- `MessagesTest` (le texte de `PveRaidItemFound` ne contient pas `:item_code`).

Les deux touchent au travail non commité de la branche `feat/alpha-objets` (messages d'objets, fichiers de langue). Rattachement aux dépendances non exclu formellement : le test n'a pas été relancé avec l'ancien lockfile.

## Reste à faire

- Appliquer la même reconstruction sur tout serveur de production (`docker-compose.prod.yml`) avant l'ouverture publique.
- Épingler la version PHP du Dockerfile ou mettre en place une veille (avis PHP, `composer audit` en CI).
- Authentifier `gh` (`gh auth login`) pour les opérations GitHub.
