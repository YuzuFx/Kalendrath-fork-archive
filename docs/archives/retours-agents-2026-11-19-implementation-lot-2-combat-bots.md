# Implémentation du lot 2 de l'alpha : héros au combat, camps de bot PvE, fermetures

19 novembre 2026. Rapport d'agent (Claude), branche locale `feat/alpha-objets`, à la suite des 6 commits du lot 1. Commits locaux uniquement : rien n'est poussé ni fusionné. Les fichiers `docs/` non commités trouvés au départ n'ont été ni modifiés ni ajoutés.

**Sources suivies.** `CLAUDE.md`, `docs/contrat-alpha.md`, le rapport du lot 1 (§5, points d'insertion revérifiés), la note de schéma (U0 à U7, en particulier U4 et U5), la relecture du schéma (défaut B2 du `RustBattleEngine`).

---

## 1. Ce qui est fait

### 1.1 Combat : effets du héros armée par armée

**Règle.** Un héros ne renforce que les unités de son propre royaume, armée par armée :

| Armée | Effets reçus |
|---|---|
| Attaquante | Effets **Empire** de son propriétaire |
| Garnison de la cité attaquée (`fleetMissionId = 0`) | Effets **Empire** du propriétaire + effets **Ville** du héros qui gouverne **cette** cité |
| Alliée venue défendre (ACS) | Effets **Empire** de **son** propriétaire uniquement (jamais ceux du propriétaire de la cité) |
| PNJ (pirates, aliens, id ≤ 0) | Aucun, sans requête |

**Axes branchés** (tous les effets de combat présents dans le catalogue du lot 1) :
- `troop_combat_bonus_percent` : attaque, bouclier et structure des **troupes** (vaisseaux OGame) de l'armée. Les défenses n'en profitent pas.
- `garrison_defense_bonus_percent` : bouclier et structure de **toutes** les unités (troupes et défenses) de la garnison de la cité gouvernée, seulement quand elle est attaquée. Jamais l'attaque, jamais une armée en mouvement.
- Cible : `*` ou machine name d'unité (ex. `light_fighter`).

**Calcul.** Valeur héritée (recherche, classe) + `floor(base × % / 100)`, additif sur la valeur de base, comme la recherche et le bonus de production. Chaque axe est **plafonné une seule fois sur son total final** (talents + objets + conseillers), via `HeroEffectsService::finalValue()` du lot 1. Sans héros, les valeurs héritées sont renvoyées telles quelles.

**Fichiers.**
- `app/Services/Hero/HeroCombatEffects.php` (neuf) : `percents()` et `unitStats()`.
- `HeroEffectsService::armyComponents()` et `HeroEffectsResolver::forArmy()` (cache par royaume du lot 1, `scoped`).
- `BattleEngine` (amont, ajout) : `attackerUnitStats()`, `defenderUnitStats()`, `defenderFleetFor()`, et `armyCombatStats()` (vue en lecture seule des valeurs réellement utilisées, pour les tests et un futur rapport de combat).
- `PhpBattleEngine` : deux blocs de trois lignes remplacés par l'appel commun.
- `RustBattleEngine` : idem, et **correction du défaut `$defenderPlayer`** (relecture B2) : chaque armée défenderesse prend désormais la recherche et le héros de **son** propriétaire, comme le moteur PHP le faisait déjà.
- `config/heroes.php` : plafond `garrison_defense_bonus_percent` = 15 (proposé, voir §3).

**Deuxième défaut de l'amont trouvé dans le moteur Rust (non signalé jusqu'ici).** Le code Rust (`rust/battle_engine_ffi/src/lib.rs`) fusionnait les métadonnées **par type d'unité** sur toutes les armées d'un camp : attaque, seuil de rebond du bouclier, régénération du bouclier et seuil d'explosion de la coque d'une unité venaient de la **dernière** armée qui avait ce type. Conséquence, même sans héros : en attaque groupée ou avec un renfort allié, deux armées de Soldats à recherches différentes se battaient avec les mêmes valeurs. Corrigé : chaque instance d'unité porte les valeurs de sa propre armée (`attack_power`, `original_shield_points`, `original_hull_plating`), comme `BattleUnit` côté PHP. Le test « chaque armée n'a que son héros » échouait avec le moteur Rust avant ce correctif (300 au lieu de 315 de puissance de feu défensive) : la preuve est dans la séance de travail, le test le garde.
- La bibliothèque est compilée au démarrage du conteneur (`rust/compile.sh`, aucun binaire versionné). Je l'ai recompilée dans `ogamex-ogamex-app-1`. **Au déploiement, recompiler et redémarrer les processus PHP qui chargent la bibliothèque (worker de file, planificateur, Octane)**, sinon ils garderont l'ancien binaire en mémoire.

