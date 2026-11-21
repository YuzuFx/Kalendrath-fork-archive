# Rétro-planning alpha : avis game design et valeur de l'alpha

18 novembre 2026 — agent « game design ». Lecture seule : CLAUDE.md, GDD (§2, §3, §5.4, §5.7, §5.8, §7, §10), décision métiers et marché (2026-11-17), références du genre, étude des serveurs en continu, feuille de route, plan Codex et note de démarrage. Aucun test, aucun code, aucune simulation n'a été exécuté. Les avis ci-dessous sont des recommandations à valider par Gregory, pas des décisions.

## 0. Verdict en cinq lignes

1. **Le plan Codex est un plan de livraison, pas un plan d'apprentissage.** Il dit quoi construire, jamais ce que l'on veut savoir au 7 janvier. Il faut écrire 5 à 6 hypothèses avant de figer le périmètre.
2. **Le plan contredit la décision du 17 novembre** : « Alpha : sans marché. Le marché se développe pendant l'alpha et arrive en bêta ». Le plan met le marché en semaine 5 comme cible de l'alpha. C'est un écart qu'il faut soit assumer explicitement, soit annuler. Mon avis : l'annuler.
3. **Un marché entre 10 et 15 joueurs ne mesure rien d'utile** (liquidité nulle, spécialités manquantes, monnaie sans puits). Il consomme la semaine la plus risquée du plan (concurrence, atomicité) pour produire du bruit. Substitut recommandé : comptoir PNJ + don direct entre deux joueurs.
4. **L'expédition est le bon premier contenu PvE** (effort « faible » dans le GDD §10, source des composants rares), mais elle teste mal le héros et ne teste pas le choix stratégique. Ajouter un seul camp fixe scoutable si le budget le permet, sinon le garder pour une alpha 2.
5. **Le risque n°1 est le rythme, pas la fonctionnalité** : sur un OGame-like à ×1, 10 testeurs en 3 semaines ne verront ni la boucle moyenne ni la longue. La feuille de route prévoit déjà un « serveur alpha à ratios augmentés ×3 à ×5 » : ce point doit entrer dans le contrat de l'alpha.

## 1. Quelles hypothèses valider avec 10 à 15 testeurs

Une alpha de 10 à 15 personnes ne valide aucune statistique (équilibrage, rétention). Elle valide des **qualitatifs observables** : comprend-on, revient-on, s'amuse-t-on. À formuler en « nous pensons que X ; nous le saurons si Y ».

