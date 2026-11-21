# Icônes d’équipement de Kalendrath

21 novembre 2026 · Branche `feat/alpha-visuels-equipements`, issue de `feat/alpha-catalogue-butin`.

Les **47 objets du catalogue** ont une icône PNG carrée de **1 024 × 1 024 px**. Les trois planches montrent chaque pièce à **128 px** puis **64 px**. Cinq pièces ont été régénérées une fois. Le Voile de la Rupture reste difficile à identifier à 64 px.

## Génération et direction artistique

Les images ont été générées avec **ImageGen d’OpenAI**, via l’outil intégré `image_gen.imagegen`. Le service ne communique pas l’identifiant ni la version précise du modèle. Les 52 demandes comprennent 47 premières générations et cinq reprises. Les fichiers retenus ont été redimensionnés et assemblés localement pour les planches. Le [fichier des prompts](../direction-artistique/equipements/prompts.md) conserve chaque demande exacte, les reprises et les adaptations Midjourney avec l’ancre du projet et `--sw 275`.

L’icône Fer a servi de référence de matière et l’icône Mana de référence de lueur. Le Bois et la Nourriture ont servi à juger le rendu des matières mates. La Kaelite a éclairé le niveau de détail et le détourage sur fond sombre. Les Forges et Académies militaires des trois paliers ont guidé le fer travaillé, le cuir et le rouge sourd. Dans les pièces d’équipement, le violet-bleu du Mana est l’unique lueur. Le rouge et le bronze restent des couleurs de matière.

Les pièces du Guetteur partagent des plaques de fer patiné, des rivets de bronze et du cuir rouge sombre. Celles de l’Adepte reprennent le tissu anthracite et les bordures rouge sombre. Les bijoux communs ont des volumes épais, des cordons rustiques et des surfaces martelées. Les pièces de Kaelhelm et le torque des Straumar ont des ornements plus grands et des incrustations plus visibles que les pièces courantes.

## Planches de contrôle

- [Guerrier · 12 icônes](../direction-artistique/equipements/planche-war.png)
- [Sorcier · 12 icônes](../direction-artistique/equipements/planche-wizard.png)
- [Commun · 23 icônes](../direction-artistique/equipements/planche-any.png)

La ligne du haut de chaque cellule montre la vignette à 128 px. La ligne du bas montre la même à 64 px. L’examen a porté sur la lecture du type d’objet, sa distinction parmi les pièces voisines, les matières et l’accent de Mana. Ce contrôle sur planche précède le test dans une grille jouable à 360 px de large.

## Auto-critique par pièce

### Guerrier

| Objet | Fichier | Lisible à 64 px | Cohérent avec les références | Défaut ou observation |
|---|---|---|---|---|
| Heaume du guetteur | [`head_warden_helm.png`](../direction-artistique/equipements/head_warden_helm.png) | oui | oui | Le nasal et l’ouverture détachent bien le casque. Le bas reste très sombre. |
| Spalières du guetteur | [`shoulders_warden_pauldrons.png`](../direction-artistique/equipements/shoulders_warden_pauldrons.png) | oui | oui | Les deux volumes arrondis et le bord riveté identifient les épaules. |
| Haubert du guetteur | [`chest_warden_hauberk.png`](../direction-artistique/equipements/chest_warden_hauberk.png) | oui | oui | La coupe en T se lit à 64 px. Le détail des mailles se juge sur la fiche. |
| Brassards du guetteur | [`wrists_warden_bracers.png`](../direction-artistique/equipements/wrists_warden_bracers.png) | oui | oui | Les extrémités ouvertes et les plaques larges différencient les brassards des grèves. |
| Gantelets du guetteur | [`hands_warden_gauntlets.png`](../direction-artistique/equipements/hands_warden_gauntlets.png) | oui | oui | Reprise retenue. Les grandes plaques mates retrouvent la simplicité du blanc. La lueur est presque imperceptible à 64 px. |
| Ceinturon du guetteur | [`belt_warden_girdle.png`](../direction-artistique/equipements/belt_warden_girdle.png) | oui | oui | Reprise retenue. Une seule boucle carrée. L’arc arrière se fond partiellement dans le fond à 64 px. |
| Grèves du guetteur | [`legs_warden_greaves.png`](../direction-artistique/equipements/legs_warden_greaves.png) | oui | oui | Deux pièces longues, sans pied ni manche. Elles restent proches des brassards dans une grille mélangée. |
| Solerets du guetteur | [`boots_warden_sabatons.png`](../direction-artistique/equipements/boots_warden_sabatons.png) | oui | oui | La pointe carrée segmentée est nette. Les revers de cuir épaississent la cheville. |
| Spalières de la herse | [`shoulders_gate_pauldrons.png`](../direction-artistique/equipements/shoulders_gate_pauldrons.png) | oui | oui | Les bandes verticales évoquent la herse. Le groupement symétrique reste lisible. |
| Haubert de Kaelhelm | [`chest_kaelhelm_mail.png`](../direction-artistique/equipements/chest_kaelhelm_mail.png) | oui | oui | Le col à panneaux dessine trois accents larges. La maille subsiste au centre. |
| Heaume de la Rupture | [`head_rupture_helm.png`](../direction-artistique/equipements/head_rupture_helm.png) | oui | oui | La fracture diagonale et le profil étagé distinguent le heaume du modèle de base. |
| Gantelets du Serment Rompu | [`hands_oath_gauntlets.png`](../direction-artistique/equipements/hands_oath_gauntlets.png) | oui | oui | Les paumes ouvertes et les runes marquées donnent une pose distincte. La coupure est plus lisible sur la fiche. |