**Moteur actif par défaut** : Rust (`SettingsService::battleEngine()`, réglage `battle_engine`, défaut `rust`). Les deux sont testés.

### 1.2 Bots PvE et butin de pillage

**État trouvé.** Rien n'existait pour les bots : aucune table, aucun compte, aucune cible repérable. Ce qui était prêt : `ItemSourceType::PVE_RAID`, `LootService` + `LootContext` (indépendants de la source), la table `pve_camp.t1` dans `config/loot.php` (non branchée), le réglage `loot_item_rate_multiplier`. Les seuls « PNJ » du code sont ceux des combats d'expédition (`NPCPlayerService`, `NPCPlanetService`, id négatifs, sans planète en base) : inutilisables comme cibles. Aucun système de guerre ni Mode de Guerre n'existe dans le code (aucune dépendance possible).

**Choix d'architecture.** Un camp est une **planète ordinaire appartenant à un compte bot unique** : la mission d'attaque, l'espionnage et les deux moteurs de combat hérités fonctionnent sans rien savoir des bots (critère 8 : « attaquable avec le moteur existant »). Une table Kalendrath dit quelles planètes sont des camps ; aucune table de l'amont n'est modifiée.

| Élément | Contenu |
|---|---|
| Migration `2026_11_19_000005_create_pve_camps_table` | `pve_camps` : `planet_id` unique (clé étrangère en cascade), `camp_type`, `tier`, `loot_table_code` (ascii_bin), `respawn_at` (indexé), `raids_won`. Jouée sur la base de développement. |
| `config/pve.php` | Compte bot (pseudo `[TBD] PvE`, adresse `pve-bot@kalendrath.invalid`, recherches de combat), positions de création (1-3 et 13-15, hors plage 4-12 des nouveaux joueurs), **un seul type** `hostile_camp` (palier 1, table `pve_camp.t1`, garnison, stock pillable, délai de retour). |
| `resources/lang/{fr,en}/t_pve.php` | `camp.hostile_camp.name` : « Camp hostile (provisoire) » / « Hostile camp (provisional) » ; nom du bot et description : `[TBD]`. |
| `PveCampService` | `botAccount()` (créé sans événements de modèle, mot de passe aléatoire, sans héros ; **refuse** si aucun compte joueur n'existe encore, car l'amont promeut administrateur le premier compte non-Legor), `spawn()`, `recordBattle()`, `respawnDue()`, `restock()` (valeurs absolues, rejouable). |
| `PveRaidLootService` | `afterBattle()` (butin), `assertCanRaid()` (sac plein), `isVictory()`. |
| `LootContext::$meta` | Données d'audit ajoutées au journal (camp, planète, identifiant du rapport de combat). Hors empreinte d'idempotence. |
| `AttackMission` (amont, deux ajouts) | Une ligne après la création du rapport de combat (`afterBattle`), une surcharge de `startMissionSanityChecks()` (sac plein). |
| Commandes | `kalendrath:pve:spawn {--type=hostile_camp} {--count=1} {--at=G:S:P}` ; `kalendrath:pve:respawn`, planifiée **chaque minute** dans `routes/console.php`. |

**Règles du pillage.**
- **Victoire** : le camp avait une garnison au début du combat, il n'a plus d'unité, les attaquants ont des survivants et ne se sont pas retirés. Match nul ou défaite : rien.
- **Un tirage par armée attaquante survivante**, clé `pve_raid:{fleet_mission_id}:loot`, bénéficiaire = propriétaire de l'armée. Une armée anéantie dans une attaque groupée victorieuse ne tire rien.
- **Attribution à l'obtention** : à la résolution du combat, dans la transaction de la mission (avant le retour de l'armée), mêmes règles que l'expédition : plafond souple de 32 (l'objet est toujours attribué), réglage `loot_item_rate_multiplier`, objet adapté à l'archétype et au niveau du héros principal, tirage journalisé même quand rien ne tombe.
- **Sac plein** : l'envoi d'une attaque **contre un camp** est refusé ; les autres missions ne changent pas.
- **Camp vidé** : une attaque contre un camp sans garnison (en attente de retour) ne donne rien et n'écrit aucun tirage (sinon un Soldat seul pillerait sans fin).
- **Attaque d'un joueur** : jamais d'objet.
- **Retour de la garnison** : le premier combat contre le camp fixe `respawn_at` = maintenant + `respawn_hours` / vitesse économique ; la commande planifiée remet garnison et stock aux valeurs du type.

