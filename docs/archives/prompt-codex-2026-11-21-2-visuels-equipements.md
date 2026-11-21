# Prompt Codex n° 2 : premier jet des visuels d'équipement (2026-11-21)

*À coller dans Codex **après** le prompt n° 1 (il a besoin de la liste d'objets du catalogue). Même branche que le n° 1 si elle n'est pas fusionnée, sinon une branche `feat/visuels-equipements`. Les visuels sont un **premier jet** : ils servent à juger la direction, pas à être livrés tels quels. Aucune image n'est publiée.*

---

Tu travailles sur Kalendrath (jeu navigateur heroic fantasy sombre, PvE-first). Lis `CLAUDE.md` (et `AGENTS.md`), puis la direction artistique : `docs/direction-artistique-planche-de-style.md` (en entier, surtout le prompt d'icône n° 3, le prompt de cadre n° 4 et l'« image ancre retenue »), `docs/direction-artistique-unites.md` (préambule de style), `docs/direction-artistique-batiments.md` et `.claude/skills/brief-midjourney-batiment/SKILL.md` (méthode et pièges déjà rencontrés), `docs/lore.md`. Regarde les **images de référence** de `docs/direction-artistique/references/` (surtout `icone-bois.png`, `icone-fer.png`, `icone-mana.png`, `icone-nourriture.png`, `icone-kaelite.png`, puis quelques bâtiments pour la matière : `batiment-forge-palier1.png` à `3`, `batiment-academie-militaire-palier*.png`) pour saisir le rendu validé : peinture numérique sombre, palette terreuse (gris fer, rouge sang séché, bronze vieilli), **lueur arcanique violet-bleu uniforme**, éclairage de côté à source unique.

## Contexte

Le catalogue d'équipement de l'alpha est dans `database/catalog/items.php` (traductions `resources/lang/{fr,en}/t_items.php`). Il comprend 12 emplacements (tête, collier, épaules, cape, torse, poignets, mains, ceinture, jambes, pieds, anneaux), 3 classes (`war` = Guerrier, `wizard` = Sorcier, `any` = commun) et 5 raretés (1 Blanc, 2 Vert, 3 Bleu, 4 Violet, 5 Orange). Le jeu affiche chaque objet en **icône d'inventaire** (cases de grille, dont l'écran mobile à 360 px de large) et en **fiche** plus grande.

## Ta tâche

1. **Jeu d'icônes.** Génère, avec ton outil de génération d'images, **une icône par objet du catalogue**. Si ton environnement ne permet pas de générer des images, ne simule rien : écris à la place, pour chaque objet, un prompt Midjourney en anglais prêt à coller (même structure que le prompt d'icône n° 3 de la planche de style, avec `--sref` de l'image ancre) et passe à la livraison du rapport.
2. **Contraintes de DA, à respecter pour toutes les icônes :**
   - Style : *dark heroic fantasy RPG item icon*, illustration numérique façon peinture, **objet isolé et centré** sur fond uni sombre neutre, silhouette simple et lisible **à 64 px**, éclairage latéral à source unique.
   - Palette de matière : gris fer, rouge sang séché, bronze vieilli, cuir sombre. **Une seule couleur de lueur : violet-bleu arcanique**, jamais orange, rouge ni vert (la couleur de rareté est ajoutée par l'interface, jamais dessinée dans l'icône).
   - **La rareté se lit dans la richesse du détail et des runes**, pas dans un changement de couleur : blanc = simple et usé, vert = soigné, bleu = gravé de quelques runes, violet = ouvragé et nettement luisant, orange = pièce unique, majestueuse, mais toujours dans la même palette.
   - Les objets `war` sont lourds (plates, mailles, fer forgé) ; les objets `wizard` sont de tissu, cuir et parchemin enchantés ; les objets `any` (colliers, capes, anneaux) sont des bijoux et vêtements de voyage.
   - Pas de texte, pas de personnage porteur, pas de main, pas de mannequin, pas de marque de rareté, pas de cadre. Pour les anneaux et colliers : objet posé, **pas porté**, en veillant à ce qu'il ne se lise pas comme un bijou de luxe moderne.
   - Cohérence entre pièces d'un même ensemble (par exemple la panoplie « Gardien » ou « Adepte » du catalogue) : mêmes matériaux et motifs.
3. **Format et rangement.** PNG carré **1024 × 1024**, nommé `<code-de-l-objet>.png`, rangé dans `docs/direction-artistique/equipements/`. Produis en plus : une **planche contact par classe** (`planche-war.png`, `planche-wizard.png`, `planche-any.png`) qui montre les icônes côte à côte à 128 px et à 64 px, pour juger la lisibilité réelle.
4. **Auto-critique.** Pour chaque icône, note dans le rapport : lisible à 64 px (oui/non), cohérente avec les références (oui/non), défaut éventuel. Régénère une fois celles qui échouent. Signale celles qui restent insatisfaisantes plutôt que de les présenter comme réussies.
5. **Prompts conservés.** Garde, pour chaque icône, le prompt exact utilisé (dans le rapport ou dans `docs/direction-artistique/equipements/prompts.md`), afin de les rejouer sous Midjourney avec `--sref` plus tard.

## Contraintes

- N'écris aucun code de jeu. Ne modifie pas le catalogue ni les traductions. Ne touche à aucun fichier hors `docs/direction-artistique/equipements/` et à ton rapport.
- Le jeu a des **règles de transparence sur l'IA générative** (voir `docs/brief-site-vitrine.md` § 1.4) : indique dans le rapport l'outil et le modèle utilisés pour les images.
- Ne publie rien, n'envoie rien en ligne.

## Livrable

`docs/archives/retours-agents-2026-11-21-visuels-equipements.md` : liste des icônes produites (objet, fichier, auto-critique), les planches, l'outil utilisé, les prompts, les icônes à refaire, ce que Gregory doit juger (direction générale, lisibilité mobile, cohérence avec les bâtiments validés). Pousse ta branche avant de t'arrêter et indique son nom.