### Sorcier

| Objet | Fichier | Lisible à 64 px | Cohérent avec les références | Défaut ou observation |
|---|---|---|---|---|
| Capuche de l'adepte | [`head_adept_hood.png`](../direction-artistique/equipements/head_adept_hood.png) | oui | oui | L’ouverture vide identifie la capuche. La partie sombre se fond avec le fond. |
| Mantelet de l'adepte | [`shoulders_adept_mantle.png`](../direction-artistique/equipements/shoulders_adept_mantle.png) | oui | oui | Le demi-cercle court et l’agrafe centrale distinguent le mantelet des capes. |
| Robe de l'adepte | [`chest_adept_robe.png`](../direction-artistique/equipements/chest_adept_robe.png) | oui | oui | Manches évasées et ourlet long. Le drapé tient sans support visible. |
| Manchettes de l'adepte | [`wrists_adept_cuffs.png`](../direction-artistique/equipements/wrists_adept_cuffs.png) | oui | oui | Les deux tubes ouverts se lisent. Le parchemin se confond avec la bordure à 64 px. |
| Gants de l'adepte | [`hands_adept_gloves.png`](../direction-artistique/equipements/hands_adept_gloves.png) | oui | oui | La finesse des doigts distingue le cuir des gantelets. Les extrémités sont sombres. |
| Écharpe de l'adepte | [`belt_adept_sash.png`](../direction-artistique/equipements/belt_adept_sash.png) | oui | oui | Le nœud et les pans de tissu séparent l’écharpe du ceinturon rigide. |
| Chausses de l'adepte | [`legs_adept_leggings.png`](../direction-artistique/equipements/legs_adept_leggings.png) | oui | oui | Le V reste dégagé. Les coutures les distinguent moins que la coupe face aux braies. |
| Sandales de l'adepte | [`boots_adept_slippers.png`](../direction-artistique/equipements/boots_adept_slippers.png) | oui | oui | La bride large et le bout ouvert restent visibles. |
| Mantelet des sceaux | [`shoulders_ward_mantle.png`](../direction-artistique/equipements/shoulders_ward_mantle.png) | oui | oui | Deux sceaux contrastent avec l’agrafe unique du mantelet d’Adepte. |
| Habit de Kaelhelm | [`chest_kaelhelm_vestment.png`](../direction-artistique/equipements/chest_kaelhelm_vestment.png) | oui | oui | La coupe plus cérémonielle et les grands pans brodés se distinguent de la robe. |
| Voile de la Rupture | [`head_rupture_veil.png`](../direction-artistique/equipements/head_rupture_veil.png) | non | oui | Reprise retenue pour la déchirure et la lueur. À 64 px, la pièce évoque encore une bannière plutôt qu’un voile de tête. À reprendre. |
| Gants des Straumar | [`hands_straumar_gloves.png`](../direction-artistique/equipements/hands_straumar_gloves.png) | oui | oui | Les longues coutures suivent une forme souple. La minceur distingue les gants de l’armure. |

### Commun

