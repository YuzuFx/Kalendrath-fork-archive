# Hermès, Grok Bot et calendrier de Kalendrath

Analyse Codex du 18 novembre 2026, à la demande de Gregory. Avis indépendant sur le choix d'automatisation et les jalons envisagés : alpha janvier 2027, bêta avril, sortie juin/juillet. Aucun outil installé, aucun changement de roadmap, de gameplay ou de code ; aucun commit ni push.

## Conclusion

Préférence conditionnelle pour Hermes Agent de Nous Research pour automatiser des tâches récurrentes proches du dépôt et garder la maîtrise de leur exécution. Il ne s'agit pas d'une supériorité de performance démontrée sur Grok Bot. Le choix doit être confirmé par un petit essai sur des tâches réelles. Grok Bot demeure une option crédible lorsque la priorité est un service hébergé prêt à déléguer des activités dans les applications.

Le calendrier est plausible comme trajectoire sous conditions de périmètre et de disponibilité régulière : alpha fermée limitée en janvier, bêta élargie en avril, sortie possible en juin/juillet si la stabilité et le rythme ont été éprouvés. Une alpha publique comprenant déjà tout le périmètre du GDD et les nouveaux métiers est beaucoup plus incertaine. Aucun planning chiffré en charge ni débit de livraison mesuré ne permet de garantir les dates.

## Éléments vérifiés dans le dépôt

Lecture de CLAUDE.md, de la dernière synthèse de session (16 novembre), de la roadmap Markdown et d'extraits de sa version HTML, de la décision d'architecture et du plan de déploiement. Contrôle des manifestes applicatifs et des modèles d'équipement.

- L'actif OGameX est réel : domaine, missions, combat, files et tests hérités. Le volume historique de tests décrit dans les documents n'a pas été revalidé par exécution dans cette analyse.
- Le package.json applicatif contient Vite, Axios et les dépendances de diffusion ; aucune dépendance React n'y est déclarée. La recherche des fichiers JSX/TSX n'a trouvé aucun fichier. Des prototypes graphiques existent dans les archives de design : ils ne prouvent pas l'intégration d'une interface React au jeu.
- composer.json déclare Laravel et Reverb ; Octane, Horizon et Sanctum n'y sont pas déclarés. Le socle cible reste donc un chantier, même si des briques existent déjà.
- EquipmentItem est explicitement un catalogue squelette ; HeroEquipment porte propriété et état équipé. Cela ne constitue pas encore une chaîne de fabrication et de commerce complète.
- Les décisions du 16 novembre ajoutent deux métiers par royaume, une monnaie commerciale et un marché par serveur. Des règles importantes restent à arbitrer ; ne pas présenter les recommandations de Claude dans cette synthèse comme des décisions de Gregory.
- Les documents de roadmap contiennent des descriptions anciennes (par exemple 6–7 héros) : leur ordre de tâches aide à repérer les dépendances, mais ne remplace pas les décisions plus récentes.

## Comparaison des outils

Sources officielles consultées le 18 novembre 2026 :

- Hermes : https://github.com/NousResearch/hermes-agent
- Planification Hermes : https://hermes-agent.nousresearch.com/docs/user-guide/features/cron
- Délégation Hermes : https://hermes-agent.nousresearch.com/docs/user-guide/features/delegation
- FAQ Grok Bot : https://docs.x.ai/grok-bot/faq
- Démarrage Grok Bot : https://docs.x.ai/grok-bot/get-started
- Annonce initiale : https://x.ai/news/introducing-grok-bot

Hermes expose un code ouvert, plusieurs fournisseurs de modèles, une mémoire, des skills, des tâches planifiées, de la délégation et plusieurs environnements d'exécution dont Docker et SSH. Son adaptation au dépôt constitue un avantage potentiel de contrôle et de portabilité ; son hébergement et sa maintenance restent une charge à prendre en compte.

Grok Bot propose une machine cloud persistante, du travail en arrière-plan, des routines, du contexte par rôle et une coordination entre bots. La FAQ indique que les bots d'un compte partagent une machine et ses fichiers : des bots distincts ne garantissent pas à eux seuls l'isolation des modifications. L'intérêt pratique est la délégation hébergée et le travail dans les outils existants.

Ces documentations établissent la disponibilité de fonctions, pas des taux de réussite comparés sur Laravel, React ou Kalendrath. Le nombre d'étoiles GitHub, l'ancienneté et le discours commercial ne démontrent ni performance ni fiabilité. La qualité dépend également du modèle choisi, du contexte, de l'environnement et des critères de réussite. Aucun abonnement existant ne doit être supposé couvrir automatiquement tous les appels d'un nouvel outil ; vérifier le mode de facturation lors du pilote, sans chiffrer un budget non testé.

