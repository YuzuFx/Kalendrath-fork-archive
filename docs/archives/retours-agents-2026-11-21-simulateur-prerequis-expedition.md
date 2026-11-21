# Retours d'agent — prérequis d'expédition au simulateur (2026-11-21)

Question : le prérequis **Académie Militaire 5 + Endurance du Fantassin 3 + Lecture des Runes 3** est-il cohérent avec le rythme ×5 et le critère 12 du contrat d'alpha (« premiers paliers de cité et première expédition dans la première session ») ? Lecture seule : `model.js` et le code PHP n'ont pas été touchés. Les scripts de simulation sont dans le scratchpad de session (hors dépôt), copie modifiée de `model.js` comprise.

## 1. Chaîne de prérequis (d'après `model.js`, calculée par `closure()`)

Fermeture : Académie 5, Maîtres d'œuvre 2, Champ de blé 3, **Hôtel de ville 4 (palier 2 « Village »)**, Rite d'Ancrage 1, Institut 1, Puits de mana 3, Endurance 3, Abondance 1, Lecture 3.

| Cible | Exige directement |
|---|---|
| Académie Militaire 5 | Maîtres d'œuvre 2, Champ de blé 3, et **palier 2** (Hôtel de ville 4, car l'Académie est un bâtiment de palier 2) |
| Hôtel de ville 4 (Village) | Hôtel de ville 3, Rite d'Ancrage 1, Maîtres d'œuvre 2 |
| Rite d'Ancrage 1 | Institut 1 |
| Institut Arcanique 1 | Hôtel de ville 2, Puits de mana 1 |
| Endurance du Fantassin 3 | Abondance 1, Institut 1 |
| Lecture des Runes 3 | Institut 1, Puits de mana 3 |

