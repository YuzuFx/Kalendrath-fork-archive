# Relecture critique : modèle de données des objets

19 novembre 2026. Rapport d'agent (Claude), relecture indépendante de [retours-agents-2026-11-19-schema-donnees-objets.md](retours-agents-2026-11-19-schema-donnees-objets.md) (désignée ci-dessous « la note »). Chaque affirmation de la note a été contrôlée dans le code de `main` après la fusion de `feat/heros-suite` (commit `82117149`), dans `origin/feat/kaelite-deux-soldes` et dans les branches de worktree poussées. Aucune migration, aucun code créé, rien de commité. Aucune suite de tests lancée.

Les décisions de Gregory du 19/11 sont intégrées : (a) alpha sans métiers, composants, fabrication, marché ni monnaie de commerce, que le schéma anticipe sans les implémenter ; (b) deux archétypes en alpha, Guerrier et Sorcier ; (c) douze emplacements : tête, collier, épaules, cape, torse, poignets, mains, ceinture, jambes, pieds, deux anneaux ; (d) le butin vient des expéditions **et** du pillage de bots PvE ; (e) les effets d'équipement **et** de talents s'appliquent à la production **et** au combat ; (f) serveur ×5.

---

## Verdict d'ensemble

**Bonne base, à corriger avant toute migration.** L'ossature est juste et doit être conservée : propriété au royaume, séparation définition / exemplaire, `code` stable, ULID, journal idempotent en ajout seul, tables additives, aucune colonne sur les tables de l'amont. Les constats de la §0 sont exacts à une nuance près. En revanche, trois points deviennent **bloquants** avec les décisions du 19/11 : le modèle d'emplacement ne supporte pas deux anneaux, le combat n'est plus « hors alpha », et le butin des bots PvE n'a ni source, ni clé d'idempotence, ni table de butin. S'y ajoutent un trou dans les contraintes `CHECK` et un calcul d'effets de production qui ignore les effets de portée « Ville » (gouverneur).

| Section | Verdict |
|---|---|
| §0 Constats | **Exacts** (lignes vérifiées), une nuance sur 0.3, un constat manquant (suppression de comptes et de cités bloquée) |
| §1 Principes | **Valides**, P4 à corriger (`value_mode`, `target`), P6 à compléter (transitions) |
| §2.1 Schéma alpha | **À corriger** : emplacements (bloquant), `CHECK` incomplets, `allowed_archetypes`, `created_operation_id` |
| §2.2 Créé mais vide | **Acceptable** (`reservation_*` justifié, `durability` facultatif) |
| §2.3 Prévu | **Valide** et conforme à la décision (a) ; ajouter les bots PvE et la table de butin |
| §3 Migration | **Simplifiable** : la copie M4 est surdimensionnée pour 8 objets de démo, et son `COALESCE` devient faux avec les nouveaux emplacements |
| §4 Amont | **Valide** |
| §5 Effets | Production **incomplète** (portée Ville, plafonds, déclencheurs de recalcul) ; combat **à refaire** (désormais en alpha) ; butin d'expédition **exact** |
| §6 Risques et questions | **Valide** ; 4 questions tranchées le 19/11, 2 risques manquants |

---

## Défauts classés par gravité

### Bloquants (à corriger avant d'écrire la moindre migration)