## Délégation proposée

Commencer avec un seul agent et deux ou trois missions récurrentes. Exemples : examiner les échecs CI et proposer un correctif, vérifier cohérence des documents et clés de traduction, exécuter un parcours de test et produire un rapport reproductible. Les synthèses et brouillons peuvent être préparés pour validation, sans publication automatique dans ce pilote.

Pour une évolution : tâche bornée et critères de réussite, branche ou worktree dédié, environnement de test, contrôles du projet, revue technique indépendante, aperçu jouable, validation finale de Gregory. Le dossier présenté doit contenir le problème résolu, les changements, les preuves des contrôles et les éventuelles décisions ouvertes. Gregory valide alors le produit sans devoir auditer chaque ligne de code.

La validation finale seule est réaliste pour une tâche déjà cadrée. Pour une mécanique dont les règles ne sont pas décidées, l'agent doit d'abord faire remonter les choix produit ; les inventer silencieusement dans le code rend la validation tardive plus coûteuse. Conserver GDD, décisions et skills du dépôt comme références communes ; une mémoire d'agent ne doit pas les remplacer.

Le pipeline déterministe garde compilation, analyse et tests ; l'agent intervient là où il faut interpréter un échec ou préparer une correction. L'automatisation des tâches de développement n'a pas à devenir une dépendance du fonctionnement du jeu en production.

Essai proposé de sept à dix jours, borne de temps indicative : mêmes missions concrètes sur un état de départ comparable si les deux outils sont évalués ; relever résultats acceptés, interruptions humaines, corrections, coût et temps gagné net. Retenir l'outil qui réduit effectivement le travail de Gregory. Ne pas installer une organisation de nombreux agents avant d'avoir observé ce bénéfice.

## Calendrier proposé : cibles et conditions

Au 18 novembre, janvier se situe à environ six à dix semaines, avril à quatre ou cinq mois et juin/juillet à sept ou huit mois. Ce sont des fenêtres calendaires, pas des estimations de charge.

| Cible | Périmètre conseillé | Condition de passage |
|---|---|---|
| Janvier 2027, alpha fermée | Petit panel ; interface des parcours essentiels ; production/construction ; mission PvE ; butin ; première fabrication et équipement ; marché minimal si prêt, ou essai dédié ensuite | Une boucle complète fonctionne, y compris après une absence et un redémarrage ; aucune duplication connue sur les parcours testés ; sauvegarde et restauration démontrées |
| Avril 2027, bêta | Métiers et marché présents si retenus dans la V1 ; progression jouable ; onboarding ; exploitation et modération ; FR/EN pour ouverture publique ; boutique seulement si elle est prête et testée | Plusieurs semaines de retours, tests de concurrence et de charge, corrections des défauts bloquants, règles de reset explicites |
| Juin/juillet 2027, sortie candidate | Périmètre de lancement figé, économie observée, contenu initial, support, exploitation et monétisation retenue stabilisés | Une période réelle suffisamment longue sans blocage critique, restaurations et déploiements éprouvés, cadence de correctifs soutenable |

Le marché peut entrer dans l'alpha avec peu de recettes : catalogue restreint et système incomplet sont deux choses différentes. Tout transfert livré doit déjà avoir les garanties essentielles de cohérence. Reporter une étape de livraison ne remet pas en cause le choix des métiers comme pilier du jeu.

Jalon de pilotage recommandé : vers mi-décembre, être capable de jouer sur un environnement distant la boucle connexion → production → mission → butin → fabrication → équipement. Si elle n'existe pas, réduire l'alpha ou déplacer sa date ; ajouter des agents n'efface pas ce manque. Après quelques semaines de livraisons, recalculer le planning à partir du travail effectivement accepté, de la disponibilité hebdomadaire de Gregory et de ce qui reste.

Les tests accélérés servent à détecter les problèmes, mais ne prouvent pas à eux seuls le rythme et l'économie en conditions normales. La promesse d'un an de contenu d'avance, présente dans les documents, peut devenir un gros poste si elle signifie un an entièrement implémenté et testé : distinguer synopsis, contenu écrit et contenu réellement jouable avant de la conserver comme condition de sortie.

## Avis final

L'objectif de Gregory est atteignable comme mode de travail : valider des livrables complets avec contrôle technique préalable. La multiplication d'agents n'est pas une mesure de capacité de livraison. Le gain doit être constaté, et le périmètre de chaque jalon explicite. Préférer un pilote Hermes borné, garder Grok Bot dans la comparaison si la facilité d'exploitation prime, et maintenir les dates comme cibles internes tant que le premier parcours complet et le débit de livraison ne sont pas établis.