Ordre raisonnable (c'est celui que suit le simulateur) : Scierie / Mine de fer / Champ 3 (<1 h) → Puits 1-5, Atelier des Maîtres d'œuvre 1 → Hôtel de ville 2 + Institut 1 → Lecture 1-3 → Rite 1 → Abondance 1 → Endurance 1 → Maîtres d'œuvre 2 → Endurance 2-3 → Hôtel de ville 3-4 → Académie 1-5 (la 5 attend le fer). Le facteur limitant est l'économie : le simulateur fait monter Scierie 13, Mine de fer 12 et Puits 10 pour payer l'Académie 5.

**Le coût dominant n'est pas l'Académie elle-même mais le palier Village** (Rite + Maîtres 2 + Hôtel de ville 4) qu'elle impose. Sans Académie (End 3 + Lec 3 seuls), on gagne environ 12 h en continu.

## 2. Temps réel et coûts à ×5

Protocole : `simulate()` de `model.js`, vitesse 5, empire en croissance (les colonies n'influent pas avant 4 jours, donc pas sur ces chiffres), profil `reference` (moteur seul), cibles `[academie 5, endurance 3, lecture 3]`. Le profil `gratuit` (+10 % production, +10 % vitesse, 2 h d'accélérateur par jour) donne 20,8 h au lieu de 23,0 h en continu.

Le modèle n'a pas de notion de session : il agit à l'instant où une ressource arrive (« continu »). Pour représenter des visites, j'ai fait une **copie de `model.js` hors dépôt** où les lancements de chantier et d'étude ne sont permis que pendant des fenêtres de visite de 15 min, première session de 1 h à la création. Les profils de visites (« très actif », « actif », « moyen », « occasionnel ») sont **mes hypothèses**, pas des profils du modèle.

| Profil | Temps pour A (Acad 5 / End 3 / Lec 3) |
|---|---|
| Continu, aucune limite de session (borne basse théorique) | 23,0 h |
| Très actif : 8 visites de 15 min par jour (7 h, 9 h, 11 h 30, 13 h, 15 h, 17 h 30, 19 h 30, 22 h) | 39,2 h (1,6 j) |
| Actif : 4 visites de 15 min par jour | 2,9 j |
| Moyen : 2 visites de 15 min par jour (12 h 30, 20 h) | 5,8 j |
| Occasionnel : 1 visite de 15 min par jour | 11,8 j |

Avec une première session de 3 h au lieu de 1 h : 37,2 h (très actif), 2,8 j (actif), 5,5 j (moyen), 10,8 j (occasionnel). Profil `gratuit` : 41,5 h, 2,9 j, 5,8 j, 11,8 j (1re session 1 h).

Jalons intermédiaires (profil `reference`, ×5, heures depuis la création du compte) :

| Jalon | Continu | Très actif | Moyen |
|---|---|---|---|
| Champ de blé 3 | 0,8 h | 0,8 h | 0,8 h |
| Hôtel de ville 2 + Institut 1 | 5,8 h | 9,2 h | 20,2 h |
| Lecture des Runes 3 | 6,5 h | 11,6 h | 36,6 h |
| Rite 1 (précède le Village) | 6,6 h | 11,7 h | 36,7 h |
| Maîtres d'œuvre 2 | 7,8 h | 11,7 h | 36,6 h |
| Endurance du Fantassin 3 | 13,1 h | 17,7 h | 44,3 h |
| Académie 3 (Village atteint, HdV 4) | 15,5 h | 22,0 h | 84,5 h |
| Académie 5 | 23,0 h | 39,2 h | 140,2 h |

Coûts (Bois / Fer / Mana, modèle) :
- **Coût direct de la chaîne** (niveaux 1 à n de tout sauf Scierie et Mine de fer ; inclut Puits 1-3) : **25 070 / 12 898 / 10 136**.
- **Coût cumulé réellement dépensé** par le joueur simulé, mines d'économie comprises (journal du simulateur, profil `reference`, ×5, continu) : **94 757 / 37 804 / 10 036**. Le surplus est l'économie nécessaire pour financer l'Académie 5 (surtout Scierie, Mine de fer, Puits).
- **Nourriture : 0.** Aucun bâtiment ni aucune de ces trois recherches ne coûte de nourriture (GDD 4.1, et `COST` ne la modélise pas). La nourriture n'interviendra que sur les troupes envoyées en expédition, non modélisées.
- Étapes : Académie 5 seule coûte 6 400 / 3 200 / 1 600 ; Endurance 3 : 1 600 / 0 / 2 400.

## 3. Lecture par rapport au critère 12

- « Première session » : même sans aucune contrainte de présence, le prérequis demande **23 h** de temps réel (21 h au profil gratuit). Aucune session ne les couvre. **Critère non tenu** pour la première expédition.
- Un joueur très actif (8 visites par jour) y arrive en **1,6 jour**, soit 4 à 5 visites réparties sur deux jours. Un joueur moyen (2 visites par jour) y arrive en **environ 6 jours**, soit 12 visites : tout juste « moins d'une semaine », sans marge.
- Les paliers de cité du critère 12 : le Village (HdV 4) tombe à 5,8 h (continu) ou 9,2 h (très actif) quand on le cible seul avec l'Institut ; avec la chaîne ci-dessus, Académie 3 (donc palier 2 atteint) à 15,5 h (continu) ou 22 h (très actif). Le Village est donc atteint dans la première journée seulement par un joueur très actif. Je n'ai pas simulé les paliers 3 et suivants.
- **Verdict : trop long** pour « première expédition en première session », bien calibré pour « boucle moyenne visible en moins d'une semaine » seulement pour un joueur au moins moyen. Les durées de construction ne sont presque pas en cause (chantiers de secondes à 25 min à ×5) : c'est le **débit de ressources** qui limite, donc le levier est le coût et le nombre de niveaux, pas les durées.
- Nuance : le temps ci-dessus est celui du prérequis. Il faut ensuite former les troupes à l'Académie, envoyer l'expédition et attendre son retour (au moins 1 h de séjour). Ce délai n'est pas modélisé.

## 4. Variantes testées (×5, profil `reference`, mêmes protocoles)

| Variante | Continu | Très actif | Moyen | Coût direct B / F / M |
|---|---|---|---|---|
| A : Acad 5 / End 3 / Lec 3 (décision) | 23,0 h | 39,2 h | 5,8 j | 25 070 / 12 898 / 10 136 |
| B : Acad 3 / End 2 / Lec 2 | 15,1 h | 22,0 h | 3,5 j | 13 070 / 7 698 / 5 336 |
| C : Acad 2 / End 2 / Lec 2 | 14,6 h | 22,0 h | 3,5 j | 11 470 / 6 898 / 4 936 |
| D : Acad 2 / End 1 / Lec 1 | 14,5 h | 22,0 h | 3,5 j | 10 270 / 6 698 / 3 736 |
| E : Acad 3 / End 1 / Lec 1 | 14,7 h | 22,0 h | 3,5 j | 11 870 / 7 498 / 4 136 |
| F : End 3 / Lec 3, sans Académie | 10,9 h | 17,7 h | 44,4 h | 6 628 / 2 980 / 5 090 |
| G : End 2 / Lec 2, sans Académie | 7,9 h | 13,1 h | 44,1 h | 4 228 / 2 580 / 2 690 |

Enseignements :
- Réduire les niveaux d'Académie, d'Endurance et de Lecture ne sert presque à rien dès qu'on garde l'Académie : le plancher est le **palier Village** (14,5 h en continu, 22 h très actif, 3,5 j moyen). B à E sont équivalents entre eux, à ±1 h près.
- Passer de A à B ou D fait gagner 8 h (continu), 17 h (très actif) et 2,3 jours (moyen).
- Seul le retrait de l'Académie du prérequis fait baisser le plancher (F, G) : 8 à 11 h en continu, 13 à 18 h très actif ; le joueur moyen reste à ~44 h car il est limité par ses visites (Institut + Puits 8 pour la mana d'Endurance).
- **Aucune variante testée ne met la première expédition dans une première session** : le minimum testé est de 7,9 h en continu. Pour y arriver, il faudrait agir sur l'économie de départ (ressources initiales, revenu de base, vitesse), pas seulement sur le prérequis. Je ne l'ai pas simulé.
- Une nuance sur G : sans Académie, avec quelles troupes envoyer l'expédition ? Le modèle ne le dit pas. Cette variante suppose une expédition d'un héros seul ou d'unités d'un autre bâtiment : à trancher par Gregory.

