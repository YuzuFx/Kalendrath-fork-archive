# Catalogue d'équipement et butin de l'alpha

21 novembre 2026 · Branche `feat/alpha-catalogue-butin`, issue de `feat/alpha-objets`.

L’alpha dispose de **47 pièces** dont 24 nouvelles. Les 23 codes existants sont conservés. Guerrier et Sorcier peuvent chacun remplir les 12 emplacements dès le niveau 1, avec des objets communs au torse et aux jambes et deux anneaux au choix. Les paliers supérieurs ajoutent quelques pièces ciblées à chaque étape. Le haubert et l’habit de Kaelhelm et le torque des Straumar distinguent le palier orange. Le heaume et le voile de la Rupture ainsi que la cape du seuil donnent des silhouettes propres au palier violet.

## Budget d’une pièce

Le budget se calcule en points à partir des pourcentages affichés. Un point de pourcentage coûte **2** pour la production d’une ressource, **4** pour la production de Bois, Fer et Mana ensemble, **1,5** pour le combat des troupes et **1** pour la garnison. La production vaut davantage car elle s’applique en continu. Le combat des troupes touche plusieurs caractéristiques en attaque comme en défense. La garnison n’ajoute que bouclier et structure lorsque la cité est défendue. Les effets se cumulent sur la base selon les consommateurs existants.

| Niveau requis | Blanc | Vert | Bleu | Violet | Orange |
|---|---:|---:|---:|---:|---:|
| 1 | 3 | — | — | — | — |
| 3 | — | 4,5 | — | — | — |
| 5 | — | 4,5 | — | — | — |
| 10 | — | — | 4 | — | — |
| 15 | — | — | 4,25 | — | — |
| 20 | — | — | 4,5 | 5 | — |
| 25 | — | — | — | 5,5 | — |
| 30 | — | — | — | — | 6 |

Les tirets signalent des combinaisons absentes de l’alpha. Les verts 3 et 5 partagent un budget pour garder les anciennes pièces hybrides. Plusieurs pièces restent sous leur budget pour privilégier une spécialité lisible. Une pièce peut répartir son budget entre deux effets. Par exemple, l’anneau de la faille coûte 5 points avec +3 % de garnison et +1 % de Mana. Le haubert orange coûte 6 points avec +4 % de combat. La règle sert à concevoir les pièces. Le moteur applique les valeurs des définitions à la portée du royaume.

| Emplacement | Guerrier (`war`) | Sorcier (`wizard`) | Commun (`any`) |
|---|---|---|---|
| Tête | C | P | P |
| Collier | — | — | P |
| Épaules | G | G, P | — |
| Cape | — | — | G, P |
| Torse | C, P | P | G |
| Poignets | P | P | P |
| Mains | C, P | P | C |
| Ceinture | P | P | P |
| Jambes | G, P | P | G, P |
| Pieds | C | P | C, P |
| Anneau (deux positions) | — | — | G, P |

**P** correspond à `production_bonus_percent`, **C** à `troop_combat_bonus_percent` et **G** à `garrison_defense_bonus_percent`. La production cible `metal`, `crystal`, `deuterium` ou `*`. Combat et garnison ciblent `*` dans le catalogue.

### Héros et équipement ensemble

Les valeurs ci-dessous maximisent chaque axe séparément en choisissant la meilleure pièce par position autorisée, y compris deux exemplaires du même anneau. Les colonnes décrivent donc plusieurs tenues spécialisées. Les rendements de talents utilisés comme référence viennent du GDD §5.5, soit 0,35 point de production et 0,5 point de combat ou de garnison par point investi. Les 29 points ne peuvent être dépensés qu’une fois.

| Profil | Bois | Fer | Mana | Combat | Garnison |
|---|---:|---:|---:|---:|---:|
| Guerrier 9, équipement | 5,5 % | 6,5 % | 4 % | 5 % | 5 % |
| Sorcier 9, équipement | 5 % | 5,5 % | 8,5 % | 2 % | 1 % |
| Guerrier 30, équipement | 12,5 % | 8,5 % | 8 % | 13,5 % | 19 % |
| Sorcier 30, équipement | 12 % | 7,5 % | 17,5 % | 5 % | 19 % |
| Contribution indicative de 29 points sur un axe | 10,15 % | 10,15 % | 10,15 % | 14,5 % | 14,5 % |
| Plus haut total théorique entre les deux classes | 22,65 % | 18,65 % | 27,65 % | 28 % | 33,5 % |

