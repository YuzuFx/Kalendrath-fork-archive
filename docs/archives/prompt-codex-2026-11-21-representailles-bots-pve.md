# Prompt pour Codex : représailles des camps de bots PvE (2026-11-21)

*Prompt à coller dans Codex. Il ne contient volontairement aucune proposition de Claude (règle du projet : l'avis de Codex se demande avant de lui montrer le nôtre). Le rapport de Codex doit être écrit dans `docs/archives/retours-agents-2026-11-21-representailles-bots-pve.md` avant la fin de sa tâche.*

---

Tu travailles sur Kalendrath (jeu navigateur heroic fantasy PvE-first, base Laravel/PHP issue d'OGameX). Lis d'abord `CLAUDE.md`, `docs/contrat-alpha.md` (§2, §3 critère 8, §6), `docs/game-design-document.md` §5.8A (bots PvE), `docs/etude-pvp.md` (protection des débutants, boucliers), `docs/comportements-bannissables-v1.md`, `app/Services/Pve/PveCampService.php`, `config/pve.php`, `app/GameMissions/AttackMission.php`.

**Contexte.** Un camp de bot PvE est une planète du compte bot. Depuis le 2026-11-21 il produit des ressources comme un joueur (mines, entrepôts, formules héritées) et recrute sa garnison manquante avec ses propres ressources. L'alpha fermée ouvre le 14 janvier 2027 (10 à 15 joueurs, serveur ×5, ordinateur et mobile). Gregory a décidé que **les camps font des représailles sur les joueurs dès l'alpha**. Il exige des **règles d'attaque équilibrées** : un bot fort ne doit jamais attaquer un joueur et le raser. Ce que le camp pille entre dans son stock. Le PvP entre joueurs, les guerres et le Mode de Guerre sont exclus de l'alpha.

**Ta tâche : concevoir (pas implémenter) la mécanique de représailles.** Réponds à chacune de ces questions avec une recommandation chiffrée ou testable, des alternatives écartées et leur motif :
1. **Déclenchement.** Quelle action du joueur provoque des représailles (victoire de pillage contre ce camp ? nombre de pillages en une fenêtre ? pillage répété du même camp ?) et après quel délai ? Un joueur qui ne touche jamais aux camps est-il jamais attaqué ?
2. **Choix de la cible.** Quel joueur, quelle cité (celle d'origine du raid ? la plus proche ? la plus riche ?) et comment éviter qu'un joueur soit visé par plusieurs camps à la fois.
3. **Force engagée.** Comment dimensionner l'attaque du bot par rapport à la force du joueur (défenses, garnison, héros) pour qu'elle soit menaçante mais jamais létale ? Le bot dépense-t-il sa garnison ou une armée distincte ? Quelle part de ses ressources ?
4. **Garde-fous d'équilibre.** Plafond de pertes par attaque (ressources, unités, bâtiments), plafond par jour et par semaine, protection des nouveaux joueurs, mode vacances, joueur hors ligne, joueur sous le seuil de puissance. Que se passe-t-il si le joueur est trop faible ?
5. **Cadence.** Fréquence maximale, temps de préparation, délai de refroidissement du camp après représailles.
6. **Lisibilité pour le joueur.** Quels avertissements (message d'alerte avant l'impact, rapport) pour que ce soit une mécanique juste et non une punition ?
7. **Pillage par le bot et stock.** Quel pourcentage de ressources le bot peut-il piller, et comment cela interagit-il avec la production et le recrutement du camp ?
8. **Risques d'abus.** Exploits possibles (faire attaquer un camp sur un autre joueur, farmer les représailles, multi-comptes) et parades.
9. **Simulation.** Quels scénarios passer au simulateur de theorycrafting (`tools/theorycrafting/`) avant tout code, avec quelles métriques de succès ?
10. **Découpage.** Un plan en tâches ordonnées avec test Pest par tâche, aucune logique dans les contrôleurs, compatible avec le moteur de combat et les tests existants.

**Livrable.** Un rapport en français, structuré par ces 10 questions, avec un tableau final des paramètres réglables (nom de config, valeur initiale proposée, plage). Écris-le dans `docs/archives/retours-agents-2026-11-21-representailles-bots-pve.md`. Ne modifie aucun fichier de code.
