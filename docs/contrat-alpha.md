# Contrat d'alpha de Kalendrath

**Statut : brouillon du 2026-11-19, à valider par Gregory.** Ce document borne ce que l'alpha doit contenir, ce qu'elle ne contient pas, et à quoi l'on reconnaît qu'elle est prête. Il remplace, pour l'alpha, le périmètre proposé dans [le rétro-planning de Codex](archives/retours-agents-2026-11-18-plan-alpha-janvier-2027.md). Il s'appuie sur les rapports d'agents du 18 et du 19 novembre (`docs/archives/retours-agents-2026-11-18-retroplanning-*.md`, `retours-agents-2026-11-19-*.md`).

**Légende.** **[Décidé]** : tranché par Gregory (conversation du 2026-11-19). **[Proposé]** : recommandation des agents ou de Claude, à valider.

## 1. À quoi sert cette alpha

L'alpha éprouve le **socle de base** du jeu avec un petit groupe de testeurs, pas la totalité de la vision. Elle doit répondre à quatre questions :

1. Les **arbres de prérequis**, la construction et l'enrôlement des unités tiennent-ils au rythme accéléré ? **[Décidé : serveur ×5 pour les éprouver]**
2. La **difficulté du contenu PvE** (expéditions, camps de bots) est-elle bien réglée ?
3. Le **héros** (Guerrier ou Sorcier, talents, équipement) change-t-il réellement la façon de jouer ?
4. Le **loot d'équipement** donne-t-il envie de repartir en expédition ?

Les fonctions écartées arrivent ensuite, de façon distillée à partir du démarrage de l'alpha, selon la [roadmap publique](roadmap-publique-brouillon.md) (en cours de rédaction).

## 2. Périmètre

### Inclus **[Décidé]**
- Bâtiments, production, études arcaniques, troupes, défenses (moteur hérité, lexique Kalendrath).
- Héros avec **deux archétypes : Guerrier et Sorcier**, leurs talents et l'inventaire.
- Équipement en **12 emplacements** : tête, collier, épaules, cape, torse, poignets, mains, ceinture, jambes, pieds, deux anneaux.
- Contenu PvE : **expéditions** et **pillage de bots PvE**, avec **butin d'équipement prêt à équiper**. Les bots PvE sont donc à anticiper dès l'alpha.
- Serveur **×5**.

### Exclu de l'alpha **[Décidé]** (mais anticipé en base et en architecture)
Métiers, composants, fabrication d'objets, marché entre joueurs, monnaie de commerce. Ils figurent dans la roadmap publique. Le schéma de données les prévoit structurellement (cf. [note sur le schéma des objets](archives/retours-agents-2026-11-19-schema-donnees-objets.md)) sans en implémenter la logique.

### Exclu de l'alpha **[Proposé]**
Autres archétypes (Rôdeur, Gardien de la Foi, Explorateur, Intendant), guerres et PvP entre joueurs, paiements et Kaelite achetable, prestige d'empire complet, événements d'alliance, parrainage, fusion de serveurs, usure de l'équipement. **Les anciennes routes de ces fonctions doivent être fermées côté serveur**, pas seulement masquées dans l'interface.