Le Mana du Sorcier atteint 11,3 % au niveau 9 avec huit points spécialisés, légèrement au-dessus du repère historique de 10 %. La garnison dépasse le repère de 30 % en fin de progression, sur l’axe qui ne vaut qu’en défense. L’arbre Guerrier de la **commande de démo** contient aussi deux nœuds cumulables à +8 et +20 % de garnison. S’ils étaient repris tels quels dans l’alpha, le total pourrait atteindre **47 %**. Il faut calibrer ces nœuds si l’arbre de démo sert de base aux talents alpha. Les arbres alpha définitifs restent nécessaires pour vérifier les configurations atteignables en jeu. Aucun plafond n’est ajouté.

## Catalogue complet

Les **47 noms FR et EN ci-dessous sont provisoires** et soumis à Gregory. Les descriptions en jeu emploient le vouvoiement en français. Les vestiges elfes et nains, les obélisques et la Rupture ancrent les pièces dans le lore. Les ombres de la cape du seuil et de l’anneau de la faille évoquent la perception instable des Vahrun en laissant leur origine indéterminée.

| Code | Nom FR provisoire | Nom EN provisoire | Type d’emplacement | Classe | Rareté | Niveau | Effets |
|---|---|---|---|---|---:|---:|---|
| `head_warden_helm` | Heaume du guetteur | Warden's Helm | Tête | `war` | 1 | 1 | C +1 % |
| `head_adept_hood` | Capuche de l'adepte | Adept's Hood | Tête | `wizard` | 1 | 1 | P Mana +1 % |
| `head_rune_circlet` | Diadème runique | Rune Circlet | Tête | `any` | 2 | 5 | P Mana +1,5 % |
| `head_rupture_helm` | Heaume de la Rupture | Helm of the Sundering | Tête | `war` | 4 | 20 | C +3 % |
| `head_rupture_veil` | Voile de la Rupture | Veil of the Sundering | Tête | `wizard` | 4 | 20 | P Mana +2,5 % |
| `neck_traveler_amulet` | Amulette du voyageur | Traveler's Amulet | Collier | `any` | 1 | 1 | P Bois +0,5 % |
| `neck_oath_pendant` | Pendentif du Serment | Oath Pendant | Collier | `any` | 2 | 3 | P Bois, Fer et Mana +0,5 % |
| `neck_rune_pendant` | Pendentif des galeries | Gallery Pendant | Collier | `any` | 3 | 15 | P Fer +2 % |
| `neck_straumar_torc` | Torque des Straumar | Straumar Torc | Collier | `any` | 5 | 30 | P Bois, Fer et Mana +1,5 % |
| `shoulders_warden_pauldrons` | Spalières du guetteur | Warden's Pauldrons | Épaules | `war` | 1 | 1 | G +2 % |
| `shoulders_adept_mantle` | Mantelet de l'adepte | Adept's Mantle | Épaules | `wizard` | 1 | 1 | P Mana +1,5 % |
| `shoulders_gate_pauldrons` | Spalières de la herse | Portcullis Pauldrons | Épaules | `war` | 3 | 10 | G +4 % |
| `shoulders_ward_mantle` | Mantelet des sceaux | Mantle of Seals | Épaules | `wizard` | 3 | 10 | G +4 % |
| `cloak_traveler_cloak` | Cape du voyageur | Traveler's Cloak | Cape | `any` | 1 | 1 | P Fer +0,5 % |
| `cloak_shard_mantle` | Manteau des Éclats | Shard Mantle | Cape | `any` | 2 | 3 | P Mana +1 % |
| `cloak_elfweave` | Cape de trame elfique | Elvenweave Cloak | Cape | `any` | 3 | 15 | P Bois, Fer et Mana +1 % |
| `cloak_threshold_cloak` | Cape du seuil | Threshold Cloak | Cape | `any` | 4 | 25 | G +4 % |
| `chest_quilted_coat` | Gambison matelassé | Quilted Coat | Torse | `any` | 1 | 1 | G +1 % |
| `chest_warden_hauberk` | Haubert du guetteur | Warden's Hauberk | Torse | `war` | 2 | 3 | C +1,5 % · P Bois +1 % |
| `chest_adept_robe` | Robe de l'adepte | Adept's Robe | Torse | `wizard` | 2 | 3 | P Mana +1 % · P Bois, Fer et Mana +0,5 % |
| `chest_kaelhelm_mail` | Haubert de Kaelhelm | Hauberk of Kaelhelm | Torse | `war` | 5 | 30 | C +4 % |
| `chest_kaelhelm_vestment` | Habit de Kaelhelm | Vestment of Kaelhelm | Torse | `wizard` | 5 | 30 | P Mana +3 % |
| `wrists_warden_bracers` | Brassards du guetteur | Warden's Bracers | Poignets | `war` | 1 | 1 | P Fer +1 % |
| `wrists_adept_cuffs` | Manchettes de l'adepte | Adept's Cuffs | Poignets | `wizard` | 1 | 1 | P Fer +1 % |
| `wrists_dwarven_cuffs` | Brassards des vestiges nains | Dwarven Ruin Bracers | Poignets | `any` | 2 | 5 | P Fer +1,5 % |
| `wrists_obelisk_bracers` | Brassards d’obélisque | Obelisk Bracers | Poignets | `any` | 3 | 20 | P Mana +2 % |
| `hands_warden_gauntlets` | Gantelets du guetteur | Warden's Gauntlets | Mains | `war` | 1 | 1 | P Bois +1 % |
| `hands_adept_gloves` | Gants de l'adepte | Adept's Gloves | Mains | `wizard` | 1 | 1 | P Bois +1 % |
| `hands_watch_gloves` | Gants de veille | Watch Gloves | Mains | `any` | 2 | 5 | C +2 % |
| `hands_oath_gauntlets` | Gantelets du Serment Rompu | Gauntlets of the Broken Oath | Mains | `war` | 4 | 25 | C +3,5 % |
| `hands_straumar_gloves` | Gants des Straumar | Straumar Gloves | Mains | `wizard` | 4 | 25 | P Mana +2,5 % |
| `belt_warden_girdle` | Ceinturon du guetteur | Warden's Girdle | Ceinture | `war` | 1 | 1 | P Fer +1,5 % |
| `belt_adept_sash` | Écharpe de l'adepte | Adept's Sash | Ceinture | `wizard` | 1 | 1 | P Mana +1 % |
| `belt_woodward_belt` | Ceinture des scieries | Sawmill Belt | Ceinture | `any` | 3 | 15 | P Bois +2 % |
| `legs_road_trousers` | Braies de chantier | Worksite Trousers | Jambes | `any` | 1 | 1 | P Fer +0,5 % |
| `legs_warden_greaves` | Grèves du guetteur | Warden's Greaves | Jambes | `war` | 2 | 3 | G +2 % · P Bois +0,5 % |
| `legs_adept_leggings` | Chausses de l'adepte | Adept's Leggings | Jambes | `wizard` | 2 | 3 | P Bois, Fer et Mana +0,5 % |
| `legs_rampart_greaves` | Jambières de rempart | Rampart Legguards | Jambes | `any` | 3 | 15 | G +4 % |
| `boots_warden_sabatons` | Solerets du guetteur | Warden's Sabatons | Pieds | `war` | 1 | 1 | C +0,5 % |
| `boots_adept_slippers` | Sandales de l'adepte | Adept's Slippers | Pieds | `wizard` | 1 | 1 | P Bois +1,5 % |
| `boots_ruin_boots` | Bottes des ruines | Ruin Boots | Pieds | `any` | 2 | 5 | P Bois +1,5 % |
| `boots_deep_road_boots` | Bottes des voies profondes | Deep Road Boots | Pieds | `any` | 3 | 20 | C +3 % |
| `ring_copper_band` | Anneau de cuivre | Copper Band | Anneau | `any` | 1 | 1 | P Bois +0,5 % |
| `ring_mana_signet` | Chevalière de mana | Mana Signet | Anneau | `any` | 1 | 1 | P Mana +0,5 % |
| `ring_iron_seal` | Sceau de fer | Iron Seal | Anneau | `any` | 2 | 3 | P Fer +1 % |
| `ring_amber_loop` | Anneau d’ambre | Amber Ring | Anneau | `any` | 3 | 10 | P Bois +2 % |
| `ring_seam_ring` | Anneau de la faille | Rift Ring | Anneau | `any` | 4 | 25 | G +3 % · P Mana +1 % |