| # | Hypothèse | Signal observable | Testée par le périmètre proposé ? |
|---|---|---|---|
| H1 | Le joueur comprend en moins de 10 minutes ce qu'il doit faire et revient le lendemain sans relance | Part des comptes qui se reconnectent à J+1 et J+3 ; abandons dans la première heure ; temps avant la première expédition | Oui, si instrumenté |
| H2 | La boucle courte (quelques minutes, files + héros + rapport) est agréable et ne devient pas une corvée | Durée moyenne de session, nombre de sessions par jour, verbatims « j'ai eu envie de relancer » | Oui |
| H3 | Le héros est le fil conducteur : le joueur s'y attache et son équipement change ses décisions (GDD §3, « le héros relie les boucles ») | Le joueur parle de « son » héros ; il retarde ou avance une expédition pour lui ; il compare des pièces | Partiellement : un héros principal seulement, sans conseillers ni gouverneur |
| H4 | L'expédition est excitante (variance, attente du retour, butin) et non frustrante | Réaction aux échecs neutres, aux pertes ; envie de relancer tout de suite | Oui |
| H5 | Le rythme asynchrone avec absences fonctionne : retour après 12 à 48 h sans se sentir perdu ni puni | Le joueur absent retrouve un message clair, une file vide ou terminée, un objectif suivant | Oui, mais seulement si l'alpha est assez longue ou accélérée |
| H6 | Le craft donne un sentiment de progression de puissance, et la chaîne composant → pièce est lisible | Le joueur sait dire d'où vient sa pièce et ce qu'il lui manque pour la suivante | Oui (fabrication + équipement) |
| H7 | Les métiers/spécialisations créent un besoin de coopération (pilier d'identité) | Les joueurs réclament un composant qu'ils n'ont pas | Non, sous la forme proposée (voir §2) |

**Hypothèses que l'alpha ne peut pas valider et qu'il faut s'interdire de conclure :** équilibrage économique, prix, rétention longue, prestige, alliances, PvP. Un faux positif ici est pire qu'un trou : ne pas écrire de « conclusions » sur l'économie à partir de 12 personnes.

**Ce qui manque au plan :** un dispositif de mesure. À prévoir dès la semaine 2, coût faible : journal d'événements anonymisé (connexion, action lancée, abandon), un court questionnaire à J+1 et J+7, un salon de retours. Sans cela, le plan produit une alpha dont on ne sait rien dire.

## 2. Le marché a-t-il un sens à 10-15 joueurs ? Avis tranché : non, pas en alpha

### Arguments

- **Liquidité.** Un marché a besoin de plusieurs offreurs par bien. Avec 12 joueurs, deux spécialisations chacun, et une dizaine de métiers possibles, chaque bien a zéro à deux vendeurs, dont certains inactifs la moitié de la semaine. Un acheteur trouve rarement ce dont il a besoin, ce qui est le contraire du signal que l'on veut recueillir.
- **Spécialités manquantes.** Le panel ne sera pas équilibré : la décision du 17 novembre dit que chaque royaume choisit 2 métiers sur 8. Avec 12 royaumes, la probabilité qu'un métier du premier lot (par exemple forge ou herboristerie) manque ou soit tenu par un joueur parti est élevée. Le plan le reconnaît (« un cas où une spécialité manque ») mais sa réponse (« mécanisme d'approvisionnement borné ») revient à construire le comptoir PNJ de toute façon.
- **Monnaie sans puits ni histoire.** La décision actuelle donne comme sources commerce, commandes de PNJ, quêtes, pillage PvE. Aucune de ces sources n'existe avant la bêta (quêtes : « 100 % à construire », GDD §3). Sans puits (frais 5 % détruits, entretien d'équipement, usure) ni inflation mesurable sur trois semaines, l'alpha ne dit rien de l'économie.
- **Convoi par transaction.** La décision impose un convoi entre royaumes, des caravanes, du temps de transport. C'est un système (unités, durées, files, retour) en plus du marché. Le plan ne l'inclut pas : soit il l'oublie, soit il le simplifie, et alors l'alpha ne teste pas la mécanique décidée.
- **Risque technique concentré.** Le plan met en semaine 5 : monnaie, réservation, achat atomique, annulation, verrous, journal, inspection admin, fermeture d'urgence. C'est la semaine où le fondateur, seul, a le moins de marge. Un défaut de duplication de biens (que le plan classe lui-même comme bloquant) tombe juste avant le gel du 20.
- **Surveillance anti-boost.** Avec 12 personnes dont des amis et des comptes de test, le registre de transactions ne signifie rien (tout le monde échange avec tout le monde).

### Ce que l'alpha doit tout de même apprendre sur les métiers (H7)

La vraie question est : « les joueurs veulent-ils des pièces qu'ils ne savent pas fabriquer ? ». Elle se teste sans marché.

### Substitut recommandé (par ordre de préférence)

1. **Comptoir PNJ** (déjà acté comme service neuf dans la décision du 17 novembre). Il vend à prix fixe un petit catalogue de composants de base, achète les surplus à prix fixe plus bas, et sert de lisseur de cours en bêta. Pour l'alpha : 10 à 15 composants, deux prix, aucun convoi, aucune annonce. Il joue le rôle de source de monnaie (vente de butin) et de puits (achats), ce qui permet de tester H6 sans dépendre d'un autre joueur. Effort : une table de prix + un écran + une transaction atomique, soit une fraction du marché.
2. **Don direct entre deux royaumes** (envoi d'un composant ou d'une pièce, sans prix, sans convoi complexe, limité en quantité par jour, journalisé). Il teste H7 : un joueur demande un composant sur le salon, un autre l'envoie. Le dialogue social est le signal. À borner (plafond quotidien) pour garder le contrôle du patrimoine.
3. **Ne pas faire** : marché simulé avec faux vendeurs. Il donne l'illusion d'un marché joueur et fausse les retours.

**Conséquences sur le plan :** supprimer la semaine 5 « métiers et commerce minimal » dans sa forme actuelle. La remplacer par : métiers (2 par royaume, choix à la création, premières recettes) + comptoir PNJ + don direct. Cela libère de l'ordre de 3 à 5 jours pour l'onboarding et la mesure. Si Gregory tient à un marché joueur en alpha, c'est un écart à acter par écrit contre sa propre décision du 17 novembre, et à ne lancer qu'après la boucle PvE jouable (jalon du 13 décembre atteint).

## 3. L'expédition est-elle le bon premier contenu PvE ?

### Pour

