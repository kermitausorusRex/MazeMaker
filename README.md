# MazeMaker
Générateur de labyrinthes parfaits

---

## client.php
interface à utiliser pour la génération de labyrinthes, gère toute la communication avec generator.php

## generator.php
script à requeter pour la génération de labyrinthes

les différents paramètres du labyrinthe sont passés dans la querystring avec les attributs suivants:
- `width`: largeur du labyrinthe
- `height`: hauteur du labyrinthe
- `seed`: graine de génération à utiliser (0 = aléatoire)
- `DEBUG`: si `DEBUG` est présent dans la querystring, le générateur fonctionnera comme une page html standard et affichera le déroulé des opérations pour la génération de labyrinthe, ainsi que le résultat en bas de la page, sinon, le script renvoie juste l'image du labyrinthe
- `imgWidth`: largeur de l'image en pixel (0 = automatique), la hauteur de l'image sera décidée automatiquement afin de maintenir un ratio d'image correct pour le labyrinthe
- `imgFormat`: format de l'image générée, doit être compris parmis { `png` , `jpg` }
- `tileset`: le jeu de tuiles à utiliser parmis ceux disponnibles, doit être compris parmis { `default`, `pixel` , `pacman` , `dark` } mais il est toujours possible d'ajouter d'autres jeux de tuiles dans la structure `$TILESETS_AVAILABLE` au début du fichier `generator.php` 
- `SOLUTION`: si `SOLUTION` est présent dans la querystring, le générateur prendra en compte `start`et `finish` pour tracer la solution du labyrinthe
- `start`: indice de la case d'entrée du labyrinthe, cet indice doit être compris entre 0 et `width` * `height`-1. **Ce paramètre est OBLIGATOIRE si `SOLUTION` est présent dans la querystring**
- `finish`: indice de la case de sortie du labyrinthe, cet indice doit être compris entre 0 et `width` * `height`-1. **Ce paramètre est OBLIGATOIRE si `SOLUTION` est présent dans la querystring**

Seul `finish` et `start` sont obligatoires et ce uniquement si `SOLUTION`est dans la querystring, les autres paramètres ont des valeurs par défaut qui seront utilisées en cas d'absence de ces paramètres. Pour les modifier, il faut changer les valeurs de la structure `$DEFAULT_VALUES` présent au début du script, après la structure `$TILESETS_AVAILABLE`.

---

## Sujet
- Côté client, l’interface permet de sélectionner la géométrie du labyrinthe (longueur/largeur), en termes de nombre de cases, ainsi que la graine permettant d’initialiser le générateur aléatoire. 
    - Deux labyrinthes générés avec la même graine doivent être identiques
- Une fois le labyrinthe conçu, il pourra être exporté sous la forme d'images au format png ou jpeg. 
    - Plusieurs tailles d’images devront pouvoir être exportées pour un même labyrinthe conçu sur le site 
    - Ainsi, suivant la taille de l’image exportée, la taille des cases (en pixels) du labyrinthe pourra être plus ou moins grande. 
- A l’occasion de l’exportation du labyrinthe, chaque case devra être décorée à l’aide d’une banque de tuiles à sélectionner dans un menu déroulant. 
    - Une banque de tuiles est fournie avec le sujet, il vous est possible d’en produire d’autres
 =>Chaque banque de tuiles devra être composée d’une unique image png comportant 5 tuiles carrées organisées de la manière suivante :

    ![tiles](https://github.com/kermitausorusRex/MazeMaker/blob/main/ressources/2D_Maze_Tiles_White.png)
  
				
    - Ordre des tuiles dans les fichiers fournis. Dans les fichiers, les tuiles doivent en réalité être collées les unes aux autres. Source originale : [2D Maze Tiles sur Itch.io](https://mapsandapps.itch.io/2d-maze-tiles)

- La page Web de résultat pourra être appelée avec des arguments en chaîne de requête (à définir), de façon à générer le labyrinthe directement sous la forme d’une image associée à son entête HTTP. L’URL correspondante pourra donc être utilisée dans une balise `<img>`.

- [BONUS] L’application permettra également de définir deux cases du labyrinthe par leurs coordonnées (entrée/sortie), et calculer le chemin permettant de relier ces deux cases, pour l’afficher sous la forme d’une nouvelle image à télécharger, dans laquelle les cases appartenant au chemin solution seront dessinées avec une couleur de fond rouge (il vous faudra alors utiliser un autre jeu de tuiles avec la couleur de fond appropriée). 