## Tables de butin

| Source | Chance par sortie admissible | Multiplicateurs Blanc / Vert / Bleu / Violet / Orange | Niveaux | Emplacements |
|---|---|---|---|---|
| Expédition `expedition.t1` | Poids 25 pour l’issue objet, soit 20,02 % avec les autres poids par défaut. Attribution 100 % après cette issue. | 1 / 1 / 1 / 0,7 / 0,6 | 1 à min(niveau du héros + 2, 30) | Tous |
| Camp 1 `pve_camp.t1` | 25 % par armée attaquante survivante d’un raid victorieux | 1 / 1,5 / 1,3 / 1,1 / 1 | Idem | Tous |
| Camp 2 `pve_camp.t2` | 35 % par armée attaquante survivante d’un raid victorieux | 0,8 / 1,8 / 2,1 / 1,2 / 1,8 | Idem | Tous sauf colliers et anneaux |

Poids de base par pièce, avant multiplication. Blanc 100 pour les pièces d’armure, 80 pour la cape et 70 pour les bijoux. Vert 30 pour l’armure, 20 pour les bijoux et la cape. Bleu 13, violet 4 et orange 3,5. Chaque code a une entrée explicite. Le camp 1 est le premier débouché de combat. Le camp 2 privilégie l’équipement porté au combat et augmente les chances de raretés élevées. Les exceptions thématiques sont portées par le filtre d’emplacement du camp 2, où un torse de Kaelhelm reste possible alors que le torque se trouve dans les deux autres sources.