- **Coût** : GDD §10 la classe « très développée et quasi prête à l'emploi », effort faible (`ExpeditionMission`). Les camps fixes (§5.8A) demandent un effort moyen (extension hors du contexte expédition, génération de garnisons sur les éclats, régénération).
- **Source des composants rares** : le GDD désigne l'expédition comme le meilleur vecteur de loot rare. Or toute la chaîne craft en a besoin.
- **Rythme** : un trajet de durée variable convient à la boucle asynchrone (H5).
- **Peu d'états à communiquer** : envoyer, attendre, rapport. Excellent pour un premier jour.

### Contre

- **Pas de choix stratégique** : l'expédition est une loterie. Le joueur choisit seulement une ambition et un contingent. Le plaisir du PvE tel que décrit (« risque calculé, pas subi », cibles scoutables) n'est donc pas testé.
- **Le héros y est secondaire** : si l'expédition marche sans héros, H3 est affaiblie. Il faut que le héros soit réellement requis (le contingent part avec lui) et que son indisponibilité en cas d'échec soit visible.
- **Hasard difficile à jauger à petit échantillon** : sur 12 joueurs, un joueur malchanceux voit 3 échecs neutres de suite et part. À borner (voir §4 : pitié, premières expéditions garanties).
- **Le plan cible « de préférence l'expédition »** sans préciser l'issue des pertes, la durée, l'indisponibilité du héros. Ces trois paramètres font le plaisir ou la frustration.

### Alternatives

| Option | Coût | Ce qu'elle teste bien | Ce qu'elle ne teste pas |
|---|---|---|---|
| A. Expédition seule | Faible | Variance, attente, loot, boucle asynchrone | Choix, préparation, héros comme moteur |
| B. Camp fixe scoutable seul | Moyen | Préparation, choix de cible, risque calculé, héros | Variance, loot rare diffus |
| C. Missions de héros scriptées (quêtes) | Élevé (système de quêtes inexistant) | Narration | Tout le reste ; écarter pour l'alpha |
| D. Expédition + **un seul** type de camp (palier unique, garnison fixe, régénération simple) | Faible + moyen réduit | Les deux familles du GDD, au plus petit format | Progression de difficulté |

**Avis : option A pour le 13 décembre, D si le jalon est tenu avec marge.** Dans les deux cas, faire du camp fixe la **deuxième itération de contenu** (mi-janvier, alpha 1.1), pas un risque sur la date d'ouverture. Si l'on doit n'en garder qu'un seul, l'expédition, parce qu'elle alimente le craft (donc H6).

Garde-fous design à fixer avec l'expédition (voir §5) : durée courte en alpha, pas de perte définitive (pilier « l'échec coûte du temps »), pitié sur les premiers lancers, rapport de retour lisible.

## 4. Rendre la première heure agréable

Le plan parle d'un « parcours guidé bref » en semaine 7, après le gel des fonctionnalités. C'est trop tard et trop faible : l'onboarding **est** la boucle courte jouée pour la première fois. À concevoir en même temps que la boucle (semaines 3 à 4), pas à la fin.

### Une première heure cible (à valider au simulateur, durées à calibrer sur le serveur accéléré)

| Minute | Événement | Pourquoi |
|---|---|---|
| 0 à 2 | Création : nom de royaume, héros (archétype), 2 spécialisations avec une phrase d'effet chacune. Une phrase de lore, pas un tunnel. | Le choix doit être compris sans lire le GDD ; réversible en alpha |
| 2 à 5 | Premier bâtiment en construction **terminé en moins d'une minute** (palier gratuit ou temps réduit) ; la ressource coule | Récompense immédiate, tutoriel par l'action |
| 5 à 10 | Premier objectif affiché en permanence (« envoyez votre héros en expédition ») ; première expédition **courte (2 à 5 minutes)** | La boucle PvE est le coeur ; la découvrir tôt |
| 10 à 20 | Retour de l'expédition, **butin garanti** (un composant lisible, une ressource) ; le héros gagne son premier niveau ou un point de talent | Première récompense de puissance |
| 20 à 40 | Première fabrication (une pièce blanche) avec les deux métiers, composants de base fournis par le comptoir PNJ | Boucle craft complète dès le premier jour (cohérent avec « première pièce dès le premier jour » de la décision du 17 novembre) |
| 40 à 60 | Équipement porté, un effet **visible** (production, vitesse, butin) ; l'objectif suivant est annoncé avec un délai long (construction de 30 à 60 minutes) qui justifie de quitter le jeu | Donne une raison de revenir et apprend l'asynchrone |

### Principes

