# Rétro-planning de l'alpha : pré-mortem et risque de direction

18 novembre 2026. Agent Claude (angle demandé : pré-mortem et gestion du risque de direction). Ce document challenge le [plan Codex du 18/11](retours-agents-2026-11-18-plan-alpha-janvier-2027.md) et le [démarrage de la semaine](retours-agents-2026-11-18-demarrage-alpha.md). Il ne vaut ni décision ni mise à jour de la roadmap. Lecture seule : aucun code, GDD ou décision modifié.

Sources lues : CLAUDE.md, les deux documents ci-dessus, [feuille-de-route-projet-jeu.md](../feuille-de-route-projet-jeu.md), [decision-architecture-cible.md](../decision-architecture-cible.md), [synthèse du 16/11](../sessions/2026-11-16-synthese-metiers-marche-questions-ouvertes.md), [taches-a-valider.md](../taches-a-valider.md), [impact métiers/commerce sur la stack](retours-agents-2026-11-17-impact-metiers-commerce-stack.md), [design partition](retours-agents-2026-11-17-design-partition-interface.md), [Hermès/Grok/calendrier](retours-agents-2026-11-18-hermes-grok-bot-calendrier.md), README du simulateur, historique git.

---

## 0. En une page

**Le plan Codex est sérieux sur les garanties (cohérence, restauration, idempotence) mais il suppose un débit qui n'a jamais été observé sur ce projet.** Les faits mesurés dans le dépôt :

- **Depuis la décision d'architecture du 26/09 (huit semaines), aucune ligne de React ni d'API n'est arrivée sur `main`.** Le dernier commit de code de jeu sur `main` date du 29/09 (`f1c2545d`, talents du héros). `package.json` ne déclare pas React, `composer.json` ni Sanctum, ni Octane, ni Horizon.
- Le code produit depuis est resté **hors de `main`** : `origin/feat/heros-suite` (30/10, 26 fichiers, +1 518 lignes) et `origin/feat/kaelite-deux-soldes` (30/10), plus quatre worktrees d'agents locaux non fusionnés. Trois semaines sans intégration.
- Pendant ce temps, l'activité a porté sur la documentation, la vitrine (prototype fusionné puis **annulé** le 03/11), les logos, Midjourney, les audits Rakorn, trois séries de maquettes d'interface (16/11, 17/11), l'outillage d'agents. Les pics de commits (142 en semaine 44) sont surtout de la documentation.
- [taches-a-valider.md](../taches-a-valider.md) compte **94 points ☐ à relire ou trancher contre 1 ☑**. Une série entière porte la mention « Gregory n'a encore rien relu ».
- Le périmètre de l'alpha a **grossi le 16/11** (métiers complets + monnaie + marché entre joueurs), deux jours avant la planification, alors que 14 questions de règles restent ouvertes.