### Format **[Proposé]**
Alpha fermée sur invitation, 10 à 15 joueurs actifs (inviter 20 à 25), un serveur, **ordinateur et mobile à égalité de priorité [Décidé 2026-11-21]** (une majorité de joueurs est attendue sur mobile), **français seul [Décidé 2026-11-19]** (alpha fermée ; clés de traduction posées partout dès le socle pour l'anglais de la bêta), **comptes remis à zéro entre l'alpha et la bêta [Décidé]**, **ouverture contractuelle le 14 janvier 2027, annoncée sur la vitrine [Décidé 2026-11-21]** (elle remplace « aucune date annoncée »). Le **7 janvier** reste un repère interne, une semaine de marge avant la date publique. **Disponibilité de Gregory : environ 10 h par semaine [Décidé]**, ce qui borne le nombre de décisions et de validations par semaine. **Fêtes : pré-alpha avec 3 à 5 proches [Décidé]**.

**Règle d'inventaire [Décidé] :** 32 places (équipement porté exclu). Un 33e emplacement temporaire reçoit un butin déjà engagé. Tout contenu PvE qui peut rapporter un objet (expéditions, camps de bots) est **bloqué côté serveur** tant que l'inventaire est à 32 ou plus, avec un message clair. La suppression d'objet existe dès l'alpha.

**Héros [Décidé] :** pas de gouverneur par cité. Un héros du royaume et des conseillers donnent chacun des bonus au royaume entier.

## 3. Critères d'acceptation

Un critère est rempli quand il est démontré **sur l'instance distante, depuis un compte neuf**, sans commande d'administration ni correction en base.

| # | Domaine | Critère d'acceptation |
|---|---|---|
| 1 | Compte | Un testeur invité se connecte à distance, crée son royaume, choisit **Guerrier ou Sorcier**, et ses données survivent à un redémarrage. Un compte ne voit jamais les données d'un autre. |
| 2 | Cité | Construire un bâtiment, voir les ressources produites, lancer et terminer une construction, avec les noms du lexique Kalendrath partout. Aucun vocabulaire hérité d'OGame visible. |
| 3 | Études et troupes | Lancer une étude arcanique, enrôler des troupes, bâtir des défenses, avec coûts, durées et prérequis validés au simulateur **à ×5**. |
| 4 | Files hors page | Les constructions, études, enrôlements et missions se **terminent à échéance sans page ouverte**, une seule fois, y compris après redémarrage des tâches. Le retour après absence montre ce qui s'est passé. |
| 5 | Héros et talents | Les talents s'achètent selon l'archétype ; leurs effets **modifient réellement** la production et le combat, à l'échelle du royaume (test automatisé par effet). Pas de gouverneur par cité. |
| 6 | Équipement | Les 12 emplacements existent ; équiper et déséquiper fonctionne ; un objet ne peut être porté que par un héros de son royaume ; les effets s'ajoutent et se retirent **réellement** dans la production et le combat. |
| 7 | Expédition | Envoyer une expédition, la suivre, recevoir son résultat ; l'issue « butin d'équipement » sort avec un taux réglable ; **un même butin n'est jamais attribué deux fois**, même en cas de double traitement. |
| 8 | Bots PvE | Au moins **un type de camp de bot** est repérable et attaquable avec le moteur de combat existant ; le pillage rapporte du butin selon la même règle d'unicité ; les bots ne dépendent pas du système de guerre. Une **table de probabilités de butin par source et par objet** est réglable et simulable. *Types de bots et difficulté : à passer au simulateur (l'alpha sert justement à les dimensionner).* |
| 9 | Inventaire | L'inventaire (32 places) liste les objets du royaume avec leur emplacement, leur rareté et leurs effets lisibles ; les objets issus d'un butin y arrivent sans intervention ; un inventaire plein bloque le contenu PvE avec un message clair ; un objet peut être supprimé. |
| 10 | Fermetures | Tout ce qui est exclu (§2) est inaccessible **côté serveur** ; un test automatisé le vérifie pour chaque route fermée. |
| 11 | Schéma | Les tables d'objets suivent la note de schéma relue ; les fonctions futures (composants, fabrication, marché) pourront s'ajouter par **migrations additives**, sans toucher aux tables héritées d'OGameX. |
| 12 | Rythme ×5 | Le serveur tourne à ×5 ; un joueur actif atteint les premiers paliers de cité et sa première expédition dans sa première session, et une boucle moyenne visible en moins d'une semaine réelle. *Seuils à fixer au simulateur.* |
| 13 | Qualité | Suite de tests existante verte (1109 tests hérités plus les tests du héros) ; chaîne du dépôt respectée (Rector, style, analyse statique, tests). |
| 14 | Exploitation | Déploiement reproductible vers l'instance distante, sauvegarde quotidienne **avec une restauration réellement testée**, remontée des erreurs, interrupteur d'urgence pour suspendre le serveur. |
| 15 | Retours | Un canal de retours privé et un journal d'événements de jeu permettent de savoir où les testeurs se bloquent (entonnoir : première construction, première expédition, premier objet équipé). |
| 16 | Mobile | Les parcours des critères 1 à 9 (compte, cité, études et troupes, héros, équipement, expédition, inventaire) sont **utilisables sur un téléphone** (largeur 360 px, tactile), sur le même code que l'ordinateur ; le design system est conçu mobile d'abord. *[Décidé 2026-11-21]* **Premier élément sacrifié si le calendrier déborde** (§5) : la version mobile peut arriver après le 14 janvier, la date ne bouge pas. |

