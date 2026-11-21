# Modèle de données des objets : alpha d'abord, sans rien à reconstruire ensuite

19 novembre 2026. Rapport d'agent (Claude), angle « modèle de données ». Je propose, je ne décide pas. Lecture seule : aucune migration créée, aucun fichier de code, de GDD ou de décision modifié, rien de commité ni de poussé. Aucune suite de tests lancée.

**Sources lues.** `CLAUDE.md` ; `docs/decision-architecture-cible.md` ; `docs/decision-metiers-et-marche.md` ; GDD §5.5 à §5.10 et §9 ; `docs/etude-serveurs-en-continu.md` (fusion) ; rapports d'agents du 15/11 (métiers et marché), du 17/11 (impact sur la stack, Codex) et du 18/11 (faisabilité technique). **Code** : migrations `2026_09_15_*`, `2026_09_16_*` et `2026_09_23_*` ; modèles `Hero`, `EquipmentItem`, `HeroEquipment` ; enum `EquipmentRarity` ; `origin/feat/heros-suite` (commit `f3a6a17c` : migrations `2026_10_30_000001/000002`, enums `EquipmentSlot`/`HeroArchetype`/`HeroRole`, services `HeroEffectsService` et `HeroEquipmentService`, `config/heroes.php`, tests) ; `origin/feat/kaelite-deux-soldes` (migrations, `EarnedKaeliteService`, `KaeliteTransactionType`, `config/kaelite.php`) ; `ExpeditionMission`, `SettingsService`, `PlayerService::updateFleetMissions`, `PlanetService` (production), `GameObjectProduction`, `ProductionIndex`, les services de propriétés de combat et `BattleEngine`/`PhpBattleEngine`/`RustBattleEngine`.

**Périmètre retenu (décision de Gregory).** L'alpha contient les bâtiments, la production, les études, les troupes, les défenses, le héros (2 archétypes, talents, inventaire), les expéditions avec un butin d'équipement prêt à équiper, et un serveur ×5. Ce qui est hors alpha mais à anticiper : les autres archétypes, les métiers (2 par royaume), les composants, la fabrication, le marché par serveur, la monnaie de commerce, les événements et quêtes avec objets, les conseillers équipables, l'usure, les objets vendables, la remise à zéro des marchés à la fusion et les accélérations en Kaelite.

---

## 0. Ce que dit le code aujourd'hui (constats qui orientent le schéma)

1. **Sur `main`, l'objet appartient au héros, pas au royaume.** `hero_equipment` porte un `hero_id` avec suppression en cascade (`database/migrations/2026_09_15_000007_create_hero_equipment_table.php:16`). Supprimer ou changer de héros (le changement de héros est prévu en boutique, GDD §9) **détruit son équipement**. Le marché, la fabrication et la fusion exigent l'inverse : un objet possédé par le royaume, porté par un héros.
2. **Le catalogue `equipment_items` stocke le nom en français** (colonne `name`, voir les objets de démo de `SeedDemoHero`). Il n'a pas de machine name stable. C'est contraire à la règle bilingue (anglais avant l'alpha publique) et inutilisable pour une fusion (les identifiants auto-incrémentés diffèrent d'un serveur à l'autre).
3. **La branche `feat/heros-suite` ajoute un seul effet par objet** (`effect_type`, `effect_value`, `effect_scope` sur `equipment_items`, migration `2026_10_30_000002`), et un `slot` sur `hero_equipment` avec un index unique `(hero_id, slot)`. C'est un bon pas, mais un effet unique ne suffit pas à une pièce de rareté violette ou orange. Et la migration **ne renseigne pas `slot` pour les lignes déjà `equipped = true`** : les objets de démo équipés restent incohérents (`equipped = 1`, `slot = NULL`).
4. **Les effets ne sont branchés nulle part** (constat du 18/11, confirmé) : ni la production ni le combat ne lisent `HeroEffectsService`.
5. **L'issue `GainItems` est inatteignable.** Le poids `expedition_weight_items` existe (`SettingsService.php:703`, formulaire d'administration `ServerSettingsController.php:73,141`), mais il manque dans `getOutcomeWeights()` (`ExpeditionMission.php:70-81`) et dans la table de correspondance de `selectRandomOutcome()` (`:953-964`). Le traitement est un `TODO` (`:665`).
6. **L'amont a lui aussi prévu des « items »** : `ProductionIndex::$items` (`app/Models/ProductionIndex.php:69,96,117`) et `GameObjectProduction::calculateItems()`, vide, avec un `TODO` (`app/GameObjects/Models/Fields/GameObjectProduction.php:542-546`). Ce sont les objets boutique d'OGame (boosters). **Ils entreront en conflit si on s'y branche** : il faut s'en écarter.
7. **La branche Kaelite fournit le bon patron de grand livre** : portefeuille verrouillable plus écritures en ajout seul, `idempotency_key` unique (191), `server_key`, `meta` JSON. Le journal des objets doit suivre la même discipline, pour qu'une opération mixte (achat au marché, accélération) se corrèle d'un grand livre à l'autre.
8. **Pièges de types** : `users.id` est un `increments` (INT UNSIGNED, `2017_12_17_141113_create_users_table.php:16`) alors que `heroes.id` est un BIGINT (`id()`). Une clé étrangère vers `users` doit donc être `unsignedInteger` (comme dans `heroes` et Kaelite), jamais `foreignId`.
9. **Les tests tournent sur MariaDB** (`.env` : `DB_CONNECTION=mysql`, l'image `mariadb:11.3.2`) : les contraintes `CHECK` et les clés étrangères composites sont bien appliquées, aussi en test.

---

## 1. Principes

### P1. Séparer la définition de l'exemplaire
- **Définition** (`item_definitions`) : ce qu'*est* un objet (type, emplacement, rareté, niveau requis, effets). Elle est partagée, versionnée et identifiée par un **`code` machine stable** (en anglais), identique sur tous les serveurs.
- **Exemplaire** (`kingdom_items`) : *cet* objet-là, avec son propriétaire, son état, sa provenance et sa future usure. Il porte un **`uid` ULID** stable qui survit à un export/import de fusion.
- **Pile** (`kingdom_item_stacks`, prévue) : une quantité d'objets fongibles (composants, consommables) **sans identité individuelle**. Deux tables distinctes plutôt qu'une table unique avec `quantity` : l'unicité « une pile par (royaume, définition) » ne s'exprime pas proprement si des exemplaires uniques partagent la table (MariaDB n'a pas d'index partiel). C'est aussi la recommandation de Codex du 17/11.

### P2. Le propriétaire est le royaume
`owner_user_id` → `users.id`. Dans la base d'un serveur, un `users` est un royaume (un compte du lobby a au plus un royaume par serveur, garanti par l'affectation du hub, cf. étude serveurs). Le héros et les conseillers **ne possèdent rien : ils portent**. Changer de héros, nommer un conseiller, vendre ou fabriquer ne demande donc aucun transfert de propriété.

### P3. Le héros et le conseiller équipent
L'équipement se décrit par deux colonnes de l'exemplaire, `holder_hero_id` et `equipped_slot`. Les conseillers sont des lignes de `heroes` avec `role = advisor` (branche `heros-suite`) : **les conseillers équipables ne demandent aucune table nouvelle**, seulement la configuration des emplacements autorisés (`config/heroes.php` › `advisors.slots`).

### P4. Les effets sont des données structurées, en lignes
Une table `item_definition_effects` admet N effets par définition. Chaque effet a un type (`effect_type`, clé libre déjà utilisée par les talents et par `config/heroes.php` › `axis_caps`), une portée (`TalentEffectScope`), une valeur, un mode (pourcentage ou valeur fixe) et une **cible optionnelle** (`target` : `metal`, `light_fighter`…). On choisit des lignes plutôt que du JSON pour pouvoir agréger en SQL, indexer par type et valider à l'écriture. Le vocabulaire reste **commun** avec les talents, pour un seul calcul d'effets et un seul plafond par axe.

### P5. La provenance est saisie à la naissance et ne se reconstitue jamais
Chaque exemplaire porte `source_type` (expedition, craft, quest, event, admin, migration, market, npc_shop), `source_ref` et `origin_server_key`. Ces informations ne se rattrapent pas après coup. Elles servent au support, à la lutte contre la duplication, au lien éventuel « accéléré en Kaelite → lié » et à l'audit de fusion.

### P6. L'état et la localisation forment une machine à états fermée
`state` ∈ {`available`, `equipped`, `reserved`, `in_transit`, `consumed`, `destroyed`}. L'alpha n'utilise que `available` et `equipped`, mais **tout code écrit dès l'alpha teste `state = 'available'`** pour autoriser une action, jamais `state <> 'equipped'`. Quand `reserved` (annonce au marché, ingrédient de fabrication) et `in_transit` (convoi) arriveront, aucun chemin existant ne pourra par erreur équiper un objet mis en vente. Les invariants sont garantis par des contraintes `CHECK` en base, pas seulement par le service.