Le jet est adapté à l’archétype et au niveau du héros principal à l’obtention. La marge de +2 fixe la borne haute et laisse toutes les anciennes pièces dans le tirage. Le réglage serveur d’attribution s’applique dans chaque table, avec une valeur par défaut de 1. La migration remplace l’ancien poids 5 par 25 et conserve les autres valeurs explicites, dont 0. Un réglage manuel à 5 se confond avec l’ancien défaut. La version des tables passe à 3.

Le camp `hostile_camp` utilise déjà la table du palier 1. La table du palier 2 est utilisable par configuration et simulable. Son camp reste à dimensionner et à activer après validation de sa garnison, de ses bâtiments et de sa cadence réelle.

## Simulation ×5

Base MariaDB isolée, migrations appliquées et catalogue synchronisé. 100 000 événements par profil et par source, graine **20261121**, pour les niveaux 1, 12, 28 et 30 des deux archétypes. Exemple de reproduction.

```bash
php artisan kalendrath:items:sync
php artisan kalendrath:loot:simulate --table=pve_camp.t2 --archetype=warrior --level=30 --runs=100000 --seed=20261121 --weekly-hours=14
```

Hypothèses de cadence en temps réel. Deux expéditions en parallèle d’environ 1 h 40 chacune, soit **1,2 expédition/h**. **0,5 victoire de camp/h**, une seule armée survivante comptée, en parallèle des expéditions. Le modèle fixe la cadence et le profil du héros. Le temps de trajet, l’accès au contenu, les échecs, la contention et les représailles peuvent réduire la cadence réelle. Le camp 2 remplace le camp 1 dans la variante simulée. Une semaine active de référence compte **14 heures**. Une session de comparaison compte **3 heures**.

### Progression du tirage

Chaque cellule de rareté présente le pourcentage théorique puis le pourcentage observé, parmi les objets obtenus.