**Conclusion du pré-mortem :** la cause la plus probable d'un échec au 15 janvier n'est pas technique. C'est le **goulot de décision et de validation** du fondateur, combiné à un **périmètre qui part de la cible (marché complet, socle de production) au lieu de partir de l'hypothèse à tester**. Les agents écrivent vite quand la consigne est fermée (le système héros de septembre s'est fait en trois jours) ; ils n'arbitrent pas à la place de Gregory.

**Recommandations clés :**

1. Faire de la semaine du 18 au 22/11 une semaine de **décisions, pas de production** : 9 décisions listées au § 5, chacune fermée par oui/non.
2. **Décider dès maintenant que le 7 janvier livre un plan B garanti** (boucle héros → expédition → butin → forge → équipement, sans marché) et que le marché est un **plan A conditionnel**, jugé sur un critère chiffré le 13/12, sinon livré en vague 2 vers le 25 janvier.
3. **Sortir du chemin critique** tout ce qui ne sert pas les 10-15 testeurs : Octane/FrankenPHP, Horizon, Reverb, lobby, pipeline CI/CD complet, FR/EN, refonte React des écrans de cité.
4. **Remplacer le « design system validé avant tout front » par des tokens v0 en temps limité** (48 h), réversibles par construction.
5. **Geler l'exploration** (vitrine, logos, Midjourney, Hermès, nouveaux audits) jusqu'au 7 janvier, sauf demande explicite.
6. **Accélérer le serveur d'alpha (×5 proposé)** : au rythme OGameX ×1, un test de deux semaines ne verra ni la première pièce fabriquée ni le marché vivre.

---

## 1. Pré-mortem : nous sommes le 15 janvier, l'alpha n'est pas sortie ou déçoit

Échelles : probabilité (P) et impact (I) de 1 à 5 ; score = P × I. Les dates de détection renvoient au calendrier alternatif du § 6.

| # | Cause probable | P | I | Score | Signal d'alerte précoce | Détectable le |
|---|---|---|---|---|---|---|
| 1 | **Goulot de décision et de validation du fondateur.** Les questions de règles restent ouvertes, les PR attendent la relecture, les agents produisent sur des hypothèses qu'il faut défaire. | 5 | 5 | **25** | Contrat d'alpha non signé ; plus de 3 PR ou branches en attente de revue depuis plus de 72 h ; nombre de ☐ en hausse. | **dim. 22/11** puis chaque dimanche |
| 2 | **Périmètre surchargé et tardif** : métiers + monnaie + marché + règles non tranchées, placés du 14 au 20/12, juste avant les fêtes, sur une boucle elle-même pas encore prouvée. | 5 | 4 | **20** | Règles du marché (source de monnaie, biens vendables, frais) non écrites au 29/11 ; boucle PvE incomplète au 13/12. | 29/11, **13/12** |
| 3 | **Dispersion et exploration continue** : nouvelles séries de maquettes, vitrine, logos, Midjourney, Hermès, audits de jeux tiers. Chaque série est intéressante et ne fait pas avancer la boucle. | 4 | 4 | **16** | Plus de 20 % des commits d'une semaine hors du périmètre d'alpha ; nouveau rapport d'exploration lancé avant le 7/01. | chaque dimanche |
| 4 | **Design system transformé en préalable sans fin.** Trois séries de maquettes en deux jours, aucune arrêtée ; la règle « aucune ligne de front avant design system arrêté » bloque tout le front. | 4 | 4 | **16** | Pas de fichier de tokens (couleurs, typo, espacements) fusionné au 25/11 ; quatrième série de maquettes demandée. | **mer. 25/11** |
| 5 | **Socle technique sur-dimensionné** : Octane/FrankenPHP, Redis/Horizon, Reverb, Sanctum, CI/CD, lobby, sauvegardes hors machine, tout en semaine 2. La semaine déborde, la démonstration distante glisse. | 4 | 4 | **16** | Aucun testeur extérieur connecté au serveur distant le 29/11. | **dim. 29/11** |
| 6 | **Fêtes de fin d'année** : la stabilisation (21/12 au 03/01) tombe sur la période où le fondateur et les testeurs sont le moins disponibles pour corriger ; un bug bloquant découvert le 26/12 n'est corrigé que le 04/01. | 4 | 3 | **12** | Disponibilités de Gregory du 19/12 au 04/01 non posées par écrit au 22/11. | 22/11 |
| 7 | **Domaine plus troué qu'annoncé.** L'équipement n'a aucun effet sur `main`, l'issue `GainItems` de l'expédition est un TODO, la branche héros n'est pas fusionnée et rien ne prouve que production ou combat consomment ses effets. L'intégration révèle des régressions ou des conflits de migrations. | 3 | 4 | **12** | Revue de `feat/heros-suite` non terminée au 22/11 ; suite Pest rouge après fusion ; `GainItems` non branché au 06/12. | 22/11, **06/12** |
| 8 | **Alpha livrée mais décevante : rythme et contenu.** Timers OGameX ×1, première pièce fabriquée après plusieurs jours, recettes non calibrées (le simulateur a un défaut connu : chantiers de 68 à 123 jours). Les testeurs se connectent deux fois et décrochent. | 3 | 4 | **12** | Au test interne du 13/12 : plus de 45 min de jeu actif (ou plus de 24 h réelles) avant la première pièce ; vitesse de serveur non fixée. | **13/12** |
| 9 | **Débit des agents IA plus bas que prévu** : limites d'usage (les sous-agents du 17/11 ont été interrompus par leur limite), travail parallèle en worktrees difficile à fusionner, reprises après changement de machine. | 3 | 3 | 9 | Moins de 4 PR fusionnées par semaine ; worktrees de plus de 7 jours non fusionnés. | 29/11 |
| 10 | **Incident à l'ouverture** : duplication d'objets ou de monnaie sur le marché, perte de progression, données d'un autre joueur visibles, compte admin par défaut ou secrets exposés (le dépôt est public). Fermeture en urgence et perte de confiance du panel. | 2 | 5 | 10 | Pas de test de concurrence achat/achat et achat/annulation au 20/12 ; restauration jamais jouée au 28/12 ; identifiants admin par défaut encore actifs. | 20/12, 28/12 |
| 11 | **Panel non mobilisé** : invitations envoyées tard, pas de canal de retours, aucune question de test définie ; on recueille des impressions au lieu de réponses. | 3 | 2 | 6 | Liste des testeurs confirmés et de leurs créneaux absente au 20/12. | 20/12 |

Lecture : les causes 1 à 5 se renforcent mutuellement. Le périmètre grossit (2) parce que l'exploration (3) ouvre des pistes, qui créent des questions (1), qui bloquent le design (4) et laissent le socle se remplir de « bonnes pratiques de production » (5). Casser la boucle en un seul point ne suffit pas ; il faut agir sur 1 et 2 cette semaine.

---

## 2. Dépendances entre lots et chemin critique réel

### 2.1 Ce que le plan Codex met en série et qui ne l'est pas

Le plan enchaîne : contrat → socle complet → cité en React → boucle PvE → marché → stabilisation. Or :

- **La cité n'est pas sur le chemin critique.** La décision d'architecture (§ 6) prévoit explicitement la **cohabitation** Blade/React : « les écrans non encore refaits continuent de tourner en Blade ». Les écrans de cité d'OGameX fonctionnent et sont couverts par les tests. Les refaire en React (lot 2 de la décision, effort « moyen ») dans la semaine du 30/11 ajoute une semaine de front et de design au chemin critique, pour une fonction qui existe déjà. Utiliser l'existant n'est pas « développer en Blade » au sens de CLAUDE.md.
- **Octane, Horizon et Reverb ne sont pas des prérequis d'une alpha à 15 joueurs.** Le conteneur `scheduler` et la file `database` existent déjà ; le middleware `GlobalGame` résout paresseusement. Une fabrication peut se résoudre à la lecture (« est-ce que `completed_at` est passé ? ») sans job planifié. Le chantier de la résolution temporelle reste nécessaire avant la bêta, pas avant l'alpha.
- **Le lobby et le compte central** (plan § 2, « dès s1 ») ne servent à rien avec un serveur unique.

### 2.2 Chemin critique réel

```
Décisions de règles (22/11)
   └─► Modèle de données objets/inventaire (catalogue, pile, exemplaire)   ← seul point coûteux à défaire
          ├─► Expédition : issue GainItems + engagement du héros
          │       └─► Forge : recettes, réservation des ingrédients, file
          │               └─► Équiper : effet réel dans 1-2 consommateurs métier
          │                       └─► [Plan A] Marché : annonces, achat atomique, monnaie
          └─► Écrans React : héros (pilote), inventaire, forge, [marché]
                   ▲
Tokens de design v0 (25/11) ──┘

En parallèle, hors chemin critique : serveur distant + sauvegarde (S1), panel et canal de retours (S3-S4), onboarding (S5).
```

Le chemin critique tient en **domaine des objets + quatre écrans React**. Tout le reste se fait à côté ou après.

### 2.3 Dépendances cachées que le plan ne nomme pas

- **Contenu à concevoir** : recettes, composants, taux de butin, effets. Il passe par le simulateur (règle du 21/10), dont le défaut connu fausse le début de partie. Il faut donc soit corriger le défaut **ciblé** (règle de rentabilité des mines) avant le 29/11, soit acter que les valeurs de l'alpha sont provisoires et calibrées par le jeu réel. Recommandation : la seconde option, écrite noir sur blanc, avec un serveur accéléré.
- **Fusion de `feat/heros-suite`** : elle conditionne le pilote, l'équipement et les effets. Tant qu'elle n'est pas fusionnée, tout agent qui touche aux héros travaille sur une base obsolète.
- **Fusion ou abandon de `feat/kaelite-deux-soldes`** : grand livre et deux soldes de Kaelite. C'est le seul code de « monnaie » existant ; décider s'il sert de modèle au grand livre de la monnaie de commerce ou s'il reste hors alpha.
- **Les quatre worktrees locaux** : à fusionner, archiver ou supprimer avant de lancer de nouveaux agents, sinon conflits garantis.

---

## 3. Ce qui est sur-spécifié et ce qui manque

### 3.1 Sur-spécifié pour une alpha fermée de 10-15 amis

| Élément du plan | Avis | Proposition |
|---|---|---|
| Design system « validé » avant tout front durable | Bloquant et sans critère d'arrêt ; déjà trois séries de maquettes | Tokens v0 + 6 composants (shell, ligne de ressource, carte d'objet, file, bouton, erreur/chargement) en **48 h, temps limité**. Tout passe par les tokens, donc la palette se change plus tard sans réécrire les écrans. |
| Octane/FrankenPHP, Redis/Horizon, Reverb en semaine 2 | Gain de performance invisible à 15 joueurs ; ajoute de l'état persistant entre requêtes (risque de fuite d'état entre joueurs, que le plan lui-même signale) | Report après l'alpha. Rafraîchissement par TanStack Query (polling 15-30 s) au lieu de Reverb. |
| Compte central et lobby minimal | Inutile avec un serveur | Report. |
| CI/CD complet, collecte d'erreurs, sauvegarde hors machine et restauration dès la semaine 2 | La sauvegarde, oui ; le reste peut être un script | Semaine 2 : `docker compose` existant sur un VPS, HTTPS, sauvegarde quotidienne hors machine. Restauration jouée une fois avant le 28/12. Journal d'erreurs : fichier de logs + Sentry gratuit si 30 min suffisent. |
| Cité refaite en React (30/11-06/12) | Lot 2 complet de la décision d'architecture, non nécessaire pour tester la boucle | Plan B : cité legacy. Plan A : seulement l'aperçu en React si le reste tient. |
| Test de charge | Sans objet à 15 joueurs | Remplacer par un test de concurrence ciblé sur le marché. |
| Inspection administrative du marché, fermeture du marché | Utile, mais un interrupteur de configuration suffit | Un drapeau `market_enabled` + requêtes SQL documentées. |
| FR/EN, parcours public de compte | Le plan le conditionne déjà à une alpha publique | Acter alpha **fermée**, FR seul. L'anglais reste une obligation avant l'alpha publique (CLAUDE.md), pas avant celle-ci. |

