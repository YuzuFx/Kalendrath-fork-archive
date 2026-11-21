# Plan proposé pour une alpha début janvier 2027

18 novembre 2026 — proposition Codex à la demande de Gregory. Cible de travail : ouverture fermée entre le 4 et le 10 janvier, avec le 7 janvier comme repère interne. Ce document n'actualise pas la roadmap officielle et ne vaut pas validation des propositions de périmètre.

## Hypothèses et niveau de confiance

Il reste environ sept semaines, avec les fêtes en fin de période. Le plan suppose une disponibilité régulière pour produire, intégrer, tester et arbitrer ; le nombre d'heures hebdomadaires et le débit réel de livraison restent inconnus. Les dates sont des dates limites proposées, pas une estimation de charge démontrée. Une seule fonctionnalité principale en intégration à la fois ; chaque étape doit produire quelque chose de jouable ou de vérifiable.

L'objectif recommandé est une alpha fermée sur invitations, avec un petit panel (ordre de grandeur proposé : 10 à 15 personnes), un serveur, une première progression et une boucle complète : développer une cité, lancer une mission PvE, recevoir le butin, fabriquer, équiper, échanger. Le marché minimal fait partie de la cible de ce plan, conformément à l'importance accordée par Gregory aux métiers et au commerce. Son retrait constituerait un arbitrage explicite, pas un glissement silencieux.

Une alpha publique ajoute dès cette échéance FR/EN complet, parcours public de compte et préparation de l'ouverture publique. Si c'est l'intention réelle, replanifier la charge correspondante dès la première étape.

## Sources et actifs existants

CLAUDE.md ; dernière synthèse de session du 16 novembre ; décision d'architecture ; étude des serveurs ; roadmap ; tâches à valider ; README du simulateur ; rapport héros du 30 octobre ; manifestes applicatifs actuels. Pas de benchmark ni de suite exécutée dans cette tâche de planification.

Point nouveau par rapport à l'avis précédent : la référence distante locale `origin/feat/heros-suite` existe au commit `f3a6a17c`. Le diff contre HEAD comprend 26 fichiers et des services de recrutement, XP, talents, équipement et effets, deux migrations et des tests. Ce travail mérite une revue avant de refaire le domaine héros. Son intégration aux calculs effectifs reste à contrôler : un service de calcul d'effets ne prouve pas que chaque consommateur de production ou de combat l'utilise déjà. Les 1192 tests annoncés sont un résultat historique, non réexécuté ici. Aucune récupération réseau, fusion ou modification de branche effectuée.

Le package.json principal ne déclare pas React ; composer.json ne déclare pas Octane, Horizon ou Sanctum. La nouvelle interface et une partie du socle cible restent à livrer. Les prototypes graphiques des archives sont des entrées utiles au design system.

## 1. Du 18 au 22 novembre : arrêter le contrat de l'alpha

Priorité absolue : borner le produit à tester avant de multiplier les tâches.

- Choisir alpha fermée ou publique, panel, appareils prioritaires et règles de reset ; signaler que les progrès de test ne sont pas garantis après l'alpha.
- Lister les écrans réellement nécessaires et les fonctions du moteur héritées accessibles. Recommandation : une cité jouable, un héros principal, périmètre initial restreint de recherches, unités et recettes ; fonctions avancées fermées côté serveur.
- Trancher le sous-ensemble initial des métiers, en conservant deux choix par royaume. Si l'on veut observer l'interdépendance, il faut des choix complémentaires entre joueurs et un approvisionnement qui ne bloque pas le petit panel.
- Définir provenance des composants, recettes, effets de l'équipement, obtention et dépense de monnaie, biens vendables, fonctionnement des annonces, règles de frais/restitution. Fixer au moins un débouché récurrent testable. Ne pas inventer de valeurs définitives ici.
- Proposer PvP et paiements désactivés pour cette alpha. Cela évite de faire dépendre le test du marché du système de guerres complet, sans supprimer les besoins de contrôle des transferts. L'accès aux anciennes actions hostiles doit être bloqué côté serveur et testé.
- Valider un design system compact à partir des maquettes : shell, navigation, ressources, carte, file, inventaire, fiche d'objet, erreurs et chargement. Priorité aux états utilisables et à la lisibilité ; pas besoin d'achever tous les assets pour tous les paliers.
- Examiner les règles supposées de la branche héros, sélectionner ce qui est réutilisable et préparer son intégration après revue et contrôles. Établir la situation de référence des tests.
- Examiner le défaut connu du simulateur et corriger ce qui compromet les conclusions nécessaires au début de partie ; toute nouvelle proposition de coût, durée ou prérequis passe par le simulateur avant GDD puis domaine. Ne pas engager une refonte exhaustive du simulateur comme préalable à tout écran.

