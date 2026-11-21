# Représailles PvE · registre et déclenchement

21 novembre 2026 · Branche `feat/alpha-represailles-pve`.

**Livraison partielle.** Le serveur peut consigner un raid déclencheur. Les entrées sont inertes. Il n’y a ni réservation, ni départ, ni frappe. Arrêt avant la tâche 3 du découpage, au seuil du traitement autonome.

## Travaux livrés

1. **Tâche 1 allégée.** Migration additive pour `pve_retaliation_intents`, unicité en base par mission source. Les identifiants de mission, de rapport, de royaume, de cité et de camp restent disponibles à l’audit après suppression des entités sources. La cité est aussi identifiée par ses coordonnées de départ. Les paramètres du rapport de conception sont bornés. Les limites de pertes par impact, sur 24 h et sur 168 h doivent être croissantes. Le réglage `pve.retaliation.enabled` prend sa valeur de `PVE_RETALIATION_ENABLED`. L’interrupteur coupe la consignation. Une configuration invalide est journalisée et la consignation est ignorée pour préserver le raid sortant. Le réglage est déployé par configuration, avec rechargement des processus qui la gardent en mémoire.
2. **Tâche 2.** Après la création du retour du raid solo, le registre reprend la définition de victoire de `PveRaidLootService` et la part de stock de l’armée survivante. Il faut au moins une unité entière de Bois, Fer ou Mana. La cité de départ doit exister, appartenir au royaume du raideur et occuper les coordonnées de départ. Le chemin normal passe dans la transaction de `PlayerService::updateFleetMissions()`, et le retraitement d’administration utilise sa propre transaction. Une réexécution conserve le premier rapport et les mêmes identifiants. Le tirage d’objet demeure indépendant.
3. **Tâches 3 à 7 en attente.** Les protections temporelles, quotas, réservations et échéances ne sont pas encore calculés. Une ligne signifie « déclencheur observé », donc les lignes de cette version doivent rester historiques lors de l’activation future. Aucun ordonnanceur ne les consomme. Aucun changement aux routes ni à l’application du combat sortant. Aucune API, alerte ou interface de représailles.

## Motif de l’arrêt

Le travail suivant introduit une deuxième voie d’exécution en concurrence avec les mises à jour par les joueurs. Le chemin normal de `PlayerService::updateFleetMissions()` verrouille d’abord les cités d’un royaume, puis la mission. Le chemin de rattrapage d’administration dans `ServerAdministrationController::processStuckMission()` prend d’abord le verrou de mission, puis peut entrer dans `AttackMission` et prendre le verrou de la planète défendue via `PlanetService::update()`. Ajouter un worker sur ces voies sans contrat commun ferait dépendre la reprise d’un ordre de verrouillage opposé. Le recruteur prend de son côté le verrou de la planète du camp avant d’ajuster les unités.

Il faut également traiter les protections dans ce même contrat. `PlayerService::activateVacationMode()` sauvegarde le compte puis règle la production de chaque cité. Ces opérations ne constituent pas une transition atomique avec la résolution d’une mission. Enfin, `BattleEngine::applyTacticalRetreat()`, appelée pendant `simulateBattle()`, peut débiter le Mana du défenseur avant le résultat. Un contrôle du résultat ajouté après cet appel arriverait trop tard.

Pour reprendre, définir et tester un ordre de verrouillage commun pour les transitions de représailles, la préparation, l’impact et le retour, avec lectures fraîches sous verrou. Couvrir au minimum le raid simultané au recrutement, l’impact concurrent avec les vacances ou un envoi de troupes et le rattrapage concurrent de l’administration. Introduire ensuite un parcours de simulation commun aux deux moteurs qui neutralise le retrait tactique avant de brancher la moindre application de résultat. Le test de concurrence hérité sur un transport ne couvre pas ces cas.

## Paramètres mis en configuration

Les rapports et parts sont écrits en fractions dans le fichier. Les valeurs ci-dessous sont celles de l’alpha ×5. Elles sont validées mais seule la bascule d’enregistrement a un effet dans ce lot.

| Clé sous `pve.retaliation` | Valeur |
|---|---:|
| `enabled` | vrai |
| `preparation_hours` | 8 h |
| `max_travel_minutes` | 120 min |
| `max_processing_delay_minutes` | 15 min |
| `camp_cooldown_hours` | 12 h |
| `target_attempts_24h` / `target_attempts_168h` | 1 / 2 |
| `min_account_age_hours` / `min_city_age_hours` | 72 h / 24 h |
| `min_defense_wood_equivalent` | 60 000 |
| `max_combat_axis_ratio` | 60 % |
| `max_detachment_share` / `min_garrison_reserve_share` | 25 % / 50 % |
| `max_fuel_stock_share` / `max_loot_stock_share` | 10 % / 10 % |
| `protected_production_hours` | 6 h |
| `max_loot_production_hours_per_hit` / `_24h` / `_168h` | 2 h / 2 h / 4 h |
| `max_unit_loss_share_per_hit` / `_24h` / `_168h` | 10 % / 10 % / 20 % |

## Vérifications

Tests ajoutés sur les paramètres et leurs bornes, la victoire avec stock mais sans objet, la conservation de la première écriture au rejeu, l’interrupteur, le camp vide et l’attaque hors camp. La migration s’applique sur MariaDB. La suite Pest complète sur base isolée passe avec 1 281 tests réussis, 42 notices et 22 485 assertions. Rector ciblé et Pint sur les sept fichiers PHP modifiés passent. PHPStan global remonte les sept erreurs déjà observées sur les commandes d’administration, de démo, de création des camps et de performance. Aucune dans les fichiers modifiés.

`ogamex:test:race-condition-game-mission` passe sur le Docker local. Cinq itérations de dix requêtes donnent chacune un seul retour du transport. Le test crée et remplace son propre compte local.

## Validation et simulation pendant l’alpha

- Vérifier la portée de 120 min depuis les positions de départ et la part des raids qui réunissent les conditions d’âge, de défense et de ratio par axe.
- Mesurer sur les deux moteurs les combats acceptés, refusés et les pertes permanentes pour les petites garnisons, les défenses asymétriques et les deux archétypes de héros.
- Rejouer vacances, déménagement, camp supprimé et redémarrage à chacune des échéances. Vérifier le préavis 8 h et l’absence d’impact offensif hors du délai validé.
- Mesurer pendant 14 jours le coût de recrutement, la consommation de Mana et le bilan stock pris, rendu et crédité. Inclure les camps disputés et les retours simultanés.
- Gregory peut trancher la nécessité d’une bascule d’administration avant activation et valider où implanter le premier camp à portée des cités alpha. Le libellé du bot reste à déterminer. La priorité technique avant activation est de tester le contrat de verrouillage et les garanties sur les effets du moteur.