### P7. Les empilables portent une quantité (prévu)
La table `kingdom_item_stacks` aura `quantity` et `reserved_quantity` (une contrainte vérifie `reserved_quantity <= quantity`), avec une seule ligne par (royaume, définition, lié/non lié). Le journal, lui, est **dès l'alpha** capable d'écrire une quantité et une définition sans exemplaire : il n'aura pas à changer quand les piles arriveront.

### P8. Versionner le catalogue
- La source de vérité du catalogue est **un fichier versionné dans le dépôt** (proposé : `database/catalog/items.php`), synchronisé en base par une commande idempotente (`upsert` par `code`). C'est l'idiome d'OGameX, qui définit ses objets de jeu en PHP (`BuildingObjects`, etc.), tout en gardant des lignes en base pour les clés étrangères.
- `item_definitions.version` est incrémenté quand `content_hash` change. `kingdom_items.definition_version` retient la version en vigueur à la création de l'exemplaire.
- **On ne supprime jamais une définition** : `retired_at` la retire du butin et des recettes, et les exemplaires existants restent valides.
- Par défaut, les statistiques sont **vivantes** : un exemplaire lit les effets actuels de sa définition, si bien qu'un rééquilibrage s'applique à tous. Le figement par exemplaire (`rolls` JSON, qualité) reste possible plus tard, par une colonne ajoutée sans destruction (question à Gregory, §6).

### P9. Idempotence et journal des mouvements
- Chaque modification d'inventaire passe par **une opération** (`item_operations`) dont la `idempotency_key` est unique. Une même requête ou une même mission rejouée n'a qu'un seul effet. Exemple : `expedition:{fleet_mission_id}:loot`.
- L'opération se détaille en **lignes de mouvement** (`item_movements`) : qui, quoi, combien, d'où vers où (propriétaire, état, héros, emplacement). Le journal ne reçoit que des ajouts.
- L'`uid` (ULID) de l'opération sert d'**identifiant de corrélation inter-grands-livres**. Il ira dans `reference` des écritures Kaelite et de la future monnaie de commerce, pour retrouver « cette accélération a produit cet objet » ou « cette vente a déplacé cet objet et ce montant ».
- Les transitions se font **sous verrou** (`lockForUpdate`), avec des mises à jour conditionnelles (`... WHERE id = ? AND state = 'available'`, une ligne affectée exactement).

### P10. Critère de frontière « créer maintenant » contre « créer plus tard »
Sous MariaDB 11, **ajouter une colonne nullable est instantané et non destructif** (`ALGORITHM=INSTANT`). Ce qui coûte cher à rattraper, c'est ce qui touche à l'**identité, la propriété, la localisation, l'unicité et les invariants** : clés, rattachement, contraintes, découpage des tables, déplacement de données. D'où la règle suivante :
- **Créé maintenant** : tout ce qui fixe l'identité, la propriété, l'état et les contraintes, plus ce qui doit être saisi au moment de l'événement (provenance).
- **Créé mais vide** : seulement les colonnes qui **participent à un invariant ou à un filtre** que le code de l'alpha doit déjà respecter.
- **Prévu (documenté, non créé)** : toute table dont aucun code de l'alpha ne lit ni n'écrit une ligne, et tout attribut pur qu'on pourra ajouter instantanément. On évite ainsi des tables mortes que le schéma figerait avant que les règles soient tranchées (monnaie, métiers, marché : questions encore ouvertes au 17/11).

---

## 2. Schéma concret

Conventions : clés vers `users` en `unsignedInteger` ; clés vers `heroes` et les tables nouvelles en `unsignedBigInteger` ; chaînes d'énumération en `string` adossées à une enum PHP, jamais `ENUM` SQL (même choix que `effect_type`, migration `2026_09_23_000001`), pour ne pas migrer à chaque nouvelle valeur ; libellés joueur dans `resources/lang/{fr,en}/t_items.php`, avec des clés dérivées du `code` (`t_items.<code>.name`, `.description`).

### 2.1 CRÉÉ et UTILISÉ en alpha

#### `item_definitions`, le catalogue