| Niveau du héros | Archétype | Table | Blanc | Vert | Bleu | Violet | Orange |
|---:|---|---|---:|---:|---:|---:|---:|
| 1 | Les deux | expedition.t1 | 90,08 / 90,15 | 9,92 / 9,85 | — | — | — |
| 1 | Les deux | pve_camp.t1 | 85,83 / 86,14 | 14,17 / 13,86 | — | — | — |
| 1 | Les deux | pve_camp.t2 | 83,02 / 82,58 | 16,98 / 17,42 | — | — | — |
| 12 | Les deux | expedition.t1 | 80,38 / 80,40 | 17,70 / 17,77 | 1,92 / 1,83 | — | — |
| 12 | Les deux | pve_camp.t1 | 73,46 / 73,50 | 24,26 / 24,34 | 2,28 / 2,16 | — | — |
| 12 | Les deux | pve_camp.t2 | 64,51 / 64,68 | 32,99 / 32,87 | 2,50 / 2,45 | — | — |
| 30 | Les deux | expedition.t1 | 75,20 / 76,01 | 16,56 / 15,94 | 7,18 / 6,97 | 0,77 / 0,81 | 0,29 / 0,27 |
| 30 | Les deux | pve_camp.t1 | 67,71 / 67,84 | 22,36 / 22,19 | 8,40 / 8,38 | 1,09 / 1,17 | 0,43 / 0,43 |
| 30 | Les deux | pve_camp.t2 | 56,39 / 56,42 | 28,83 / 28,90 | 13,12 / 13,20 | 1,15 / 1,01 | 0,50 / 0,47 |

Les deux archétypes ont les mêmes distributions aux profils simulés, car les variantes de classe ont les mêmes poids. Le niveau 28 produit les mêmes répartitions que le niveau 30, avec des oranges équipables seulement au niveau 30. Au niveau maximal, le violet vaut **0,77 à 1,15 %** des objets et l’orange **0,29 à 0,50 %**. Les deux objectifs de rareté sont atteints. Les blancs représentent encore **56 à 75 %** des objets de niveau de héros 30 selon la source. Cela laisse peu d’améliorations aux héros déjà bien équipés. Le palier 2 réduit le poids de ces doublons en privilégiant les verts et bleus.

| Niveau du héros | Table | Part théorique par niveau requis de l’objet | Objets observés sur 100 000 événements (profil Guerrier) |
|---:|---|---|---:|
| 1 | expedition.t1 | 1 → 90,08 %, 3 → 9,92 % | 20108 |
| 1 | pve_camp.t1 | 1 → 85,83 %, 3 → 14,17 % | 25131 |
| 1 | pve_camp.t2 | 1 → 83,02 %, 3 → 16,98 % | 35139 |
| 12 | expedition.t1 | 1 → 80,38 %, 3 → 8,85 %, 5 → 8,85 %, 10 → 1,92 % | 20108 |
| 12 | pve_camp.t1 | 1 → 73,46 %, 3 → 12,13 %, 5 → 12,13 %, 10 → 2,28 % | 25131 |
| 12 | pve_camp.t2 | 1 → 64,51 %, 3 → 13,20 %, 5 → 19,79 %, 10 → 2,50 % | 35137 |
| 30 | expedition.t1 | 1 → 75,20 %, 3 → 8,28 %, 5 → 8,28 %, 10 → 1,79 %, 15 → 3,59 %, 20 → 1,99 %, 25 → 0,58 %, 30 → 0,29 % | 20108 |
| 30 | pve_camp.t1 | 1 → 67,71 %, 3 → 11,18 %, 5 → 11,18 %, 10 → 2,10 %, 15 → 4,20 %, 20 → 2,37 %, 25 → 0,82 %, 30 → 0,43 % | 25131 |
| 30 | pve_camp.t2 | 1 → 56,39 %, 3 → 11,53 %, 5 → 17,30 %, 10 → 2,19 %, 15 → 6,56 %, 20 → 4,76 %, 25 → 0,77 %, 30 → 0,50 % | 35138 |

### Cadence et capacité du sac

| Sources utilisées | Objets/h active | Objets/semaine de 14 h | Premier objet équipable au niv. 1 | Équipables/session de 3 h au niv. 1 | 32 objets au sac | 32 au sac après avoir équipé 12 pièces |
|---|---:|---:|---:|---:|---:|---:|
| Expédition seule | 0,240 | 3,36 | 4,6 h | 0,65 | 9,5 sem. | 13,1 sem. |
| Camp 1 seul | 0,125 | 1,75 | 9,3 h | 0,32 | 18,3 sem. | 25,1 sem. |
| Camp 2 seul | 0,175 | 2,45 | 6,9 h | 0,44 | 13,1 sem. | 18,0 sem. |
| Expédition et camp 1 | 0,365 | 5,11 | 3,09 h | 0,97 | 6,26 sem. | 8,61 sem. |
| Expédition et camp 2 | 0,415 | 5,81 | 2,77 h | 1,08 | 5,51 sem. | 7,57 sem. |