### 3.2 Absent du plan

1. **Capacité réelle du fondateur** : nombre d'heures par semaine, créneaux de validation fixes (proposition : 2 × 45 min par semaine, mardi et dimanche), disponibilités pendant les fêtes. Le plan le reconnaît comme inconnu mais n'en fait pas une entrée.
2. **Les questions auxquelles l'alpha doit répondre.** Sans elles, on ne sait ni quoi couper ni comment juger. Proposition :
   - H1 : la boucle héros → expédition → butin → forge → équipement donne envie de revenir (mesure : part du panel qui joue au moins 4 jours sur 7 la première semaine ; nombre de pièces fabriquées par joueur).
   - H2 : la spécialisation par métiers crée des échanges entre joueurs (mesure : part des joueurs ayant acheté ou vendu au moins une fois ; plan A uniquement).
   - H3 : le rythme est lisible (mesure : temps jusqu'à la première pièce ; questionnaire court à J3 et J10).
3. **Vitesse du serveur d'alpha.** La roadmap (Phase 6) note déjà un « serveur alpha à ratios augmentés ×3 à ×5 ». Le plan n'en parle pas. Au rythme ×1, deux semaines de test ne montrent qu'un début de partie.
4. **Télémétrie minimale** : une table d'événements (mission lancée, butin, fabrication, équipement, annonce, achat) pour mesurer H1-H3 sans interroger chaque testeur.
5. **Règle anti-dispersion** : gel des sujets hors alpha jusqu'au 7/01, écrit dans la synthèse de session.
6. **Gestion des branches** : fusionner ou fermer les worktrees et branches existants avant de lancer de nouveaux agents ; une seule fonctionnalité en intégration à la fois (le plan le dit, sans l'outiller).
7. **Sécurité de base d'un serveur exposé avec un dépôt public** : identifiants admin par défaut changés, `APP_DEBUG=false`, phpMyAdmin non exposé, secrets hors dépôt, accès restreint (Cloudflare Access ou liste d'invitations).
8. **Données personnelles des testeurs** : courte notice (quelles données, combien de temps, reset), même entre amis, puisque des adresses e-mail sont stockées.
9. **L'après-alpha** : date de la vague 2 et de la prochaine décision, pour que l'alpha ne devienne pas une fin en soi.

### 3.3 Les quatre points cités dans la commande

- **Design system avant tout front** : principe juste (pas de front jetable), mise en œuvre à changer : temps limité et tokens, pas « arrêté ». Le vrai risque n'est pas de coder sur une palette provisoire, c'est de coder **sans** tokens.
- **Marché dans l'alpha** : la décision de Gregory (pilier d'identité) est légitime, mais le marché dépend de règles non tranchées (Q2, Q4, Q13), d'une boucle qui n'existe pas encore, et il a besoin de **liquidité**, que 10-15 testeurs avec 2 métiers chacun fourniront mal. Le garder dans **l'alpha** mais pas forcément dans **l'ouverture du 7 janvier** : critère chiffré le 13/12 (§ 6). Si le temps manque, un « marché v0 » réduit (composants seulement, prix fixe, achat/annulation, pas d'historique) suffit à tester H2.
- **Déploiement distant dès la semaine 2** : **à garder**, c'est l'une des meilleures idées du plan (on découvre tôt les problèmes de prod), mais en version minimale (§ 3.1). Ce qui coûte, c'est d'y empiler le socle de production.
- **Fêtes** : le plan place la stabilisation dessus. Deux lectures : le fondateur est moins disponible (risque), mais les amis testeurs ont du temps libre (opportunité). Proposition : **pré-alpha à 3-5 proches du 21/12 au 03/01**, gel fonctionnel, seuls les bugs bloquants sont corrigés, et l'ouverture du 7/01 profite de ces deux semaines d'observation.

---

## 4. Le risque de « se tromper de direction »

Le fondateur redoute de s'engager dans la mauvaise voie. Deux remarques préalables :

- **L'alpha est l'instrument qui répond à la question de direction.** Plus elle sort tôt et petite, plus vite on sait si la boucle tient. Une alpha ambitieuse et tardive repousse la réponse ; elle augmente le risque de direction au lieu de le réduire.
- **Peu de décisions de cette période sont vraiment irréversibles**, car l'alpha est fermée et réinitialisable. Ce qui est coûteux, ce sont les **promesses publiques**, les **données de joueurs payants** et l'**écart avec l'upstream**. Aucune n'est nécessaire avant le 7 janvier.

### 4.1 Décisions coûteuses à défaire (à prendre lentement ou à éviter maintenant)

| Décision | Pourquoi c'est coûteux | Recommandation |
|---|---|---|
| Annoncer publiquement une date, un contenu d'alpha ou le marché | Promesse publique, crédibilité du devlog et du Discord | **Aucune communication publique de date avant le 13/12.** Annoncer après le go/no-go, et annoncer ce qui est fait, pas ce qui est prévu. |
| Règles de monétisation liées au marché (Q11, Q12 : accélération en Kaelite, biens vendables) | Une fois de l'argent réel engagé, revenir en arrière touche des achats | Hors alpha. Paiements fermés côté serveur. |
| Modèle de données des objets (catalogue / pile / exemplaire), grand livre de monnaie | Toutes les fonctionnalités suivantes s'y branchent ; migration lourde ensuite | Seule décision technique à **soigner** cette semaine : revue indépendante (Claude + Codex) du schéma avant code. |
| Modifications du domaine hérité (`ExpeditionMission`, combat, production) | Écart avec `lanedirt/OGameX`, coût des correctifs de sécurité | Brancher par extension (nouveau service, issue d'expédition ajoutée) plutôt que réécriture. Limiter les fichiers amont modifiés et les lister. |
| Politique de reset annoncée aux testeurs | L'attente se fixe dès l'invitation | L'écrire dans l'invitation : « progression effacée à la fin de l'alpha ». |
| Rouvrir la stack (Hermès, autre framework, autre front) | Huit semaines déjà consommées sans code d'interface | Ne pas rouvrir. La stack est tranchée ; l'analyse du 17/11 confirme qu'elle tient les métiers. |

### 4.2 Décisions réversibles, à prendre vite (en moins d'une heure chacune)

Palette et typographie v0 ; liste des métiers de l'alpha et leurs noms provisoires ; recettes et valeurs (provisoires, recalibrées) ; vitesse du serveur ; utiliser la cité legacy pour l'alpha ; fournisseur du VPS ; nombre de testeurs ; source de la monnaie pour l'alpha (même provisoire) ; biens vendables (composants seulement) ; date de vague 2. **Une mauvaise réponse à l'une d'elles coûte quelques jours. Une absence de réponse coûte la date.**

### 4.3 Règle de pilotage proposée

« Si la décision se défait en moins d'une semaine sans perte de données joueur, elle se prend dans la séance de validation, sur la recommandation de l'agent, sans nouvelle étude. » Les études multi-agents (préférence connue de Gregory) sont réservées aux décisions du § 4.1.

---

## 5. Ce qui doit être tranché CETTE SEMAINE (avant dimanche 22/11)

Chaque décision appelle un oui/non ou un choix dans une liste. Proposition entre crochets.

1. **Format** : alpha fermée, 10-15 personnes, ordinateur, FR seul, reset annoncé. [Oui]
2. **Plan B garanti au 7/01 + plan A conditionnel**, avec les go/no-go du § 6 ; le marché est jugé le 13/12. [Oui]
3. **Cité** : écrans legacy OGameX pour l'alpha (avec fichiers de langue en vocabulaire fantasy là où c'est simple, menus des fonctions fermées bloqués côté serveur), React réservé aux écrans nouveaux (héros, inventaire, forge, marché). [Oui, à valider par Gregory en connaissance de cause : les testeurs verront l'ancienne apparence sur la cité]
4. **Socle** : pas d'Octane, Horizon, Reverb, lobby ni CI/CD complet avant l'alpha ; VPS + `docker compose` + sauvegarde. [Oui]
5. **Commande du VPS** cette semaine (OVH VPS-2 ou équivalent, prévu en Phase 7) et domaine ou sous-domaine de test. [Oui]
6. **Contenu PvE et métiers de l'alpha** : source unique des composants = expédition (issue `GainItems`) ; **3 métiers proposés** (pour que deux royaumes à 2 métiers soient complémentaires), 2 à 3 recettes chacun ; effets réels sur 1 à 2 statistiques (ex. production d'une ressource, puissance en expédition). Valeurs provisoires. [Oui, noms via le lexique]
7. **Règles minimales du marché (plan A)** : biens vendables = composants seulement ; monnaie gagnée en vendant au marché et via une issue d'expédition, non achetable, non pillable ; frais de vente fixes détruits ; pas d'expiration ; pas de Comptoir PNJ (approvisionnement de secours par un compte admin si une spécialité manque). [Oui]
8. **Vitesse du serveur d'alpha** : ×5. [Oui]
9. **Disponibilités et gel** : créneaux de validation (2 × 45 min/semaine), disponibilités du 19/12 au 04/01 écrites, gel de l'exploration hors alpha jusqu'au 7/01. [Oui]

À fusionner ou fermer cette semaine (travail d'agent, validation de Gregory) : revue puis fusion de `feat/heros-suite` avec suite Pest verte ; décision sur `feat/kaelite-deux-soldes` (garder comme modèle de grand livre, ne pas fusionner) ; nettoyage des quatre worktrees.

### Ce qui peut attendre (après le 7/01, sans risque pour l'alpha)

Q3 (guerres et marché, le PvP est fermé), Q10 (usure), Q11-Q12 (monétisation), Q13 (Comptoir PNJ), Q14 (fusion), nombre final de métiers (Q6 au-delà de l'alpha), chantier de la résolution temporelle, Octane/Horizon/Reverb, lobby et multi-serveur, mobile, FR/EN, refonte React de la cité, correction complète du simulateur (au-delà du défaut ciblé), prestige, Hôpital, conseillers avancés, vitrine, logos, Midjourney, Hermès/Grok, les 94 points de [taches-a-valider.md](../taches-a-valider.md) qui ne touchent pas l'alpha.

---

## 6. Calendrier alternatif avec go/no-go chiffrés

Les dimanches servent de jalons, comme dans le plan Codex. Chaque go/no-go a un critère vérifiable par une démonstration de 10 minutes.

| Semaine | Contenu | Go/no-go (critère chiffré) | Si raté |
|---|---|---|---|
| **S0 — 18 au 22/11** | Les 9 décisions du § 5. Contrat d'alpha d'une page. Revue du schéma objets/inventaire (Claude + Codex). Revue et fusion de `feat/heros-suite`. VPS commandé. | **G0 dim. 22/11** : contrat signé ; 9/9 décisions prises ; suite Pest verte sur `main` après fusion ; VPS disponible. | Passage automatique au plan B. Chaque décision manquante est prise par défaut selon la recommandation entre crochets. |
| **S1 — 23 au 29/11** | Serveur distant (legacy tel quel, HTTPS, sauvegarde quotidienne hors machine, admin par défaut retiré). Shell React + Sanctum + tokens v0 (temps limité 48 h, **mer. 25/11**). Écran héros en lecture via `/api/v1`. Schéma objets/inventaire migré, avec tests. | **G1 dim. 29/11** : une personne extérieure crée un compte à distance, joue sa cité (legacy) et voit son héros (React) ; au moins 4 PR fusionnées dans la semaine. | Couper le plan A dès maintenant ; S2-S3 restent sur la boucle. |
| **S2 — 30/11 au 06/12** | Issue `GainItems` de l'expédition + engagement du héros (indisponible pendant la mission, XP au retour). Inventaire en React. Table d'événements (télémétrie). | **G2 dim. 06/12** : sur le serveur distant, depuis un compte neuf, une expédition revient avec des composants visibles dans l'inventaire ; 0 test rouge. | Réduire à 1 métier et 2 recettes. |
| **S3 — 07 au 13/12** | Forge (recettes, réservation des ingrédients, file résolue à la lecture), équiper/déséquiper avec effet réel dans 1-2 consommateurs, écran forge. Test interne par Gregory + 2 proches. | **G3 dim. 13/12 — décision majeure** : boucle complète jouée à distance par 2 personnes extérieures ; première pièce fabriquée en **moins de 45 min de jeu actif** (sur serveur ×5) ; 0 bug bloquant ouvert. **GO plan A** si G3 tenu **et** G1-G2 tenus à l'heure ; sinon **plan B**. | Si la boucle elle-même manque : alpha décalée au **jeu. 21/01** (date annoncée aux testeurs ce jour-là, pas la veille). |
| **S4 — 14 au 20/12** | Plan A : marché v0 (composants, prix fixe, achat/annulation atomiques, monnaie et son journal, drapeau `market_enabled`). Plan B : onboarding court, correction des frictions de G3, invitations. | **G4 dim. 20/12** (plan A) : tests de concurrence verts (deux achats simultanés de la dernière unité : un seul réussit ; achat contre annulation : une seule issue) ; 2 testeurs échangent sans intervention ; **gel fonctionnel**. | Marché désactivé par drapeau, livré en **vague 2 (lun. 25/01)**. Pas de report de l'alpha pour le marché. |
| **Fêtes — 21/12 au 03/01** | Pré-alpha à 3-5 proches, gel, bugs bloquants uniquement. Restauration jouée une fois sur base jetable (**avant le 28/12**). Invitations du panel. | **G5 dim. 03/01** : au moins 5 jours de pré-alpha sans perte ni duplication ; restauration réussie en moins de 30 min ; liste de 10-15 testeurs confirmés. | Ouvrir à la seule pré-alpha élargie (5-8 personnes) le 7/01. |
| **Ouverture** | **Jeu. 07/01** : vague 1 (5-8 personnes). **Dim. 10/01** : panel complet si aucun bug bloquant en 72 h. Questionnaire à J3 et J10. | — | — |
| **Après** | **Lun. 25/01** : vague 2 (marché si pas livré, ou autre manque). **Dim. 31/01** : bilan H1-H3 et décision sur la suite (bêta d'avril, cf. calendrier Hermès). | — | — |

Différences avec le plan Codex : cité legacy au lieu d'une semaine de React ; socle réduit ; tokens en temps limité ; marché jugé le 13/12 et désactivable par drapeau au lieu d'un arbitrage le 20/12 juste avant les fêtes ; pré-alpha pendant les fêtes ; vitesse ×5 ; télémétrie ; vague 2 datée.

---

## 7. Plan B réduit (garanti au 7/01) et plan A ambitieux

| | **Plan B — alpha minimale garantie** | **Plan A — alpha ambitieuse** |
|---|---|---|
| Hypothèses testées | H1, H3 | H1, H2, H3 |
| Cité | Legacy OGameX, vocabulaire fantasy par fichiers de langue là où c'est simple, fonctions hors alpha bloquées côté serveur | Idem, + aperçu de cité en React si le temps le permet |
| Héros | Héros principal, XP, talents (branche fusionnée) | Idem + conseillers si déjà fonctionnels dans la branche |
| PvE | Expédition avec héros engagé et issue `GainItems` | Idem |
| Métiers | 3 métiers proposés, 2 par royaume, 2-3 recettes chacun | Idem, 3-4 recettes chacun |
| Équipement | Équiper/déséquiper, effet réel sur 1-2 statistiques | Effets sur 2-3 statistiques |
| Commerce | Aucun (dons d'admin si besoin de tester une recette) | Marché v0 : composants, prix fixe, monnaie de commerce gagnée en jeu, frais détruits, drapeau de fermeture |
| Écrans React | Héros, inventaire, forge | + marché |
| Technique | VPS + `docker compose`, sauvegarde quotidienne, restauration testée, polling | Idem |
| Hors périmètre | Octane, Horizon, Reverb, lobby, PvP, paiements, FR/EN, mobile | Idem |
| Charge estimée (relative) | 1 | ≈ 1,5 à 1,7 (le marché concentre l'essentiel du risque de cohérence) |
| Panel | 10-15, vague 1 le 7/01 | Idem |

**Ce que le plan B ne sacrifie pas** (identique au plan Codex) : isolation des comptes, absence de duplication, résolution unique des files, sauvegarde et restauration, suite de tests verte.

**Ce que le plan B accepte consciemment** : la cité a l'apparence d'OGameX, donc les retours sur l'esthétique de la cité sont hors sujet et doivent être annoncés comme tels aux testeurs ; le pilier « commerce » n'est testé qu'en vague 2.

---

## 8. Trois actions immédiates

1. **Séance de décision de 45 min avant samedi 21/11** sur les 9 points du § 5, avec les recommandations par défaut. Toute décision non prise dimanche soir est prise par défaut.
2. **Fusionner ce qui existe avant de produire du neuf** : revue et fusion de `feat/heros-suite`, sort de `feat/kaelite-deux-soldes`, nettoyage des worktrees.
3. **Commander le VPS et lancer le schéma objets/inventaire** (revue croisée Claude + Codex), les deux seuls préalables techniques au chemin critique.

*Limites : aucune mesure de charge, aucun test exécuté, aucune estimation horaire ; la disponibilité de Gregory est inconnue et reste l'hypothèse la plus sensible de tout calendrier. Les constats sur le dépôt sont datés du 18/11 (`main` à `bd29f70b`).*