## 5. Défaut connu du simulateur et limites

Défaut du README (règle « action la moins chère » qui lance des mines aux chantiers de 68 à 123 jours et monopolise la file, saut de 130 jours sur le facteur de l'Hôtel de ville) : il concerne les **paliers lointains** et les durées longues. **Il n'affecte pas ces chiffres** : à ×5 et sur ce périmètre, le plus long chantier est de 0,4 h et la file de construction n'est jamais bloquée (journal inspecté). Je ne l'ai pas contourné, il n'est simplement pas atteint. Il pèserait si on voulait lire les paliers 4 à 6 de la même simulation.

Limites réelles qui touchent ces chiffres :
- **Pas de sessions dans le modèle** : « continu » est une borne basse irréaliste, les profils de visites sont ma copie modifiée hors dépôt et mes hypothèses (15 min par visite, horaires). Les deux autres moteurs de lancement (construction et étude) sont traités indépendamment.
- Ressources de départ du modèle : 500 bois, 500 fer, 0 mana. **Non vérifié** contre les ressources de départ du domaine PHP.
- Revenu de base 30 bois / 15 fer par heure, ×5, hérité d'OGameX ; pas d'énergie, pas de marchand, de pillage, d'expédition, de héros ni de classe (README). Un joueur actif réel, avec classe, héros et pillage de bots, irait plus vite.
- Le modèle utilise les prérequis **du modèle** (Académie : Maîtres 2 + Champ 3 + palier 2). Le **domaine PHP actuel est encore celui d'OGameX** : `shipyard` n'exige que `robot_factory` 2, `combustion_drive` exige `energy_technology` 1 + `research_lab` 1, `laser_technology` exige `energy_technology` 2 + `research_lab` 1, et il n'existe pas de Hôtel de ville ni de Rite dans `app/GameObjects`. La chaîne du modèle n'est donc pas encore celle du jeu.
- Aucune durée ni aucun coût n'a été inventé : tout vient de `COST`, `DUREES` et des formules de `model.js`. Estimations manuelles : aucune dans ce rapport, hormis la lecture « 4 à 5 visites » (jalons divisés par l'intervalle entre visites).
- Les profils `gratuit` et `payeur` ne sont donnés qu'en continu ; les autres combinaisons ne sont pas présentées.

## 6. Où le prérequis actuel (astrophysics) est vérifié, et fichiers à modifier

Vérifié dans le code PHP (rien modifié) :

