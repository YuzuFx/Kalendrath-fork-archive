# Prompt à envoyer à Codex — relecture indépendante du schéma des objets (2026-11-19)

À coller tel quel dans Codex, dans le dépôt `/Users/greg/Projets/OgameX` (branche `main`, qui contient désormais le merge `82117149` de `feat/heros-suite`).

---

Tu relis, de façon **indépendante**, le schéma de données des objets de Kalendrath avant que la moindre migration soit écrite. Lis `AGENTS.md` (qui renvoie à `CLAUDE.md`), puis `docs/contrat-alpha.md`.

**Document à relire.** `docs/archives/retours-agents-2026-11-19-schema-donnees-objets.md`, **uniquement ses sections d'origine**. Ce fichier se termine peut-être par une section « Mise à jour du 2026-11-19 » : **ne la lis pas**. Ne lis pas non plus `docs/archives/retours-agents-2026-11-19-relecture-schema-objets.md`. Règle du dépôt : ton avis sur une décision de conception se demande avant de voir celui de Claude. Tu pourras les lire après avoir livré tes conclusions, et dire alors où tu es d'accord ou non.

**Décisions de Gregory à respecter (2026-11-19).**
- L'alpha ne contient **ni métiers, ni composants, ni fabrication, ni marché, ni monnaie de commerce**. Ces fonctions arriveront plus tard (calendrier public sur 2027) : le schéma doit les **anticiper structurellement** pour qu'aucune ne demande de migration destructive, sans implémenter leur logique ni créer de tables mortes.
- Alpha : deux archétypes de héros (Guerrier, Sorcier), talents, inventaire, 12 emplacements d'équipement (tête, collier, épaules, cape, torse, poignets, mains, ceinture, jambes, pieds, deux anneaux).
- Sources de butin en alpha : expéditions **et** pillage de bots PvE.
- Les effets d'équipement et de talents doivent s'appliquer réellement à la production **et** au combat.
- Les tables sont **additives** : aucune modification des tables héritées d'OGameX (`app/Services`, `app/GameMissions`, `app/GameObjects`, `app/Models` restent proches de l'upstream `lanedirt/OGameX`).
- Serveur accéléré ×5 en alpha.
- **Rééquilibrage :** les objets doivent pouvoir être buffés ou nerfés **un par un** (un ou quelques objets à la fois), sans variable globale massive. Les effets d'un exemplaire sont donc lus depuis sa définition, pas copiés à la création.
- **Butin :** attribué dès l'obtention (résolution de l'issue de l'expédition ou du pillage), pas au retour de la flotte ; le héros peut s'équiper aussitôt, la présence du héros n'est volontairement pas contrôlée.
- **Inventaire :** 32 places (objets équipés exclus), plafond configurable, plus une opération de **suppression** d'un objet (logique, journalisée). L'artisanat la remplacera naturellement plus tard.
- **Données :** les comptes sont remis à zéro entre l'alpha et la bêta ; aucune migration de données alpha vers bêta n'est à prévoir.

**Ce que j'attends de toi.** Vérifie dans le code (cite `fichier:ligne`), ne te fie pas aux affirmations de la note :
1. Les affirmations factuelles sur l'existant (propriété de l'équipement par le héros, cascade de suppression, issue `GainItems` inatteignable dans `ExpeditionMission`, points d'insertion dans `ProductionIndex`, `GameObjectProduction`, `PlanetService`, singletons de fabriques, verrous de `PlayerService`).
2. La solidité SQL en **MariaDB** : contraintes `CHECK`, index uniques sur colonnes nullables, clés étrangères et cascades, clé d'idempotence du journal.
3. La **machine à états** des objets : transitions manquantes ou contradictoires, concurrence (double équipement, double attribution de butin, annulation contre achat plus tard).
4. Le **juste équilibre** : ce qui est sur-spécifié (tables ou colonnes mortes) et ce qui est sous-spécifié (ce qui forcerait une migration destructive pour composants, fabrication, marché, monnaie, événements, conseillers équipables, usure).
5. La migration depuis `hero_equipment` et les collisions de dates avec `feat/kaelite-deux-soldes` (migrations `2026_10_30_000001/000002`).
6. Le **butin des bots PvE** : comment l'attribuer une seule fois, et comment modéliser une table de butin par source sans figer des taux appelés à baisser quand la fabrication arrivera.
7. Les risques sous des processus persistants (Octane est reporté après l'alpha, mais le schéma ne doit pas l'interdire).
8. **Rééquilibrage par objet :** le versionnage des définitions (`version`, `content_hash`, `retired_at`) suffit-il à buffer ou nerfer un objet sans toucher aux autres ? Comment invalider et recalculer la production des royaumes concernés sans course ? Un exemplaire doit-il garder la version à l'acquisition (audit) ?
9. **Plafond d'inventaire et suppression :** comment appliquer 32 places sans course entre deux butins simultanés ? Que faire quand le butin arrive inventaire plein (la proposition est un dépassement temporaire et un refus d'envoyer une nouvelle expédition tant que l'inventaire est plein) ? La suppression doit-elle être logique (état « détruit ») ou physique, et comment la rendre idempotente et traçable ?
10. **Anneaux et classes :** la séparation « type d'objet » (11 valeurs) et « position portée » (12 valeurs dont `ring_1`, `ring_2`) est-elle la bonne ? Une `equip_class` par objet (Guerrier, Sorcier, commune) est-elle plus robuste qu'une liste d'archétypes autorisés en JSON quand de nouveaux archétypes arriveront ?
11. **Trou à signaler :** liste tout ce que ni la note ni ces décisions ne couvrent et qui fera mal en bêta (suppression d'un joueur, purge d'une cité, fusion de serveurs, conseillers équipables, réinitialisation des comptes).

**Livrable.** Écris ton rapport en français dans `docs/archives/retours-agents-2026-11-19-codex-relecture-schema-objets.md`, avec : verdict par section de la note, défauts classés par gravité (bloquant / à corriger / confort) avec correction concrète, désaccords éventuels avec la note, questions restantes pour Gregory. Dans ta réponse finale, donne le chemin du fichier et un résumé de 10 lignes maximum. Ne crée aucune migration, ne modifie aucun code, ne commite et ne pousse rien d'autre que ce rapport. Utilise le vocabulaire de Kalendrath (skill `lexique-kalendrath`), jamais celui d'OGame.