## 4. Jalons **[Proposé]**

| Date | Jalon | Test de passage |
|---|---|---|
| 22 nov. | Contrat validé, schéma relu, premières maquettes | Ce document validé ; décisions de la semaine tranchées |
| 29 nov. | Démonstration distante | Critères 1, 14 (déploiement) |
| 6 déc. | Cité et héros jouables | Critères 2, 3, 4, 5 |
| 13 déc. | **Boucle héros complète** : expédition, butin, équipement, effet réel | Critères 6, 7, 9 |
| 20 déc. | Bots PvE jouables, **gel des nouvelles fonctions** | Critères 8, 10 |
| 21 déc. - 3 janv. | Pré-alpha avec 3 à 5 proches, stabilisation | Critères 11, 12, 13, 15 |
| 7 janv. | Repère interne : critères tous remplis, une semaine de marge | Tous les critères, plus restauration démontrée |
| **14 janv.** | **Ouverture de l'alpha fermée, date publique** | Invitations envoyées ; aucun repli prévu hors défaut architectural décidé le 13 déc. |

**Règle de décision.** Si le 13 décembre la boucle héros n'est pas jouable, on réduit le contenu (par exemple un seul type de bot) plutôt que de reculer les garanties de cohérence. Si le défaut est architectural, on décale la date. Ce choix se fait le 13, jamais la veille de l'ouverture.

## 5. Ce que l'on sacrifie en premier

1. **La version mobile** (le code reste conçu mobile d'abord, mais l'interface mobile peut arriver après le 14 janvier [Décidé 2026-11-21]).
2. Variantes graphiques, animations, illustrations de tous les paliers.
3. Raretés élevées du butin et variété du catalogue d'objets (viser une poignée d'objets par emplacement).
4. Deuxième type de bot PvE.
5. Parcours guidé détaillé (garder une aide courte).

**Ne jamais sacrifier** : cohérence des ressources et des objets, isolation des comptes, résolution unique et durable des événements, restauration de sauvegarde, capacité à diagnostiquer un incident.

## 6. Questions ouvertes pour Gregory

**Camps de bots [Décidé, implémenté le 2026-11-21] :** un camp produit des ressources comme un joueur (bâtiments de production et de stockage dans `config/pve.php`, formules héritées) et recrute sa garnison manquante avec ses propres ressources, au coût normal des unités : un camp pillé reconstruit lentement. Plus de minuterie ni de remise à valeur fixe. S'il attaque un joueur en représailles, ce qu'il pille entre dans son stock. Le butin d'objet reste tiré par la table de la source. Niveaux de bâtiments provisoires, à calibrer au simulateur.

**Représailles des camps [Décidé 2026-11-21] : dès l'alpha.** Règles d'attaque **équilibrées** exigées : un bot fort ne doit jamais raser un joueur (plafond de pertes, proportionnalité à la force du joueur, protection des débutants, fréquence limitée). À concevoir avant d'écrire du code (voir « Encore ouvertes »).