**B1. Le modèle d'emplacement ne supporte pas deux anneaux, et confond type d'objet et position.**
- La note fait porter un `slot` à la définition (`item_definitions.slot`, note l. 83) et un `equipped_slot` à l'exemplaire, avec `UNIQUE(holder_hero_id, equipped_slot)` (l. 130), puis M4 recopie la valeur de la définition dans `equipped_slot` (`COALESCE(hero_equipment.slot, equipment_items.slot)`, l. 218). Le service actuel fait de même : `EquipmentSlot::tryFrom($item->slot)` puis `slot = $slot->value` (`app/Services/Hero/HeroEquipmentService.php:33,50`).
- Avec deux anneaux, un objet de type « anneau » doit pouvoir occuper la position `ring_1` **ou** `ring_2`. Si `equipped_slot` reçoit la valeur de la définition (`ring`), l'index unique interdit le second anneau. Si la définition dit `ring_1`, un anneau ne peut aller qu'à un seul doigt.
- La liste du 19/11 **supprime les deux emplacements d'arme** (`weapon_main_hand`, `weapon_off_hand`, `app/Enums/EquipmentSlot.php`) et les objets de démo qui les occupent (`SeedDemoHero.php:349-350`). Le GDD est en retard : il décrit toujours 8 emplacements avec armes (`docs/game-design-document.md:535,564`), et `config/heroes.php` › `advisors.slots` les liste aussi.
- **Correction** : distinguer deux vocabulaires. `item_definitions.slot_type` (`EquipmentSlotType` : `head`, `neck`, `shoulders`, `cloak`, `chest`, `wrists`, `hands`, `belt`, `legs`, `boots`, `ring`, soit 11 types) et `kingdom_items.equipped_slot` (`EquipmentSlot` : les mêmes valeurs, sauf `ring` remplacé par `ring_1` et `ring_2`, soit 12 positions). Une méthode `EquipmentSlotType::positions()` donne les positions admises (`ring` → `[ring_1, ring_2]`, les autres → elles-mêmes). Le service choisit la position libre (ou celle demandée par le joueur). L'index `UNIQUE(holder_hero_id, equipped_slot)` reste alors correct. On garde les valeurs déjà en base (`head`, `chest`, `hands`, `belt`, `legs`, `boots`) pour ne rien migrer, et l'on mappe « pieds » sur `boots` : seul le libellé affiché change.