| Colonne | Type | Contrainte / défaut | Rôle en alpha |
|---|---|---|---|
| `id` | BIGINT UNSIGNED PK | auto | clé locale |
| `code` | VARCHAR(64) | **UNIQUE**, non nul | machine name stable inter-serveurs (ex. `boots_wanderer_t1`) |
| `kind` | VARCHAR(32) | non nul, INDEX | `ItemKind` : `equipment` (seul utilisé en alpha) ; plus tard `component`, `consumable`, `pattern`, `quest`, `cosmetic` |
| `slot` | VARCHAR(32) | NULL | `EquipmentSlot` (8 aujourd'hui, 10 décidés le 17/11 : ajouter une valeur ne demande aucune migration). NULL hors équipement |
| `rarity` | TINYINT UNSIGNED | défaut 1 | `EquipmentRarity` (blanc → orange) |
| `required_level` | TINYINT UNSIGNED | défaut 1 | niveau de héros requis |
| `allowed_archetypes` | JSON | NULL = tous | « un sorcier ne porte pas de plaque » ; sert au filtre du butin « prêt à équiper » |
| `version` | INT UNSIGNED | défaut 1 | incrémenté par la synchronisation quand `content_hash` change |
| `content_hash` | CHAR(64) | non nul | empreinte de la définition source (détection de changement) |
| `retired_at` | TIMESTAMP | NULL | retiré du butin, jamais supprimé |
| `created_at`, `updated_at` | TIMESTAMP | | |

Index : `UNIQUE(code)`, `INDEX(kind, retired_at)`, `INDEX(slot, rarity, required_level)` (tirage du butin). Contraintes : `CHECK (kind <> 'equipment' OR slot IS NOT NULL)`, `CHECK (rarity BETWEEN 1 AND 5)`. *(La seconde est à assouplir si une 6ᵉ rareté arrive : c'est la seule contrainte qu'il faudrait réécrire, et la réécrire est sans risque.)*

#### `item_definition_effects`, les effets structurés

| Colonne | Type | Contrainte / défaut | Rôle |
|---|---|---|---|
| `id` | BIGINT UNSIGNED PK | | |
| `item_definition_id` | BIGINT UNSIGNED | FK → `item_definitions.id` ON DELETE CASCADE | |
| `effect_type` | VARCHAR(64) | non nul | même vocabulaire que les talents et `config/heroes.php` › `axis_caps` (ex. `production_bonus_percent`) |
| `effect_scope` | VARCHAR(16) | non nul | `TalentEffectScope` : `hero`, `city`, `group`, `empire` |
| `target` | VARCHAR(64) | NULL | cible facultative : `metal`, `crystal`, `deuterium`, machine name d'unité… NULL = tout l'axe |
| `value` | DECIMAL(10,4) | non nul | magnitude |
| `value_mode` | VARCHAR(16) | défaut `percent` | `percent` ou `flat` |
| `sort_order` | TINYINT UNSIGNED | défaut 0 | ordre d'affichage |

Index : `INDEX(item_definition_id)`, `INDEX(effect_type)`. La cascade est sans danger ici, puisqu'une définition n'est jamais supprimée (P8). La synchronisation remplace les effets d'une définition dans une transaction.

#### `kingdom_items`, les exemplaires uniques (équipement)

| Colonne | Type | Contrainte / défaut | Rôle en alpha |
|---|---|---|---|
| `id` | BIGINT UNSIGNED PK | | clé locale |
| `uid` | CHAR(26) | **UNIQUE** | ULID stable (API, fusion, support) |
| `item_definition_id` | BIGINT UNSIGNED | FK → `item_definitions.id` **RESTRICT** | |
| `definition_version` | INT UNSIGNED | non nul | version du catalogue à la création |
| `owner_user_id` | INT UNSIGNED | FK → `users.id` RESTRICT, non nul | **le royaume** |
| `state` | VARCHAR(16) | défaut `available` | `available`, `equipped` (alpha) ; puis `reserved`, `in_transit`, `consumed`, `destroyed` |
| `holder_hero_id` | BIGINT UNSIGNED | NULL | héros ou conseiller qui porte l'objet |
| `equipped_slot` | VARCHAR(32) | NULL | emplacement occupé |
| `source_type` | VARCHAR(32) | non nul | `expedition` et `migration` en alpha (`admin` pour les outils de test) |
| `source_ref` | VARCHAR(64) | NULL | ex. id de la mission de flotte |
| `origin_server_key` | VARCHAR(64) | non nul | serveur de naissance (même clé que `kaelite.server_key`) |
| `created_operation_id` | BIGINT UNSIGNED | FK → `item_operations.id` | opération qui a créé l'exemplaire |
| `acquired_at` | TIMESTAMP | non nul | entrée dans le royaume actuel |
| `created_at`, `updated_at` | TIMESTAMP | | |

Index et contraintes :
- `UNIQUE(uid)`.
- `UNIQUE(holder_hero_id, equipped_slot)` : un objet par emplacement et par héros. Les NULL sont distincts, donc les objets au sac ne gênent pas.
- `INDEX(owner_user_id, state)` (inventaire du royaume), `INDEX(item_definition_id)`.
- **Clé étrangère composite** `(holder_hero_id, owner_user_id)` → `heroes(id, user_id)` **ON DELETE RESTRICT**. Elle exige un `UNIQUE(id, user_id)` sur `heroes`, une table à nous et non à l'amont. La base interdit alors qu'un héros porte l'objet d'un autre royaume, et qu'on supprime un héros qui porte encore quelque chose : le changement de héros en boutique devra déséquiper d'abord, explicitement.
- `CHECK (state IN ('available','equipped','reserved','in_transit','consumed','destroyed'))`.
- `CHECK ((state = 'equipped') = (holder_hero_id IS NOT NULL AND equipped_slot IS NOT NULL))`, avec `holder_hero_id` et `equipped_slot` nuls ensemble hors équipement.
- Les `CHECK` s'écrivent par `DB::statement('ALTER TABLE … ADD CONSTRAINT …')`, car Blueprint ne les expose pas. MariaDB les applique depuis la 10.2.

#### `item_operations`, l'en-tête du journal

| Colonne | Type | Contrainte / défaut | Rôle |
|---|---|---|---|
| `id` | BIGINT UNSIGNED PK | | |
| `uid` | CHAR(26) | UNIQUE | identifiant de corrélation (Kaelite, future monnaie) |
| `idempotency_key` | VARCHAR(191) | **UNIQUE** | même clé deux fois = un seul effet |
| `type` | VARCHAR(32) | non nul | `loot_grant`, `equip`, `unequip`, `admin_grant`, `admin_revoke`, `migration_import` en alpha |
| `subject_user_id` | INT UNSIGNED | non nul, INDEX | royaume concerné (**sans clé étrangère**, cf. ci-dessous) |
| `actor_user_id` | INT UNSIGNED | NULL | joueur ou administrateur ; NULL = système |
| `server_key` | VARCHAR(64) | non nul | |
| `source_type`, `source_ref` | VARCHAR(32), VARCHAR(64) | NULL | |
| `meta` | JSON | NULL | ex. tirage du butin (graine, table, poids) |
| `created_at` | TIMESTAMP | `useCurrent` | |

#### `item_movements`, les lignes du journal (ajout seul)

| Colonne | Type | Contrainte / défaut |
|---|---|---|
| `id` | BIGINT UNSIGNED PK | |
| `item_operation_id` | BIGINT UNSIGNED | FK → `item_operations.id` RESTRICT |
| `kingdom_item_id` | BIGINT UNSIGNED | NULL (NULL pour une pile, plus tard) |
| `item_uid` | CHAR(26) | NULL (copie de l'ULID : le journal reste lisible après fusion ou purge) |
| `item_definition_id` | BIGINT UNSIGNED | non nul |
| `quantity` | INT | non nul, signé (1 pour un exemplaire) |
| `from_owner_user_id`, `to_owner_user_id` | INT UNSIGNED | NULL |
| `from_state`, `to_state` | VARCHAR(16) | NULL |
| `from_hero_id`, `to_hero_id` | BIGINT UNSIGNED | NULL |
| `from_slot`, `to_slot` | VARCHAR(32) | NULL |
| `created_at` | TIMESTAMP | `useCurrent` |

Index : `(kingdom_item_id, id)`, `(to_owner_user_id, id)`, `(item_operation_id)`.

**Choix délibéré : aucune clé étrangère du journal vers `users`, `heroes` ou `kingdom_items`.** Un journal doit survivre à la suppression d'un héros, d'un compte ou d'un exemplaire. Une clé étrangère bloquerait ces suppressions, ou effacerait l'historique en cascade. Seule la clé étrangère interne vers `item_operations` est conservée.

### 2.2 CRÉÉ MAIS VIDE en alpha (nullable, sans logique métier)

| Où | Colonne | Pourquoi maintenant |
|---|---|---|
| `kingdom_items` | `reservation_type` VARCHAR(32) NULL, `reservation_id` BIGINT UNSIGNED NULL | Elles font partie de la machine à états : `CHECK ((state = 'reserved') = (reservation_type IS NOT NULL AND reservation_id IS NOT NULL))`. Poser l'invariant complet dès l'alpha évite de réécrire les contraintes et oblige le code à traiter `reserved` dès le départ (P6). Elles serviront à l'annonce au marché, à l'ingrédient en fabrication et au convoi. |
| `kingdom_items` | `durability` INT UNSIGNED NULL (NULL = pas d'usure) | La requête d'agrégation des effets la filtre **dès l'alpha** (`durability IS NULL OR durability > 0`). Ainsi, l'arrivée de l'usure ne modifie pas le calcul des effets, déjà branché sur la production. La durabilité maximale reste un attribut de la définition, ajouté plus tard. |

C'est volontairement tout. `bound`, `bind_policy`, `quality`, `rolls`, `expires_at` et `base_value` sont des attributs purs : on les ajoutera instantanément, avec un défaut, quand la règle sera tranchée. Les créer maintenant figerait un choix encore ouvert (liaison des objets accélérés, qualité à trois paliers, prix de référence).

### 2.3 PRÉVU (documenté, à ne pas créer maintenant)

| Table / colonne | Esquisse | Déclencheur | Ce qui la rend additive |
|---|---|---|---|
| `kingdom_item_stacks` | `id`, `owner_user_id` INT UNSIGNED, `item_definition_id`, `quantity` INT UNSIGNED, `reserved_quantity` INT UNSIGNED, `bound` BOOL ; `UNIQUE(owner_user_id, item_definition_id, bound)` ; `CHECK(reserved_quantity <= quantity)` | composants et consommables | `kind` les distingue déjà ; le journal porte déjà `quantity` et une définition sans exemplaire |
| `item_definitions.bind_policy`, `kingdom_items.bound` / `bound_at` | `none`, `on_acquire`, `on_equip` ; objet lié = non vendable | objets de quête, accélérations en Kaelite (question D4 du 15/11) | colonnes avec défaut `none`/`false` : ajout instantané |
| `item_definitions.max_durability`, `repair_cost`… | usure | décision « casse ou consommable » | `durability` est déjà filtrée |
| `kingdom_items.quality`, `rolls` JSON | qualité de fabrication, affixes tirés | métiers, butin à affixes | lecture par-dessus la définition vivante |
| `kingdom_items.expires_at` | objets d'événement temporaires | événements | colonne nullable |
| `item_definitions.base_value` | prix de référence du Comptoir PNJ, en unité minimale de la monnaie de commerce | Comptoir, marché | colonne nullable |
| `kingdom_professions` | `user_id`, `profession_code`, `slot_index` (1-2), `level`, `xp`, `chosen_at` ; `UNIQUE(user_id, slot_index)`, `UNIQUE(user_id, profession_code)` | métiers (2 par royaume) | rattachée au royaume, aucun lien avec le héros |
| `recipes`, `recipe_inputs` | `code`, `profession_code`, `output_definition_id`, `output_quantity`, `duration_seconds`, coûts en ressources ; ingrédients par définition et quantité | fabrication | tout référence `item_definitions` par clé |
| `craft_jobs` | file sur le modèle de `UnitQueue` : `uid`, `owner_user_id`, `planet_id`, `recipe_id`, `status`, `starts_at`, `finishes_at`, `operation_id`, `kaelite_reference` | fabrication et accélération en Kaelite | ingrédients réservés via `reservation_*` et `reserved_quantity` |
| `trade_wallets`, `trade_ledger_entries` | copie conforme du patron Kaelite gagnée : `user_id`, `server_key`, `balance` BIGINT UNSIGNED, écritures `amount`/`balance_after`/`idempotency_key`/`reference` | monnaie de commerce (nom **[TBD]**) | corrélée par `item_operations.uid` |
| `market_listings`, `market_trades`, `market_price_refs` | annonce (`uid`, vendeur, `kingdom_item_id` ou définition + quantité, prix unitaire, statut, `server_key`, `expires_at`) ; transaction (acheteur, vendeur, frais de 5 % détruits, `operation_uid`, ids de compte du lobby pour la surveillance) ; référence de prix (médiane, borne ±30 %) | marché (bêta) | l'objet passe en `reserved` ; le transfert de propriété est un mouvement du journal |
| `loot_tables`, `loot_table_entries` en base | tables de butin modifiables à chaud | événements administrables | en alpha, le butin vit dans `config/loot.php` (versionné) |
| `fleet_mission_heroes` | `fleet_mission_id` UNIQUE, `hero_id` | héros qui accompagne une armée (bonus de combat local) | table de liaison : **aucune colonne ajoutée à `fleet_missions`, qui appartient à l'amont** |
| `quests`, `quest_rewards`, `event_*` | récompenses = opérations `quest_reward` / `event_reward` | quêtes et événements | réutilisent `item_operations` |
| Fusion | aucune table : outil d'export/import (`uid`, `code`, `origin_server_key`, provenance) | fusion de serveurs | cf. §6 |

**Pourquoi rien de tout cela n'est créé maintenant.** Aucun code de l'alpha n'y lirait ni n'y écrirait une ligne. Les règles qui en fixeraient la forme sont encore ouvertes : monnaie et Kaelite, 3ᵉ métier, choix définitif des métiers, usure, liaison des objets accélérés, fermeture du marché en guerre. Une table créée trop tôt devient une table qu'on corrige en production. Chacune ne fait que *pointer vers* les tables de l'alpha (définitions, exemplaires, opérations) : leur création future est purement additive.

---

## 3. Migration depuis l'existant

### 3.1 Ordre recommandé
1. **Fusionner `feat/heros-suite` d'abord** (fusion propre, vérifiée le 18/11). Ses migrations du 30/10 passent avant les nôtres, et la migration de données n'a qu'une forme source à traiter dans la plupart des cas.
2. Puis, dans une même livraison :
   - `M1 create_item_definitions_tables` (définitions et effets) ;
   - `M2 create_item_journal_tables` (opérations et mouvements ; à créer avant M3, puisque `kingdom_items.created_operation_id` pointe vers `item_operations`) ;
   - `M3 create_kingdom_items_table`, plus `UNIQUE(id, user_id)` sur `heroes`, plus les contraintes `CHECK` ;
   - `M4 copy_hero_equipment_to_kingdom_items` (migration de données, cf. 3.2).
3. **Dans une livraison ultérieure**, une fois l'écran héros React passé sur la nouvelle API : `M5 drop_hero_equipment_and_equipment_items`. Garder les anciennes tables le temps d'une version rend `M4` réversible et évite de casser l'écran Blade `HeroController@show` (`app/Http/Controllers/HeroController.php:51-54`, qui lit `$hero->equipment`) avant son remplaçant.

### 3.2 Contenu de `M4` (copie, non destructive)
- Pour chaque `equipment_items` : créer une `item_definitions` avec `code = 'legacy_demo_' . id` (ou un slug validé), `kind = 'equipment'`, `slot`, `rarity`, `required_level` (défaut 1 si la colonne n'existe pas), `version = 1`. Si `Schema::hasColumn('equipment_items', 'effect_type')` est vrai (heros-suite fusionnée) et l'effet non nul, créer la ligne `item_definition_effects` correspondante. **Le garde `hasColumn` rend la migration correcte, que heros-suite soit fusionnée avant ou non.**
- Pour chaque `hero_equipment` : créer un `kingdom_items` avec `owner_user_id = heroes.user_id`, `source_type = 'migration'`, `source_ref = 'hero_equipment:{id}'`, et `origin_server_key` lu dans la configuration. Si la ligne est équipée : `state = 'equipped'`, `holder_hero_id = hero_id`, `equipped_slot = COALESCE(hero_equipment.slot, equipment_items.slot)`. **Ce `COALESCE` corrige l'incohérence laissée par la migration heros-suite** (constat 0.3).
- **Doublons d'emplacement** : sur `main`, rien n'interdit deux objets équipés au même emplacement. On garde le plus récent (`updated_at`, puis `id`) et on renvoie les autres en `available`, sinon l'index unique fait échouer la migration.
- Une `item_operations` `migration_import` par ligne copiée (`idempotency_key = 'migration:hero_equipment:{id}'`), suivie de son mouvement. Rejouer la migration ne duplique rien.
- Les noms et descriptions français de démo partent dans `resources/lang/fr/t_items.php`, avec une entrée anglaise **[TBD]** à rédiger (les noms de démo ne sont pas un catalogue validé, GDD §5.5).
- `down()` supprime les lignes dont `source_type = 'migration'` ainsi que les opérations correspondantes, puis les tables.

En pratique, la base ne contient que les 8 objets de démo de « John Doe ». La migration sert surtout à **prouver le chemin** et à rester réversible.

### 3.3 Conflits et retouches côté `feat/heros-suite`
| Élément de la branche | Sort |
|---|---|
| Migration `2026_10_30_000002` (effets sur `equipment_items`, `slot` + unique sur `hero_equipment`) | **conservée** telle quelle (historique), puis rendue obsolète par M4 et M5 |
| `HeroEquipmentService::equip/unequip` (opère sur `HeroEquipment`) | **réécrit** sur `KingdomItem`, avec verrous, transitions conditionnelles et journal. Les règles (emplacement autorisé, niveau requis, permutation d'emplacement) sont reprises telles quelles |
| `HeroEffectsService::forHero` (lit `hero->equipment.equipmentItem`, un effet par objet) | la partie équipement est **réécrite** pour lire `kingdom_items` (`state = 'equipped'`, durabilité filtrée) joints à `item_definition_effects` (N effets). La partie talents et les plafonds (`config/heroes.php`) ne changent pas |
| `EquipmentSlot` (8 cas) | conservé ; on ajoutera les 2 emplacements supplémentaires décidés le 17/11 (noms **[TBD]**), sans migration |
| `HeroEquipmentServiceTest`, `HeroEffectsServiceTest`, `SeedDemoHero` | adaptés. Ce sont nos tests (une partie des 83 de la branche), **pas les 1109 de l'amont**, qui ne touchent aucune table héros |
| `Hero::equipment()` | remplacée par `Hero::equippedItems()` (`hasMany(KingdomItem, 'holder_hero_id')`) ; on ajoute `User`/`PlayerService` › inventaire du royaume sans toucher au modèle `User` de l'amont, via un service ou une requête dédiée |

**Collision de préfixe relevée** : `feat/heros-suite` et `feat/kaelite-deux-soldes` ont toutes deux des migrations `2026_10_30_000001` et `2026_10_30_000002`. Les noms complets diffèrent, Laravel les exécute donc toutes, dans l'ordre alphabétique, et elles ne dépendent pas l'une de l'autre. Ce n'est pas bloquant, mais il faudra dater les nôtres **après** les deux branches (`2026_11_xx_*`).

---

## 4. Compatibilité avec l'amont (`lanedirt/OGameX`)

1. **Tables additives uniquement.** Aucune colonne ajoutée aux tables de l'amont (`users`, `planets`, `fleet_missions`, `user_tech`…). Les liens se font depuis nos tables vers les leurs. Le seul index ajouté va sur `heroes`, qui est à nous.
2. **Nommage à l'écart de l'amont.** L'amont a prévu des « items » OGame (`ProductionIndex::$items`, `calculateItems()`, `GainItems`) et pourrait un jour créer des tables `items` ou `user_items`. Nos noms (`item_definitions`, `item_definition_effects`, `kingdom_items`, `item_operations`, `item_movements`) évitent ces deux noms. **À revérifier à chaque reprise de correctifs de l'amont.**
3. **Points d'injection d'une ligne dans les fichiers de l'amont, rien de plus.** La logique vit dans des fichiers neufs (`app/Services/Items/*`, `app/Services/Hero/*`). Liste exhaustive en §5. Chaque injection est neutre en l'absence de héros, d'où l'absence d'effet sur les 1109 tests.
4. **Ne pas réutiliser les crochets « items » de l'amont** (`ProductionIndex::$items`, `calculateItems()`) : on ajoute une composante `hero` à côté. Si l'amont remplit son `TODO`, la fusion ne se heurte pas au nôtre.
5. **Dans `ExpeditionMission`, notre code se réduit à un appel de service.** Si l'amont implémente ses propres objets à cet endroit, le conflit se limite à trois lignes, faciles à arbitrer.
6. **Migrations datées après tout l'historique de l'amont**, sans aucune `Schema::table()` sur une table de l'amont.

---

## 5. Brancher un effet d'équipement pour qu'il s'applique réellement

### 5.1 Calcul des effets (fichiers neufs)
- `HeroEffectsService` (heros-suite) : la partie équipement lit `kingdom_items` et `item_definition_effects` (cf. 3.3).
- **Mémoïsation par requête** : un `EmpireEffectsResolver` enregistré par `$this->app->scoped(...)`, dont le cache par `user_id` est remis à zéro entre deux requêtes, et donc **sûr sous Octane**, contrairement aux fabriques singletons signalées le 18/11 (`AppServiceProvider.php:47-60`). Sans cela, `forEmpire()` serait recalculé pour chaque bâtiment de chaque planète.
- Pour un joueur sans héros (tous les tests de l'amont, les PNJ), une requête unique renvoie un tableau vide : **bonus nul**.

### 5.2 Production (consommateur unique de l'alpha, recommandé le 18/11)
| Fichier:ligne | Modification | Neutralité pour les tests |
|---|---|---|
| `app/Models/ProductionIndex.php:69-77` (propriétés), `:96` (constructeur), `:117` (`add()`) | ajouter `public Resources $hero`, son initialisation et son cumul | une composante à zéro ne change aucun total |
| `app/GameObjects/Models/Fields/GameObjectProduction.php:84` | propriété `public ?EmpireEffectsResolver $empireEffects = null;` | NULL par défaut, donc aucun bonus |
| `…/GameObjectProduction.php:130` | après `calculateItems`, appel `$this->calculateHeroEffects($productionIndex);` : nouvelle méthode privée sur le modèle de `calculateGeologist()` (`:267-298`), `floor((mine + planet_slot) × pct / 100)` par ressource, avec `target` facultatif. Le bonus est **additif sur la base**, comme le prévoit le GDD §5.5 (vérification du 23/09) | sort immédiatement si `empireEffects` est NULL ou si le total est nul |
| `…/GameObjectProduction.php:554-566` (`calculateTotal`) | `$productionIndex->total->add($productionIndex->hero);` | ajoute zéro |
| `app/Services/PlanetService.php:2376` (`getObjectProductionIndex`) | une ligne : `$object->production->empireEffects = app(EmpireEffectsResolver::class);` | le résolveur renvoie zéro sans héros |
| Plafond | `production_bonus_percent` = 10 dans `config/heroes.php` › `axis_caps`, déjà appliqué par `HeroEffectsService` | — |

**Piège du cache de production** (constat du 18/11, `PlanetService::updateResourceProductionStats()`, `:2123`) : les taux de production sont **stockés sur la planète**, et les ressources s'accumulent au taux stocké. Équiper ou déséquiper doit donc suivre cet ordre exact, dans une transaction :
1. verrouiller les planètes du joueur, dans le même ordre que `PlayerService::updateFleetMissions()` (`app/Services/PlayerService.php:709-718`) ;
2. **solder les ressources au taux ancien** : `PlanetService::update()` (`:1318`), qui appelle `updateResources()` puis recalcule ;
3. effectuer la transition d'équipement (verrou héros → verrou exemplaire → mise à jour conditionnelle → journal) ;
4. appeler `updateResourceProductionStats()` puis `save()` pour **chaque** planète du joueur.

Sans l'étape 2, le nouveau bonus s'appliquerait rétroactivement depuis la dernière mise à jour. Sans l'étape 4, le bonus serait affiché sans être appliqué sur les autres cités.

### 5.3 Butin d'expédition (alpha)
| Fichier:ligne | Modification |
|---|---|
| `app/GameMissions/ExpeditionMission.php:70-81` | ajouter `'items' => $settingsService->expeditionWeightItems(),` (le réglage existe déjà) |
| `…/ExpeditionMission.php:953-964` | ajouter `'items' => ExpeditionOutcomeType::GainItems,` |
| `…/ExpeditionMission.php:665` | remplacer le `TODO` par `$granted = app(ExpeditionLootService::class)->grant($mission);` et transmettre le nom de l'objet au message `ExpeditionGainItem` |

Le service neuf `ExpeditionLootService` :
- tire une définition dans `config/loot.php` (poids par rareté ; filtre `kind = equipment`, `retired_at IS NULL`, `required_level <= niveau du héros principal + marge`, `allowed_archetypes` contenant l'archétype du héros principal), pour que l'objet soit **prêt à équiper** ;
- crée l'exemplaire et le journal avec la clé `expedition:{mission_id}:loot`.

L'appel s'exécute **dans la transaction et sous les verrous** de `updateFleetMissions()` (planètes puis mission, `PlayerService.php:710-731`), avant `processed = 1` (`ExpeditionMission.php:212-213`). Une exception annule donc à la fois le butin et le traitement de la mission. La clé unique protège en plus contre la double résolution (rattrapage paresseux contre commande planifiée). Le générateur aléatoire doit pouvoir recevoir une graine en test, comme `DefenseRepairService`.

Le poids `expedition_weight_items` vaut 0 par défaut (migration `2025_12_17_000000`) : **les tests de l'amont ne tirent jamais cette issue**. Le réglage de l'alpha se fait par l'administration, ou par une migration de réglage propre à Kalendrath, à valider au simulateur.

### 5.4 Combat (hors alpha, points repérés)
- **Bonus global du royaume** (simple, mais pas conforme au GDD) : les points d'injection seraient `AttackPropertyService::getBonusPercentage()` (`app/GameObjects/Services/Properties/AttackPropertyService.php:20-25`), `ShieldPropertyService.php:20-25` et `StructuralIntegrityPropertyService.php:22-27`. On y ajouterait l'effet `troop_combat_bonus_percent`, arrondi à l'entier, puisque `ObjectPropertyService::calculateProperty()` travaille en pourcentage entier (`Abstracts/ObjectPropertyService.php:39-45`). Inconvénient : l'effet s'appliquerait partout, affichage compris, alors que le GDD parle des « troupes accompagnées » par le héros.
- **Bonus local, conforme au GDD** : il passe par le contexte de flotte, qui n'existe que dans le moteur. Les points d'injection sont `PhpBattleEngine.php:40-42` (attaquants) et `:58-60` (défenseurs), `RustBattleEngine.php:117-119` et `:149-151`, ainsi que `BattleEngine/Models/AttackerFleet.php:95-99`. Il faut d'abord savoir quel héros accompagne quelle flotte : la table de liaison prévue `fleet_mission_heroes`, sans modifier `fleet_missions`. Les niveaux affichés dans le rapport (`BattleEngine.php:121-144`) restent des entiers de recherche ; le bonus du héros doit apparaître séparément dans le rapport.
- **Garde-fou commun** : un PNJ (`NPCPlayerService`) n'a pas de héros, son bonus est donc nul. Les tests de combat de l'amont ne changent pas.

### 5.5 Pourquoi les 1109 tests restent verts
Chaque injection est soit une composante à zéro, soit une propriété NULL par défaut, soit une issue de poids nul. Aucune table de l'amont n'est modifiée. Les seuls tests à réécrire sont les nôtres (héros). **À démontrer par une exécution complète de la suite avant fusion** : ce rapport ne l'a pas lancée.

---

## 6. Risques et questions ouvertes

### 6.1 Risques
| Risque | Parade dans le schéma ou le service |
|---|---|
| **Double clic, double requête** (équiper deux fois, permuter en parallèle) | verrou héros + verrou exemplaire ; `UPDATE … WHERE state = 'available'` (une ligne attendue) ; `UNIQUE(holder_hero_id, equipped_slot)` en dernier rempart |
| **Interblocage** entre une résolution de mission et une action d'inventaire | **ordre de verrouillage unique** : planètes du joueur → héros → exemplaires (même ordre que `updateFleetMissions`) ; transactions courtes |
| **Duplication de butin** (mission résolue deux fois : middleware paresseux et commande planifiée) | `processed` + verrous existants + `item_operations.idempotency_key` unique (défense en profondeur) |
| **Objet équipé puis mis en vente** (bêta) | machine à états : seule `available` autorise une annonce ; `CHECK` sur `reserved` |
| **Suppression d'un héros qui porte des objets** (changement de héros en boutique) | clé étrangère composite RESTRICT : le service déséquipe d'abord, et l'objet reste au royaume |
| **Effet affiché mais non appliqué**, ou appliqué rétroactivement | séquence solde → transition → recalcul de toutes les planètes (§5.2) |
| **Fuite d'état sous Octane** | résolveur `scoped`, jamais singleton |
| **Fusion de serveurs** : identifiants en collision | export par `uid` et `code`, réimport avec de nouveaux `id` locaux ; `origin_server_key` et provenance conservés ; une `migration_import` par exemplaire, à clé `merge:{merge_id}:{uid}` (rejouable sans double import) ; vérification préalable que tous les `code` existent sur le serveur d'arrivée (parité du catalogue) ; journal source archivé, pas migré |
| **Fusion : marché et réservations** | avant l'export, annulation des annonces : retour en `available`, opérations idempotentes `merge:{merge_id}:listing:{uid}` ; convois `in_transit` livrés ou rendus ; remise à zéro des références de prix, sans toucher au patrimoine (rappel de Codex, 17/11) |
| **Multi-royaume** (deux royaumes d'un même compte du lobby sur un serveur après fusion) | l'affectation du hub l'empêche (étude serveurs). Sinon, `owner_user_id` reste correct, car chaque royaume est un `users`, mais la surveillance anti-boosting du marché devra s'appuyer sur l'**identifiant de compte du lobby**, à stocker dans `market_trades` (prévu) |
| **Conflit avec l'amont** sur les crochets « items » | composante `hero` séparée ; un seul appel dans `ExpeditionMission` ; noms de tables distincts |
| **Rééquilibrage du catalogue** qui change silencieusement les objets possédés | statistiques vivantes par défaut, `definition_version` pour l'audit, figement possible plus tard (`rolls`) |
| **Volume** | faible en alpha : jamais de suppression physique, état final `consumed` ou `destroyed`. Purge ou archivage du journal à prévoir avant le marché |

### 6.2 Questions pour Gregory
1. **Rééquilibrage** : quand une pièce change dans le catalogue, les objets déjà possédés suivent-ils (recommandé) ou gardent-ils leurs anciennes valeurs ?
2. **Moment du butin** : l'objet arrive-t-il dans l'inventaire dès l'arrivée de l'expédition (recommandé, comme les objets d'OGame) ou au retour de l'armée, avec les ressources ?
3. **Taille de l'inventaire du royaume** : illimitée en alpha ? Sinon, que devient un butin quand le sac est plein ?
4. **« Prêt à équiper »** : le butin est-il toujours portable par le héros principal (archétype et niveau filtrés), ou peut-il tomber pour un autre archétype, et donc pour un futur conseiller ?
5. **Les 2 archétypes de l'alpha** : lesquels ? Ils fixent les valeurs de `allowed_archetypes` du premier catalogue.
6. **Les 2 emplacements supplémentaires** (10 décidés le 17/11, 8 dans le code) : lesquels ? Noms FR/EN **[TBD]**. Les armes à deux mains occupent-elles les deux emplacements d'arme ?
7. **Changement de héros** : l'équipement reste au royaume (c'est ce que le schéma garantit) : confirmé ?
8. **Détruire, recycler ou revendre au PNJ en alpha** : non, d'après le périmètre. Un objet inutile reste-t-il donc indéfiniment au sac ?
9. **Remise à zéro entre l'alpha et la bêta** : les données de l'alpha sont-elles effacées ? Si oui, aucune migration de données de l'alpha vers la bêta n'est à prévoir, seulement des schémas additifs.
10. **Usure** : une pièce à 0 perd-elle ses effets en restant au sac, ou disparaît-elle ? Le schéma supporte les deux, mais l'état final diffère (`available` à durabilité 0, ou `destroyed`).
11. **Monnaie de commerce à la fusion** : les soldes suivent-ils le royaume ? La « remise à zéro des marchés » ne détruit pas les patrimoines (Codex) : à confirmer.
12. **Objets fabriqués avec une accélération en Kaelite** : vendables, ou liés ? Cela décide de l'arrivée de `bound`.

---

## 7. Points à faire relire par Codex

*(Conformément à CLAUDE.md, demander l'avis de Codex **avant** de lui montrer ce rapport pour les choix de conception ; les points ci-dessous sont des vérifications techniques.)*

1. **Clé étrangère composite** `kingdom_items(holder_hero_id, owner_user_id)` → `heroes(id, user_id)` sous MariaDB 11.3, avec `holder_hero_id` NULL : la contrainte est-elle bien ignorée quand une colonne est NULL, et la migration Laravel l'exprime-t-elle proprement ?
2. **Contraintes `CHECK`** posées par `DB::statement` : syntaxe MariaDB, comportement en test (`RefreshDatabase` n'est pas activé dans `tests/Pest.php:18`, vérifier comment la base de test est migrée) et réversibilité dans `down()`.
3. **Types** : toutes les clés vers `users` en INT UNSIGNED (`increments`), vers `heroes` en BIGINT UNSIGNED.
4. **ULID sur une colonne qui n'est pas la clé primaire** : `HasUlids` avec `uniqueIds(): ['uid']` laisse-t-il `id` auto-incrémenté (`getIncrementing()`) ?
5. **Ordre des verrous et interblocages** entre `PlayerService::updateFleetMissions()` (planètes → mission → insertion de butin) et le service d'équipement (planètes → héros → exemplaire).
6. **Séquence de recalcul de la production** (§5.2) : `PlanetService::update()` solde-t-il bien les ressources au taux ancien avant la transition ? Y a-t-il des appels où `updateResourceProductionStats(false)` n'est pas suivi d'une sauvegarde ?
7. **Arrondi du bonus de production** (`floor` par composante, comme le géologue) contre l'arrondi `ceil` de `getObjectProduction()` (`PlanetService.php:2409-2412`) : un écart d'une unité est-il acceptable dans les tests ?
8. **Résolveur `scoped`** : vérifier qu'il est bien réinitialisé entre les requêtes Octane et les jobs de file (Horizon).
9. **Migration `M4`** : garde `Schema::hasColumn`, `COALESCE` du slot, règle de dédoublonnage, idempotence et `down()`.
10. **Journal sans clé étrangère** vers les entités mutables : acceptable, ou ajouter une clé `kingdom_item_id` en `SET NULL` ?
11. **Effets en lignes** (`item_definition_effects`) contre les trois colonnes de `talent_nodes` : faut-il unifier tout de suite (une table `effects` polymorphe) ou plus tard ? Je recommande plus tard, pour ne pas toucher aux talents juste avant l'alpha.
12. **Piles prévues** : `reserved_quantity` sur la ligne, ou une ligne par état ? Impact sur la concurrence (dernière unité achetée contre annulation).
13. **Surface de conflit avec l'amont** : vérifier sur l'amont actuel que `ExpeditionMission.php:70-81/953-964/665`, `GameObjectProduction.php:84/130/554-566`, `ProductionIndex.php` et `PlanetService.php:2376` n'ont pas bougé, et qu'aucune table `items` ou `user_items` n'y est en préparation.
14. **Clés d'idempotence** : format, longueur (191) et portée (locale au serveur). Collision possible entre `expedition:{id}:loot` et un `id` de mission recyclé après purge ?

---

## Résumé
- Les objets passent du héros au **royaume** : définitions (`item_definitions`, `code` stable), effets en lignes (`item_definition_effects`), exemplaires (`kingdom_items`, `uid` ULID, état, porteur et emplacement), journal idempotent (`item_operations` + `item_movements`).
- Le héros et les conseillers **portent**, ils ne possèdent pas. La base l'impose par une clé étrangère composite, des `CHECK` et un index unique par emplacement.
- Créé mais vide : seulement `reservation_*` et `durability`, qui participent à des invariants. Tout le reste (piles, métiers, recettes, fabrication, monnaie, marché, liaison, qualité) est documenté et **additif**.
- Migration en copie depuis `equipment_items`/`hero_equipment` après la fusion de `feat/heros-suite`, suppression des anciennes tables dans une version suivante.
- Effet de production branché sur une composante `hero` à part, et non sur les crochets « items » de l'amont : 5 points d'injection d'une ligne, neutres sans héros. Le butin d'expédition se branche en 3 lignes dans `ExpeditionMission`.
- Douze questions pour Gregory, quatorze vérifications pour Codex.

---

## Mise à jour du 2026-11-19 : décisions de Gregory et corrections de relecture

*Ajoutée après relecture indépendante, vérifiée dans le code de `main` après la fusion de `feat/heros-suite` (commit `82117149`). Le détail des défauts, leur gravité et les preuves (fichier:ligne) sont dans [retours-agents-2026-11-19-relecture-schema-objets.md](retours-agents-2026-11-19-relecture-schema-objets.md). Le texte ci-dessus est conservé tel quel ; **en cas de contradiction, cette section prévaut**.*

### U0. Décisions de Gregory du 19/11 et questions tranchées

| Décision | Effet sur le schéma |
|---|---|
| (a) Alpha **sans** métiers, composants, fabrication, marché ni monnaie de commerce (planifiés en 2027) | Conforme à §2.3 : rien de tout cela n'est créé. L'anticipation structurelle reste : `item_definitions.kind`, `reservation_*`, journal capable d'écrire une quantité sans exemplaire, `item_operations.uid` pour corréler un futur grand livre. Ajout : un multiplicateur de taux de butin réglable (U4), pour baisser le butin quand la fabrication arrivera sans migration |
| (b) Deux archétypes en alpha : **Guerrier** (`warrior`) et **Sorcier** (`sorcerer`) | Répond à la question 6.2-5. Le premier catalogue ne cible que ces deux archétypes, via `equip_class` (U2) |
| (c) **12 emplacements** : tête, collier, épaules, cape, torse, poignets, mains, ceinture, jambes, pieds, 2 anneaux | Répond à 6.2-6. Plus d'emplacement d'arme : la question des armes à deux mains disparaît. Modèle à deux vocabulaires (U1) |
| (d) Butin en alpha : expéditions **et** pillage de bots PvE | Nouvelle source `pve_raid`, idempotence par armée, tables de butin par source (U4) |
| (e) Effets d'équipement **et** de talents sur la production **et** le combat | Le combat entre dans l'alpha (U5) ; la portée Ville est prise en compte ; plafond appliqué au total final |
| (f) Serveur ×5 | Taux de butin et régénération des camps à calibrer pour ×5 au simulateur (`tools/theorycrafting/`) |

Questions de la §6.2 : **5 et 6 tranchées** (ci-dessus) ; **4** tranchée en partie (le butin vise l'archétype du héros principal, Guerrier ou Sorcier ; un royaume sans héros principal tire dans le catalogue des deux) ; **8** tranchée par (a) : ni recyclage ni revente en alpha, un objet inutile reste au sac ; **11 et 12** reportées avec le marché et la fabrication (2027). Restent ouvertes : 1, 2, 3, 7, 9, 10.

### U1. Emplacements : type d'objet et position sont deux choses (corrige un défaut bloquant)

Avec deux anneaux, la position occupée ne peut plus être la valeur de la définition : `UNIQUE(holder_hero_id, equipped_slot)` interdirait le second anneau. On sépare donc :
- `item_definitions.slot_type` (remplace `slot`) : enum PHP `EquipmentSlotType`, 11 valeurs ;
- `kingdom_items.equipped_slot` : enum PHP `EquipmentSlot`, 12 positions ;
- `EquipmentSlotType::positions()` : `ring` → `[ring_1, ring_2]`, les autres types → leur propre position. Le service d'équipement prend la position demandée par le joueur, sinon la première libre.

| Type (`slot_type`) | Position(s) (`equipped_slot`) | Français | Anglais (*provisoire, à valider*) |
|---|---|---|---|
| `head` | `head` | Tête | Head |
| `neck` | `neck` | Collier | Neck |
| `shoulders` | `shoulders` | Épaules | Shoulders |
| `cloak` | `cloak` | Cape | Cloak |
| `chest` | `chest` | Torse | Chest |
| `wrists` | `wrists` | Poignets | Wrists |
| `hands` | `hands` | Mains | Hands |
| `belt` | `belt` | Ceinture | Belt |
| `legs` | `legs` | Jambes | Legs |
| `boots` | `boots` | Pieds | Feet |
| `ring` | `ring_1`, `ring_2` | Anneau (1, 2) | Ring (1, 2) |

Les valeurs déjà présentes en base (`head`, `chest`, `hands`, `belt`, `legs`, `boots`) sont gardées telles quelles ; « pieds » n'est qu'un libellé. `weapon_main_hand` et `weapon_off_hand` sortent de l'enum. À répercuter dans le même commit : `app/Enums/EquipmentSlot.php`, `config/heroes.php` › `advisors.slots`, `resources/lang/{fr,en}/t_heroes.php` (`slot.*`) et le GDD §5.5 (`game-design-document.md:535,564`, encore à 8 emplacements avec armes).

### U2. Corrections du schéma de la §2.1

**`item_definitions`**
- `slot` devient `slot_type` (U1). La contrainte devient `CHECK (kind <> 'equipment' OR slot_type IS NOT NULL)`.
- `allowed_archetypes` (JSON) est remplacé par `equip_class VARCHAR(32) NOT NULL DEFAULT 'any'`. La correspondance archétype → classes autorisées vit dans `config/heroes.php` › `archetypes.<code>.equip_classes`. Ajouter un archétype en 2027 = une entrée de configuration, pas une revue du catalogue. Valeurs des classes à valider par Gregory.
- `code`, `kind`, `slot_type`, `equip_class` en `CHARACTER SET ascii COLLATE ascii_bin` : la base est en `utf8mb4_unicode_ci`, insensible à la casse.

**`item_definition_effects`**
- **Supprimer `value_mode`** : l'unité est déjà dans le nom du type (`…_percent`), comme pour les talents. Une valeur fixe aura son propre type (`…_flat`).
- `target VARCHAR(64) NOT NULL DEFAULT '*'` (au lieu de NULL), puis `UNIQUE(item_definition_id, effect_type, effect_scope, target)`. Avec NULL, l'index unique serait sans effet sous MariaDB.
- `value DECIMAL(8,2)`, alignée sur `talent_nodes.effect_value` et `equipment_items.effect_value`.

**`kingdom_items`**
- Contraintes supplémentaires (trou établi en relecture : `available` avec un porteur mais sans emplacement passait) :
  - `CHECK ((holder_hero_id IS NULL) = (equipped_slot IS NULL))`
  - `CHECK ((reservation_type IS NULL) = (reservation_id IS NULL))`
  - `CHECK (state <> 'equipped' OR reservation_type IS NULL)` (un objet porté n'est jamais réservé)
- `created_operation_id` : **index simple, sans clé étrangère** (cohérent avec un journal purgeable ou archivable).
- `state`, `equipped_slot`, `source_type` en `ascii_bin`.
- La clé étrangère composite `(holder_hero_id, owner_user_id)` → `heroes(id, user_id)` reste en **RESTRICT** : c'est le seul choix compatible avec les `CHECK` (un `SET NULL` laisserait `state = 'equipped'` sans porteur). Vérifié : InnoDB ignore la clé quand `holder_hero_id` est NULL, les types concordent (BIGINT / INT UNSIGNED).
- Ajouter un état demandera de réécrire `CHECK (state IN (...))` : opération sans risque, mais c'est une migration (nuance à la convention de la §2).

**`item_operations`**
- `payload_hash CHAR(64)` : une clé rejouée avec un contenu différent lève une erreur de conflit, comme `KaeliteIdempotencyConflictException` sur la branche Kaelite.
- `INDEX(subject_user_id, type, created_at)` : historique et plafond de butin journalier éventuel.
- `idempotency_key` en `ascii_bin`.

**Clé de serveur.** `origin_server_key` et `item_operations.server_key` lisent une configuration partagée (`config/server.php` › `key`, `env('SERVER_KEY')`), que Kaelite lira aussi : `config/kaelite.php` n'existe pas sur `main`.

**`durability`** (§2.2) : acceptable, mais facultative. Si on la retire, le filtre de l'agrégation s'ajoutera le jour de l'usure, dans un seul fichier. `reservation_*` reste justifié.

### U3. Machine à états : transitions autorisées

| Opération | De | Vers | Garde |
|---|---|---|---|
| `loot_grant`, `admin_grant` | (création) | `available` | idempotence |
| `equip` | `available` | `equipped` | porteur du même royaume, `equip_class` admise pour l'archétype, niveau requis, position admise pour le rôle, position libre ou libérée dans la même opération |
| `unequip` | `equipped` | `available` | — |
| `swap` (une opération, deux mouvements) | `equipped` (A) | `available` puis `equipped` (B) | mêmes gardes que `equip` ; jamais d'`equipped` → `equipped` direct |
| `admin_revoke` | `available` | `destroyed` | — |
| (2027) `reserve` / `release` | `available` ↔ `reserved` | | marché, fabrication |
| (2027) `consume` | `reserved` | `consumed` | fabrication |

Tout changement d'archétype, de rôle (principal ↔ conseiller) ou de héros principal **déséquipe d'abord** les objets devenus interdits, dans la même transaction (trou relevé : rien ne le prévoyait). Le code de l'alpha teste toujours `state = 'available'` pour autoriser une action (P6, inchangé).

### U4. Butin : expéditions et pillage de bots PvE

**Un service, un contexte.** `LootService::grant(LootContext $ctx)` où `LootContext` porte : `source_type`, `source_ref`, `loot_table_code`, `beneficiary_user_id`, `idempotency_key`, la graine aléatoire. Le service ne connaît ni l'expédition ni le combat : ajouter une source (quête, événement, 2027) ne le modifie pas.

| Source | `source_type` | Clé d'idempotence | Bénéficiaire | Condition |
|---|---|---|---|---|
| Expédition | `expedition` | `expedition:{fleet_mission_id}:loot` | propriétaire de la mission | issue `GainItems` (§5.3, lignes vérifiées) |
| Pillage d'un camp PvE | `pve_raid` | `pve_raid:{fleet_mission_id}:loot`, **une par armée attaquante** | propriétaire de chaque `AttackerFleetResult` | victoire de l'attaquant |

Règles du pillage, établies dans le code :
- En attaque groupée, seule la mission initiatrice exécute le combat (`AttackMission.php:93`), mais `BattleResult::$attackerFleetResults` donne une ligne par armée (`AttackMission.php:227-237`). La clé par `fleet_mission_id` d'armée garantit **un tirage au plus par armée et par combat**, quel que soit le chemin qui résout la mission (requête du joueur ou commande planifiée), et quel que soit le nombre de rejeux.
- `BattleResult` n'a pas de champ « vainqueur » : la victoire se définit par `defenderUnitsResult` vide et `attackerUnitsResult` non vide. Match nul = pas de butin.
- L'appel se place dans `AttackMission::processArrival()`, après la création du rapport de combat (`:457-461`, l'identifiant du rapport va dans `meta`) et avant les `processed = 1` (`:237`, `:293`, `:517`) : même transaction, une exception annule tout.
- Le service **ne verrouille pas** la ligne du héros (lecture simple de l'archétype et du niveau) et n'écrit que des insertions. La mission peut être résolue sous les verrous des cités du **bot** et non de l'attaquant : en n'ajoutant aucun verrou, on ne crée aucun ordre de verrouillage concurrent de celui de l'équipement (cités → héros → exemplaires).
- **Identifier un camp sans toucher à l'amont** : table Kalendrath `pve_camps` (`planet_id` UNIQUE, clé étrangère vers `planets` en cascade, `tier`, `loot_table_code`, `respawn_at`). Elle relève de l'infrastructure des bots (GDD §5.8A), pas du schéma des objets : le schéma des objets n'en attend qu'un `loot_table_code`. Une attaque contre une cité qui n'est pas un camp ne donne jamais d'objet (pas de butin en PvP).
- Les PNJ d'expédition (identifiants négatifs, `NPCPlayerService.php:50-54`) ne reçoivent jamais de butin : le service sort si `beneficiary_user_id <= 0`.

**Tables de butin : versionnées, pas figées.**
- Source de vérité : `config/loot.php`, versionné dans le dépôt (pas de table en base en alpha, §2.3 inchangé). Chaque table a un `code` (`expedition.t1`, `pve_camp.t1`… `pve_camp.tN` selon le palier du camp) et définit : `drop_chance` (probabilité qu'un objet tombe), `rarity_weights`, `level_band` (écart toléré avec le niveau du héros principal), `slot_types` admis, et éventuellement `max_per_day`. Le fichier porte un `version`.
- **Levier de baisse sans migration** : réglage serveur `loot_item_rate_multiplier` (table `settings`, via `SettingsService`, modifiable dans l'administration), défaut 1.0, appliqué à `drop_chance`. Quand la fabrication arrivera, on baisse ce réglage (global) ou les `drop_chance` (par source) : aucune donnée existante ne change. Ne pas réutiliser `expedition_reward_multiplier_items` de l'amont, qui ne couvre que l'expédition.
- Chaque tirage écrit dans `item_operations.meta` : `loot_table_code`, version du fichier, multiplicateur en vigueur, graine, tirage. On peut ainsi auditer après coup un taux de butin anormal.
- Expédition : l'issue `GainItems` est exclusive (poids relatif, modèle OGame). Un tirage indépendant en plus de l'issue est possible : à trancher par Gregory. Le schéma est le même dans les deux cas.
- Calibrage au simulateur pour un serveur ×5 avant de fixer `drop_chance` et `expedition_weight_items`.

### U5. Effets : production et combat (remplace §5.1, complète §5.2, remplace §5.4)

**Résolveur.** `HeroEffectsResolver`, enregistré en `scoped` (inchangé, sûr sous Octane), deux méthodes :
- `forCity(int $userId, int $planetId)` = effets `empire` du héros principal + effets `empire` des conseillers (plafonnés au ratio, comme aujourd'hui) + effets `city` des héros dont `planet_id = $planetId`. **Le plafond par axe s'applique au total final** : aujourd'hui, `HeroEffectsService::forHero()` plafonne chaque portée séparément (`:48-52`), ce qui permettrait de cumuler 10 % Ville et 10 % Empire de bonus de production.
- `forArmy(int $ownerId, int $fleetMissionId, ?int $defendedPlanetId)` = effets `empire` + effets `city` de défense si l'armée défend la cité gouvernée + effets `group` du héros qui accompagne l'armée (table `fleet_mission_heroes`, prévue en §2.3 ; tant qu'aucun héros n'accompagne d'armée en alpha, cette part vaut zéro).
- Les deux renvoient zéro, sans requête, si l'identifiant est ≤ 0 (PNJ) ; une seule requête vide pour un royaume sans héros (tests de l'amont).
- La partie équipement lit `kingdom_items` (`state = 'equipped'`) joints à `item_definition_effects`, groupés par `(scope, effect_type, target)`.

**Production.** Points d'injection de la §5.2 confirmés (`ProductionIndex.php:69,96,117`, `GameObjectProduction.php:84,130,565`, `PlanetService.php:2376`), avec `forCity($userId, $planetId)` au lieu de `forEmpire()`. Cibles admises pour `production_bonus_percent` : `metal`, `crystal`, `deuterium` (Bois, Fer, Mana) ; `*` = les trois ; la Nourriture (`energy`) n'est pas concernée sauf cible explicite.

**Un seul déclencheur de recalcul.** La séquence de la §5.2 (solder au taux ancien → modifier → recalculer et sauver chaque cité) s'applique à **tout** changement d'effet, pas seulement à l'équipement : `HeroTalentService::allocate()` (`:42`) et `respec()` (`:98`), nomination ou retrait d'un gouverneur, recrutement ou remplacement du héros principal, nomination d'un conseiller. Un service unique, `HeroEffectsChanged::apply(User, Closure $change)`, encapsule la séquence ; chaque chemin y passe.

**Combat (désormais dans l'alpha).** Injection **dans les moteurs, par armée**, pas dans les `*PropertyService` (qui changeraient aussi l'affichage, les simulateurs et les missiles) :
- `PhpBattleEngine.php:40-42` (attaquants) et `:58-60` (défenseurs) ;
- `RustBattleEngine.php:117-119` (attaquants) et `:149-151` (défenseurs). **Attention** : ce bloc calcule toutes les armées défenderesses avec le propriétaire de la cité (`$defenderPlayer`, `:132`) alors que chaque `DefenderFleetResult` a son `ownerId`. Le bonus du héros doit être résolu avec l'`ownerId` de chaque armée, sinon les renforts alliés recevraient le bonus du héros du défenseur, et seulement avec le moteur Rust (moteur par défaut, `AttackMission.php:123-130`) ;
- `AttackerFleet.php:95-99`.
- Calcul : bonus **additif sur la valeur de base** de la propriété (comme la production et le GDD §5.5), `floor(base × pct / 100)` ajouté au total calculé par la recherche. Axes : `troop_combat_bonus_percent` (plafond 15 %), `garrison_defense_bonus_percent` (portée Ville), cibles possibles par machine name d'unité.
- Les combats d'expédition (rencontres hostiles) passent par les mêmes moteurs : couverts sans code de plus.
- Le rapport de combat affiche le bonus du héros à part ; les niveaux de recherche affichés (`BattleEngine.php:121-144`) restent des entiers.

### U6. Migration et ordre de livraison (remplace §3.1-3.2)
- `feat/heros-suite` est fusionnée : la garde `Schema::hasColumn` de M4 n'a plus d'objet.
- **Remplacer M4 par une réécriture de `SeedDemoHero`** sur le nouveau schéma. La base ne contient que les 8 objets de démo, re-semables (`SeedDemoHero.php:51,340`), dont 2 armes qui n'ont plus d'emplacement. Une migration de données réversible avec dédoublonnage coûte plus qu'elle ne prouve. M5 (suppression de `hero_equipment` et `equipment_items`) reste après la bascule de l'écran héros en React (`HeroController.php:51-54` lit encore `$hero->equipment`).
- Dater les migrations au plus tôt du `2026_11_19`. Le préfixe `2026_10_30_000001` est déjà partagé par trois branches (`heros-suite`, `kaelite-deux-soldes`, et `create_personal_access_tokens_table` sur `origin/worktree-agent-adc2991a55fce0a8c`) : sans gravité, ne pas renommer celles déjà jouées.
- **Ajouter, sur la table `heroes` (à nous)** : `planet_id` en `nullOnDelete`. Sur `main` aujourd'hui, la clé sans action bloque la purge d'une cité détruite gouvernée par un héros (`CleanupDestroyedPlanets` → `permanentlyDeletePlanet()`), et `heroes.user_id` bloque `PlayerService::delete()` (`:902-942`) pour tout joueur qui a un héros. Les RESTRICT des objets s'y ajoutent : prévoir un service de purge de royaume (exemplaires, puis héros) appelé avant `PlayerService::delete()`.

### U7. Statut des vérifications pour Codex (§7)
- **4 (ULID)** : levée. `HasUniqueStringIds::getIncrementing()` ne renvoie `false` que si la clé primaire figure dans `uniqueIds()` ; avec `uniqueIds(): ['uid']`, `id` reste auto-incrémenté.
- **1 (clé composite)** : confirmée en relecture (MATCH SIMPLE d'InnoDB, types concordants, index `UNIQUE(id, user_id)` requis). Reste à vérifier l'écriture Laravel exacte.
- **11 (effets polymorphes)** : toujours « plus tard », mais `target` et la suppression de `value_mode` rapprochent déjà les deux formats.
- Nouvelles : ordre de verrouillage d'un pillage résolu sous les verrous du bot (U4) ; résolution par `ownerId` dans `RustBattleEngine` (U5) ; collation `ascii_bin` sur les colonnes de code (U2).
