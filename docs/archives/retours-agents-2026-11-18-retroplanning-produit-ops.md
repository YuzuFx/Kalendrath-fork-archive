# Rétro-planning alpha 7 janvier 2027 : angle produit, exploitation, communication

*Rapport d'agent du 2026-11-18. Lecture seule, rien n'a été modifié ni poussé hors ce fichier. Documents lus : CLAUDE.md, plan-deploiement, plan-marketing, feuille de route (texte du HTML), étude des serveurs, README et CGU du dossier legal, en-tête et principes des comportements bannissables, taches-a-valider (extraits), et les deux documents Codex du 18 novembre. Les durées de travail de Gregory sont des estimations de ma part : sa disponibilité réelle est inconnue (Codex le dit aussi). Rien ici n'est un avis juridique.*

Calendrier : le 7 janvier 2027 est un jeudi ; le 18 novembre est un mercredi ; les repères du plan (22 nov, 13 déc, 20 déc) sont des dimanches.

---

## 0. Verdict en cinq lignes

1. Une alpha fermée de 10 à 15 invités le 7 janvier est **compatible** avec la feuille de route publique et le plan marketing, à condition de ne **rien annoncer de daté** et de ne pas la présenter comme le jalon « Lancement de l'alpha fermée » tel que la feuille de route le décrit (vagues depuis une liste d'e-mails, ratios accélérés, Patreon, bilingue). C'est une **vague 0** privée.
2. Le plan de Codex suppose, sans les nommer comme bloquants, plusieurs actes que seul Gregory peut faire et dont certains ont des délais externes : achat du VPS, activation de GitHub Actions, décision dépôt public/privé, MariaDB ou PostgreSQL, immatriculation, textes légaux minimaux.
3. Une alpha **en français seul est acceptable** si l'internationalisation par clés est en place dès le premier écran, et si la règle de CLAUDE.md est précisée (voir section 4).
4. La charge de Gregory est **sous-estimée dans le plan** (il y est listé en une ligne, « validation hebdomadaire par démonstration »). Je propose un rythme d'environ 5 à 6 heures de gouvernance par semaine, plafonné, plus un budget séparé pour la direction artistique (section 5).
5. La période du 21 décembre au 3 janvier est celle où le plan concentre stabilisation, préparation et décision d'ouverture : c'est aussi celle où Gregory sera le moins disponible. Je recommande de **déplacer le gel et la décision de go/no-go au 18 décembre**, et de traiter les fêtes comme une fenêtre d'observation sans déploiement (section 3).

---

## 1. Cohérence avec la feuille de route publique et le plan marketing

### 1.1 Ce qui est promis publiquement ou arrêté

| Source | Engagement | État par rapport à une alpha au 7 janvier |
|---|---|---|
| Plan marketing, principe 3 et décision 19 | Des jalons, **pas de dates**, jusqu'aux préinscriptions ; ensuite des dates annoncées qu'on tient | Ne pas annoncer « 7 janvier ». Aucune date publique d'alpha. |
| Feuille de route v2, jalon « Lancement de l'alpha fermée » | Versions 0.1 à 0.4, vagues de testeurs **depuis la liste d'e-mails**, ratios accélérés, notes de version à chaque mise à jour | Pas de liste d'e-mails en service (legal/README : aucune liste au 31/10, prestataire non choisi, Brevo non retenu). Le panel viendra du réseau de Gregory et du Discord. Les ratios accélérés ne sont pas mentionnés dans le plan de Codex (voir 6.5). |
| Feuille de route, jalons précédents | « Design system et écran Héros », « Boucle courte jouable » (aperçu, ressources, bâtiments, recherche, chantier, premiers bots PvE) précèdent l'alpha | Le plan de Codex les compresse en trois semaines. Cohérent dans l'ordre, pas dans les durées. |
| Feuille de route | **Aucune mention de métiers ni de marché** ; le périmètre publique de l'alpha est la boucle courte | Les décisions du 16 novembre (métiers, marché) ne sont pas dans la feuille de route publique. Si le marché est dans l'alpha, la feuille de route doit le dire à la prochaine mise à jour, ou rester silencieuse. Ne rien promettre sur le marché publiquement tant qu'il n'est pas fiable (arbitrage du 20 décembre). |
| Plan marketing, décision 9 | Patreon **pendant** l'alpha ; « pas de seuil d'ouverture » | Voir 1.3 : bloqué par l'auto-entreprise et la licence Midjourney. |
| Plan marketing, décision 10 et jalon « Préinscriptions bêta, avec date » | Les préinscriptions se font **en fin d'alpha**, avec la date de bêta | Impossible en janvier : on n'aura ni durée d'alpha mesurée, ni date de bêta tenable. |
| Plan marketing 4 et 6 | Anglais dans le jeu dès la première version publique ; Discord bilingue ; comptes FR et EN | Voir section 4. |
| Plan marketing, section sanctions | Les règles bannissables se publient « **avant** l'alpha fermée », en FR et EN | Exigence de publication qui s'applique à la vague 0 : il faut au moins la version courte des règles (1.4). |
| Roadmap Phase 5 | « Alpha entre amis » sur NAS + Cloudflare Tunnel | Le plan de Codex la remplace par une démo distante dès fin novembre. Le plan de déploiement (section 6) dit que le staging sur VPS peut remplacer le NAS : cohérent, mais à acter dans la roadmap (une ligne). |

