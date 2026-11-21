# Prompt Codex n° 3 : implémentation des représailles des camps PvE (2026-11-21)

*À coller dans Codex. **Facultatif si le budget manque** : Claude peut aussi l'implémenter. Décision de Gregory (2026-11-21) : on implémente au plus vite et on équilibre pendant l'alpha avec les retours des joueurs ; le passage au simulateur recommandé dans le rapport de conception n'est donc **pas un préalable**, mais tous les paramètres restent réglables. Branche : `feat/representailles-pve`, créée depuis `feat/alpha-visuels-equipements` (qui contient déjà le catalogue, les visuels et la fusion de `main`). Pousser avant de s'arrêter.*

---

Tu travailles sur Kalendrath (Laravel 13 / PHP 8.5, tests Pest). Lis `CLAUDE.md` (et `AGENTS.md`), `docs/contrat-alpha.md` (§2, §3 critères 4, 8 et 10, §6), puis **ta propre conception** : `docs/archives/retours-agents-2026-11-21-representailles-bots-pve.md` (en entier : règles, plafonds, découpage en 7 tâches, tableau des paramètres `pve.retaliation.*`). Lis ensuite `app/Services/Pve/PveCampService.php`, `app/Services/Pve/PveRaidLootService.php`, `config/pve.php`, `app/GameMissions/AttackMission.php`, `app/GameMissions/BattleEngine/`, `tests/Feature/Items/PveRaidLootTest.php`, `tests/Feature/FleetDispatch/FleetDispatchAttackTest.php`.

## Décisions de Gregory

- Les camps font des **représailles dès l'alpha**. Ils produisent des ressources et recrutent leur garnison avec leurs propres ressources (fait le 2026-11-21 : plus de `restock` ni de `respawn_at`).
- Un bot fort ne doit **jamais** raser un joueur : les garde-fous de ton rapport (plafonds de pertes, protection des nouveaux joueurs, cadence, aucune destruction de bâtiments, de héros ni d'équipement) sont des **invariants**.
- **Implémenter vite**, équilibrer pendant l'alpha : tous les chiffres dans `config/pve.php` sous `retaliation`, avec les valeurs initiales de ton tableau, et un **interrupteur d'urgence** (`pve.retaliation.enabled`, modifiable sans redéploiement via les réglages d'administration si le dépôt le permet, sinon par configuration).
- Aucune logique de jeu dans les contrôleurs : règles dans `app/Services/Pve` ou `app/GameMissions`, test Pest pour chaque règle. Ne rien développer en Blade durable.
- Le jeu est en français d'abord, anglais aussi : messages, rapports et alertes en clés de traduction `resources/lang/{fr,en}/` (vouvoiement, ton épique avec légèreté discrète, lexique Kalendrath : Bois, Fer, Mana, Nourriture ; pseudo du bot `[TBD]`, ne jamais inventer de nom de bot).

## Ta tâche

Implémente les **tâches 2 à 7** de ton découpage, dans cet ordre, en t'arrêtant de façon propre (code livrable, tests verts) après chacune :
1. Registre des intentions par migration **additive** ; paramètres `pve.retaliation.*` et interrupteur ; **tâche 1 allégée** : pas de modèle de simulateur, seulement les bornes et validations de configuration.
2. Enregistrement de l'intention à la résolution d'une victoire de pillage admissible, dans la transaction de la mission, idempotent.
3. Réservations exclusives, éligibilité et protections, cadences, échéances persistées, traitement **sans page ouverte** (jobs ou commande planifiée avec rattrapage, clés d'idempotence).
4. Départ du détachement et débit du Mana, intégration au recrutement (les absents comptent dans l'effectif).
5. Résolution avec le moteur configuré (PHP et Rust), validation du résultat **avant** toute écriture, abandon complet du résultat s'il dépasse les plafonds.
6. Butin de représailles normalisé avant écriture, comptabilité conservée au retour (une seule fois), camp supprimé en route.
7. API (lecture de l'intention, de l'ETA et du rapport) et messages persistants. **Pas d'écran React dans cette tâche** : l'interface mobile et ordinateur est un chantier séparé ; prévois des charges utiles sérialisées stables et testées.

Dans ton rapport, signale tout écart à ton rapport de conception et ce qui reste à décider. Si une tâche te paraît trop risquée pour le moteur existant, **arrête-toi avant elle** plutôt que de forcer, et explique pourquoi.

## Contraintes

- Chaîne du dépôt avant de rendre : Rector, Pint (uniquement sur tes fichiers, **jamais sur tout le dépôt**), PHPStan, puis tests concernés (`tests/Feature/Items`, `tests/Feature/Hero`, `tests/Feature/FleetDispatch`) et le test de concurrence des missions. Les 1109 tests hérités doivent rester verts.
- Ne modifie pas l'attaque ordinaire entre joueurs : les routes héritées PvP restent fermées côté serveur. Le comportement actuel du raid sortant ne change pas.
- Pas de commit sur `main`.

## Livrable

`docs/archives/retours-agents-2026-11-21-implementation-representailles-pve.md` : tâches faites, tests ajoutés, écarts, paramètres finaux, risques restants, ce que Gregory doit trancher, scénarios à passer au simulateur pendant l'alpha. Pousse ta branche et indique son nom.
