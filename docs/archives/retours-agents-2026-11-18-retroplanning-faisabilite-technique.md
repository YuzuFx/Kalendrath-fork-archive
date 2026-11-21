# Rétro-planning alpha janvier 2027 : faisabilité technique et charge, vérifiées dans le code

18 novembre 2026. Agent Claude, angle « faisabilité technique et estimation de charge ». Lecture seule du code. Aucune suite de tests lancée. Commandes utilisées : `git`, `grep`, lecture de fichiers. Documents challengés : [plan alpha janvier 2027](retours-agents-2026-11-18-plan-alpha-janvier-2027.md) et [démarrage alpha](retours-agents-2026-11-18-demarrage-alpha.md), à la lumière de la [décision d'architecture](../decision-architecture-cible.md).

## Verdict en une phrase

Le plan est juste sur l'ordre des étapes et sur les garanties à ne pas sacrifier. En revanche, **il est irréaliste en charge** : la boucle complète (cité, expédition, butin, forge, équipement, métiers, marché) sur une interface React neuve demande de **75 à 95 jours d'agent**, pour environ 30 jours ouvrés utiles d'ici au 7 janvier. Le marché et la refonte React des écrans de cité ne tiennent pas ensemble dans cette fenêtre. Le chemin critique ne passe pas par le code mais par **les règles non tranchées** (14 questions ouvertes du 16 novembre) et par **la bande passante de validation de Gregory**.

---

## 1. Affirmations du plan : confirmées ou infirmées

| # | Affirmation (plan ou démarrage) | Verdict | Preuve |
|---|---|---|---|
| 1 | `package.json` ne déclare pas React | **Confirmé** | `package.json` : devDependencies = axios, laravel-echo, laravel-vite-plugin, pusher-js, vite. Ni React, ni TypeScript, ni TanStack, ni Tailwind. `vite.config.js` ne compile que le CSS legacy et concatène les scripts jQuery (`resources/js/ingame/*`). |
| 2 | `composer.json` ne déclare ni Octane, ni Horizon, ni Sanctum | **Confirmé** | `composer.json:7-17` : fortify, framework, reverb, tinker, ui, discord-alerts, permission, impersonate. Rien d'autre. |
| 3 | Reverb est en place | **Confirmé, mais quasi inutilisé** | `composer.json:12`, conteneur `ogamex-reverb` dans les deux `docker-compose`. Un seul événement diffusé : `app/Events/ChatMessageSent.php`. `routes/channels.php` ne déclare que des canaux de chat. Aucun événement de fin de file ou de mission. |
| 4 | « La nouvelle interface et une partie du socle restent à livrer » (implicite : une API existe en partie) | **Plus grave que dit : l'API est à zéro** | `routes/api.php:16` : une seule route, protégée par `auth:api`, garde qui **n'existe pas** (`config/auth.php` ne définit que des pilotes `session`/`eloquent`). Aucun dossier `app/Http/Controllers/Api`. Tout passe par `routes/web.php` et des vues Blade. |
| 5 | Une partie des contrôleurs existants parle déjà JSON | **Confirmé, utile** | Réponses JSON dans `FleetController` (62 occurrences), `FacilitiesController` (40), `ResearchController` (17), `ShipyardController` (17), `AbstractBuildingsController` (17)… Ce sont des appels AJAX de l'interface legacy, pas une API versionnée, mais ils montrent les points d'entrée à porter. |
| 6 | Les contrôleurs restent fins, les règles sont dans les services | **Partiellement infirmé** | `FleetController::dispatchSendFleet` (`app/Http/Controllers/FleetController.php:472-650`) contient des règles de jeu : blocage d'attaque, vitesses autorisées selon la classe, durée de maintien d'expédition bornée par le niveau d'Astrophysique (`:554-556` environ), validation d'union. Porter l'envoi d'expédition en API **exige d'abord d'extraire ces règles dans un service** (avec tests), sinon on viole la règle « aucune logique dans les contrôleurs API ». |
| 7 | La branche `origin/feat/heros-suite` (commit `f3a6a17c`) existe : 26 fichiers, services recrutement/XP/talents/équipement/effets, deux migrations, tests | **Confirmé** | `git diff main...origin/feat/heros-suite --stat` : 26 fichiers, +1518/−10. Services `app/Services/Hero/{HeroEffectsService,HeroEquipmentService,HeroExperienceService,HeroRosterService,HeroTalentService}.php`, migrations `2026_10_30_000001/000002`, 6 fichiers de tests. **Fusion sans conflit** (`git merge-tree` propre) : `main` n'a reçu **aucun commit de code** dans `app/`, `database/`, `tests/`, `config/`, `routes/` depuis la base commune du 30 octobre. |
| 8 | « Son intégration aux calculs effectifs reste à contrôler » | **Contrôlé : aucune intégration** | `git grep "HeroEffectsService\|forEmpire\|forHero(" origin/feat/heros-suite -- app` ne renvoie que le service lui-même. Ni `PlanetService`, ni le moteur de combat, ni `ExpeditionMission` ne lisent les effets. Idem pour `HeroExperienceService` et `HeroRosterService` : **aucun appelant** hors de leurs tests. Le héros ne gagne d'XP par aucune action de jeu. |
| 9 | L'équipement n'a aucun effet (synthèse du 16 novembre) | **Confirmé sur `main`, partiellement levé sur la branche** | Sur `main`, `equipment_items` = nom, emplacement, rareté. La branche ajoute `effect_type/effect_scope/effect_value` (migration `2026_10_30_000002`), agrégés par `HeroEffectsService`, mais consommés nulle part (cf. 8). |
| 10 | Un point d'accroche existe pour appliquer des effets à la production | **Confirmé (ajout du présent rapport)** | `PlanetService::calculatePlanetBonuses()` (`app/Services/PlanetService.php:2201`) et le modèle de `CharacterClassService` (`getMineProductionBonus`, `:230`) montrent où brancher un bonus. **Piège** : la production est **mise en cache par planète** (`updateResourceProductionStats`, `:2123`) ; équiper/déséquiper doit recalculer toutes les planètes du joueur, sinon le bonus reste affiché sans être appliqué (ou l'inverse). |
| 11 | L'issue d'expédition `GainItems` n'est pas codée | **Confirmé, et elle est même inatteignable** | `ExpeditionMission.php:660-670` : `processExpeditionGainItemOutcome` envoie un message et rien d'autre (`// TODO: Implement actual item giving logic`). Surtout, `selectRandomOutcome()` (`:950-965`) et `getOutcomeWeights()` (`:67-82`) **ne contiennent pas de clé `items`** : l'issue ne peut jamais être tirée. Il faut ajouter le poids (réglage), la table de butin et le crédit d'inventaire. |
| 12 | L'expédition est le parcours PvE à réutiliser | **Confirmé, avec un coût caché** | Prérequis : Astrophysique ≥ 1 (`ExpeditionMission.php:137-139`), elle-même exigeant Propulsion à impulsion 3, Laboratoire 3, Espionnage 4 (`app/GameObjects/ResearchObjects.php:228-231`), plus des vaisseaux et du deutérium, et une cible en position 16. À vitesse ×1, un compte neuf met **plusieurs jours** à envoyer sa première expédition. La boucle « mission → butin → forge » le premier jour impose soit une vitesse serveur relevée (réglages `economy_speed`, `research_speed`, `fleet_speed*` dans `SettingsService.php:105-155`, coût nul), soit un prérequis allégé (modification du domaine). Le plan ne le dit pas. |
| 13 | Les files ne se résolvent qu'à l'ouverture d'une page | **Confirmé** | `app/Http/Middleware/GlobalGame.php:30-70` : mise à jour du joueur, de la planète courante, des missions et des déménagements à chaque requête authentifiée. `app/Jobs/` ne contient que `CreateLegorMoon.php`. `routes/console.php` ne planifie que des tâches annexes (classements, nettoyages, matière noire). **Aucune résolution hors page.** |
| 14 | La résolution hors page exige Redis/Horizon | **Infirmé pour l'alpha** | Les verrous existent déjà : `PlayerService::updateFleetMissions()` (`app/Services/PlayerService.php:709-755`) verrouille les planètes puis chaque mission (`lockForUpdate`), et `FleetMissionService::updateMission()` (`:549`, `:595`) ignore une mission déjà `processed`. Les conteneurs `ogamex-scheduler` (`schedule:run`) et `ogamex-queue-worker` (`queue:work`, `QUEUE_CONNECTION=database`) existent déjà (`docker-compose.prod.yml:68-100`, `docker/entrypoint.sh:24-30`). Une commande planifiée chaque minute qui parcourt les joueurs ayant une échéance passée et appelle les mêmes méthodes suffit pour 15 testeurs. |
| 15 | Octane est un gain sans risque particulier | **Infirmé : risque concret de fuite d'état entre joueurs** | `app/Providers/AppServiceProvider.php:47-60` enregistre `PlayerServiceFactory` et `PlanetServiceFactory` en **singletons**, qui gardent des caches d'instances (`PlayerServiceFactory.php:16`, `PlanetServiceFactory.php:27-41`). Sous Octane, un singleton survit d'une requête à l'autre dans le même worker : le cache d'un joueur peut servir à la requête suivante. `docs/plan-deploiement.md:102` le signalait déjà comme « non audité ». À 15 joueurs, php-fpm suffit largement. |
| 16 | Inventaire, fabrication, marché, métiers, monnaie de commerce | **Confirmé : rien n'existe** | Aucun résultat pour `recipe`, `marketplace`, `profession`, `metier` dans `app/`, `database/migrations/`, `config/`. « craft » n'apparaît que dans des commentaires (`EquipmentItem.php:10-11`, migration `2026_09_15_000006:19`). `MerchantService` n'échange que ressources contre ressources (erreur déjà notée le 16 novembre). |
| 17 | L'inventaire actuel peut servir de base au marché | **Infirmé** | `hero_equipment` est rattaché à **un héros** (`hero_id`, suppression en cascade, `database/migrations/2026_09_15_000007_create_hero_equipment_table.php:16`). Le marché a besoin d'un **inventaire au niveau du royaume** (composants empilables + exemplaires uniques d'équipement, état « réservé à la vente »). Nouveau modèle de données et migration de l'existant. |
| 18 | « 1192 tests annoncés, résultat historique » | **Non réexécuté (conforme à la consigne)** | Comptage brut : 112 fichiers de tests, environ 1 000 cas déclarés par `test(`/`it(`/`function test` (approximation par grep, les jeux de données Pest multiplient les cas). Cohérent avec 1 109 + 83 de la branche héros. |
| 19 | Une instance distante peut être déployée dès fin novembre | **Plausible techniquement, bloqué côté décision** | `docker-compose.prod.yml` et `Dockerfile` (php-fpm + nginx) existent. `docs/plan-deploiement.md:3` : « rien n'est en place », pas de VPS, pas de workflow. Prérequis non techniques : achat du VPS, DNS, secrets. |
| 20 | Le domaine fantasy (Nourriture stockée au lieu de l'Énergie, Silo, suppression du Réacteur) est à adapter | **Confirmé, et sous-estimé** | GDD §4.1 et lexique (`docs/game-design-document.md:77-115`). C'est une modification réelle de `PlanetService` (production, stockage, prérequis : la Propulsion à impulsion dépend de la Technologie énergétique). Plusieurs jours avec tests, et un écart assumé avec l'amont. |

**Signal de vélocité à ne pas ignorer.** Depuis le 1er septembre, `main` compte **trois commits de code** (`e6376733` le 19/09, `f1c2545d` le 29/09, plus deux correctifs amont le 01/09) et deux branches non fusionnées du 30 octobre. Les sept dernières semaines ont produit de la conception, de la direction artistique et des études, pas de code intégré. Le plan suppose, sans le dire, un basculement brutal vers un rythme d'intégration quotidien.

---

## 2. Estimation de charge par lot

Unité : **jour d'agent** = une journée de travail d'un agent de code, tests Pest et contrôles (Rector, Pint, PHPStan) compris, sur une tâche bornée et une spécification claire. **Validation humaine** = temps de Gregory pour démontrer, jouer, arbitrer, relire (en heures). Les fourchettes supposent des règles déjà tranchées ; une règle floue double facilement la borne haute.

| Lot | Contenu | Agent (j) | Validation Gregory (h) | Dépend de |
|---|---|---:|---:|---|
| **A. Contrat d'alpha et règles** | Réponses aux questions 1 à 14 du 16 novembre (au minimum 1, 2, 4, 5, 6, 8, 13), périmètre, catalogue initial (composants, 5 à 10 recettes, effets) passé au simulateur | 1–2 (rédaction, simulateur) | **8–15** | — |
| **B. Socle API + front** | Sanctum en mode SPA (cookie de session, réutilise la connexion Fortify), squelette `/api/v1`, ressources JSON, React 19 + TS + TanStack Router/Query + Tailwind 4 + shadcn, i18next sur `resources/lang`, Playwright de fumée | 4–5 | 3 | — |
| **C. Design system** | Jetons et composants à partir des maquettes `docs/archives/design-kalendrath-2026-11-17/` (shell, rail, ressources, file, carte d'objet, états vides/erreur/chargement) | 3–4 | **6–10** | B (partiel), maquettes validées |
| **D. Déploiement test** | VPS, image php-fpm existante, TLS, secrets, sauvegarde hors machine + restauration testée, alertes d'erreur (Discord déjà présent), déploiement reproductible | 2–3 | 3–4 (comptes, DNS, paiement) | achat VPS |
| **E. Résolution hors page** | Commande planifiée « résoudre les échéances » (files bâtiments/recherche/unités + missions), idempotence, tests de double résolution | 2 | 1 | — |
| **F. Pilote héros** | Revue et fusion de `feat/heros-suite`, endpoints héros/talents/équipement, écrans React | 4–5 | 3–4 | B, C |
| **G. Cité en React** | API + écrans Aperçu, Ressources, Bâtiments, Recherche, Chantier (formes de file répétitives) | 9–12 | 5–8 | B, C, E |
| **G′. Domaine fantasy** | Nourriture stockée, Silo, retrait Énergie/Réacteur, prérequis revus | 4–6 | 3 | A, simulateur |
| **H. Expédition jouable** | Extraction des règles de `dispatchSendFleet` dans un service, API envoi/suivi/rappel, écran d'envoi simplifié (cible position 16 automatique), rendu JSON du rapport d'expédition | 6–8 | 3 | B, E |
| **I. Butin, inventaire, forge, effets** | Issue `GainItems` atteignable + table de butin ; inventaire du royaume (empilable + exemplaires) et migration de `hero_equipment` ; recettes, réservation des ingrédients, file de fabrication résolue à échéance ; effet de production branché dans `calculatePlanetBonuses` avec recalcul des caches ; XP du héros au retour d'expédition ; écrans inventaire/forge/équipement | 12–16 | 6–8 | A, F, H |
| **J. Métiers + monnaie** | Choix des 2 métiers du royaume, premier palier de progression, monnaie entière + journal des mouvements | 3–4 | 3 | A, I |
| **K. Marché + Comptoir PNJ** | Annonces, réservation, achat atomique, annulation, idempotence, tests de concurrence (dernière unité, achat contre annulation), Comptoir PNJ borné, inspection/arrêt d'urgence (admin Blade), écrans | 10–13 | 5–8 | J |
| **L. Fermetures côté serveur** | Bloquer PvP, espionnage, destruction, paiements, routes legacy hors périmètre (avec tests) | 1–2 | 1 | — |
| **M. Stabilisation et ouverture** | Parcours guidé, textes FR (EN si public), tests de reprise, mesure de charge du panel, correctifs | 5–8 | 8–12 | tout |
| **Total plan complet** | | **≈ 75–95** | **≈ 70–100** | |

**Capacité réelle.** Du 23 novembre au 3 janvier, environ 30 jours ouvrés, fêtes déduites. Des agents en parallèle peuvent produire 1,5 à 2 jours d'agent par jour calendaire **si** les tâches sont indépendantes et les spécifications prêtes, soit 45 à 60 jours d'agent au mieux. Mais le plan impose (à juste titre) « une seule fonctionnalité principale en intégration à la fois », et Gregory doit valider : 70 à 100 heures de validation en 6 semaines représentent 12 à 17 heures par semaine, fêtes comprises. **Le plan complet dépasse la capacité d'au moins 50 %.**

### Chemin critique

```
A (règles, 22/11) ──► I (inventaire/forge) ──► J (métiers/monnaie) ──► K (marché) ──► M
B (socle) ──► C (design system) ──► F (héros) ──┘
E (résolution) ──► H (expédition) ──────────────┘
```

Le maillon le plus long est **A → I → J → K** : chaque étape dépend de règles non tranchées (source des composants, ce qui est vendable, monnaie, Comptoir PNJ). Tant que les questions 2, 4, 5, 6, 8 et 13 restent ouvertes, I et K ne peuvent pas démarrer proprement. Le second chemin, **B → C → G**, consomme beaucoup de jours d'agent mais n'apporte rien de nouveau au test (ce sont les écrans OGameX existants, refaits).

---

## 3. Les cinq sous-estimations les plus dangereuses

1. **Le marché comme « semaine du 14 au 20 décembre ».** Ce n'est pas un écran, c'est un sous-système économique transactionnel : réservation, achat atomique, idempotence, concurrence, restitution, journal, arrêt d'urgence, plus le Comptoir PNJ sans lequel un panel de 10 à 15 personnes n'a pas de liquidité. 10 à 13 jours d'agent, et ses règles ne sont pas écrites (questions 2, 3, 4, 11, 12, 13 ouvertes). Placé en fin de chaîne, il absorbera tout retard accumulé, au moment même où le plan gèle les fonctionnalités. Un défaut de duplication y est bloquant par définition (le plan le dit lui-même).

2. **« Livrer le pilote héros » comme si le héros fonctionnait.** La branche apporte des services testés, mais **rien ne les appelle** : pas d'XP gagnée par le jeu, pas d'effet appliqué à la production ou au combat, inventaire rattaché au héros et non au royaume. Le pilote est un squelette de règles, pas une fonctionnalité. Brancher un seul effet proprement (production + recalcul des caches de planète) et refondre l'inventaire coûte à lui seul 4 à 6 jours.

3. **L'expédition « réutilise le moteur disponible ».** Le moteur la résout, mais : (a) les règles d'envoi sont dans le contrôleur et doivent migrer vers un service ; (b) l'issue `GainItems` est inatteignable et sans table de butin ; (c) un compte neuf n'y accède qu'après plusieurs jours de recherche à vitesse ×1 ; (d) le rapport d'expédition est un message rendu en Blade, à re-sérialiser. 6 à 8 jours avant même le butin.

4. **Le socle « en une semaine », Octane et Redis compris.** API, Sanctum, shell React, design system, déploiement, sauvegardes, Octane, Redis, Horizon, Reverb et lobby en 7 jours relève du vœu. Octane ajoute un risque réel de **fuite d'état entre joueurs** par les fabriques singletons (`AppServiceProvider.php:51-60`), précisément ce que le plan classe parmi les garanties non négociables. Pour 15 testeurs, Octane et Horizon n'apportent rien de mesurable.

5. **La validation humaine et les règles, pas le code.** Les 14 questions du 16 novembre sont toujours ouvertes ; le simulateur a un défaut connu qui fausse les durées du début de partie (`tools/theorycrafting/README.md:7`) ; les maquettes ne sont pas validées ; aucun code n'a été intégré depuis le 30 septembre. Le plan suppose 70 à 100 heures de validation pendant les fêtes. C'est la ressource la plus rare du projet, et le plan ne la chiffre pas.

*Mention hors classement :* le domaine fantasy (Nourriture stockée, Silo, retrait de l'Énergie) est présenté comme une adaptation ; c'est 4 à 6 jours de modification de `PlanetService`, avec divergence vis-à-vis de l'amont et nouveau passage au simulateur.

---

## 4. Alternatives qui raccourcissent

Chaque option est rapprochée de la [décision d'architecture](../decision-architecture-cible.md). Aucune ne remet en cause la cible (API JSON, React, domaine conservé) ; certaines **réordonnent** le lot 0, ce qui est un écart de séquencement, pas de destination.

| Alternative | Gain | Rapport à la décision | Avis |
|---|---|---|---|
| **1. Écrans de cité et de flotte laissés en Blade legacy pour l'alpha, renommés par les fichiers de langue ; React réservé aux écrans neufs** (héros, inventaire, forge, métiers, marché, envoi d'expédition simplifié) | 9–12 j (lot G) | **Conforme** : la décision §6 et §7 assume la cohabitation Blade/React « derrière la même session », et CLAUDE.md interdit de *développer* en Blade, pas de *servir* l'existant. Aucune ligne Blade nouvelle ; seules les chaînes de `resources/lang/` changent, et elles servent ensuite au front React. | **Recommandé.** Les écrans de cité n'ont rien de différenciant à tester en alpha ; les testeurs jugeront la boucle neuve. Prix : une alpha visuellement hybride, à annoncer comme telle. |
| **2. Différer Octane/FrankenPHP, Redis et Horizon après l'alpha** | 3–5 j + suppression d'un risque de fuite d'état | **Écart de séquencement explicite** (la décision les met au lot 0). Justification : la décision les motive par la performance et la résolution planifiée ; à 15 joueurs, php-fpm et la file `database` déjà configurée suffisent, et l'audit Octane des singletons n'est pas fait. | **Recommandé.** Les garder en tête du lot suivant l'alpha, avec le test de fumée Octane prévu par le plan de déploiement. |
| **3. Résolution hors page par commande planifiée chaque minute** (au lieu de jobs Redis différés à l'horodatage exact) | 2–3 j | Conforme à l'esprit de la règle §5.3 (« l'état avance sans joueur connecté ») ; latence de résolution ≤ 60 s, compensée côté client par l'interpolation (§5.1-5.2). | **Recommandé.** Réutilise les verrous existants de `PlayerService`. Le job à l'horodatage exact viendra avec Redis. |
| **4. Reverb différé ; TanStack Query relit l'état à l'échéance** | 1–2 j | Conforme : §5.4 est une amélioration de réactivité, pas une garantie. | Recommandé pour l'alpha. |
| **5. Sanctum en mode SPA cookie, front servi par Laravel sur le même domaine** | 1 j vs jetons + CORS | Conforme (décision §3 « Sanctum » ; plan de déploiement option A). Réutilise la connexion Fortify existante. | **Recommandé.** |
| **6. Pas de lobby ni de compte central** pour un serveur unique | 2–3 j | Écart vis-à-vis du plan (s1), pas de la décision. | Recommandé : l'alpha a un seul serveur. |
| **7. Domaine fantasy limité aux libellés** : garder Énergie/Deutérium d'OGameX sous des noms provisoires, ou neutraliser la contrainte d'énergie par réglage | 4–6 j | Conforme au principe « formules OGameX héritées en V1 » et à « le domaine ne se touche qu'avec raison ». | Recommandé : la Nourriture stockée est un chantier de Phase 4, pas un prérequis de test de la boucle PvE–forge. |
| **8. Vitesse serveur relevée (×3 à ×5) et/ou prérequis d'expédition allégés par réglage** | rend la boucle testable en quelques jours de jeu | Réglages existants, aucun code. Le simulateur doit le refléter. | Recommandé, à valider par Gregory et au simulateur. |
| **9. Un seul consommateur d'effet : la production** (pas le combat) | 2 j | Conforme. | Recommandé : effet visible et vérifiable (ressources/heure). |
| **10. Héros sans immobilisation en mission** : XP créditée au héros principal au retour d'expédition, sans « engagement » | 2 j | Conforme (GDD 5.5 dit déjà que le bonus reste actif en mission). | Recommandé pour l'alpha. |
| **11. Marché en alpha 2** (fin janvier), ou réduit à un **Comptoir PNJ** (achat/vente de composants à prix bornés, sans annonces entre joueurs) | 6–10 j | Arbitrage de périmètre que le plan lui-même prévoit le 20 décembre ; ici avancé au 22 novembre. | **Recommandé** : décider maintenant plutôt que le 20 décembre. Le Comptoir PNJ est de toute façon requis (question 13) et pose la monnaie, son journal et les transactions atomiques, réutilisés ensuite par le marché. |
| **12. Livewire ou Inertia pour aller plus vite** | — | Inertia explicitement écarté (§3) ; Livewire produirait du Blade durable, interdit par CLAUDE.md. | **Déconseillé.** L'alternative 1 donne le gain sans dette. |
| **13. Hébergement : un VPS unique avec `docker-compose.prod.yml` tel quel**, sauvegarde `mysqldump` quotidienne vers un stockage objet, restauration testée une fois | évite d'écrire l'image FrankenPHP et les workflows CI/CD complets (2–3 j) | Conforme au plan de déploiement (démarrage simple), qui reste la cible. | Recommandé. Déploiement par script SSH manuel versionné, CI de déploiement après l'alpha. |

Avec les alternatives 1 à 10 retenues et le marché remplacé par le Comptoir PNJ, la charge tombe à **environ 40–50 jours d'agent** et **40–55 heures de validation** : tenable dans la fenêtre, avec une marge réelle pour la stabilisation. Avec le marché entre joueurs en plus, on revient à 50–62 jours d'agent : à la limite, sans marge, avec les fêtes au milieu.

---

## 5. Séquencement révisé recommandé

Principe : **la boucle neuve d'abord, sur un socle minimal ; l'existant reste servi tel quel.** Chaque semaine se termine par une démonstration jouable sur l'instance distante.

**Semaine 1 — 18 au 22 novembre : règles et décisions (Gregory).**
- Trancher : alpha fermée ; marché entre joueurs **reporté en alpha 2** ou Comptoir PNJ seul ; vitesse serveur ; domaine fantasy limité aux libellés ; Octane/Horizon différés.
- Répondre aux questions 2, 4, 5, 6, 8, 13 du 16 novembre pour le seul premier lot : 2 métiers de démonstration, 6 à 10 composants, 5 à 8 recettes, un effet de production, la source de la monnaie.
- En parallèle (agents) : revue puis fusion de `feat/heros-suite` (sans conflit) ; commande de résolution hors page (lot E) avec tests ; fermetures côté serveur (lot L). Ces trois tâches ne dépendent d'aucune règle ouverte.

**Semaine 2 — 23 au 29 novembre : socle minimal et première mise en ligne.**
- Sanctum SPA, `/api/v1`, shell React + TanStack + Tailwind/shadcn + i18next, design system restreint (shell, rail, ressources, carte d'objet, file, états).
- VPS, `docker-compose.prod.yml`, TLS, sauvegarde et restauration testée, alertes d'erreur.
- Écran héros React sur l'API (lot F).
- Démonstration : connexion distante, écrans legacy renommés, écran héros React.

**Semaine 3 — 30 novembre au 6 décembre : expédition jouable.**
- Extraction des règles d'envoi dans un service, API envoi/suivi/rappel, écran d'envoi d'expédition simplifié en React, rapport d'expédition en JSON.
- Issue `GainItems` atteignable, table de butin, inventaire du royaume (migration de `hero_equipment`).
- Démonstration : un compte neuf (vitesse relevée) envoie une expédition et reçoit des composants, même sans page ouverte.

**Semaine 4 — 7 au 13 décembre : forge et effet réel.**
- Recettes, réservation des ingrédients, file de fabrication résolue à échéance, équipement depuis l'inventaire, effet de production appliqué avec recalcul des caches, XP du héros au retour.
- **Jalon du 13 décembre** (identique au plan) : connexion → cité → expédition → butin → forge → équipement → effet mesurable, à distance. S'il manque, couper la présentation, pas les garanties.

**Semaine 5 — 14 au 20 décembre : métiers, monnaie, Comptoir PNJ.**
- Choix des 2 métiers, premier palier ; monnaie entière et journal ; Comptoir PNJ (achat/vente de composants à prix bornés) avec transaction atomique et tests de concurrence.
- Premiers testeurs invités dès cette semaine (2 à 4 personnes).
- **Gel fonctionnel le 20 décembre.**

**Semaines 6 et 7 — 21 décembre au 3 janvier : stabilisation, avec disponibilité réduite.**
- Reprise après absence, redémarrage des workers, doubles requêtes, restauration, parcours guidé, textes FR, limites connues.
- Si et seulement si la semaine 5 est verte avec deux jours d'avance : annonces entre joueurs **limitées aux composants**, prix fixes, sans expiration, réutilisant le journal et les verrous du Comptoir. Sinon, alpha 2 fin janvier.

**Semaine 8 — 4 au 10 janvier : ouverture graduelle**, conditions du plan inchangées (aucune perte ni duplication connue, droits isolés, restauration démontrée).

**Après l'alpha (ordre suggéré)** : marché entre joueurs complet ; Octane/FrankenPHP avec audit des singletons et test de fumée ; Redis + Horizon et résolution à l'horodatage exact ; Reverb pour les fins de file ; refonte React des écrans de cité (lot 2 de la décision) ; domaine fantasy (Nourriture stockée).

---

## Annexe : commandes de vérification utilisées

- `git diff main...origin/feat/heros-suite --stat` ; `git merge-tree --write-tree main origin/feat/heros-suite` (fusion propre) ; `git diff --stat $(git merge-base main origin/feat/heros-suite) main -- app database tests config routes` (vide).
- `git grep -n "HeroEffectsService\|forEmpire\|forHero(" origin/feat/heros-suite -- app` ; idem pour `HeroExperienceService`, `HeroRosterService`.
- `grep -n "GainItems\|items" app/GameMissions/ExpeditionMission.php` ; lecture de `getOutcomeWeights()` et `selectRandomOutcome()`.
- Lecture de `routes/api.php`, `routes/console.php`, `routes/channels.php`, `config/auth.php`, `app/Http/Middleware/GlobalGame.php`, `app/Services/PlayerService.php:700-755`, `app/Providers/AppServiceProvider.php`, `docker-compose.prod.yml`, `docker/entrypoint.sh`, `vite.config.js`, `composer.json`, `package.json`.
- `git log --since=2026-09-01 -- app database tests routes resources/js` (vélocité de code).