| Objet | Fichier | Lisible à 64 px | Cohérent avec les références | Défaut ou observation |
|---|---|---|---|---|
| Amulette du voyageur | [`neck_traveler_amulet.png`](../direction-artistique/equipements/neck_traveler_amulet.png) | oui | oui | Le médaillon ovale et la boucle de corde restent distincts. |
| Pendentif du Serment | [`neck_oath_pendant.png`](../direction-artistique/equipements/neck_oath_pendant.png) | oui | oui | La plaque rectangulaire le différencie de l’amulette. L’encoche devient un petit accent. |
| Cape du voyageur | [`cloak_traveler_cloak.png`](../direction-artistique/equipements/cloak_traveler_cloak.png) | oui | oui | Le triangle et l’agrafe se détachent. Les reprises du tissu se voient en grand. |
| Manteau des Éclats | [`cloak_shard_mantle.png`](../direction-artistique/equipements/cloak_shard_mantle.png) | oui | oui | Le drapé en biais se distingue de la cape du voyageur. Le fermoir minéral est petit. |
| Anneau de cuivre | [`ring_copper_band.png`](../direction-artistique/equipements/ring_copper_band.png) | oui | oui | L’anneau ouvert reste identifiable. La texture martelée évite l’effet joaillerie. |
| Chevalière de mana | [`ring_mana_signet.png`](../direction-artistique/equipements/ring_mana_signet.png) | oui | oui | Le plateau trapu et la taille oblique se lisent. Proche du sceau de fer dans une grille. |
| Sceau de fer | [`ring_iron_seal.png`](../direction-artistique/equipements/ring_iron_seal.png) | oui | oui | Les angles du plateau et les traits resserrés aident à le distinguer de la chevalière. |
| Gambison matelassé | [`chest_quilted_coat.png`](../direction-artistique/equipements/chest_quilted_coat.png) | oui | oui | Les manches étroites et le volume matelassé séparent le gambison de la maille. |
| Braies de chantier | [`legs_road_trousers.png`](../direction-artistique/equipements/legs_road_trousers.png) | oui | oui | Les jambes courtes et la coupe large distinguent les braies des chausses. |
| Diadème runique | [`head_rune_circlet.png`](../direction-artistique/equipements/head_rune_circlet.png) | oui | oui | Reprise retenue. Les pointes et le large bandeau le distinguent des anneaux. |
| Pendentif des galeries | [`neck_rune_pendant.png`](../direction-artistique/equipements/neck_rune_pendant.png) | oui | oui | La masse trapézoïdale et les rainures se lisent. La corde fine compte moins à 64 px. |
| Cape de trame elfique | [`cloak_elfweave.png`](../direction-artistique/equipements/cloak_elfweave.png) | oui | oui | Le long ourlet en éventail est net. La trame elfique demande la fiche. |
| Brassards des vestiges nains | [`wrists_dwarven_cuffs.png`](../direction-artistique/equipements/wrists_dwarven_cuffs.png) | oui | oui | Les volumes courts et cerclés se distinguent des longs brassards. Les repères d’atelier sont fins. |
| Gants de veille | [`hands_watch_gloves.png`](../direction-artistique/equipements/hands_watch_gloves.png) | oui | oui | Les paumes épaisses et les doigts écartés se lisent. Le cuir oxblood reste mat. |
| Ceinture des scieries | [`belt_woodward_belt.png`](../direction-artistique/equipements/belt_woodward_belt.png) | oui | oui | Reprise retenue. La bascule de Bois se lit sur une forme plus simple. |
| Jambières de rempart | [`legs_rampart_greaves.png`](../direction-artistique/equipements/legs_rampart_greaves.png) | oui | oui | Les larges bandes étagées séparent les jambières des grèves du guetteur. |
| Bottes des ruines | [`boots_ruin_boots.png`](../direction-artistique/equipements/boots_ruin_boots.png) | oui | oui | Les chevilles et les semelles massives se lisent. L’écharde de Bois reste un détail de fiche. |
| Anneau d’ambre | [`ring_amber_loop.png`](../direction-artistique/equipements/ring_amber_loop.png) | oui | oui | L’inclusion brune reste mate et différencie l’anneau. Le fil de Bois est difficile à lire à 64 px. |
| Cape du seuil | [`cloak_threshold_cloak.png`](../direction-artistique/equipements/cloak_threshold_cloak.png) | oui | oui | L’ourlet asymétrique détache la silhouette. La couture luisante reste contenue dans l’étoffe. |
| Brassards d’obélisque | [`wrists_obelisk_bracers.png`](../direction-artistique/equipements/wrists_obelisk_bracers.png) | oui | oui | La pointe des incrustations sépare les brassards du modèle nain plus trapu. |
| Bottes des voies profondes | [`boots_deep_road_boots.png`](../direction-artistique/equipements/boots_deep_road_boots.png) | oui | oui | Les tiges sont plus hautes que sur les bottes des ruines. Vérifier les deux côte à côte en jeu. |
| Anneau de la faille | [`ring_seam_ring.png`](../direction-artistique/equipements/ring_seam_ring.png) | oui | oui | La fracture lumineuse et le biseau sont visibles. La masse pourrait paraître lithique en grand. |
| Torque des Straumar | [`neck_straumar_torc.png`](../direction-artistique/equipements/neck_straumar_torc.png) | oui | oui | Le C ouvert et les deux terminaisons massives différencient le torque d’un anneau fermé. |

## À revoir avec Gregory

- **Voile de la Rupture.** La reprise rend la déchirure plus nette. La forme suspendue évoque encore une bannière miniature. Une future passe peut privilégier un bandeau frontal massif et une étoffe très courte, clairement attachée aux côtés.
- **Direction générale.** Juger si le rendu des plates, du bronze et des étoffes reste cohérent à côté des icônes de ressources et des Forges et Académies validées.
- **Lisibilité mobile.** Dans une même grille, comparer les brassards aux grèves, les deux anneaux à plateau et les deux paires de bottes communes. Vérifier aussi les pièces sombres sur le fond définitif du sac.
- **Progression visuelle.** Comparer les panoplies de départ aux pièces de Kaelhelm. En particulier, vérifier que les cols et les grandes incrustations des pièces orange les distinguent sans bordure de rareté.