Le simulateur estime le délai d’un **premier objet utile** comme l’inverse du taux horaire de pièces immédiatement équipables pour un héros sans équipement. Il suppose un flux établi, avec les missions déjà lancées. Pour plusieurs sources, leurs taux s’additionnent. L’estimation ignore les créneaux exacts de retour. Au niveau 30, ces délais mixtes passent à 2,74 h et 2,41 h lorsque toutes les pièces sont équipables. Pour un héros déjà équipé, une amélioration sur un emplacement donné est plus rare. Le simulateur ne suit pas les remplacements et les doublons au fil des sorties.

Le rythme mixte atteint l’objectif d’**environ un objet utile par session de trois heures** en début de partie. En phase d’exploration seule, ce rythme dépend notamment de l’ouverture des expéditions et du nombre de départs en parallèle. Le sac de 32 places prend **5,5 à 6,3 semaines** en jeu mixte à 14 h par semaine si tous les objets restent au sac. Équiper douze pièces et conserver tout le reste demande 44 attributions, soit **7,6 à 8,6 semaines**. Les réservations de sorties peuvent bloquer ponctuellement plus tôt. La suppression de doublons repousse le remplissage effectif. À 35 heures actives par semaine, 32 objets demandent environ 2,2 à 2,5 semaines en jeu mixte.

Je retiens **le rythme d’obtention par session** comme cible principale. À 14 heures par semaine, remplir 32 places en deux semaines implique 1,14 objet par heure, soit environ trois objets par session de trois heures. Le sac saturerait plus tôt et suspendrait les départs PvE. Un nouveau test de remplissage avec les habitudes des joueurs de la pré-alpha aidera à fixer cette cible.

## Décisions à valider et suites possibles

- Valider les noms provisoires du tableau, en particulier les références directes à Kaelhelm, à la Rupture et au Serment Rompu.
- Trancher la priorité entre la cadence de découverte et la rapidité de remplissage. Mesurer les sorties terminées par heure et le temps jusqu’au premier objet équipé pendant la pré-alpha.
- Dimensionner le camp de palier 2 avec les pertes, le coût de reconstruction et la concurrence entre joueurs. Mesurer l’intérêt du filtre sans bijoux et le taux réel de doublons. Si les blancs dominent trop tard, tester une pondération par écart au niveau du héros avant de relever le taux global.
- Rejouer la comparaison équipement et talents sur les deux arbres alpha avant leur mise en service. Tester en priorité la garnison et la production de Mana.

Effets supplémentaires par priorité, à étudier après mesure de ces tables.

1. **Qualité du butin** avec `loot_bonus_percent`. Le brancher sur les poids d’un tirage déjà acquis permettrait une spécialisation exploration avec un impact limité sur le volume. Tester les seuils des deux raretés hautes et le journal d’attribution.
2. **Temps de trajet des missions**. Profil logistique lisible. Tester les dates de départ et de retour, les changements d’équipement pendant un trajet et les interactions avec ×5.
3. **Temps de construction ou d’étude**. Envisager un instantané au lancement et vérifier les files déjà commencées et les effets cumulés à ×5.
4. **Réduction des coûts**. Reporter après observation de l’économie. Tester arrondis, remboursements et effets composés sur les délais d’accès au PvE.

Le catalogue repose sur les trois consommateurs actuels. Les fonctionnalités de fabrication restent hors périmètre alpha.

## Vérification

Le premier `kalendrath:items:sync` a créé 47 définitions sur la base isolée. Rector ciblé et Pint limité aux fichiers modifiés passent. Le contrôle Rector global signale quatre transformations préexistantes dans `EquipmentItem`, `HeroEquipment`, `HeroTalent` et `TalentNode`. PHPStan global signale sept erreurs hors des fichiers modifiés, dans les commandes d’administration, de héros de démo, de création des camps et de performance du moteur. La vérification Pest couvre aussi le poids d’expédition et la traduction du message de butin au moment de sa lecture.

La suite Pest complète sur MariaDB isolée, avec six processus, termine à **1 271 tests réussis, aucun échec, 42 notices et 22 441 assertions**. Le test statistique à graine fixe vérifie les trois tables pour chaque classe au niveau 30. Les tests de catalogue vérifient la synchronisation, les noms et descriptions dans chaque langue, la couverture des 12 positions au niveau 1 et les poids des 47 codes.