### 1.2 Que faut-il annoncer, quand

Principe retenu : on annonce ce qui est vrai **au moment où on le dit**, jamais une date future.

| Quand | Annonce | Canal | Contenu à éviter |
|---|---|---|---|
| Fin novembre | Devlog : la nouvelle interface a un shell, le pilote héros tourne à distance (si la démo du 29 novembre tient) | Devlog FR/EN, Discord, X | Aucune date d'alpha, aucune promesse de marché |
| Mi-décembre | Devlog : première boucle jouable en interne (cité, expédition, butin, fabrication) avec captures | Devlog, Reddit EN, X | « Alpha en janvier », « bientôt jouable » avec une échéance |
| Après le 13 décembre, si la boucle tient | Appel à candidatures **fermé** pour un petit panel : formulaire, adultes uniquement, sans promesse d'être retenu, sans date | Discord (salon #annonces), Reddit EN | Date d'ouverture, nombre de places « garanties » |
| Le 7 janvier ou après | « L'alpha fermée a commencé, avec N testeurs. Voici ce qu'on teste, voici ce qu'on ne teste pas. » Au passé, pas au futur. | Discord, X, vitrine | Résultats chiffrés précoces, promesse de vague 2 datée |
| Fin de la première semaine | Premières notes de version `0.1.x` | Discord #notes-de-version | |

Ce qu'il ne faut **pas** promettre, dans tous les cas : une date de préinscriptions ou de bêta ; le Patreon avant que l'immatriculation et la licence Midjourney soient réglées ; le marché, la guerre d'alliances ou les paiements ; la conservation de la progression (reset possible) ; l'anglais dans le jeu pour l'alpha ; une capacité en nombre de joueurs.

### 1.3 Patreon

Le Patreon est décidé « pendant l'alpha », mais la feuille de route elle-même le conditionne : « statut d'auto-entreprise en place avant tout encaissement, donc avant le Patreon de l'alpha » (Phase 6, tâche B) et « licence commerciale des images Midjourney : à vérifier avant l'ouverture du Patreon » (Phase 3a). Aucune de ces deux conditions n'est cochée dans les documents lus. Recommandation : **ne pas lier l'ouverture du Patreon au 7 janvier**. Le plan marketing parle de l'ouvrir « pendant l'alpha », pas « au lancement ». Lancer d'abord la démarche d'auto-entreprise cette semaine (délai administratif, un à quatre semaines selon mon expérience, à vérifier) ; l'ouverture du Patreon devient un événement de la semaine 3 ou 4 de l'alpha, quand il y a quelque chose à montrer et des notes de version publiées.

### 1.4 Légal minimal pour une vague 0

Le dossier legal/ est un brouillon marqué « à ne publier qu'après relecture d'un avocat », avec de nombreux champs `[À COMPLÉTER]` qui dépendent de l'immatriculation (dénomination, adresse, téléphone, SIRET). Le tableau du README donne « CGU : avant l'alpha fermée » et « confidentialité : avant le formulaire d'e-mail » ; le registre (07) et la procédure (08) sont requis « avant le premier traitement ». Une alpha de 10 à 15 personnes qui créent un compte est un premier traitement.

Je propose un **jeu minimal de vague 0**, à faire relire avant le 20 décembre (le délai d'un avocat est inconnu : l'engager cette semaine) :