| Fichier | Rôle actuel | Modification pour le nouveau prérequis |
|---|---|---|
| `app/GameMissions/ExpeditionMission.php`, l. 158-165 (méthode de vérification de mission possible) | Refuse l'envoi si `getResearchLevel('astrophysics') <= 0` | Remplacer par Académie `shipyard` >= 5, `combustion_drive` >= 3, `laser_technology` >= 3 ; adapter le message d'erreur (+ traduction `resources/lang/fr` et `en`) |
| `app/Services/PlayerService.php`, l. 605-624 (`getExpeditionSlotsMax`) | Emplacements = `floor(sqrt(niveau astrophysics))` + bonus serveur + bonus de classe : **sans astrophysics, 0 emplacement, donc aucune expédition même avec le nouveau prérequis** | Redéfinir la source des emplacements (formule sur un autre niveau, ou emplacement de base de 1). Le réglage `bonus_expedition_slots` (SettingsService l. 543, admin) existe déjà et peut servir de solution temporaire |
| `app/Http/Controllers/FleetController.php`, l. 553-563 | Durée d'attente (`holding_hours`) bornée par le niveau `astrophysics` : **sans astrophysics, durée max 0 h, envoi refusé** | Nouvelle borne de durée (niveau d'Endurance ou constante) |
| `resources/views/ingame/fleet/index.blade.php`, l. 1311 | Boucle des durées proposées `for $i <= astrophysics` (Blade legacy, à jeter) | À refaire dans la nouvelle interface React ; l'API doit exposer la durée max |
| `app/GameObjects/ResearchObjects.php`, l. 220-246 | Définit `astrophysics` : prérequis, `MAX_COLONIES`, `MAX_EXPEDITION_SLOTS` | Retirer `MAX_EXPEDITION_SLOTS` si la source change ; la Découverte Tellurique garde la colonisation |
| `app/GameObjects/StationObjects.php` (`shipyard`, l. 45) | `shipyard` exige `robot_factory` 2 | Ajouter Champ de blé 3 et palier de cité (HdV) si le modèle est repris dans le domaine |
| `app/GameObjects/ResearchObjects.php` (`combustion_drive` l. 124-132, `laser_technology` l. 44-52) | Prérequis OGameX (`energy_technology`, `research_lab`) | Aligner sur le modèle (Abondance 1 + Institut 1 ; Institut 1 + Puits de mana 3) |
| `app/Http/Controllers/TechtreeController.php` (l. 385-405) | Table d'astrophysique liée aux emplacements d'expédition | Adapter si la formule des emplacements change |
| `tools/theorycrafting/model.js` | Pas de prérequis d'expédition modélisé | Ajouter un élément « Expédition » avec ces prérequis et une fenêtre cible, après décision |
| `docs/game-design-document.md` (l. 263, 461, 621) | Dit que `astrophysics` porte `MAX_EXPEDITION_SLOTS` | Mettre à jour |

Colonisation (`ColonisationMission.php`, `PlayerService` l. 862-890, `GalaxyController` l. 612-764) continue d'utiliser `astrophysics` : elle ne change pas.

Tests à adapter (ils posent `astrophysics` pour autoriser l'expédition) : `tests/Feature/FleetDispatch/FleetDispatchExpeditionTest.php` (l. 54-55, 127-128, 161-176 et suivants), `tests/Feature/Items/ExpeditionItemLootTest.php` (l. 41, 137-138), `tests/Feature/ExpeditionDiscovererCombatReductionTest.php`, `tests/Feature/FleetDispatch/FleetDispatchMissionResourceHandlingTest.php`, et ajouter un test Pest du nouveau refus (un par prérequis manquant). Sans modifier ceux qui couvrent la colonisation.

## 7. Recommandation

1. Le prérequis A est cohérent avec « boucle moyenne en moins d'une semaine », mais **pas avec « première expédition en première session »** : 23 h en continu, 1,6 jour très actif, ~6 jours moyen, à ×5.
2. Le goulot est le **palier Village imposé par l'Académie** et le débit de ressources, pas les niveaux demandés : réduire à Acad 3 / End 2 / Lec 2 ne gagne que 8 à 17 h, sans atteindre la première session.
3. Pour tenir le critère 12 tel qu'écrit, il faut soit retirer l'Académie du prérequis d'envoi (variante G : 8 h en continu, 13 h très actif), soit reformuler le critère en « première expédition dans les 24 premières heures pour un joueur très actif » (variantes B à E).
4. Le prérequis A convient si Gregory veut un début plus lent, à condition de relâcher le critère 12 ; je le déconseille tel quel pour l'alpha, où l'entonnoir « première expédition » doit être franchi vite par les testeurs.
5. Avant tout choix, corriger le code : sans `astrophysics`, les emplacements et la durée d'expédition tombent à 0 (§6), donc le nouveau prérequis ne suffit pas à rendre l'expédition possible.