**B2. Le combat est désormais dans l'alpha, et la §5.4 l'en exclut.**
- La décision (e) impose que talents et équipement agissent sur le combat. La note ne prévoit que des « points repérés » (l. 291-294). Les lignes citées sont exactes : `PhpBattleEngine.php:40-42` (attaquants) et `:58-60` (défenseurs), `RustBattleEngine.php:117-119` et `:149-151`, `AttackerFleet.php:95-99`, et `ObjectPropertyService::calculateProperty()` travaille en pourcentage entier (`Abstracts/ObjectPropertyService.php:39-45`).
- **Défaut supplémentaire trouvé dans l'amont** : dans `RustBattleEngine.php:132-151`, les caractéristiques de **toutes** les armées défenderesses (garnison du propriétaire et renforts alliés en défense) sont calculées avec `$defenderPlayer`, le propriétaire de la cité, alors que `DefenderFleetResult` porte un `ownerId` (`DefenderFleetResult.php:39`). Le moteur PHP, lui, utilise bien `$defenderFleet->player` (`PhpBattleEngine.php:58-60`). Si l'on injecte le bonus du héros à cet endroit, le héros du défenseur renforcerait aussi les troupes de ses alliés, et seulement avec le moteur Rust (le moteur par défaut, `AttackMission.php:123-130`). Le bonus doit donc être résolu **par armée** (`ownerId`, `fleetMissionId`), jamais par cité.
- **Correction** : voir la mise à jour de la note, §M5. Injection au niveau du moteur, par armée, et non dans les `*PropertyService` (qui changeraient aussi l'affichage, les simulateurs et les missiles).

**B3. Le butin des bots PvE n'est pas modélisé.**
- La note ne connaît qu'une source de butin en alpha, l'expédition (l. 7, 121, 276-289). Il manque : la valeur `source_type` du pillage, la clé d'idempotence d'un combat, la règle d'attribution en cas d'attaque groupée, la condition de victoire, et la façon d'identifier une cible « bot » sans toucher aux tables `users` et `planets` de l'amont.
- Faits établis dans le code : un combat d'attaque est exécuté par la seule mission initiatrice d'une attaque groupée (`AttackMission.php:93`) et produit un `AttackerFleetResult` par armée participante (`:227-237`), chacune avec son `fleet_mission_id` ; `BattleResult` n'a pas de champ « vainqueur » (`BattleEngine/Models/BattleResult.php`) ; les PNJ d'expédition ont des identifiants négatifs (`NPCPlayerService.php:50-54`) ; les camps du GDD §5.8A se régénèrent après pillage (`game-design-document.md:573-582`), donc un même camp est pillé de nombreuses fois.
- **Correction** : voir la mise à jour de la note, §M3 et §M4.

### Majeurs (à corriger dans la même livraison)

**M1. Les `CHECK` d'état laissent passer des combinaisons incohérentes.**
`CHECK ((state = 'equipped') = (holder_hero_id IS NOT NULL AND equipped_slot IS NOT NULL))` (note l. 134) accepte `state = 'available'`, `holder_hero_id = 7`, `equipped_slot = NULL` : la partie droite vaut FAUX, la gauche aussi, la contrainte est satisfaite. La phrase « nuls ensemble hors équipement » n'est donc pas garantie par la base. Même trou pour `reservation_type` / `reservation_id` (l. 176). Une telle ligne fausserait la clé étrangère composite (un « porteur » sans emplacement). **Correction** : ajouter `CHECK ((holder_hero_id IS NULL) = (equipped_slot IS NULL))` et `CHECK ((reservation_type IS NULL) = (reservation_id IS NULL))`. Sous MariaDB, une expression `CHECK` qui vaut NULL est acceptée : ces formes en `IS NULL` ne valent jamais NULL, elles sont donc sûres.

**M2. Les effets de production ne couvrent que la portée Empire, et les plafonds sont appliqués par portée.**
- Le résolveur proposé s'appuie sur `forEmpire()` (note l. 255), qui ne retient que la portée `empire` (`HeroEffectsService.php:67,72`). Or les talents existants utilisent massivement la portée `city`, celle de la cité gouvernée par le héros (`SeedDemoHero.php:252,273,289,304`, `TalentEffectScope.php`). Avec la décision (e), un talent ou un objet « +x % de Bois dans la cité gouvernée » ne s'appliquerait jamais.
- `forHero()` plafonne **chaque portée séparément** (`HeroEffectsService.php:48-52`), puis `forEmpire()` ajoute les conseillers. Une cité gouvernée recevrait donc jusqu'à 10 % (portée Ville) **plus** 10 % (Empire) **plus** 5 % (conseillers) de bonus de production, alors que le GDD fixe un plafond de 10 % par axe (`config/heroes.php` › `axis_caps.production_bonus_percent`). **Correction** : le plafond s'applique au total final, par cité et par axe (et par cible si `target` est retenu).

**M3. Les recalculs de production ne sont prévus que pour équiper et déséquiper.**
La séquence « solder au taux ancien → modifier → recalculer toutes les cités » (note l. 268-274, exacte : `PlanetService::update()` `:1318`, `updateResources()` `:1405`, `updateResourceProductionStats()` `:2123`) doit s'appliquer à **tout** ce qui change un effet de production : allocation et réinitialisation de talents (`HeroTalentService::allocate()` `:42`, `respec()` `:98`), nomination ou retrait d'un gouverneur (`heroes.planet_id`), recrutement ou remplacement du héros principal, nomination d'un conseiller. Sans cela, la décision (e) produit des bonus rétroactifs ou non appliqués. **Correction** : un seul point d'entrée, `HeroEffectsChanged` (service ou événement synchrone) appelé par chacun de ces chemins, qui exécute la séquence.

**M4. `allowed_archetypes` en JSON est une liste par objet qu'il faudra réécrire à chaque nouvel archétype.**
L'alpha n'a que Guerrier et Sorcier ; quatre archétypes arrivent ensuite (`HeroArchetype.php`). Avec une liste blanche par définition, ajouter le Rôdeur oblige à revoir chaque définition du catalogue. **Correction** : une colonne `equip_class` (`heavy`, `light`, `arcane`, `any`… valeurs à valider) sur la définition, et la table de correspondance archétype → classes autorisées dans `config/heroes.php`. Ajouter un archétype devient une ligne de configuration. La règle « un Sorcier ne porte pas de plaque » s'exprime une fois.

**M5. `value_mode` contredit le vocabulaire commun, et `target` nullable empêche toute unicité.**
- Les types d'effets portent déjà leur unité dans leur nom (`production_bonus_percent`, `config/heroes.php` › `axis_caps`), et les talents n'ont pas de mode (`2026_09_23_000001`). Un effet `production_bonus_percent` en mode `flat` est un non-sens que le schéma autoriserait. **Correction** : supprimer `value_mode` ; une valeur fixe s'exprime par un autre `effect_type` (`…_flat`).
- `target` NULL signifie « tout l'axe » : un `UNIQUE(item_definition_id, effect_type, effect_scope, target)` serait inopérant (les NULL sont distincts sous MariaDB), et deux lignes identiques se cumuleraient silencieusement. **Correction** : `target VARCHAR(64) NOT NULL DEFAULT '*'`, puis l'index unique. Aligner aussi la précision : `DECIMAL(10,4)` ici contre `DECIMAL(8,2)` sur les talents (`2026_09_23_000001`, `2026_10_30_000002`).

**M6. Suppression de comptes et de cités déjà bloquée sur `main` (constat absent de la note).**
`heroes.user_id` et `heroes.planet_id` ont des clés étrangères sans action (`2026_09_15_000001_create_heroes_table.php:17-23`), donc RESTRICT. Conséquences sur `main` aujourd'hui : `PlayerService::delete()` (`:902-942`, utilisé par `PreviewDeleteUsers` et un test de l'amont, `FleetDispatchEspionageTest.php:607`) échoue pour un joueur qui a un héros, et la purge des cités détruites (`CleanupDestroyedPlanets` → `permanentlyDeletePlanet()`) échoue si un héros gouverne la cité. La note ajoute deux RESTRICT de plus (`kingdom_items.owner_user_id`, la clé composite) sans prévoir de chemin de suppression. **Correction** : `heroes.planet_id` en `nullOnDelete` (un gouverneur perd sa cité, il ne la retient pas), et un service Kalendrath de purge de royaume (exemplaires → héros) à appeler avant `PlayerService::delete()`. Hors périmètre strict des objets, mais à traiter avant l'alpha.