Livrable : périmètre d'une page, liste ordonnée de tâches avec critères d'acceptation, décisions ouvertes attribuées à Gregory et premières maquettes validées. Si ce contrat n'est pas stabilisé le 22, la suite ne dispose plus de toute sa fenêtre.

## 2. Du 23 au 29 novembre : socle et premier déploiement

- Poser React/TypeScript, API versionnée, authentification Sanctum, composants du design system et traductions par clés. Garder les contrôleurs fins et les règles dans les services.
- Mettre en place Redis/Horizon, runtime cible et Reverb ; vérifier l'isolation d'état entre joueurs dans les processus persistants. La configuration de production doit être testée dès ce lot.
- Prévoir dès s1 le compte central et un lobby minimal : connexion et entrée sur l'unique serveur, sans construire tout le multi-serveur.
- Déployer tôt une instance de test distante à accès restreint, avec secrets adaptés, collecte des erreurs, sauvegarde hors machine et première restauration sur une base jetable.
- Automatiser les contrôles et le déploiement reproductible vers le test. Pour le code, respecter la chaîne du dépôt Rector → style → analyse → tests ; build après JS/CSS ; contrôles d'interface adaptés.
- Livrer le pilote héros et son équipement sur des données de démonstration, avec protection des données de chaque compte.

Livrable : un testeur se connecte à distance, accède à son royaume et à son héros, et les données survivent au redémarrage. Les maquettes et les contrôles peuvent avancer pendant la réalisation du socle ; aucun développement de front durable ne précède le design system validé.

## 3. Du 30 novembre au 6 décembre : développement de cité

- Aperçu, ressources, constructions et files ; recherches et recrutement strictement nécessaires aux premières missions.
- Adapter les règles du périmètre fantasy qui changent réellement le domaine, notamment la nourriture stockée si elle est utilisée ; une simple traduction de l'énergie héritée ne suffit pas. Borner les bâtiments accessibles pour ne pas exposer des systèmes non livrés.
- Résoudre les files du périmètre à échéance, sans page ouverte, avec horodatages durables, traitements idempotents et rattrapage après interruption.
- Vérifier les coûts, prérequis et durées du premier parcours au simulateur, ainsi que les erreurs et reprises d'action dans l'interface.

Livrable : depuis un compte neuf, le joueur développe sa cité et prépare une mission sans commande d'administration ni correction de base. Le retour après absence est cohérent.

## 4. Du 7 au 13 décembre : première boucle PvE–fabrication–équipement

- Exposer un seul parcours de mission PvE réutilisant le moteur disponible, de préférence l'expédition pour cette première boucle. L'intégration du héros doit être explicite : engagement, indisponibilité éventuelle, XP et conséquences du retour selon les règles retenues.
- Livrer envoi, suivi, résultat, retour et attribution unique du butin, y compris les composants de fabrication encore absents de la branche courante.
- Construire l'inventaire, les recettes initiales, la consommation/réservation des ingrédients, la file de fabrication et sa livraison persistante.
- Équiper/déséquiper ; appliquer réellement au moins les effets nécessaires au test, dans leurs consommateurs métier, et vérifier leur retrait. Ne pas se limiter à une statistique affichée.
- Déployer et jouer depuis un compte neuf le parcours entier. Vérifier échec de mission et fabrication interrompue, en plus du cas favorable.

Livrable du 13 décembre : connexion → développement → mission → butin → fabrication → équipement, jouable à distance. Jalon de décision : si cette boucle manque, réduire le contenu et la présentation ; si le défaut est architectural ou de cohérence, réviser la date plutôt que rogner les garanties.

## 5. Du 14 au 20 décembre : métiers et commerce minimal

- Choix des deux métiers au niveau du royaume ; progression et déblocages du premier segment selon les règles validées.
- Monnaie commerciale locale : attribution justifiée, solde entier et journal des mouvements ; aucun achat en argent réel pour cette alpha recommandée.
- Marché local : catalogue paginé, mise en vente, achat et annulation, restitution unique des biens, historique minimal d'opérations. Expiration uniquement si elle est retenue dans les règles initiales.
- Réserver les biens à la publication ; achat atomique débit/crédit/transfert ; idempotence, contraintes et verrous adaptés. Deux achats concurrents de la dernière unité ne doivent pas réussir tous les deux ; achat contre annulation doit avoir une seule issue.
- Empêcher un objet proposé d'être équipé, consommé ou revendu ailleurs. Prévoir inspection administrative des opérations et fermeture du marché en cas d'incident.
- Tester au moins deux royaumes complémentaires et un cas où une spécialité manque. Un mécanisme d'approvisionnement éventuel doit être borné et validé, pas une boutique PNJ complète ajoutée par défaut.
- Limiter catalogue, filtres et décoration pour tenir la date. Mettre réellement les échanges entre joueurs en test dès qu'ils sont cohérents.

