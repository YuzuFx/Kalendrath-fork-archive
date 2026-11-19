# Implémentation du lot 1 de l'alpha : objets, butin d'expédition, effets du héros sur la production

19 novembre 2026. Rapport d'agent (Claude), branche locale `feat/alpha-objets` (créée depuis `main` après la fusion de `feat/heros-suite`). Commits locaux uniquement : rien n'est poussé ni fusionné. Les fichiers `docs/` non commités trouvés au départ n'ont été ni modifiés ni ajoutés.

**Sources suivies.** `CLAUDE.md` ; `docs/contrat-alpha.md` ; la note de schéma `docs/archives/retours-agents-2026-11-19-schema-donnees-objets.md`, **y compris sa mise à jour U0 à U7** (elle prévaut) ; la relecture `docs/archives/retours-agents-2026-11-19-relecture-schema-objets.md` ; puis les **décisions de Gregory transmises en cours de tâche**, qui remplacent les défauts donnés au départ (statistiques vivantes, butin à l'obtention, inventaire de 32 places, suppression d'objet, remise à zéro entre alpha et bêta).

---

## 1. Ce qui est fait

### 1.1 Schéma (migrations additives, datées du 2026-11-19)

Aucune table héritée d'OGameX n'est modifiée. Quatre migrations, **réellement exécutées, annulées (`migrate:rollback --step=4`) puis rejouées** sur MariaDB 11.3.2 ; le `SHOW CREATE TABLE` confirme les contraintes.