### Mineurs

- **m1. `server_key` n'existe pas sur `main`.** La note lit `origin_server_key` « dans la configuration » (l. 218), soit `config/kaelite.php` › `server_key`, qui n'existe que sur `feat/kaelite-deux-soldes`. Créer une clé partagée (`config/server.php` › `key`, `env('SERVER_KEY')`) que Kaelite et les objets lisent tous deux.
- **m2. `kingdom_items.created_operation_id` en clé étrangère** (l. 124) contredit la purge ou l'archivage du journal prévus (l. 318) et le principe « journal sans clé étrangère vers les entités ». Le passer en simple index.
- **m3. Collation.** La base est en `utf8mb4_unicode_ci` (`config/database.php:52`), insensible à la casse : `CHECK (state IN (...))` accepte `'EQUIPPED'`, que l'enum PHP refusera ensuite. Déclarer `code`, `state`, `slot_type`, `equipped_slot`, `source_type`, `idempotency_key` en `ascii` / `ascii_bin` (index plus petits, comparaisons exactes).
- **m4. `CHECK (state IN (...))` contredit « aucune migration par nouvelle valeur »** (convention l. 72). Ajouter un état demandera de réécrire la contrainte. C'est acceptable (opération sans risque), mais il faut l'écrire.
- **m5. Constat 0.3, nuance** : la migration `2026_10_30_000002` ne remplit pas `slot`, mais `SeedDemoHero.php:109-110` le remplit pour les objets équipés. L'incohérence ne touche que les bases où le héros de démo a été créé avant la migration et pas re-semé depuis.
- **m6. Collision de préfixe à trois branches, pas deux** : `2026_10_30_000001` existe aussi sur `origin/worktree-agent-adc2991a55fce0a8c` (`create_personal_access_tokens_table`). Sans gravité (noms complets distincts, aucune dépendance), mais les migrations d'objets doivent être datées au plus tôt du `2026_11_19`. Ne pas renommer celles de Kaelite : une base de développement qui les a déjà jouées les rejouerait.
- **m7. Aucun héros principal garanti.** `HeroRosterService::recruitMain()` n'est appelé à aucune inscription : un royaume peut n'avoir aucun héros. Le filtre « prêt à équiper » doit prévoir ce cas (butin tiré dans le catalogue des deux archétypes de l'alpha).
- **m8. Conflit d'idempotence non détecté.** La même clé rejouée avec un contenu différent doit lever une erreur, comme `KaeliteIdempotencyConflictException` sur la branche Kaelite. Ajouter `payload_hash CHAR(64)` sur `item_operations`.
- **m9. Index manquant** pour un plafond de butin journalier et l'historique : `INDEX(subject_user_id, type, created_at)` sur `item_operations`.
- **m10. Trous de la machine à états** : changer l'archétype ou le rôle d'un héros (principal ↔ conseiller, emplacements de conseiller restreints) peut laisser équipé un objet devenu interdit ; le passage direct d'un objet d'un héros à un conseiller n'est pas décrit. Il faut une table de transitions explicite (voir la mise à jour de la note).
- **m11. Surdimensionnement de M4.** La base ne contient que les 8 objets de démo de « John Doe » (note l. 224), dont 2 armes sans emplacement dans la liste du 19/11. Une migration de données réversible, gardée par `hasColumn`, avec dédoublonnage, pour des données de démo re-semables (`SeedDemoHero` les supprime et les recrée, `:51,340`), coûte plus qu'elle ne prouve. Préférer la réécriture de `SeedDemoHero` sur le nouveau schéma.
- **m12. Serveur ×5.** Les expéditions et les raids reviennent cinq fois plus vite : un taux de butin calibré au rythme ×1 inonderait les royaumes. Le taux doit être réglé pour ×5 au simulateur (`tools/theorycrafting/`), et la régénération des camps suivre la vitesse du serveur.

### Points vérifiés et confirmés
- `hero_equipment.hero_id` en cascade (`2026_09_15_000007:16`), et `equipment_item_id` aussi (même fichier, l. 17 : supprimer un objet du catalogue efface les inventaires, ce que la note ne dit pas mais qui renforce son constat).
- `equipment_items.name` en français, sans machine name (`2026_09_15_000006`, `SeedDemoHero.php:343-350`).
- Issue `GainItems` inatteignable : `getOutcomeWeights()` (`ExpeditionMission.php:70-81`) et la table de `selectRandomOutcome()` (`:953-964`) n'ont pas d'entrée `items` ; `TODO` à `:665` ; réglage `expedition_weight_items` présent (`SettingsService.php:701-703`), à 0 par défaut (`2025_12_17_000000:48`).
- Crochets « items » de l'amont : `ProductionIndex.php:69,96,117` et `calculateItems()` vide (`GameObjectProduction.php:542-546`, appelé `:130`, cumulé `:565`).
- Types : `users.id` en `increments`, `heroes.user_id` en INT UNSIGNED, `heroes.id` en BIGINT.
- Tests sur MariaDB 11.3.2 (`docker-compose.yml:5`, `.env` `DB_CONNECTION=mysql`), sans base de test séparée dans `phpunit.xml`, `RefreshDatabase` commenté (`tests/Pest.php:18`).
- Clé étrangère composite avec `holder_hero_id` NULL : InnoDB ne vérifie pas une clé étrangère dont une colonne est NULL. Types compatibles (BIGINT/BIGINT, INT/INT). L'index `UNIQUE(id, user_id)` sur `heroes` est nécessaire (le préfixe de la clé primaire seule ne suffit pas). RESTRICT est le **seul** choix compatible avec les `CHECK` : un `SET NULL` remettrait `holder_hero_id` à NULL en laissant `state = 'equipped'`.
- `HasUlids` avec `uniqueIds()` surchargé à `['uid']` laisse `id` auto-incrémenté (`HasUniqueStringIds::getIncrementing()`, l. 86-92 du framework) : la vérification n° 4 pour Codex est levée.
- Verrous : `updateFleetMissions()` verrouille les cités puis la mission (`PlayerService.php:709-731`) ; `processed = 1` à `ExpeditionMission.php:212-213`.
- Kaelite : patron de grand livre conforme à la description (`idempotency_key` 191 unique, `server_key` 64, `meta` JSON). Seule différence : ses écritures **ont** une clé étrangère vers `users`, ce que la note écarte pour le journal des objets ; les deux choix se défendent, mais un compte avec des écritures Kaelite ne sera de toute façon pas supprimable sans purge (cf. M6).

---

## Corrections concrètes

Elles sont reportées dans la note elle-même, section « Mise à jour du 2026-11-19 » : emplacements à deux vocabulaires, `CHECK` complétés, `equip_class`, effets sans `value_mode` et avec `target` non nul, table des transitions, résolveur d'effets par cité et par armée avec plafond final, déclencheur unique de recalcul, injection du combat dans les moteurs, modèle de butin par source (expédition et pillage de bot) avec clés d'idempotence, tables de butin versionnées en configuration et multiplicateur de serveur réglable, réponses aux questions tranchées.

## Questions qui restent à Gregory
1. Noms anglais des 12 emplacements (proposition provisoire dans la note mise à jour).
2. Le butin tombe-t-il à l'arrivée de l'expédition ou au retour de l'armée ? (recommandé : à l'arrivée).
3. Un pillage de bot donne-t-il un objet à chaque victoire, ou selon une probabilité ? Un plafond journalier par royaume ?
4. L'issue « objet » de l'expédition remplace-t-elle une autre issue (modèle OGame, poids relatif) ou s'ajoute-t-elle aux autres en tirage indépendant ?
5. Un conseiller peut-il gouverner une cité (et donc porter des effets de portée Ville) ?
6. Les classes d'équipement (`equip_class`) : combien, et lesquelles pour le Guerrier et le Sorcier ?