- **Un seul objectif visible à la fois** pendant la première heure (liste de quêtes de départ à 5 ou 6 marches, sans système de quêtes narratif complet : un simple tracker d'étapes suffit, pas les quêtes PNJ du GDD).
- **Ne pas montrer ce qui n'existe pas** : tout écran ou bâtiment fermé côté serveur est invisible dans l'interface (le plan l'exige déjà côté serveur ; l'imposer côté interface).
- **Récompense garantie, variance ensuite** : les trois premières expéditions ne font pas d'échec neutre.
- **Le retour après absence est un moment de design** : un écran « pendant votre absence » (files terminées, expédition revenue, ce qu'il reste à faire). Il porte H5 et coûte peu.
- **Rien qui oblige à lire** : textes courts, vouvoiement, ton épique avec une touche de légèreté (voix-kalendrath). FR et EN : pour une alpha fermée, le français suffit si le panel est francophone, l'infrastructure de clés de traduction doit exister (l'anglais est requis pour l'alpha **publique**, pas pour une fermée).

## 5. Décisions de game design à trancher cette semaine (avec recommandation)

| # | Décision | Recommandation |
|---|---|---|
| D1 | **Marché en alpha ou non** (écart avec la décision du 17 novembre) | Non. Comptoir PNJ + don direct. Marché joueur en bêta, comme décidé. |
| D2 | **Rythme de l'alpha** : ×1 ou serveur accéléré | Accéléré ×3 à ×5 sur les durées de construction, recherche et expédition (point déjà prévu à la roadmap, ligne « Serveur alpha à ratios augmentés »). À ×1, les testeurs n'atteignent pas la boucle moyenne en trois semaines. Prévoir 2 plages : accéléré au début, puis ralentissement annoncé. |
| D3 | **Durée de l'alpha et reset** | Au moins 3 semaines, avec un reset annoncé au départ ; une fenêtre de ralentissement vers J+10 pour observer le rythme réel (H5). |
| D4 | **Métiers du premier lot** | 4 métiers de fabrication/récolte complémentaires pour un lot court : par exemple forgeron + mineur (arme), couturier ou travailleur du cuir + herboriste. Le choix de 2 par royaume reste, mais le panel doit couvrir les 4 par construction : **assigner les paires** aux 12 testeurs de façon à ce que chaque métier ait au moins 3 titulaires, plutôt que de laisser un choix libre. Noms de métiers provisoires, à passer par le lexique avant tout texte joueur. |
| D5 | **Récolte contre « composants issus du PvE seulement »** (point de tension 1 de la décision du 17 novembre) | Pour l'alpha : **tous les composants viennent de l'expédition et du comptoir PNJ**. La spécialisation module les recettes accessibles et un bonus (rendement, qualité), pas une récolte séparée. Les denrées de terroir sont reportées. |
| D6 | **Nombre d'emplacements et de raretés en alpha** | Les décisions donnent 10 emplacements, 5 raretés, durabilité. Pour l'alpha : **6 emplacements actifs** (parmi les 8 du GDD §5.7 ou les 10 de la décision, à trancher), **3 raretés** (blanc, vert, bleu), **pas d'usure** (la casse et l'entretien sont encore « réflexion ouverte »). Chaque pièce porte un effet lisible. |
| D7 | **Effets d'équipement : lesquels, et sur quoi** | 3 effets seulement, chacun branché sur un consommateur déjà existant : production de ressources, vitesse ou durée d'expédition, butin d'expédition. Le plan a raison : un effet affiché mais sans effet réel est un défaut d'alpha. |
| D8 | **Issue d'une expédition ratée et indisponibilité du héros** | Aucune perte définitive (pilier). Échec neutre = temps perdu ; échec sévère = héros indisponible pour une durée courte, scalée au niveau (mécanisme du §5.5). À fixer en chiffres avant le 13 décembre, sur simulateur. |
| D9 | **Durée des expéditions et plafond quotidien** | Deux durées seulement à l'alpha (courte pour apprendre, longue pour l'absence) ; plafond de créneaux d'expédition via `MAX_EXPEDITION_SLOTS`. Pas de plafond quotidien. |
| D10 | **Instrumentation et retours** | Journal d'événements + questionnaire J+1 et J+7 + un salon (voir §1). À décider cette semaine car il touche l'API. |
| D11 | **Nombre de villes, de héros** | Une ville, un héros principal, pas de conseiller ni de gouverneur (le plan le suppose). Le dire explicitement : cela limite H3 et c'est assumé. |
| D12 | **Accès aux hostilités** | PvP fermé côté serveur (déjà dans le plan). Garder le PvE contre bots seulement. |