Livrable : deux testeurs produisent et échangent sans intervention manuelle, avec conservation correcte des soldes et des biens. Le 20 décembre, geler les nouvelles fonctionnalités. Si le marché n'est pas fiable, arbitrage Gregory entre alpha de la boucle seule et report de l'alpha incluant le commerce.

## 6. Du 21 au 27 décembre : petit test continu et stabilisation

- Inviter quelques testeurs avant l'élargissement ; recueillir blocages, incompréhensions et parcours abandonnés. Les premiers essais ne doivent pas attendre cette semaine : commencer sur les lots précédents.
- Tester comptes et permissions, doubles requêtes, concurrence, reconnexion, redémarrage de workers, interruption de fabrication, absence prolongée et retour au jeu.
- Mesurer le comportement sous la charge attendue du panel : latences et retard des tâches notamment. Ce test ne valide pas une capacité de milliers de joueurs.
- Vérifier restauration récente et procédure de redéploiement/repli ; ne pas supposer qu'un retour d'image annule une migration de données.
- Corriger les blocages de progression et préserver les garanties de cohérence. Adapter le début de partie au simulateur et aux retours ; ne pas chercher à équilibrer toute l'aventure.

Livrable : plusieurs jours d'observation réelle, sans défaut critique connu sur les parcours retenus, et une version candidate identifiée. Prévoir explicitement les disponibilités de fin d'année.

## 7. Du 28 décembre au 3 janvier : préparer l'ouverture

- Geler le catalogue initial et les réglages ; aucun nouveau système. Corriger les défauts bloquants.
- Parcours guidé bref pour démarrer, aide sur les absences et les files, limites connues, instructions de signalement et politique de reset.
- Comptes du panel prêts, accès contrôlés, sauvegardes et supervision actives, canal de retours, possibilité de suspendre les échanges et le serveur si nécessaire.
- Si l'ouverture est publique : livrer et relire FR/EN et traiter la préparation publique prévue au projet ; ne pas transformer implicitement l'alpha fermée en ouverture générale.
- Vérifier qu'une personne est disponible pour surveiller et corriger les premiers jours. Éviter de mettre la date d'ouverture au milieu d'une absence prévue.

## 8. Du 4 au 10 janvier : décision et ouverture graduelle

Repère interne : 7 janvier. Ouvrir d'abord à quelques personnes, puis au panel prévu selon les observations. Conditions : compte neuf capable de terminer la boucle, reprise après absence, droits isolés, absence de duplication connue, restauration démontrée, déploiement reproductible et suivi des erreurs. Tout défaut de perte/duplication de patrimoine ou d'accès aux données d'autrui interdit l'ouverture du parcours concerné tant qu'il n'est pas corrigé.

Si la date doit bouger, le décider à partir du jalon raté en décembre, pas la veille de l'ouverture.

## Répartition du travail et priorités de coupe

Gregory : périmètre, direction visuelle, décisions de jeu, validation hebdomadaire par démonstration. Agents de développement : tâches bornées avec contrôles et aperçu. Revue indépendante : risques, règles et cas limites avant validation finale. Ce plan n'autorise ni lancement d'agents supplémentaires, ni fusion, ni publication.

L'adoption Hermes/Grok Bot n'est pas un préalable à l'alpha. La tester seulement sur une mission utile et bornée, par exemple le rapport de tests. Les scripts et la CI exécutent les contrôles déterministes. Aucun agent n'est requis en permanence pour faire fonctionner le jeu.

À différer en premier : variantes graphiques et animations secondaires, tous les paliers illustrés, catalogue complet des recettes et archétypes, conseillers avancés, prestige complet, Hôpital inédit, guerres/PvP, événements d'alliance, boutique/paiements, parrainage, statistiques de prix avancées et fusion multi-serveur. Ces reports sont des propositions de périmètre d'alpha, pas l'abandon des décisions de V1. Les fonctions reportées ne doivent pas rester accessibles par leurs anciennes routes.

Ne pas sacrifier pour gagner du temps : cohérence des stocks et soldes, isolation des comptes, résolution durable et unique des événements, restauration, contrôles de régression et capacité à diagnostiquer un incident.

## Les trois premières actions

1. Valider le contrat d'alpha et les règles qui bloquent inventaire, recettes et marché.
2. Revoir les maquettes nécessaires et la branche héros existante pour figer le premier lot réalisable.
3. Lancer le socle avec une démonstration distante prévue avant fin novembre.

Ce plan donne un ordre d'exécution et des points d'arbitrage. Sa faisabilité doit être recalée dès le premier lot accepté, puis chaque semaine, selon le travail réellement intégré et le temps de validation disponible.