| Migration | Contenu |
|---|---|
| `2026_11_19_000001_create_item_definitions_tables` | `item_definitions` (`code` unique stable, `kind`, `slot_type` (11 types), `equip_class`, `rarity`, `required_level`, `version`, `content_hash`, `retired_at`) ; `item_definition_effects` (N effets par définition, `target` NOT NULL DEFAULT `'*'`, `value DECIMAL(8,2)`, **pas de `value_mode`**, unique `(définition, type, portée, cible)`). CHECK : un équipement a un `slot_type` ; rareté 1 à 5. |
| `2026_11_19_000002_create_item_journal_tables` | `item_operations` (`uid` ULID, `idempotency_key` unique, `payload_hash`, `type`, `subject_user_id` sans clé étrangère, `server_key`, provenance, `meta` JSON, index `(subject_user_id, type, created_at)`) ; `item_movements` en ajout seul, seule clé étrangère vers `item_operations`. |
| `2026_11_19_000003_create_kingdom_items_tables` | `UNIQUE(id, user_id)` sur `heroes` ; `kingdom_items` (`uid` ULID, `owner_user_id` INT UNSIGNED → `users`, `state`, `holder_hero_id` + `equipped_slot`, `reservation_*` et `durability` **créés mais vides**, provenance, `created_operation_id` en simple index, `definition_version` pour l'audit). Unique `(holder_hero_id, equipped_slot)`. **Clé étrangère composite** `(holder_hero_id, owner_user_id) → heroes(id, user_id)` RESTRICT. Six CHECK : liste fermée des états ; `equipped` ⇔ porteur **et** position ; porteur et position nuls ensemble ; `reservation_type`/`reservation_id` nuls ensemble ; `reserved` ⇔ réservation ; un objet porté n'est jamais réservé. |
| `2026_11_19_000004_set_heroes_planet_id_null_on_delete` | `heroes.planet_id` en `nullOnDelete` (un gouverneur perd une cité détruite, U6). |

Colonnes de code (`code`, `kind`, `slot_type`, `equip_class`, `state`, `equipped_slot`, `source_type`, `idempotency_key`…) en `ascii` / `ascii_bin` : la contrainte `state IN (...)` refuse `'AVAILABLE'` (testé).

**Pas de table** pour les métiers, composants, fabrication, marché ou monnaie. **Pas de copie figée des effets** : une table `kingdom_item_effects` avait été écrite pour le défaut « stats figées », puis retirée avant tout commit quand Gregory a tranché pour des statistiques vivantes.

### 1.2 Énumérations, modèles, configuration
- Enums : `EquipmentSlotType` (11 types, `positions()` : `ring` → `ring_1`, `ring_2`), `EquipmentSlot` réécrite (12 positions, armes supprimées ; `boots` reste la valeur, libellé « Pieds » / « Feet »), `ItemKind`, `EquipClass` (`heavy`, `arcane`, `any`), `KingdomItemState`, `ItemOperationType` (dont `discard` et `definition_rebalance`), `ItemSourceType` (dont `pve_raid` pour le lot 2).
- Modèles : `ItemDefinition`, `ItemDefinitionEffect` (avec `describe()` lisible, ex. « +1,5 % de production (Bois) »), `KingdomItem` et `ItemOperation` (ULID dans `uid`, `id` auto-incrémenté), `ItemMovement`. `Hero::equippedItems()` ajoutée ; `Hero::equipment()` marquée dépréciée.
- `config/server.php` (`key` = `SERVER_KEY`, à partager avec Kaelite), `config/items.php` (`inventory.capacity` = 32, `ITEMS_INVENTORY_CAPACITY`), `config/loot.php` (tables versionnées), `config/heroes.php` (`archetypes.warrior/sorcerer.equip_classes`, `alpha_archetypes`, emplacements de conseiller passés à 12).

### 1.3 Services
| Service | Rôle |
|---|---|
| `Items\ItemLedger` | Opération unique par clé d'idempotence, dans une transaction. Même clé + même contenu = rejeu sans effet ; même clé + contenu différent = `ItemIdempotencyConflictException` (via `payload_hash`). Course concurrente gérée par l'index unique. Écrit les mouvements (d'où → vers : propriétaire, état, héros, position). |
| `Items\KingdomItemService` | `grant()` (création au sac, provenance, journal, idempotent), `discard()` (état `destroyed`, suppression logique, journalisée, idempotente, refusée sur un objet porté, réservé ou d'un autre royaume), `inventory()`, `bagCount()` (objets portés non comptés), `isBagFull()`. |
| `Hero\HeroEquipmentService` (réécrit) | `equip()` : objet du royaume du héros, état `available`, type compatible avec la position et le rôle, niveau requis, classe d'équipement autorisée pour l'archétype ; position demandée ou première libre ; position occupée = **une opération `swap`, deux mouvements**, jamais `equipped` → `equipped`. `unequip()` (refusé si le sac est plein), `unequipAll()` (suppression ou changement de héros). Ordre des verrous : cités du royaume → héros → objets. Mises à jour conditionnelles (`WHERE state = 'available'`, une ligne attendue). |
| `Hero\HeroEffectsService` (réécrit) | Effets des talents et des objets portés, **lus en direct depuis la définition**, avec cible. `forCity()` : effets Empire du héros principal + Empire des conseillers (somme plafonnée au ratio) + effets **Ville** des héros qui gouvernent la cité, **plafond appliqué une seule fois au total final**, par axe et par cible. `forHero()` / `forEmpire()` conservées pour l'affichage. |
| `Hero\HeroEffectsResolver` | Cache par royaume, enregistré en `scoped` (sûr sous Octane) ; zéro sans requête pour un identifiant ≤ 0 (PNJ, tests unitaires). |
| `Hero\HeroEffectsChanged` | **Point d'entrée unique** de tout changement d'effet : verrouille les cités des royaumes concernés, **solde les ressources à l'ancien taux** (`PlanetService::update()`), applique le changement, vide le cache, **recalcule et sauve chaque cité**. Utilisé par équiper, déséquiper, `HeroTalentService::allocate()` et `respec()`, `HeroRosterService::appointGovernor()` (nouveau) et le rééquilibrage d'un objet. |
| `Items\ItemCatalogService` + `kalendrath:items:sync [--code=…]` | Synchronise `database/catalog/items.php` (upsert par `code`, codes absents retirés, jamais supprimés). **Rééquilibrage d'un seul objet** : on modifie son entrée et on lance `kalendrath:items:sync --code=<code>` ; la définition change de version, l'opération `definition_rebalance` journalise les effets avant et après, et chaque royaume dont un héros porte l'objet est soldé puis recalculé. Aucun multiplicateur global de statistiques. |
| `Items\LootService` + `LootContext` | Tirage indépendant de la source : `drop_chance` × réglage `loot_item_rate_multiplier`, rareté pondérée, définitions non retirées, niveau ≤ niveau du héros principal + bande, classe portable par l'archétype du héros principal (sans héros principal : union des classes Guerrier + Sorcier). Graine injectable, tirage complet journalisé dans `meta` (table, version, multiplicateur, graine, rareté, objet). Un « rien n'est tombé » est aussi journalisé : un rejeu ne retire jamais. Aucun verrou pris (insertions seules). PNJ exclus. |
| `Items\ExpeditionLootService` | Clé `expedition:{fleet_mission_id}:loot`, table `expedition.t1`. |
| `Items\KingdomPurgeService` | Supprime les objets puis les héros d'un royaume (le journal reste) ; appelé en une ligne au début de `PlayerService::delete()`, qui échouait auparavant pour un joueur ayant un héros (relecture M6). |
| `kalendrath:items:reset --force` | Vide `kingdom_items`, `item_movements`, `item_operations` (catalogue conservé), pour la remise à zéro entre alpha et bêta. |

### 1.4 Points d'injection dans l'amont (une ligne ou presque chacun)
- `ExpeditionMission` : `'items'` ajouté aux poids et à la table de correspondance (l'issue `GainItems` devient atteignable) ; le `TODO` remplacé par l'appel à `ExpeditionLootService` **dans la transaction de résolution de l'issue**, avant `processed = 1` ; si rien ne tombe, repli sur l'issue « échec ». Dans `startMissionSanityChecks()` : refus d'une nouvelle expédition quand le sac est plein.
- `ProductionIndex` : composante `hero` (à côté de `items`, crochet de l'amont non utilisé). `GameObjectProduction` : propriété `heroEffects` (nulle par défaut), `calculateHeroEffects()` (`floor((mine + planet_slot) × % / 100)` par ressource, cibles `metal`/`crystal`/`deuterium`, `*` = les trois, la Nourriture n'est jamais touchée), cumul dans `calculateTotal()`. `PlanetService::getObjectProductionIndex()` : une ligne.
- `PlayerService::delete()` : appel de la purge. `SettingsService::lootItemRateMultiplier()`. `AppServiceProvider` : enregistrement `scoped` du résolveur.
- Administration (Blade, autorisée) : champ « multiplicateur du taux de butin d'objets » dans les réglages serveur.

### 1.5 Catalogue de départ (provisoire)
`database/catalog/items.php` : **23 objets**. Huit pièces `heavy` (Guerrier) et huit `arcane` (Sorcier) sur les huit types d'armure, plus des bijoux et capes `any` (2 colliers, 2 capes, 3 anneaux). Chaque archétype peut remplir les 12 positions (testé). Raretés Blanc à Bleu, niveaux requis 1 ou 3. Valeurs prudentes : 0,5 à 2 % par effet, total porté très en deçà des plafonds. Noms affichés **suffixés « (provisoire) » / « (provisional) »**, descriptions **[TBD]**, clés `t_items.item.<code>.name|description` en français et en anglais (`resources/lang/{fr,en}/t_items.php`), machine names en anglais. Vocabulaire du lexique (Bois, Fer, Mana ; Guerrier, Sorcier). Les effets `troop_combat_bonus_percent` et `garrison_defense_bonus_percent` de certaines pièces de Guerrier sont **inertes jusqu'au lot 2** (voir §5).

### 1.6 Démo, interface héritée, GDD
- `ogamex:dev:seed-demo-hero` réécrit sur le nouveau schéma (au lieu d'une migration de copie, comme le recommande la relecture) : synchronise le catalogue, déséquipe et retire les objets de démo de l'ancien héros, puis donne au royaume les 15 objets portables par un Guerrier et en équipe 6. **Exécuté deux fois de suite sans erreur** sur la base de développement (compte Legor : 6 portés, 9 au sac).
- Fiche héros Blade (`HeroController::show`, `hero/show.blade.php`) : adaptation minimale pour lire `kingdom_items` et afficher les 12 positions, afin que l'écran de démo existant ne casse pas. Aucune fonction nouvelle en Blade.
- `t_heroes.slot.*` (12 positions) et nouveaux messages d'erreur en FR et EN. GDD §5.5 et §5.7 : mention des 12 emplacements (décision du 19/11), sans réécrire le reste.
- Les anciennes tables `equipment_items` / `hero_equipment` et leurs modèles restent en place (suppression prévue après l'écran React, U6).

---

## 2. Preuves de tests

**Ligne de base (avant toute modification, `main` à `82117149`, migrations du 30/10 appliquées)** : suite complète `1 failed, 1191 passed` en 227 s. L'échec préexistant est **le nôtre**, pas un test hérité : `Tests\Feature\Hero\HeroEquipmentServiceTest > all eight slots can be worn at once` (« This slot is not available to this hero »). Ce test a été réécrit avec le nouveau modèle (12 positions).

**Tests ajoutés ou réécrits (113 tests verts pour `tests/Feature/Items` + `tests/Feature/Hero`)** :
- `Items/KingdomItemSchemaTest` (9) : chaque CHECK, la clé composite (un héros ne porte pas l'objet d'un autre royaume), l'unicité de position, l'interdiction de supprimer un héros qui porte un objet, l'unicité des effets, la casse des états.
- `Hero/HeroEquipmentServiceTest` (19) : 12 positions à la fois, anneaux en `ring_1` puis `ring_2`, position demandée, position incompatible, swap en une opération à deux mouvements, journal, rejeu idempotent, niveau, autre royaume, Guerrier contre pièce arcanique, Sorcier contre pièce lourde, objet détruit, objet porté par un autre héros, emplacements de conseiller, déséquiper, sac plein, déséquiper tout.
- `Items/KingdomItemServiceTest` (11) : provenance et journal, idempotence, conflit de clé, suppression logique journalisée et idempotente, refus sur objet porté, réservé ou étranger, comptage du sac, capacité 32, **suppression d'un joueur avec héros et objets** (le journal survit).
- `Hero/HeroEffectsServiceTest` (14) : tests existants adaptés, plus plafond au total final entre portées, cibles, rééquilibrage d'une définition suivi par les objets portés.
- `Items/HeroProductionEffectsTest` (15, sur un vrai compte à deux cités) : **un test par effet et par chemin**, en vérifiant le taux de production **stocké** sur la cité : talent Empire (les deux cités), réinitialisation des talents, talent Ville (seule la cité gouvernée), retrait du gouverneur, objet Empire ciblé Bois, objet Ville, déséquiper, plafond 10 % sur le total talents + objets, ratio des conseillers, **solde à l'ancien taux avant application** (vérifié aussi par mutation : sans l'étape de solde, le test échoue), rééquilibrage d'un objet porté, Nourriture non touchée, règles du gouverneur, perte du gouvernorat à la suppression de la cité.
- `Items/ItemCatalogTest` (9) : synchronisation complète et idempotente, retrait sans suppression, rééquilibrage d'un seul objet (version, journal, version d'acquisition conservée), noms FR et EN pour chaque objet, 12 positions remplissables par chaque archétype, effet lisible, commandes `sync --code` et `reset --force`.
- `Items/LootServiceTest` (10) : objet portable aussitôt, filtre Sorcier, bande de niveau, **jamais deux fois le même butin**, multiplicateur à 0 et absence de nouveau tirage, audit du tirage, types d'emplacement de la table, royaume sans héros principal, PNJ, plafond souple du sac.
- `Items/ExpeditionItemLootTest` (4, envoi réel d'une expédition par HTTP) : l'objet arrive **à la résolution de l'issue, avant le retour de l'armée**, et s'équipe immédiatement ; rejeu sans double attribution ; sac plein = expédition refusée ; aucun objet éligible = repli sur l'échec.

**Chaîne qualité** : Rector appliqué aux fichiers touchés (il convertit `$fillable` en attribut `#[Fillable]`, y compris sur `Hero`), Pint, PHPStan **sur tout le dépôt sans erreur**.

**Suite complète après modifications** : **`1264 passed (21195 assertions)`, 0 échec, en 241 s** (dans le conteneur `ogamex-ogamex-app-1`, commande `php vendor/bin/pest`). Les tests hérités restent tous verts ; l'écart avec la ligne de base (1192) correspond aux tests ajoutés (72 de plus), et l'échec préexistant a disparu avec la réécriture du test concerné.

---

## 3. Décisions

### 3.1 Tranchées par Gregory pendant la tâche (appliquées)
1. **Statistiques vivantes** : un exemplaire lit les effets de sa définition ; un rééquilibrage vise une définition à la fois (version + empreinte, journalisé, production des porteurs soldée puis recalculée). Aucun multiplicateur global de statistiques. `definition_version` ne sert qu'à l'audit.
2. **Butin à l'obtention** : attribué à la résolution de l'issue de l'expédition, équipable immédiatement, sans contrôle de présence du héros.
3. **Inventaire de 32 places** (réglage), objets portés non comptés, **suppression d'objet** (`discard`).
4. Guerrier et Sorcier.
5. Remise à zéro entre alpha et bêta : pas de migration de données, commande `kalendrath:items:reset`.

### 3.2 Choisies par défaut, à confirmer
| Sujet | Choix (le plus prudent et réversible) |
|---|---|
| **Sac plein au moment du butin** | Plafond **souple** : l'objet est toujours attribué, le sac peut dépasser 32 ; tant qu'il est plein ou au-delà, **une nouvelle expédition est refusée côté service**, et **déséquiper est refusé** (il faut d'abord supprimer un objet). Un échange (swap) reste permis : il ne change pas le nombre d'objets au sac. |
| Classes d'équipement | `heavy` (Guerrier), `arcane` (Sorcier), `any` (bijoux, capes). Un archétype non configuré ne porte que `any`. |
| Gouverneurs | Un seul héros par cité ; tout héros (principal ou conseiller) peut gouverner et apporter ses effets Ville (question 5 de la relecture encore ouverte). |
| Issue d'expédition « objet » | Exclusive (poids relatif, modèle OGame) ; `drop_chance` de `expedition.t1` = 1. Si le multiplicateur ou le catalogue empêche l'objet, l'issue devient « échec ». |
| Poids `expedition_weight_items` | **Laissé à 0 par défaut** (comme l'amont) : l'issue est atteignable mais doit être activée dans l'administration, au taux calibré au simulateur pour ×5. |
| Rééquilibrage | Passe par le fichier du catalogue puis `kalendrath:items:sync --code=…` (pas d'outil d'administration en ligne). |
| Objet retiré du catalogue | Reste valide chez ses détenteurs, ne tombe plus en butin. |
| Message d'expédition | Message générique de l'amont conservé ; il ne nomme pas l'objet (voir dettes). |
| Effets de combat du catalogue | Présents sur 4 pièces de Guerrier, inertes jusqu'au lot 2. |

---

## 4. Dettes et limites connues
1. **Valeurs provisoires** : catalogue, `config/loot.php` (`drop_chance`, poids de rareté, bande de niveau) et poids d'expédition non calibrés au simulateur pour ×5 (relecture m12).
2. **Message d'expédition** : le texte de l'amont (« Une flotte en fuite a laissé un objet… ») ne nomme pas l'objet et garde le vocabulaire OGame ; à réécrire avec la voix Kalendrath et un paramètre `:item`.
3. **Pas d'API JSON ni d'écran React** pour l'inventaire, l'équipement, la suppression ou le gouverneur : seuls les services existent (contrôleurs à écrire : valider, appeler le service, sérialiser). La fiche Blade n'est qu'adaptée.
4. **Unicité du gouverneur** vérifiée par le service seulement ; une course entre deux nominations simultanées reste théoriquement possible. Remède additif : `UNIQUE(planet_id)` sur `heroes`.
5. **Coût d'un changement d'effet** : chaque équipement ou talent met à jour toutes les cités du royaume (quelques cités en alpha : acceptable). Un rééquilibrage d'objet très porté se fait en une seule transaction.
6. **Changement d'archétype, de rôle ou de héros principal** : aucun chemin n'existe encore ; `unequipAll()` est prêt pour eux (U3).
7. `HeroEffectsService::forHero()` additionne les cibles pour l'affichage : vue d'ensemble, pas le calcul réel (qui passe par `forCity()`).
8. Anciennes tables `equipment_items` / `hero_equipment` et modèles associés toujours présents (suppression après l'écran React).
9. Concurrence réelle (deux requêtes parallèles) non testée en parallèle : garantie par verrous, mises à jour conditionnelles, index uniques et clés d'idempotence.
10. Les tests partagent la base de développement (constat existant, `RefreshDatabase` désactivé) ; les nouveaux tests sont en transaction.
11. Enum `EquipmentRarity::getName()` et `TalentEffectScope::getName()` renvoient encore du français en dur (préexistant) ; les libellés de rareté existent désormais dans `t_items.rarity`.

## 5. Lot 2 (combat et bots PvE) : points d'insertion prêts
- **Résolution par armée** : ajouter à `HeroEffectsService` une méthode `forArmy(ownerId, fleetMissionId, ?defendedPlanetId)` sur le modèle de `cityComponents()` (Empire + Ville si l'armée défend la cité gouvernée + Groupe du héros accompagnant, via la table prévue `fleet_mission_heroes`), et l'exposer dans `HeroEffectsResolver` ; plafonds `troop_combat_bonus_percent` 15, `garrison_defense_bonus_percent` à ajouter dans `config/heroes.php` › `axis_caps`.
- **Moteur PHP** : `app/GameMissions/BattleEngine/PhpBattleEngine.php:40-42` (attaquants, `$attackerFleet->player`/`ownerId`/`fleetMissionId`) et `:58-60` (défenseurs, `$defenderFleet->player`). Bonus additif `floor(base × % / 100)` sur `structural_integrity`, `shield`, `attack`.
- **Moteur Rust** (par défaut) : `RustBattleEngine.php:117-119` (attaquants) et `:149-151` (défenseurs). **Attention**, confirmé : ce bloc calcule toutes les armées défenderesses avec `$defenderPlayer` (`:132`), le propriétaire de la cité ; le bonus du héros doit utiliser l'`ownerId` de chaque `DefenderFleetResult`, sinon les renforts alliés recevraient le bonus du défenseur.
- `BattleEngine/Models/AttackerFleet.php:95-99`. Ne pas injecter dans les `*PropertyService` (affichage, simulateurs, missiles).
- **Butin de pillage** : `LootService::grant(new LootContext(ItemSourceType::PVE_RAID, (string) $fleetMissionId, 'pve_camp.tN', $ownerId, 'pve_raid:{fleet_mission_id}:loot'))`, une fois par `AttackerFleetResult` (`AttackMission.php:227-237`), après la création du rapport (`:457-461`, identifiant du rapport dans `meta`) et avant les `processed = 1` (`:237`, `:293`). Victoire = défenseurs sans unités et attaquants avec unités. La table `pve_camp.t1` existe déjà dans `config/loot.php` ; la table `pve_camps` (cible repérable sans toucher aux tables de l'amont) reste à créer avec l'infrastructure des bots.
- Le refus d'expédition quand le sac est plein devra être étendu à l'envoi d'une attaque contre un camp, si l'on garde la même règle.

## 6. Ce qui n'a pas pu être fait (hors périmètre ou non demandé)
- Combat et bots PvE (lot 2, cf. §5).
- API et interface React de l'inventaire.
- Calibrage au simulateur des taux à ×5.
- Fermeture côté serveur des routes exclues de l'alpha (critère 10 du contrat).
- Partage effectif de `config/server.php` avec la branche Kaelite (non fusionnée).

## 7. Commits (locaux, `feat/alpha-objets`)
- `8b1214ba` feat(objets) : schéma additif des objets du royaume (définitions, exemplaires, journal)
- `4ab2fdce` feat(objets) : inventaire, équipement et effets du héros sur les objets du royaume
- `189ac206` feat(production) : bonus réel des talents et de l'équipement du héros
- `1e967e53` feat(butin) : catalogue de départ, rééquilibrage par objet et butin d'expédition
- `ac506f89` chore(démo) : héros de démo et fiche héritée sur les objets du royaume
- commit suivant : ce rapport.

**À faire au déploiement** : `php artisan migrate`, puis `php artisan kalendrath:items:sync`, puis régler `expedition_weight_items` (et au besoin `loot_item_rate_multiplier`) dans l'administration après calibrage au simulateur.