Les décisions D1 à D4 conditionnent le calendrier de toute la suite ; les autres peuvent suivre dans la semaine du 23 novembre, avant le chantier du 7 au 13 décembre.

## 6. Périmètre alpha alternatif

### Alternative proposée : « alpha de la boucle héros »

**Inclus**
- Une cité (aperçu, ressources, files de construction et de recherche limitées), serveur accéléré.
- Un héros principal avec niveau et un petit arbre de talents ou 2 à 3 talents (la branche `feat/heros-suite` existe et doit servir).
- Expédition à 2 durées, butin avec composants, résolution durable sans page ouverte.
- Métiers (2 par royaume, paires assignées), 8 à 12 recettes, fabrication, 6 emplacements, 3 raretés, 3 effets.
- **Comptoir PNJ** et **don direct** limité.
- Écran de retour d'absence, tracker d'objectifs de départ, journal d'événements, questionnaire.
- PvP, paiements, alliances, prestige, marché fermés.

**Sacrifie** : le marché joueur et le convoi (H7 testée par le don seulement), les camps fixes, les conseillers, l'usure, les raretés violet et orange, la monnaie échangeable entre joueurs. Gagne de l'ordre d'une semaine de développement à investir dans l'onboarding, la mesure et la marge de fin d'année.

### Alternative plus petite : « alpha de la première heure et du retour »

**Inclus** : cité, héros, expédition, butin, **sans** craft, **sans** métiers, avec onboarding soigné et écran de retour. Dure 10 jours accélérés.
**Sacrifie** : H6 et H7 entièrement, donc le pilier métiers. À utiliser si le jalon du 13 décembre n'est pas tenu. Elle teste H1, H2, H4, H5, pour un coût minimal, et prépare une alpha 2 avec craft mi-février.

### Alternative différente : « alpha de la coopération »

Inverse du plan : cité réduite, expédition, métiers avec **don direct** et un objectif commun (contribution collective à un seuil, façon événement d'alliance du GDD). Teste H7 et le pilier « coopération plutôt que compétition » plus tôt que le marché. **Sacrifie** : la progression individuelle profonde. Plus risquée en design (objectif collectif à inventer) ; je ne la recommande pas pour la première alpha mais elle mérite d'être l'alpha 2.

### Mon choix

L'alpha de la boucle héros, avec bascule sur la « première heure et du retour » si le jalon du 13 décembre glisse de plus d'une semaine. Cette règle de bascule doit être écrite maintenant, pas la veille.

## 7. Autres observations sur le plan

- **Le plan ne mentionne ni la capacité d'observation ni la communication aux testeurs** (promesse, règles de reset, ce que l'on attend d'eux) : à ajouter semaine 7 au plus tard, les préinscriptions du plan marketing sont une source de testeurs naturelle.
- **Charge du fondateur seul** : le plan enchaîne socle, cité, boucle, marché, stabilisation avec une personne qui valide chaque semaine. Chaque retrait de périmètre au §6 réduit directement le nombre de validations à faire. La dépendance la plus critique est la validation visuelle (design system) : si elle tarde, tout le front attend.
- **Réutilisation** : la branche héros et ses talents doivent servir H3 ; je n'ai pas vérifié leur contenu.
- **Les testeurs sont des proches ou des amateurs du genre** : le signal est biaisé en faveur de la tolérance. Recruter au moins 3 testeurs qui ne connaissent pas le projet, pour H1.
- **À ne pas conclure** : toute valeur numérique (coûts, durées, taux de loot, prix du comptoir) reste à passer au simulateur avant le GDD, conformément à la règle du projet. Les durées de première heure du §4 sont des cibles de ressenti, pas des valeurs.

## 8. Écarts avec les décisions actées, à justifier

| Écart | Justification |
|---|---|
| Alpha sans marché joueur (conforme à la décision du 17 novembre), contre le plan Codex qui l'inclut | Retour à la décision actée ; voir §2 |
| Paires de métiers assignées plutôt que choisies librement | Nécessité d'un panel de 10 à 15 ; le choix libre reste testé comme question de questionnaire |
| 6 emplacements, 3 raretés, pas d'usure en alpha | Sous-ensemble de la décision, non contradictoire ; les 10 emplacements et 5 raretés restent la cible |
| Tous les composants via expédition et comptoir PNJ | Résolution provisoire du point de tension 1, à reprendre au brainstorm métiers |

Noms affichés au joueur : « Comptoir PNJ » (terme de travail de la décision du 17 novembre) et les noms de métiers sont **provisoires** et doivent passer par le lexique avant tout texte joueur ; ce document n'en invente pas de définitifs.