- version courte des CGU de test (article 5.1 déjà rédigé : instable, perte de données, remise à zéro) et règles de conduite en une page, tirées des comportements bannissables (principes 0 et 8, niveaux d'avertissement à suspension) ;
- politique de confidentialité réduite à ce que l'alpha collecte réellement (e-mail, pseudonyme, IP et journaux de jeu, Discord) ;
- mentions légales : l'éditeur doit être identifiable. Tant que l'EI n'existe pas, la question de la publication des coordonnées personnelles ou d'une domiciliation est ouverte (README 4.1) ; c'est un argument de plus pour lancer l'immatriculation tout de suite ;
- registre des traitements (07) et procédure droits/violations (08) remplis et tenus, même minimalement, car ils sont internes et rapides ;
- **adultes uniquement pour la vague 0** (18 ans et plus). Cela évite d'appliquer les règles pour mineurs (13 ans, accord parental avant 15 ans, plafond de dépense) alors qu'il n'y a aucun paiement.

---

## 2. Panel de 10 à 15 testeurs

### 2.1 D'où viennent-ils

Le plan marketing dit : « avant l'alpha, on parle aux communautés de développeurs et au panel connu » ; les communautés de joueurs ne sont ciblées qu'à l'alpha puis aux préinscriptions. Il n'y a pas de liste d'e-mails. Les sources réalistes, par ordre de fiabilité :

1. **Cercle proche (5 personnes)** : amis, anciens joueurs d'OGame/Travian de l'entourage de Gregory, qui répondent à un message direct. Ils servent de noyau et de dernier recours.
2. **Discord Kalendrath et r/Kalendrath (jusqu'à 5 à 10)** : candidature par formulaire, avec langue, expérience de jeux du genre, disponibilité sur deux semaines, appareils. Je ne sais pas combien de membres comptent ces deux espaces aujourd'hui ; c'est la première donnée à relever.
3. **Réseau de développeurs (2 à 3)** : pour les retours techniques (isolation, doubles requêtes, exploits du marché).

Il faut **sur-inviter** : pour 12 testeurs actifs, inviter environ 20 à 25. Les alphas de ce type perdent couramment la moitié des invités après le premier jour (c'est une règle de métier, pas une mesure sur ce projet).

Mix recherché, parce que le marché a besoin de complémentarité : au moins 3 vétérans de jeux de stratégie par navigateur, 3 joueurs casuels, 2 profils très techniques (exploiteurs de failles), et suffisamment de monde pour que **les deux métiers de chaque royaume se répartissent** (le plan de Codex demande « deux royaumes complémentaires et un cas où une spécialité manque » ; il faut donc un minimum de 6 à 8 testeurs actifs en même temps sur le même serveur).

### 2.2 Cadre

| Élément | Recommandation |
|---|---|
| Accès | Invitation par code à usage unique, un compte par personne, un seul serveur. Adultes. Pas de paiement, pas de Patreon lié, pas de monnaie premium. |
| Engagement du testeur | Charte d'une page, pas d'accord de confidentialité juridique : un NDA légal est lourd, difficile à faire respecter et incohérent avec la ligne « projet ouvert à la communauté ». À la place : une demande de confiance (« merci de ne pas publier de captures ni de vidéos d'ici l'ouverture d'un salon public, sauf accord ») et un engagement de signaler une faille plutôt que de l'exploiter. |
| Exploits | Règle simple, tirée des comportements bannissables : un bug d'enrichissement (duplication, marché) **signalé** est remercié ; **utilisé** au-delà d'un test entraîne la remise à zéro du compte. Pas de rémunération. |
| Reset | Annoncé dans la charte : « la progression peut être remise à zéro ; préavis de 7 jours sauf urgence ». Le texte de CGU 5.1 le dit déjà. |
| Données | E-mail, pseudonyme, journaux de jeu et IP (anti-triche), identifiant Discord si lié. Conservation proposée : suppression des comptes de test 30 jours après la fin de l'alpha, sauf consentement à rester dans le panel. Base légale pour l'anti-triche : intérêt légitime (comportements bannissables, principe 7). |
| E-mails transactionnels | Prestataire non choisi. Pour 15 personnes, la solution la plus simple est l'**SMTP de la messagerie OVH** déjà utilisée pour `contact@` (hypothèse à vérifier : limites d'envoi et réputation d'envoi). Aucun envoi marketing, donc aucun suivi d'ouverture. |
| Publication des captures | À autoriser explicitement pour les captures d'écrans d'interface quand Gregory le décide : c'est du matériel de devlog. À ne pas laisser au hasard. |

### 2.3 Canal et format des retours

- **Un seul canal principal : un forum Discord privé** réservé au rôle « testeur » (la spécification Discord prévoit déjà `#alpha`, renvoyé à « plus tard »). Étiquettes : bug, blocage, incompréhension, suggestion, équilibre. Les issues GitHub sont à **éviter** : le dépôt est public, et un exploit du marché ne doit pas y être décrit.
- **Gabarit de bug** (Claude peut le coller dans le forum) : ce que vous vouliez faire, ce qui s'est passé, ce que vous attendiez, étapes, capture, heure approximative, nom du royaume.
- **Un sondage court par semaine** (5 questions maximum, formulaire hébergé dans l'UE ou formulaire Discord) : ce qui m'a bloqué, ce que j'ai compris tard, ce que j'ai aimé, probabilité de revenir, une chose à corriger en premier.
- **Deux sessions d'observation** (30 minutes, partage d'écran) avec des testeurs différents la première semaine : c'est le seul moyen de trouver des incompréhensions que personne n'écrira.
- **Instrumentation** : un entonnoir côté serveur, sans coût de développement important si c'est écrit tôt : compte créé, premier bâtiment, première mission, premier butin, première fabrication, objet équipé, première annonce, première vente, retour après absence. C'est la donnée la plus fiable, et elle correspond aux indicateurs de rétention J1/J7/J14 que le plan marketing veut mesurer. À mentionner dans la politique de confidentialité.
- **Triage** : une revue quotidienne de 20 minutes par Gregory ou un agent chargé de classer en P0 (perte ou duplication de patrimoine, accès aux données d'autrui, serveur inaccessible), P1 (blocage de la boucle), P2, P3. Engagement vis-à-vis des testeurs : accusé de réception sous 48 heures, réponse dans les notes de version.
- **Outillage minimal** : forum Discord, un tableau privé de suivi (dépôt privé, ou fichier suivi dans `docs/`), le formulaire hebdomadaire, un tableau de bord SQL ou une page d'administration pour l'entonnoir. Pas de produit de suivi à acheter.

---

## 3. Exploitation

### 3.1 Hébergement de la démo distante

Le plan de déploiement fournit déjà les éléments. Pour une alpha de 15 personnes, **la solution la plus simple compatible avec ce plan** :

- **Un seul VPS OVH VPS-2** (4 vCPU, 8 Go, 8,65 € TTC par mois selon le plan de déploiement, prix relevé le 30 octobre, à revérifier à l'achat), qui porte lobby minimal, s1 et staging. Le VPS-1 (4,57 € TTC, 4 Go) est trop juste pour Octane, Horizon, Reverb, MariaDB et Redis réunis (le plan le dit lui-même, hypothèse non testée).
- **Une pile `docker compose`** : image du jeu (FrankenPHP/Octane), MariaDB, Redis, avec Caddy intégré à FrankenPHP pour le TLS Let's Encrypt. Déploiement par SSH et script ; pas de Kamal ni de runner auto-hébergé (le plan de déploiement les écarte avant s1).
- **Domaine** : un enregistrement A `alpha.kalendrath.com` (ou `staging.`) **chez OVH**, où le DNS est déjà. **Ne pas migrer le DNS vers Cloudflare avant l'alpha** : le plan de déploiement lui-même classe cette migration comme sensible (messagerie OVH) et nécessaire seulement pour mettre la vitrine sur le domaine nu. La vitrine peut rester sur `kalendrath.pages.dev` ou son prototype.
- **Coût** : environ 9 € par mois pour le VPS, plus un stockage objet pour les sauvegardes (Cloudflare R2 : 10 Go gratuits, ou stockage objet OVH). Ordre de grandeur : **moins de 15 € par mois** pour l'alpha. Cela s'inscrit dans l'enveloppe de 40 € par mois du plan marketing. Le coût en temps est le vrai coût.
- **Charge** : inconnue. Un test de charge simple (quelques dizaines de comptes simulés pendant une heure) est prévu par Codex en semaine 6 ; je le placerais **en semaine 3 (du 7 au 13 décembre)**, avant le gel, pour garder du temps de correction.

### 3.2 Ce que Gregory doit débloquer pour que la démo du 29 novembre soit possible

La démo distante de fin novembre exige, **avant le 24 novembre** :

1. Souscrire le VPS (OVH) et donner l'accès SSH (ou créer la clé de déploiement).
2. Cliquer pour **activer GitHub Actions** sur le fork (le plan de déploiement le décrit comme un clic de Gregory ; aucune exécution n'a jamais eu lieu).
3. Décider **dépôt public ou privé** : le plan de déploiement le pose comme décision de Gregory. Public : environnements avec approbation gratuits, mais code, formules et mécanismes anti-triche lisibles pendant l'alpha. Privé : 2 000 minutes Actions mensuelles (hypothèse du plan, non relue) et GitHub Pro pour l'approbation manuelle. Recommandation : **rester public** pour l'alpha, parce que le jeu est open source par héritage (licence OGameX) et que l'ouverture est une valeur du projet ; mais **retirer toute valeur sensible du dépôt** (secrets sur le VPS uniquement, `.dockerignore` corrigé, image GHCR sans `.env`) et ne jamais décrire un exploit du marché dans une issue.
4. Trancher **MariaDB ou PostgreSQL** (« à trancher avant le premier déploiement » dans la feuille de route). Recommandation : **MariaDB**, parce que tout le domaine, les 1 109 tests et les deux `docker-compose` y sont déjà ; changer de moteur à sept semaines de l'alpha est un risque sans bénéfice pour l'alpha.
5. Choisir le stockage de sauvegarde hors machine et créer le compte (R2 ou OVH).

Sans ces cinq actes, les « deux premières semaines » de Codex dérivent. Ce sont des tâches de 20 à 40 minutes chacune, mais elles attendent Gregory.

### 3.3 Sauvegardes

Reprendre la section 5 du plan de déploiement, **réduite** : dump chiffré quotidien de la base vers le stockage objet, **plus un dump avant chaque déploiement**, rétention 7 jours quotidiens et 4 hebdomadaires (la rétention est marquée « à décider » dans le plan : je recommande cette valeur, qui coûte quasiment rien à ce volume). L'instantané OVH sert de filet secondaire. **Un test de restauration réel avant le 20 décembre** (« une sauvegarde non restaurée n'existe pas », plan de déploiement) et un deuxième avant l'ouverture, dans une base jetable, avec comptage de joueurs, royaumes, objets et annonces. La clé de chiffrement est sur le VPS **et** hors ligne chez Gregory.

Redis n'est pas sauvegardé : acceptable seulement si le rattrapage des événements différés est testé (c'est le point que le plan de déploiement dit « à réexaminer » avec la résolution temporelle). À ajouter aux critères d'acceptation : « arrêter Redis pendant une file en cours, redémarrer, vérifier que la file se termine ».

### 3.4 Supervision

Minimale, gratuite ou presque, avec alerte sur le téléphone de Gregory :

- sonde externe sur `/up` toutes les 5 minutes (un service gratuit type UptimeRobot ou Better Stack, hypothèse à vérifier au moment du choix) ;
- collecte d'erreurs applicatives (Sentry ou équivalent, offre gratuite) ou à défaut une alerte par webhook Discord privé sur les erreurs 500 et les échecs de jobs ;
- une alerte d'espace disque et de mémoire sur le VPS ;
- un indicateur « âge du plus vieux job en attente » (c'est la mesure qui dit si Horizon est en panne) ;
- **un interrupteur d'urgence documenté** : mode maintenance du jeu, fermeture du marché seule, suspension d'une expédition. Codex le prévoit pour le marché ; il faut aussi le prévoir pour tout le serveur.

### 3.5 Qui surveille pendant les fêtes

Gregory est seul, et un agent ne surveille pas : « aucun agent n'est requis en permanence » (Codex), ce qui est vrai pour le fonctionnement, mais personne n'est donc d'astreinte non plus. Je propose un **mode « fêtes » explicite** communiqué aux testeurs :

- **gel des déploiements** du 24 au 26 décembre et du 31 décembre au 2 janvier, sauf correctif P0 ;
- **heures de veille annoncées** (par exemple deux créneaux par jour de 30 minutes), pas de promesse de disponibilité permanente ;
- aucun testeur n'est invité à jouer la boucle du marché pendant ces jours sans un binôme éveillé ;
- un contact de secours : une personne de confiance ayant les clés de coupure (arrêt du marché, maintenance) et la procédure de restauration écrite sur une page.

Conséquence directe sur le calendrier de Codex : les semaines 6 et 7 (21 décembre au 3 janvier) ne sont pas des semaines de travail normales. Je propose de les redéfinir (section 6.1).

---

## 4. Alpha fermée en français seul

### 4.1 Ce que disent les textes

CLAUDE.md : « depuis le 2026-10-27, l'anglais doit être disponible dans le jeu **dès la première version publique** (…) tout texte destiné au joueur a donc sa version anglaise **avant l'alpha publique** ». Le plan de Codex dit lui-même : une alpha **publique** ajoute FR/EN complet, parcours public de compte et préparation de l'ouverture. La feuille de route publique distingue « alpha fermée » (0.1 à 0.4) de la bêta et ne promet pas l'anglais dans l'alpha fermée ; le plan marketing donne « bilingue d'emblée » pour la **communication** et « l'anglais dans le jeu dès la première version publique ».

### 4.2 Recommandation

**Oui, une alpha fermée en français seul est acceptable**, avec quatre conditions :

1. **Des clés de traduction partout dès le premier écran.** Aucun texte écrit en dur ni dans les composants ni dans les messages d'erreur de l'API (point déjà ouvert dans taches-a-valider : « messages d'erreur de l'API à traduire avant l'alpha publique »). Si cette discipline n'est pas tenue pendant le socle du 23 au 29 novembre, le rattrapage coûtera bien plus tard. C'est un critère d'acceptation du socle, à ajouter à la liste de Codex.
2. **Clarifier la règle de CLAUDE.md** par une phrase : « alpha fermée sur invitation : français seul toléré ; première version publique (alpha publique, bêta) : FR et EN complets ». Sinon un agent ou Codex pourra lire la règle comme bloquante. C'est une modification d'un fichier de règles que seul Gregory valide : je la propose, je ne la fais pas.
3. **Les testeurs sont prévenus** : le panel est francophone, et la charte le dit. Un testeur anglophone peut être admis seulement s'il accepte une interface en français.
4. **La traduction anglaise de la portée de l'alpha est préparée en parallèle, sans bloquer** : les chaînes de la boucle (cité, expédition, butin, fabrication, équipement, marché) sont peu nombreuses si le périmètre reste borné. Claude peut les traduire dès qu'elles existent ; les noms anglais du lexique sont « à valider » par Gregory depuis le 27 octobre (feuille de route, tâche « Noms anglais du lexique »). **Cette validation devient prioritaire dès qu'une chaîne anglaise est écrite**, sinon on traduit sur des noms provisoires. À mettre dans la semaine du 14 au 20 décembre (non bloquante) pour que la vague 2 puisse être bilingue.

Risque principal : si on découvre en février qu'il faut ouvrir en anglais plus tôt (via Reddit, où la communauté est en anglais), le retard sera celui de l'**infrastructure**, pas des traductions. D'où la condition 1.

**À ne pas faire** : présenter l'alpha comme bilingue, ou communiquer en français seul. La communication reste bilingue, c'est une règle séparée.

---

## 5. Travail non-code du fondateur dans le plan de Codex, et rythme soutenable

### 5.1 Inventaire (ce que le plan met sur Gregory, ou omet)

| Catégorie | Tâches | Source dans le plan | Fréquence |
|---|---|---|---|
| Décisions de jeu | Contrat d'alpha (format, panel, reset, appareils), sous-ensemble de métiers, provenance des composants et recettes, monnaie, biens vendables, frais, restitution, PvP/paiements désactivés, ratios | Section 1 (18-22 nov) puis arbitrages du 13 et du 20 déc | Un gros lot en semaine 1, puis un arbitrage par semaine |
| Direction artistique et UX | Validation du design system (shell, navigation, ressources, carte, file, inventaire, fiche d'objet, erreurs, chargement), maquettes PC et mobile, icônes d'objets, paliers visuels | Section 1 ; recoupe la Phase 3a toujours « en cours » (paliers de bâtiments, 4 écrans mobiles en attente, mockup héros, design system) | Plusieurs fois par semaine en décembre |
| Production d'images | Midjourney est un processus manuel de Gregory (prompts, upscale, jugement), d'après la synthèse du 2 novembre. L'alpha demande des icônes d'objets, d'équipement et de composants qui n'existent pas encore (« équipement de Forge » est dans la file de production) | Non chiffré dans le plan | Variable, c'est la principale source de dérapage |
| Validation par démonstration | Jouer chaque jalon (29 nov, 6 déc, 13 déc, 20 déc) et dire oui/non | Section « Répartition du travail » | Hebdomadaire |
| Lexique et textes | Noms anglais du lexique, noms de métiers, d'objets et de recettes (via le skill lexique), libellés d'interface | Implicite | Au fil de l'eau, et à la fin pour l'anglais |
| Actes administratifs | Auto-entreprise, compte bancaire dédié, avocat, DPA des prestataires, achat VPS, Actions, DNS | **Absent du plan de Codex** | Quelques jalons dans les trois premières semaines |
| Communication | Devlog bimensuel (validation de Gregory), Discord, recrutement du panel, charte, réponses aux candidats | **Absent du plan de Codex** | Environ 1 heure par semaine (cible du plan marketing : « moins d'une heure par semaine par action récurrente ») |
| Panel | Tri des candidatures, invitations, premier accueil, modération, réponses | Mentionné comme « canal de retours » | Intense du 7 au 20 janvier |

### 5.2 Charge hebdomadaire réaliste

Je ne connais pas le temps disponible de Gregory ; je chiffre donc des plafonds, pas des certitudes.

| Bloc | Semaine type (nov.-déc.) | Fenêtre d'ouverture (janv.) |
|---|---|---|
| Arbitrage de décisions de jeu (lot de 3 à 5 décisions recommandées, réponse oui/non/modifier) | 45 min | 30 min |
| Revue de maquettes ou de composants (design system, écrans) | 1 h 30 | 45 min |
| Démonstration jouée et critères d'acceptation | 1 h 30 | 2 h |
| Triage de retours (panel) | 0 | 2 h |
| Communication (devlog bimensuel, Discord) | 45 min | 1 h |
| Actes administratifs (une seule fois, cumulé sur 3 semaines : environ 6 à 8 h) | 2 à 3 h les semaines 1 à 3 | 0 |
| **Total de gouvernance** | **environ 5 h 30 en régime, 8 h les trois premières semaines** | environ 6 h |
| Production d'images (budget séparé, plafonné) | 3 h maximum | 0 |

Ces chiffres sont **des plafonds de planification**, pas des mesures. Une charge réelle supérieure doit se traduire par une réduction de périmètre, pas par un glissement silencieux (c'est la règle du plan de Codex).

### 5.3 Rythme hebdomadaire de validation proposé

Un rythme fixe, court, qui laisse les agents travailler entre les rendez-vous :

- **Lundi, 30 à 45 minutes : « lot de décisions ».** Codex ou Claude dépose, la veille, **un document d'une page** contenant au plus 5 décisions, chacune avec recommandation, alternative et conséquence sur le calendrier. Gregory répond par oui, non ou modification. Toute décision non traitée le lundi est reportée explicitement à la semaine suivante, avec son coût de retard noté.
- **Mercredi, 1 h 30 : « revue visuelle ».** Maquettes et captures de composants, jugées à partir de pages d'aperçu publiées (les maquettes privées existent déjà sous forme d'artefacts). Seuls les écrans de la boucle d'alpha sont examinés ; les variantes graphiques attendent.
- **Vendredi, 1 h 30 : « démonstration et acceptation ».** Gregory joue lui-même le parcours sur l'instance distante, avec une liste de critères d'acceptation courte. Résultat consigné en trois états : accepté, accepté avec réserves, refusé. C'est cette séance qui alimente le jalon du dimanche.
- **Dimanche, aucune obligation.** Seules les dates de jalon du plan (22 nov, 13 déc, 20 déc) restent des « dimanches de décision » ; tout le reste est fait avant.
- **Règle des trois semaines sans réponse** : si un lot de décisions reste sans réponse pendant deux lundis consécutifs, la recommandation de l'agent devient la valeur par défaut pour l'alpha (réversible, non publique). Cela évite qu'un arbitrage manquant gèle le développement.
- **Pause des fêtes** : pas de lot de décisions les lundis 21 et 28 décembre ; tous les arbitrages nécessaires pour la période sont pris au plus tard le 18 décembre.

### 5.4 Ce qu'il faut sortir du chemin critique

- **Direction artistique** : l'alpha a besoin de composants lisibles, pas de tous les paliers illustrés (Codex le dit). Concrètement : pour l'alpha, un **jeu d'icônes sobre** (placeholders cohérents, éventuellement générés en lot) pour les composants, recettes et objets ; **les illustrations de bâtiments** utilisent les paliers déjà validés ; aucune nouvelle séance Midjourney avant le 13 décembre sans accord préalable, sauf pour un manque qui bloque un écran.
- **Direction visuelle** déjà consignée dans la synthèse du 16 novembre (sanctuaire d'objet, rail de navigation) : la considérer comme la décision de référence, pas comme un chantier ouvert.

---

## 6. Décisions de produit à prendre cette semaine, avec recommandation

Décisions classées par urgence. Chacune est réversible sauf mention contraire.

### 6.1 Le contrat de calendrier : repositionner la décision de go/no-go au 18 décembre

**Problème.** Le plan de Codex prévoit gel le 20 décembre, stabilisation du 21 au 27, préparation du 28 au 3 janvier, ouverture du 4 au 10. Les deux dernières semaines coïncident avec les fêtes. Le jalon du 13 décembre (boucle PvE-fabrication-équipement) et celui du 20 (marché) sont déjà serrés pour une seule personne qui valide.

**Recommandation.** Garder la **cible du 7 janvier** comme repère interne, mais :
- date de **gel fonctionnel : 18 décembre** (au lieu du 20) ;
- **go/no-go : le vendredi 18 décembre**, sur la base du jalon du 13 et de l'état du marché ;
- **du 19 au 23 décembre** : stabilisation, restauration, test de charge ;
- **du 24 décembre au 3 janvier : pas de nouveau développement**, observation avec un petit noyau de testeurs (5 amis) seulement si Gregory est joignable, sinon rien ;
- **ouverture du 7 janvier au panel élargi** si les conditions du plan (compte neuf capable de terminer la boucle, reprise après absence, droits isolés, aucune duplication connue, restauration démontrée, déploiement reproductible) sont atteintes ; **sinon 14 janvier, sans drame**, puisque rien n'a été annoncé publiquement.

Cette décision est la plus importante : elle transforme la période des fêtes d'un risque en tampon.

### 6.2 Alpha fermée uniquement, ou alpha publique ?

**Recommandation : alpha fermée, adultes, sur invitation**, aucune ouverture publique, aucune annonce de date. Conséquences : FR seul toléré (section 4), pas de parcours public de compte, pas de CGV ni de boutique, légal minimal (1.4).

### 6.3 Le marché dans la cible ou non

Gregory a tranché le 16 novembre (marché inclus) contre la recommandation d'un agent. Le plan de Codex le conserve avec un arbitrage le 20 décembre. **Recommandation : maintenir, avec un point de décision avancé au 13 décembre sur une question précise** : « la boucle PvE-fabrication-équipement est-elle jouable de bout en bout ? ». Si non, le marché est reporté sans autre discussion. Si oui, le marché se construit du 14 au 18 décembre, et son état au 18 décembre décide d'une ouverture avec ou sans marché (« alpha de la boucle seule » acceptable comme vague 0, le marché devenant la vague 1).

**Ce qu'on peut tenir publiquement** : rien. Le marché n'est pas encore dans la feuille de route publique ; ne pas l'y ajouter avant qu'il soit fiable.

### 6.4 Administratif à lancer cette semaine

1. **Immatriculation en auto-entreprise** (la tâche est déjà dans « Cette semaine » de la feuille de route et jamais cochée). Elle conditionne les mentions légales, le compte bancaire dédié, le Patreon, la boutique.
2. **Avocat** : prendre contact pour la relecture du jeu de textes minimal (1.4), avec le délai de réponse en tête.
3. **Souscrire le VPS**, **activer GitHub Actions**, trancher **dépôt public ou privé** et **MariaDB** (3.2).

### 6.5 Ratios accélérés

La feuille de route promet des **ratios accélérés** (×3 à ×5) pour l'alpha afin de repérer les erreurs de conception de rythme. Le plan de Codex n'en parle pas. Pour une alpha de deux à trois semaines et un marché à observer, un serveur à vitesse ×1 empêcherait de voir la boucle complète en peu de temps. **Recommandation : ×5, paramétré par serveur (réglage existant d'OGameX, à vérifier), validé contre le simulateur de theorycrafting avant l'ouverture, et annoncé dans la charte comme réglage d'alpha non représentatif**. Cela touche aussi le calendrier de résolution temporelle et les tests de reprise après absence : à intégrer aux critères d'acceptation, pas à ajouter à la fin.

### 6.6 Périmètre du panel et son unique serveur

**Recommandation** : 12 testeurs actifs visés, 20 à 25 invités, 6 à 8 simultanément nécessaires pour tester le marché, un serveur, ordinateur d'abord (mobile non garanti), navigateur récent. Une cohorte unique : on évite de multiplier les vagues avant d'avoir une base de données solide.

### 6.7 Position sur le Patreon et les préinscriptions

**Recommandation** : aucune ouverture ni annonce avant le 7 janvier ; le Patreon à partir de la semaine 3 de l'alpha, **si** l'immatriculation et la vérification de la licence Midjourney sont terminées (liste d'attente sinon). Préinscriptions à la bêta : **pas en janvier**, à décider après deux vagues de l'alpha et une mesure de rétention.

### 6.8 La règle des agents Hermes/Grok Bot

Codex le place hors chemin critique : d'accord. **Pas d'agent d'exploitation autonome pendant l'alpha**, en cohérence avec « ne jamais déléguer comptabilité, droit, bannissements, engagements » (taches-a-valider) et la règle de circuit de décision humain des comportements bannissables.

---

## 7. Écarts et risques que le plan de Codex ne couvre pas

| Écart | Conséquence | Mesure |
|---|---|---|
| Aucune ligne pour l'**administratif** (immatriculation, avocat, VPS, DNS, sauvegardes hors site) | Retards externes imprévus qui bloquent la démo de fin novembre | Les lancer cette semaine (6.4) ; ajouter une colonne « dépend de Gregory » au backlog |
| Aucune ligne pour la **communication** (devlog, recrutement, charte) | Le panel n'est pas prêt le 7 janvier ; ou les annonces dépassent ce qu'on peut tenir | Calendrier d'annonces de 1.2 ; un devlog tous les quinze jours, relu par la checklist de publication |
| Aucune ligne pour le **légal minimal** | Ouverture bloquée en fin de parcours (CGU et règles de conduite à publier « avant l'alpha fermée ») | Relecture d'un jeu minimal avant le 20 décembre |
| **Ratios accélérés** absents | Boucle trop lente pour que le marché soit observé en deux semaines | Voir 6.5 |
| **Calendrier des fêtes** : semaines 6 et 7 | Stabilisation en l'absence du seul valideur | Voir 6.1 et 3.5 |
| **Tests de charge en semaine 6** | Découverte tardive | Avancer à la semaine 3 |
| **Charge de DA** non chiffrée | Dérapage du design system | Plafond de 3 h par semaine, placeholders pour les icônes |
| **Branche `origin/feat/heros-suite`** (26 fichiers, 1 192 tests annoncés, non réexécutés) | Dépendance cachée du calendrier : tout repose sur une revue de cette branche en première semaine | Hors de mon angle, mais à noter comme **prérequis du lot du 23 novembre** ; si elle est inutilisable, le pilote héros glisse |
| **Dépôt public** et exploits du marché | Fuite de vecteurs d'attaque dans une issue ou un commit | Canal privé pour les failles ; `SECURITY.md` existe dans le dépôt |
| **Panel trop petit pour le marché** | Le marché n'est pas testé en pratique | Seuil de 6 à 8 simultanés ; sinon scénarios scriptés avec comptes de test |

---

## 8. Liste d'actions pour la semaine du 18 au 22 novembre (côté produit et exploitation)

Par Gregory :
1. Trancher 6.1 (go/no-go au 18 décembre, repère du 7 janvier), 6.2, 6.5 (ratios), 6.6.
2. Lancer l'auto-entreprise et contacter un avocat (6.4).
3. Souscrire le VPS, activer GitHub Actions, trancher dépôt public ou privé (recommandation : public) et MariaDB (recommandation : MariaDB).
4. Valider ou refuser la phrase de précision sur l'alpha fermée dans CLAUDE.md (section 4).
5. Dire quel temps hebdomadaire il peut réellement consacrer au projet, et si les fêtes sont ou non travaillées. **C'est l'information qui manque à tous les plans.**

Par Claude (sur demande) :
1. Rédiger la charte du testeur d'une page, le gabarit de bug, le formulaire de candidature et le sondage hebdomadaire.
2. Préparer la version minimale des CGU de test et des règles de conduite en une page, à faire relire.
3. Préparer le calendrier d'annonces de 1.2 en brouillons FR/EN, soumis à la checklist de publication et au skill de voix.
4. Ajouter à la liste des critères d'acceptation du socle : clés de traduction partout ; entonnoir de jeu ; reprise après arrêt de Redis ; interrupteurs d'urgence.

---

## 9. Ce que je n'ai pas pu vérifier

- Le nombre de membres actuels du Discord et de `r/Kalendrath`, la disponibilité de Gregory, l'état réel du compte Cloudflare, de l'auto-entreprise et du VPS.
- Les prix et conditions OVH, Cloudflare R2 et services de supervision : repris du plan de déploiement (relevés le 30 octobre) ou de ma connaissance générale, non revérifiés aujourd'hui.
- Les délais d'immatriculation et de réponse d'un avocat.
- Les limites d'envoi de la messagerie OVH pour des e-mails transactionnels.
- L'existence d'un réglage de ratios par serveur dans OGameX (je ne l'ai pas cherché dans le code).
- Le contenu complet des comportements bannissables et du dossier legal : lus en diagonale (en-têtes, principes, README).
- Aucun code n'a été lu ni exécuté.
