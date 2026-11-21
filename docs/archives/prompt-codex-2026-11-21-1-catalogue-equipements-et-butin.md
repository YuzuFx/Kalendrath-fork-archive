# Prompt Codex n° 1 : catalogue d'équipements et tables de butin (2026-11-21)

*À coller dans Codex. Lancer **avant** le prompt n° 2 (visuels), qui dépend de la liste d'objets. Branche conseillée : `feat/catalogue-equipements`, créée depuis `feat/alpha-objets` après commit de l'état actuel. Pousser la branche avant de s'arrêter (`git push -u origin <branche>`).*

---

Tu travailles sur Kalendrath (jeu navigateur heroic fantasy PvE-first, base Laravel 13 / PHP 8.5 issue d'OGameX, tests Pest). Lis d'abord, dans cet ordre : `CLAUDE.md` (et `AGENTS.md`), `docs/contrat-alpha.md` (en entier), `docs/game-design-document.md` §5.5 (héros, talents, équipement, cadre d'équilibrage des talents) et §5.7 (craft), `docs/archives/retours-agents-2026-11-19-schema-donnees-objets.md` et `retours-agents-2026-11-19-implementation-lot-1-objets.md` (schéma et lot 1), `docs/archives/retours-agents-2026-11-19-implementation-lot-2-combat-bots.md` (butin des camps), `database/catalog/items.php`, `config/loot.php`, `config/heroes.php`, `config/pve.php`, `app/Services/Items/` (surtout `ItemCatalogService`, `LootTable`, `LootService`), `app/Console/Commands/Items/SimulateLoot.php`, les skills `.claude/skills/lexique-kalendrath/` et `.claude/skills/voix-kalendrath/`, `docs/lore.md`.

## Décisions de Gregory à respecter (2026-11-19 et 2026-11-21)

- **12 emplacements** : tête, collier, épaules, cape, torse, poignets, mains, ceinture, jambes, pieds, deux anneaux. **Classes d'équipement** : `war` (Guerrier), `wizard` (Sorcier), `any` (commun).
- **5 raretés** : 1 Blanc, 2 Vert, 3 Bleu, 4 Violet, 5 Orange (`EquipmentRarity`). **Niveaux requis** de 1 à 30 (niveau maximal du héros : 30).
- **Marge de niveau du butin** : un objet peut tomber jusqu'à +2 niveaux au-dessus du héros (`level_margin`).
- **Le héros est une mécanique centrale. Aucun plafond global des bonus** (`heroes.axis_caps` est vide) : le poids vient des talents (budget de 29 points) et de l'équipement. Tu ne dois pas réintroduire de plafond ; mais l'**ensemble** équipement + talents d'un héros de niveau 30 pleinement équipé doit rester dans un ordre de grandeur cohérent avec le GDD §5.5 (le héros compte beaucoup sans renverser seul une mécanique). Documente le budget que tu choisis par rareté et par niveau.
- **Seuls les effets qui ont un consommateur réel dans le jeu comptent** (critère 6 du contrat : les effets s'ajoutent *réellement* dans la production et le combat). Aujourd'hui : `production_bonus_percent` (cible `metal` = Bois, `crystal` = Fer, `deuterium` = Mana, ou `*`), `troop_combat_bonus_percent`, `garrison_defense_bonus_percent`. Un objet du catalogue n'utilise **que** ces effets, sauf si tu implémentes un nouveau consommateur avec ses tests (phase 2 facultative ci-dessous).
- **Pas de métiers, de composants, de fabrication ni de marché** dans l'alpha. Pas d'usure. Pas de recyclage ni de revente. Inventaire de 32 places.
- **Textes affichés en français d'abord, anglais aussi** (`resources/lang/fr/t_items.php` et `en/t_items.php`, clés `t_items.item.<code>.name` et `.description`). **Vouvoiement** dans les descriptions, ton épique avec une pointe de légèreté discrète, noms du lexique (Bois, Fer, Mana, Nourriture ; jamais de vocabulaire OGame). Ne jamais inventer le nom d'un élément du jeu : un nom provisoire se marque comme tel dans le rapport. Le code (machine names, codes d'objets) reste en anglais.
- Les sources de butin de l'alpha : **expédition** (issue « objet ») et **camp de bot PvE** (une table par palier). Le butin est attribué à l'obtention, adapté à l'archétype et au niveau du héros principal.

## Ta tâche

**Phase 1 (obligatoire) : le catalogue.**
1. Concevoir un catalogue d'équipement **par emplacement, par classe, par rareté et par palier de niveau** (propose les paliers : par exemple 1, 5, 10, 15, 20, 25, 30). Vise une poignée d'objets par emplacement pour l'alpha (contrat §5 : variété sacrifiable), avec une progression lisible et au moins un objet marquant par rareté élevée. Les noms et descriptions s'appuient sur le lore (Straumar, mana, Rupture, Vahrun, ruines elfiques et naines) sans contredire `docs/lore.md`.
2. Écrire les valeurs d'effets selon une **règle de budget explicite** (tableau : rareté × niveau → budget total en points de pourcentage, répartition possible entre effets, effets autorisés par emplacement et par classe). Équilibre entre axes : la production se cumule sur des semaines, le combat sur une bataille, la garnison seulement quand la cité est attaquée.
3. Mettre à jour `database/catalog/items.php` (même format que l'existant, compatible `php artisan kalendrath:items:sync`), les traductions FR et EN, `config/loot.php` (`item_weights` : chaque code du catalogue doit y figurer, un test le vérifie) et tout le nécessaire pour que les tests existants restent verts. Les codes existants peuvent être conservés, renommés ou retirés (un code retiré se « retire » du catalogue, il ne se supprime pas ; voir l'en-tête de `items.php`).

**Phase 2 (obligatoire) : les tables de butin.**
4. Définir, par **source** (expédition, camp de bot palier 1, camp de bot palier 2) : la probabilité d'obtenir un objet (`drop_chance`, ou le poids de l'issue « objet » des expéditions), la **répartition entre raretés** (via `rarity_multipliers` et les poids), la **répartition des niveaux** (marge de niveau) et le filtre d'emplacements éventuel. Une expédition est une exploration, un camp est un combat : justifie les différences (par exemple raretés élevées plus probables en camp, expéditions plus variées en emplacements).
5. Appuie-toi sur `php artisan kalendrath:loot:simulate` (étends-le au besoin) pour démontrer : le nombre d'objets par heure active et par semaine pour un joueur actif sur serveur ×5, la **distribution des raretés**, le délai moyen avant le premier objet utile, et le délai avant que l'inventaire de 32 places soit plein. Objectifs proposés à confirmer ou corriger dans ton rapport : environ un objet utile par session active ; inventaire plein en une à deux semaines ; une rareté orange exceptionnelle (moins de 1 % des tirages) ; une rareté violette rare (moins de 5 %).
6. Tests Pest : le catalogue se synchronise sans erreur, chaque objet est équipable par au moins un archétype alpha, chaque emplacement a au moins un objet pour chaque archétype, chaque code a un poids, les tables de butin produisent les répartitions annoncées (test statistique à graine fixe), aucune régression sur `tests/Feature/Items` et `tests/Feature/Hero`.

**Phase 3 (facultative, seulement s'il te reste du budget) : élargir les effets.** Propose, dans le rapport, une liste hiérarchisée des effets supplémentaires qui rendraient le catalogue plus intéressant (par exemple `loot_bonus_percent`, réduction du temps de construction ou de recherche, vitesse de mission, réduction de coût). Implémente au plus **deux** consommateurs, les plus simples et les plus sûrs, avec leurs tests, sans logique de jeu dans les contrôleurs, et n'ajoute à des objets du catalogue que les effets effectivement branchés.

## Contraintes

- Chaîne du dépôt avant de rendre : Rector, Pint (uniquement sur les fichiers que tu modifies, jamais sur tout le dépôt), PHPStan, puis les tests concernés. Les tests existants (1109 hérités plus ceux des objets et du héros) doivent rester verts.
- Ne touche pas à `config/heroes.php` `axis_caps`, ni au schéma des objets sauf nécessité démontrée (alors migration additive).
- Ne modifie aucun fichier sans rapport avec la tâche. Pas de commit sur `main`.

## Livrable

Un rapport en français, `docs/archives/retours-agents-2026-11-21-catalogue-equipements-et-butin.md`, avec : la règle de budget (tableau), la liste complète des objets (code, nom FR et EN, emplacement, classe, rareté, niveau, effets), les tables de butin (tableau final par source), les résultats de simulation et leur lecture par rapport aux objectifs, les noms provisoires à faire valider par Gregory, les décisions que tu lui laisses, les limites. Pousse ta branche avant de t'arrêter et indique son nom dans ton rapport.
