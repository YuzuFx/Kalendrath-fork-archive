# Retour d'agent : CI GitHub saine (2026-11-21)

Statut : **partiel**. La partie locale est faite et validée par `actionlint`. La partie GitHub (audit distant, PR, activation d'Actions, observation de la CI, protection de `main`) n'a **pas pu être faite** : `gh` n'est pas authentifié et le jeton Git du poste n'a pas la portée `workflow`.

## 1. Outils

- `gh` et `actionlint` absents, **Homebrew absent** du poste. Binaires officiels téléchargés dans le dossier temporaire de la session (`gh` 2.105.0, `actionlint` 1.7.12, arm64), rien d'installé sur le système.
- `gh auth status` : « You are not logged into any GitHub hosts ». Pas de connexion tentée, aucun jeton lu.
- Conséquence : audit distant (tâche 2) **non réalisé** ; tâches 5, 6, 7 non réalisées.

## 2. Audit distant

Non réalisé (pas d'authentification). À refaire une fois `gh auth login` fait : visibilité, fork/parent, `actions/permissions`, runners, noms des secrets et variables, protection de `main`, exécutions passées, Dependabot / secret scanning / code scanning.

Constat local seulement : `origin` est `https://github.com/YuzuFx/Kalendrath.git`, `origin/main` = `bd29f70b`.

## 3. Décisions workflow par workflow (branche `chore/ci-saine`, depuis `origin/main`)

| Workflow | Décision | Raison |
|---|---|---|
| `deploy-main.yml` | supprimé | runner `[self-hosted, ctogx]`, déploie vers `main.ogamex.dev` |
| `deploy-release.yml` | supprimé | idem, `release.ogamex.dev` |
| `pr-preview.yml` | supprimé | runner `ctogx-preview`, domaines `*.preview.ogamex.dev`, comptes de test `@ogamex.dev` |
| `pr-preview-teardown.yml` | supprimé | pendant du précédent |
| `dependabot-auto-merge.yml` | supprimé | pas de dépendance à l'amont, mais `pull_request_target` avec écriture et fusion automatique sans contrôle obligatoire : risque inutile tant que `ci-ok` n'est pas requis. Réintroductible plus tard, une fois `main` protégé |
| `run-docker-compose-prod.yml` | gardé, modifié | test de fumée du build de production sur runner GitHub, aucune dépendance de l'amont. Devenu réutilisable, permissions `contents: read`, lancé seulement si Docker, composer, `app/`, `config/` ou `.github/` changent |
| `run-tests-docker-compose.yml` | gardé, modifié | 3 shards Pest + tests de concurrence ; réutilisable ; `retry` à 3 tentatives conservé tel quel (à reconsidérer : il peut masquer un test instable) |
| `run-laravel-pint-code-style-checker.yml` | gardé, modifié | réutilisable, permissions minimales |
| `run-phpstan-code-analysis.yml` | gardé, modifié | idem |
| `run-rector-code-analysis.yml` | gardé, modifié | idem (n'avait pas de filtre de chemin) |
| `run-tests-flaky-detection.yml` | gardé, `permissions: contents: read` ajoutées | il était déjà uniquement en `workflow_dispatch` |
| `ci.yml` | **créé** | point d'entrée : job `changes` (le back est testé sauf si seuls `docs/`, `site-prototype/`, `tools/`, `.claude/`, `.agents/`, `.codex/` ou des `.md` changent ; logique inversée par prudence), `concurrency` annulant les exécutions obsolètes, appel des cinq contrôles, job final **`ci-ok`** (`if: always()`, accepte `success` et `skipped`, échoue sur `failure` ou `cancelled`) |

Les actions tierces étaient déjà épinglées par SHA avec commentaire de version ; conservé. Le filtre de chemins vit dans `ci.yml` plutôt que dans `on.paths` : c'est ce qui évite qu'un contrôle requis reste « en attente » (plan de déploiement, §3.1).

`actionlint` : aucun avertissement sur l'ensemble de `.github/workflows/`.

Autres constats :
- `docs/decision-architecture-cible.md` : la mention « MySQL 8 » est **déjà corrigée** sur `origin/main` (MariaDB 11.3, note de revue). Rien à modifier.
- `.github/dependabot.yml` (npm, docker, composer, github-actions, hebdomadaire) laissé tel quel. Les PR Dependabot ne seront plus fusionnées automatiquement.
- `.dockerignore` : n'excluait **pas** `.env` ; ajout de `.env`, `.env.*` avec exceptions `.env.example` et `.env.example-prod`. À vérifier au premier build : si un script d'image attendait un `.env` copié, il faudra l'adapter.

## 4. Sécurité

- Seuls `.env.example` et `.env.example-prod` sont versionnés, avec des mots de passe d'exemple (`DB_PASSWORD`, `MAIL_PASSWORD`, `REVERB_APP_SECRET`) : valeurs de développement, à ne jamais réutiliser en production.
- `nginx/ssl/nginx.key` : clé privée **versionnée** (certificat de développement hérité d'OGameX). À confirmer qu'elle ne sert qu'en local ; ne jamais l'utiliser en production, et envisager de la régénérer à la volée.
- Aucun jeton GitHub, clé AWS, clé `sk-` ou Slack trouvé par motifs simples (recherche non exhaustive, pas de scan d'historique).

## 5. Publication : bloquée

`git push -u origin chore/ci-saine` rejeté : « refusing to allow a Personal Access Token to create or update workflow `.github/workflows/ci.yml` without `workflow` scope ». Le jeton Git du poste n'a pas la portée `workflow`. Pas contourné. La branche existe **localement** dans le worktree `/Users/greg/Projets/OgameX/.claude/worktrees/agent-a54932e4f630ca345` (commit `9c0c6344` puis le commit de ce rapport). **PR : non ouverte. CI : non observée. Actions : état inconnu. Protection de `main` : non touchée.**

## 6. Reste à faire par Gregory

1. `gh auth login` (navigateur) avec la portée `workflow` (`gh auth refresh -s workflow` si déjà connecté), ou donner à Git un jeton ayant cette portée. Ensuite, depuis ce worktree : `git push -u origin chore/ci-saine`, puis `gh pr create --base main`.
2. Auditer le dépôt (visibilité, Actions, secrets, runners) et **vérifier avant tout que les workflows de déploiement hérités encore présents sur `main` ne tournent pas** : tant que la PR n'est pas fusionnée, `deploy-main.yml` & co. existent sur `main`. Si Actions est activé, les désactiver individuellement (`gh workflow disable <nom>`) ou fusionner la PR en premier.
3. Fusionner la PR (hors périmètre de l'agent), activer Actions (idéalement `allowed_actions=selected` : actions de GitHub plus `shivammathur/*`, `nick-fields/*`, `dependabot/*` si réintroduit).
4. Après une CI verte : protéger `main` avec le contrôle requis **`ci-ok`** (seul), sans « enforce for admins » pour que le propriétaire puisse contourner ; PR obligatoire optionnelle (0 relecture requise en solo).
5. Décider public ou privé, détachement du fork, secrets de déploiement (non touchés par cette tâche).

## 7. Limites

Aucune exécution réelle de la CI : la syntaxe est validée par `actionlint`, pas le comportement (le build Docker en CI, le job `changes`, le comportement de `ci-ok` sur des contrôles ignorés restent à confirmer à la première exécution). Les tests ne sont pas lancés localement.