**Héros [Décidé 2026-11-21] :** le héros dirige le **royaume** (aucune cité n'est gouvernée) et s'entoure de conseillers. **Aucun plafond global des bonus** : le poids du héros vient de ses arbres de talents (budget de 29 points) et de son équipement ; `config/heroes.php` `axis_caps` est vide par défaut et reste disponible pour plafonner un axe si la simulation montre un emballement. Le « 15 % » venait d'une proposition du GDD du 2026-09-23 (objectif « le héros ne renverse jamais une mécanique »), abandonnée : le héros est une mécanique centrale, encore plus avec les métiers et le craft.

**Classes d'équipement [Décidé 2026-11-21] :** `war` (Guerrier), `wizard` (Sorcier), `any` (commun), alignées sur les archétypes.

**Plateformes [Décidé 2026-11-21] :** ordinateur et mobile à égalité, majorité de joueurs attendue sur mobile (critère 16).

**Date [Décidé 2026-11-21] :** alpha fermée contractuelle le **14 janvier 2027**, annoncée publiquement sur la vitrine. Si le calendrier déborde, on sacrifie du contenu (la version mobile d'abord, §5), jamais la date.

**Marge de niveau du butin [Décidé] :** un objet peut tomber jusqu'à **+2 niveaux** au-dessus du héros, en cohérence avec les paliers de difficulté des camps et expéditions. À ajuster pendant l'alpha selon les retours.

**Tranchées le 2026-11-19 :** rééquilibrage par objet (effets lus depuis la définition, pas de variable globale) ; butin attribué à l'obtention, équipable aussitôt, présence du héros non contrôlée ; inventaire de 32 places avec 33e temporaire et blocage du PvE ; comptes remis à zéro avant la bêta ; alpha en français seul ; 10 h par semaine ; fêtes en pré-alpha ; bots PvE dans l'alpha.

**Encore ouvertes :**
- **Règles des représailles des camps** : conception de Codex reçue le 2026-11-21 ([rapport](archives/retours-agents-2026-11-21-representailles-bots-pve.md)) : une contre-attaque annoncée (préavis de 8 h) après une victoire de pillage, contre la cité d'origine, 25 % au plus de la garnison mobile, pertes du joueur plafonnées (10 % par impact, 20 % sur 168 h, jamais de bâtiments ni de héros), 1 tentative par 24 h et 2 par 168 h par royaume. **Gregory veut l'implémenter au plus vite et équilibrer pendant l'alpha avec les retours des joueurs** : le passage au simulateur recommandé par Codex n'est pas un préalable, les paramètres restent réglables par configuration (`pve.retaliation.*`) avec un interrupteur d'urgence.
- **Table de butin** (pourcentages par objet, niveau d'équipement, rareté et source : camp ou expédition) : à produire par agents ou Codex **une fois le catalogue d'objets arrêté**.
- **Prérequis d'envoi d'une expédition** [Décidé 2026-11-21] : **Académie Militaire 5, Endurance du Fantassin 3, Lecture des Runes 3** (maintenu après le passage au simulateur, [rapport](archives/retours-agents-2026-11-21-simulateur-prerequis-expedition.md)). Le simulateur trouve 23 h en continu, 39 h pour un joueur très actif, 5,8 jours pour un joueur moyen : le goulot est le palier Village (débit de ressources, pas durées de chantier). **Remède retenu : des événements de tutoriel qui donnent des ressources** pour atteindre le palier Village vite ; le critère 12 reste inchangé et se mesure avec le tutoriel. À dimensionner : quel don de ressources ramène l'Académie 5 dans la première session ? La Découverte Tellurique reste réservée à la colonisation.
- **Emplacements de missions et expéditions** [Proposé 2026-11-21, à confirmer] : 5 emplacements de missions par défaut (réglable en configuration), +1 par niveau de la **Chaîne de Ravitaillement** ; les expéditions occupent des emplacements de missions, avec un plafond séparé de **2 expéditions simultanées** ; durée d'attente de l'expédition choisie dans une fourchette fixe (1 à 3 h, configuration), sans étude. Le code hérité les fait aujourd'hui dépendre de l'astrophysique (`PlayerService::getExpeditionSlotsMax`, `FleetController`, vue `fleet/index`, `ExpeditionMission` l. 158-165) : à remplacer, avec tests, quand Codex aura rendu la main sur le dépôt.
- **Coût du mobile** : la version mobile est le premier élément sacrifié (§5) ; seule la conception « mobile d'abord » du design system est exigée avant le 14 janvier. Chiffrage à faire avant le 25 novembre.
- **Noms anglais des 12 emplacements** (Head, Neck, Shoulders, Cloak, Chest, Wrists, Hands, Belt, Legs, Boots, Ring I, Ring II proposés).