### 1.3 Fermetures côté serveur

Liste établie, **aucune fermeture faite** (aucune n'était à la fois triviale et sans effet sur les tests hérités) : voir §5.

---

## 2. Preuves de tests

**Ligne de base** : suite du lot 1, `1264 passed`.

**Tests ajoutés (22).**
- `tests/Feature/Items/HeroCombatEffectsTest.php` (12, vrais comptes, vrais héros, moteurs construits comme dans `AttackMission`) :
  - sans effet de héros, valeurs héritées inchangées (avec recherche Affûtage 3) ;
  - talent Empire `troop_combat_bonus_percent` : attaque, bouclier, structure de l'armée ;
  - **objet du catalogue** `head_warden_helm` (troupes +1 %), puis déséquipé : retour aux valeurs héritées ;
  - le bonus de troupes ne renforce pas les défenses ;
  - **objet du catalogue** `shoulders_warden_pauldrons` (garnison +2 %) : rien si le héros ne gouverne pas la cité, bouclier et structure de toute la garnison s'il la gouverne, jamais l'attaque ;
  - le bonus de garnison n'atteint jamais une armée attaquante ;
  - **plafonds sur le total final** : 14 (talent) + 1 + 1,5 (deux objets du catalogue) = 16,5 → 15 ; garnison 20 → 15 ;
  - effet ciblé sur `light_fighter` : n'atteint pas `heavy_fighter` ;
  - **isolation entre armées**, PHP et Rust : garnison du défenseur renforcée, renfort allié inchangé, attaquant inchangé ;
  - **défaut Rust `$defenderPlayer`** : l'entrée envoyée au moteur Rust porte la recherche Affûtage 5 de l'allié pour son armée, le bonus du héros pour la garnison seulement ;
  - **les deux moteurs combattent avec le bonus** : puissance de feu exacte du premier tour (10 Soldats à 55 = 550 ; Garde du défenseur 165 + Garde allié 150 = 315), PHP et Rust ;
  - **deux armées du même type d'unité** gardent leurs propres valeurs dans les deux moteurs (550 + 500).
- `tests/Feature/Items/PveRaidLootTest.php` (10, envoi réel d'attaques par HTTP) : création d'un camp (compte bot non administrateur, sans héros, garnison, réutilisation du compte) ; **victoire = objet à la résolution du combat, avant le retour**, journal (`pve_camp.t1`, camp, rapport de combat), victoire comptée, retour planifié ; **même chose avec le moteur PHP** ; défaite = rien, retour planifié quand même ; camp vidé = rien, aucun tirage ; attaque d'un joueur = rien ; multiplicateur à 0 = tirage journalisé sans objet ; sac plein = attaque d'un camp refusée, attaque d'un joueur permise ; attaque groupée : **un tirage par armée survivante, jamais deux** (rejeu), armée anéantie exclue ; retour de garnison et de stock au bon moment (72 min à ×5) par la commande planifiée.

**Test du lot 1 rendu déterministe** (commit séparé) : `ExpeditionItemLootTest > the item arrives…` échouait au hasard (environ une fois sur quatre) : la bande de niveau du butin (+2) peut tirer un objet de niveau 3 qu'un héros de niveau 1 ne peut pas porter. Le test met le héros au niveau 3. Le comportement n'a pas changé ; la question de fond est au §3.

**Tests hérités** : **aucun test hérité de combat n'a changé.** `tests/Unit/BattleEngine` (76), `HamillManoeuvreTest`, `tests/Feature/FleetDispatch` (238) verts après les deux correctifs Rust.

**Chaîne qualité** : Rector et Pint sur les fichiers touchés, **PHPStan sur tout le dépôt sans erreur**.

**Suite complète** (conteneur `ogamex-ogamex-app-1`, `php vendor/bin/pest`) : **`1286 passed (21271 assertions)`, 0 échec, en 252 s.** Écart avec la ligne de base : les 22 tests ajoutés.

---

## 3. Décisions prises par défaut, à confirmer par Gregory

| Sujet | Choix par défaut |
|---|---|
| Ce que renforce `troop_combat_bonus_percent` | Attaque, bouclier et structure des **troupes** seulement (pas des défenses) |
| Ce que renforce `garrison_defense_bonus_percent` | Bouclier et structure (pas l'attaque) de **toute** la garnison, défenses comprises, de la cité gouvernée, quand elle est attaquée. Lune non comprise (une lune a son propre identifiant) |
| Plafond de l'axe garnison | **15 %**, aligné sur les troupes (absent de la table du GDD). Les deux axes sont plafonnés séparément : une garnison peut donc cumuler 15 % + 15 % en bouclier et structure |
| Effets du héros sur un renfort allié | Jamais ceux du propriétaire de la cité ; seulement ceux du royaume de l'allié (Empire) |
| Héros qui accompagne une armée (portée Groupe) | Zéro en alpha (aucune table `fleet_mission_heroes`) |
| Recherche d'un renfort allié avec le moteur Rust | La sienne (correction de l'amont, alignée sur le moteur PHP) |
| Camp = planète d'un compte bot | Un seul compte bot ; camp visible et espionnable comme une cité ; vitesse de retour divisée par la vitesse économique |
| Butin quand une armée est anéantie dans une victoire groupée | Pas de tirage pour elle |
| Camp vidé, garnison pas encore revenue | Pas de tirage |
| Sac plein | Attaque d'un camp refusée (même règle que l'expédition) |
| `drop_chance` de `pve_camp.t1` | Inchangé (0,25, provisoire) |
| Lisibilité du butin | Aucun message dédié : l'objet apparaît dans l'inventaire (voir dettes) |
| Bande de niveau du butin (+2) | Inchangée. Elle peut donner un objet **qu'on ne peut pas encore porter** (le test du lot 1 en échouait au hasard), ce qui contredit « prêt à équiper ». **À trancher** : bande à 0, ou objet tolérée « pour plus tard » |

## 4. Dettes et limites connues

1. **Rapport de combat** : n'affiche pas le bonus du héros (U5 le demande). `BattleEngine::armyCombatStats()` donne déjà les valeurs ; l'affichage se fera dans l'interface React, pas en Blade.
2. **Message de butin** : ni l'expédition ni le pillage ne nomment l'objet obtenu (dette 2 du lot 1, étendue au pillage).
3. **Compte bot visible comme un joueur** : classement (Highscore), vue de la galaxie (pseudo `[TBD] PvE`, marqué inactif), alliances, messagerie et compagnons possibles en théorie. À masquer ou filtrer avant l'alpha (classement en particulier).
4. **Le bot reçoit les rapports de combat** et ses messages s'accumulent (purgés par la rétention de 7 jours de l'amont).
5. **Production du camp** : la planète du bot produit le revenu de base entre deux retours ; son stock dépasse donc un peu les valeurs du type. Sans gravité, à surveiller.
6. **Lune sur un camp** : un combat peut créer une lune appartenant au bot ; elle n'est pas un camp (aucun butin). Option : interdire la lune sur un camp.
7. **Ordre de déploiement** : le compte bot doit être créé **après** le compte administrateur (la commande refuse sinon). Si l'administrateur s'inscrit après le bot, il ne sera pas promu (règle de l'amont).
8. **Bibliothèque Rust** : recompiler et redémarrer les processus PHP au déploiement (§1.1).
9. **`kalendrath:pve:spawn` non exécutée sur la base de développement** (pour ne pas y laisser de compte bot) : elle est couverte par les tests du service, pas par un test de la commande.
10. Les camps n'ont qu'un palier et une table de butin ; le danger croissant par Contrée (GDD 5.8A) n'existe pas.
11. Concurrence réelle (deux résolutions parallèles du même combat) non testée en parallèle : garantie par les verrous de mission de l'amont et la clé d'idempotence.

## 5. Fermetures côté serveur : routes et actions héritées liées aux fonctions hors alpha

Aucune n'est fermée. Toutes sont accessibles aujourd'hui à un compte connecté. **Proposition de mécanisme** : un réglage `ALPHA_CLOSURES` (désactivé par défaut, pour que les tests hérités restent verts), un middleware qui renvoie 403 sur les routes listées, et une garde dans `GameMission::isMissionPossible()` pour les missions ; un test par route fermée (critère 10).

| Route / action | Fonction | Risque en alpha | Proposition |
|---|---|---|---|
| `POST /ajax/fleet/dispatch/send-fleet`, `send-mini-fleet`, mission **1 (attaque)** vers une cité de joueur | PvP | Élevé : pillage entre testeurs, hors contrat | **Fermer** sauf si la cible est un camp (`PveCampService::campAt`) |
| Missions **2 (attaque groupée)**, `POST /ajax/fleet/union/create`, `union/join`, `GET union/available`, `/overlay/fleet/federation` | PvP groupé | Élevé | Fermer vers les joueurs ; à décider pour les camps (garder l'attaque groupée contre un camp ?) |
| Mission **9 (destruction de lune)** | PvP | Élevé | Fermer |
| Mission **10 (missiles)**, `GET /overlay/galaxy/missile-attack`, `POST /ajax/galaxy/missile-attack` | PvP | Élevé | Fermer |
| Mission **6 (espionnage)** vers un joueur | Préparation du PvP | Moyen | Fermer vers les joueurs, **garder vers les camps** (GDD 5.8A : camps espionnables) |
| Mission **5 (défense groupée)** | Coopération militaire | Faible sans PvP | Garder, ou fermer avec le PvP |
| Mission **3 (transport)** vers un autre joueur | Échange de ressources entre comptes | Moyen : transfert entre testeurs, contournement de l'économie (marché exclu) | Fermer vers les autres joueurs, garder entre ses cités |
| `POST /ajax/phalanx/scan` | Tour de guet sur les flottes d'autrui | Faible | Garder |
| `GET /merchant`, `/merchant/resource-market`, `/merchant/market/{type}`, `POST /merchant/call`, `trade`, `dismiss`, `GET /merchant/scrap`, `POST /merchant/scrap/bargain`, `scrap/execute` | Marchand OGame (échanges de ressources, ferraille contre Kaelite) | Élevé : marché et monnaie exclus de l'alpha | **Fermer** |
| `GET /premium`, `GET /shop`, `GET /overlay/payment`, `/overlay/payment/iframe` | Boutique et paiement | Élevé (paiements exclus) | **Fermer** (pages sans transaction aujourd'hui, mais vitrine du hors-périmètre) |
| `POST /ajax/facilities/halve-building`, `complete-building`, `POST /ajax/research/halve-research`, `POST /ajax/shipyard/halve-unit`, `complete-unit` | Accélération contre Kaelite (matière noire) | Élevé : fausse le rythme ×5 que l'alpha doit éprouver | **Fermer** |
| `POST /ajax/facilities/complete-repairs` | Réparation instantanée (à vérifier : coût en Kaelite probable) | Moyen | Vérifier, fermer si payant |
| `GET /characterclass`, `POST /characterclass/select`, `deselect` | Classes OGame (Collecteur, Général, Explorateur), payées en Kaelite | Élevé : bonus étrangers au héros (production, combat, butin), double l'archétype | **Fermer** (et vérifier qu'aucun compte n'a de classe) |
| `GET /planet-move`, `POST /ajax/planet-move`, `GET /ajax/planet-move/cancel` | Déménagement de cité contre Kaelite | Moyen | Fermer |
| `POST /ajax/alliance-depot/send-supply-rocket` | Ravitaillement de flottes alliées | Faible | Fermer avec le PvP |
| `GET/POST /alliance…` (création, candidature, rangs, exclusion, textes) | Alliances | Faible (événements d'alliance exclus, pas l'alliance elle-même) | **À trancher** : garder (social) ou fermer |
| `GET /rewards` | Récompenses héritées | À vérifier | Vérifier le contenu, fermer si Kaelite |
| Expédition : issue « marchand » et issue « matière noire » | Marché et Kaelite par expédition | Moyen | Mettre leurs poids à 0 dans l'administration (`expedition_weight_merchant`, `expedition_weight_dark_matter`) : réglage, pas de code |
| Commande planifiée `DarkMatterRegenerateCommand` | Génération de Kaelite | Moyen | Désactiver en alpha (ou régler à 0) |
| Inscription Fortify (`Features::registration()`, `/register`) | Création de compte libre | **Élevé** : alpha fermée sur invitation | Fermer l'inscription libre, créer les comptes invités par commande ou par lien d'invitation |
| `Route::impersonate()` | Usurpation d'identité (administrateurs) | Faible (réservée aux administrateurs) | Garder, journaliser |
| `routes/api.php` (`/api/user`, garde `auth:api`) | API héritée | Faible | Garder (l'API JSON viendra avec Sanctum) |

À noter : le **blocage d'attaque** de l'administration (`attack_block_until`) bloque toutes les missions hostiles, **y compris le pillage des camps** : il ne peut pas servir à fermer le PvP.

## 6. Paramètres de difficulté à passer au simulateur (×5)

Le simulateur (`tools/theorycrafting/`) ne modélise ni le combat ni le pillage (README : « Absents : pillage, expéditions, héros »). Il faudra lui ajouter un module « camp » ; voici les paramètres à y faire varier.

**Type actuel `hostile_camp` (valeurs provisoires de `config/pve.php`).**
- Garnison : 20 Soldats (`light_fighter`), 5 Gardes (`heavy_fighter`), 10 `rocket_launcher` (défense, nom [TBD]). Recherches de combat du bot : 0.
- Puissance de feu par tour ≈ 20 × 50 + 5 × 150 + 10 × 80 = **2 550** ; points de structure ≈ 80 000 + 50 000 + 20 000 = **150 000** (bouclier non compté).
- Valeur de la garnison (coût de remplacement pour un joueur) : 20 × (3 000 Bois + 1 000 Fer) + 5 × (6 000 + 4 000) + 10 × 2 000 Bois = 130 000 Bois et 45 000 Fer.
- Stock pillable : 20 000 Bois, 10 000 Fer, 5 000 Mana ; pillage à 50 % = **17 500** au plus par victoire (limité par la soute).
- Retour : 6 heures de jeu / vitesse économique → **72 minutes à ×5**.
- Butin d'objet : `drop_chance` 0,25 × multiplicateur, rareté 75 / 22 / 3 (Blanc, Vert, Bleu), bande de niveau +2.

**Questions à faire trancher par le simulateur.**
1. **Jour de premier pillage gagnant** d'un joueur actif à ×5 : quand peut-il aligner une armée qui gagne en 6 tours avec des pertes acceptables (Cavaliers, Gardes) ? Cible proposée : fin de la première semaine réelle (critère 12, « boucle moyenne visible »).
2. **Rentabilité** : pertes moyennes de l'attaquant (ressources) contre 17 500 de pillage + valeur d'un objet. Le pillage doit rapporter plus qu'il ne coûte à partir du jour visé, sans dépasser la production de quelques heures.
3. **Rythme du butin** : nombre de victoires par jour (retour de 72 min, durée des trajets à ×5) × 0,25 = objets par jour et par camp. Avec 10 à 15 testeurs, combien de camps faut-il pour éviter la file d'attente (un camp vidé ne rapporte rien jusqu'à son retour), et combien d'objets par semaine remplissent l'inventaire de 32 ?
4. **Paliers suivants** (si un deuxième type devient nécessaire, contrat §5 : à sacrifier en premier) : multiplier garnison et stock par ×3 à ×4 par palier, recherches de combat du bot +2 par palier, `pve_camp.t2` avec rareté décalée.
5. **Effet du héros** : avec le plafond de 15 % (troupes), mesurer de combien il avance le jour de premier pillage gagnant ; le GDD veut qu'il compte sans renverser la mécanique.

## 7. Ce qui reste

- Fermetures du §5, après arbitrage de Gregory (mécanisme proposé, un test par route).
- Masquer le compte bot (classement, galaxie) et nommer les bots (aujourd'hui [TBD]).
- Module « camp » du simulateur et calibrage (§6), puis `drop_chance`, garnison, stock et délai de retour.
- Bonus du héros dans le rapport de combat et message de butin nommant l'objet, dans l'interface React.
- API JSON et écrans React (inventaire, équipement, camps).
- Au déploiement : `php artisan migrate`, `php artisan kalendrath:items:sync`, recompiler la bibliothèque Rust et redémarrer les processus PHP, créer l'administrateur **puis** `php artisan kalendrath:pve:spawn --count=N`, vérifier que le planificateur tourne (`kalendrath:pve:respawn`).

## 8. Commits (locaux, `feat/alpha-objets`)

- `5a16fc90` feat(combat) : effets du héros par armée dans les moteurs PHP et Rust
- `115e291b` test(butin) : test d'expédition rendu déterministe (héros de niveau 3)
- `54528131` feat(pve) : camps de bot PvE attaquables et butin d'équipement du pillage
- commit suivant : ce rapport.
