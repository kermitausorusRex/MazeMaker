<?php
    # : name : generator.php
    # : authors : VABOIS Juliette & DUTHOIT Thomas
    # : function : generate perfect mazes
    # : usage : request to get an image, arguments to use are specified in README.md
    # : principle : random path fusion (https://fr.wikipedia.org/wiki/Modélisation_mathématique_d%27un_labyrinthe)
    //http://www.apache.org/licenses/LICENSE-2.0 












            ####################################################################
            ######                                                      ########
            ######                  BANQUE DE TILESETS                  ########
            ######                                                      ########
            ####################################################################

    // structure qui stock tous les jeux de tile disponnibles pour le générateur ainsi que
    // le chemin vers les tilesets associés.
    // il se compose toujours de la manière suivante afin de permettre des ajouts rapides:
    // "nomDuTileset" => [
    //      "chemin/vers/le/tileset/normal.png",
    //      "chemin/vers/le/tileset/coloré.png"],
    //
    $TILESETS_AVAILABLE = [
        "default" => [
            "ressources/2D_Maze_Tiles_White.png", 
            "ressources/2D_Maze_Tiles_Red.png"],

        "pixel" => [
            "ressources/pixel.png",
            "ressources/pixel_coloured.png"],
        "pacman" => [
            "ressources/pacman.png",
            "ressources/pacman_coloured.png"],
    ];












            #################################################################
            ######                                                   ########
            ######                  FONCTIONS DEBUG                  ########
            ######                                                   ########
            #################################################################


    function dbg_echo($val) {  // fonction utilisée pour l'affichage en mode DEBUG
        global $DEBUG;  // on récupère la variable $DEBUG qui est extérieure au scope de la fonction
        if (!$DEBUG) return;
        echo $val;
    }

    function dbg_echo_tab($tab) {  // fonction utilisée pour l'affichage de tableaux en mode DEBUG
        global $DEBUG;  // on récupère la variable $DEBUG qui est extérieure au scope de la fonction
        if (!$DEBUG) return;
        echo "<pre>";
        print_r($tab);
        echo "</pre>";
    }



            ###################################################################
            ######                                                     ########
            ######                  VERIFICATIONS GET                  ########
            ######                                                     ########
            ###################################################################


    $DEBUG = false;
    $SOLUTION = false;

    if (isset($_GET["DEBUG"])) {  // récupération du mode DEBUG
        $DEBUG = true;
    }

    if(isset($_GET["SOLUTION"])){
        $SOLUTION=true;
    }

    if (!isset($_GET["width"])) {
        die("Erreur: Largeur inconnue");  // vérification de la présence de la largeur dans la querystring
    }
    if (!isset($_GET["height"])) {
        die("Erreur: Longueur inconnue");  // vérification de la présence de la hauteur dans la querystring
    }

    if (!isset($_GET["seed"])) {
        die("Erreur: Graine inconnue");  // vérification de la présence de la graine dans la querystring
    }

    if (!isset($_GET["imgWidth"])) {
        die("Erreur: Taile de l'image inconnue");  // vérification de la présence de la largeur d'image dans la querystring
    }

    if (!isset($_GET["imgFormat"])) {
        die("Erreur: Format inconnu");  // vérification de la présence du format de l'image dans la querystring
    }
    $exportAsPng = ($_GET["imgFormat"] == "png");

    if (!isset($_GET["tileset"])) {
        die("Erreur: Tileset inconnu");  // vérification de la présence du tileset à utiliser dans la querystring
    }

    $tileSetName = $_GET["tileset"];  // on récupère le nom du tileSet
    $flag = false;                    // falg utilisé pour la vérification de la compatibilité du tileSet
    foreach ($TILESETS_AVAILABLE as $ts => $_) {
        if ($ts == $tileSetName) {
            $flag = true;  // tileSet trouvé
            break;
        }
    }
    if (!$flag) {
        die("Erreur: Tileset incorrect");  // incompatibilité du tileset (pas dans la banque de tilesets)
    }











            ################################################################
            ######                                                  ########
            ######                  INITIALISATION                  ########
            ######                                                  ########
            ################################################################
    

    set_time_limit(300);  // on mets la limite de timeout à 5 minutes 
                          // au cas où l'on rencontre une génération d'un gros labyrinthe
                          // afin d'éviter d'être timeout et donc d'obtenir une erreur
    

    dbg_echo('<h1 style="width:100%;text-align:center">generator.php - mode DEBUG</h1>');
    
    dbg_echo("<details><summary>");  // affichage des données recu avec la querystring
    dbg_echo('<h2 style="display:inline"><pre style="display:inline">$_GET</pre></h2>');
    dbg_echo("</summary>");
    dbg_echo_tab($_GET);
    dbg_echo("</details><hr>");

    
    $infosLab = array(                                 // Structure contenant toutes les informations importantes sur le labyrinthe à génrérer
        'height'=> $_GET["height"],                    // largeur du labyrinthe
        'width'=> $_GET["width"],                      // hauteur du labyrinthe
        'nbTiles'=> $_GET["width"] * $_GET["height"],  // nombre de tuiles
        'nbOpenWalls' => 0,                            // initialement, tous les murs sont fermés
                                                       // le labyrinthe est complet quand cette valeur vaut width*height -1
        'nbOpenWallsTarget' => $_GET["width"] * $_GET["height"] - 1,
        'seed' => $_GET["seed"]                        // seed du labyrinthe
    );

    // affichage de la structure infosLab si on est en mode debug sous la forme d'une section repliable avec la balise <details>
    dbg_echo("<details open><summary>");
    dbg_echo('<h2 style="display:inline"><pre style="display:inline">$infosLab</pre></h2>');
    dbg_echo("</summary>");
    dbg_echo_tab($infosLab);
    dbg_echo("</details><hr>");


    $objTile = array(      // structure utilisée pour chaque tile du labyrinthe
        'valeur'=> 0,      // valeur de la tile
        //'composante'=> 0,  // le numéro actuel de la case
        'murN'=> 1,        // mur Nord  (1: fermé, 0: ouvert) |=> Par défaut, tous les murs sont fermés
        'murE'=> 1,        // mur Est   (1: fermé, 0: ouvert) |
        'murS'=> 1,        // mur Sud   (1: fermé, 0: ouvert) |
        'murO'=> 1,        // mur Ouest (1: fermé, 0: ouvert) |
    );

    // affichage de la structure objTile par défaut
    dbg_echo("<details open><summary>");
    dbg_echo('<h2 style="display:inline"><pre style="display:inline;">$objTile</pre> (par défaut)</h2>');
    dbg_echo("</summary>");
    dbg_echo_tab($objTile);
    dbg_echo("</details><hr>");

    $labyrinthe = array();  // tableau qui contiendra l'entièreté des tiles de notre labyrinthe

    for ($i=0; $i<$infosLab["nbTiles"]; $i++) {
        $labyrinthe[]=$objTile;          // On clone notre structure
        $labyrinthe[$i]["valeur"] = $i;  // Et on lui assigne une valeur unique à chaque tile à la création

        // Pour la génération du labyrinthe, on désactive les "bordures" du labyrinthe 
        // pour faciliter les calculs

        if ($i < $infosLab["width"]) {  // Test ...
            $labyrinthe[$i]["murN"]=0;  // ... et suppression pour la bordure Nord
        }
        if($i > ($infosLab["nbTiles"]-1)-$infosLab["width"]) {  // Test ...
            $labyrinthe[$i]["murS"]=0;                          // ... et suppression pour la bordure Sud
        }
        if($i%$infosLab["width"]==0){  // Test ...
            $labyrinthe[$i]["murO"]=0;  // ... et suppression pour la bordure Ouest
        }
        if($i%$infosLab["width"]==$infosLab["width"]-1){  // Test ...
            $labyrinthe[$i]["murE"]=0;                    // ... et suppression pour la bordure Est
        }
    }

    // affichage du labyrinthe après son remplissage avec des objTiles, pour vérifier si il est correctement rempli
    // et si les valeurs des tiles sont correctes
    dbg_echo("<details open><summary>");
    dbg_echo('<h2 style="display:inline"><pre style="display:inline;">$labyrinthe</pre> (après remplissage)</h2>');
    dbg_echo("</summary>");
    dbg_echo_tab($labyrinthe);
    dbg_echo("</details><hr>");
    
    








    
    


            ############################################################
            ######                                              ########
            ######                  GENERATION                  ########
            ######                                              ########
            ############################################################

    // FUSION ALEATOIRE

    // On a un labyrinthe de dimensions l*L
    // Chaque cas a un identifiant unique, ces identifiants sont stockes dans un tableau
    // On accede a deux id au hasard et on ouvre leur mur s'il n'est pas deja ouvert
    // ca forme un couloir qui prend le meme id que l'un des ids d'origine
    // on repete le processus jusqu'a ce que tous les id aient au moins un mur ouvert
    // quand toutes les cases ont le meme id on ouvre des murs exterieurs pour l'entree et la sortie


    function choisirTileAdjacenteAlea($indice) {
        /**
         * renvoie l'indice d'une case adjacente à celle passée en paramètre avec 
         * un numero différent de la case actuelle et un mur fermé en commun
         * renvoie -1 si aucune case ne correspond aux attentes de la fonction
         */
        // tile a gauche = $tile-1 (murO)
        // tile a droite = $tile+1 (murE)
        // tile en haut  = $tile-width (murN)
        // tile en bas   = $tile+width (murS)
        global $labyrinthe;  // | => On récuupère les variables qui sont en dehors du scope de la fonction
        global $infosLab;    // |

        $tile = $labyrinthe[$indice];
        $tO = $indice-1;                   // tile Ouest
        $tE = $indice+1;                   // tile Est
        $tN = $indice-$infosLab["width"];  // tile Nord
        $tS = $indice+$infosLab["width"];  // tile Sud

        $tabTileAdj = array();                   // Contient tous les indices des cases potentielles adjacentes
        if ($tile["murN"]) $tabTileAdj[] = $tN;  // Test mur Nord
        if ($tile["murS"]) $tabTileAdj[] = $tS;  // Test mur Sud
        if ($tile["murO"]) $tabTileAdj[] = $tO;  // Test mur Ouest
        if ($tile["murE"]) $tabTileAdj[] = $tE;  // Test mur Est

        if (count($tabTileAdj) == 0) return -1;  // Incompatible: aucun mur fermé

        // Vérification pour savoir si au moins une des tiles adjacentes a une valeur différente à celle passée en paramètre
        $flag=false;
        for ($i=0; $i<count($tabTileAdj); $i++) {
            if ($labyrinthe[$tabTileAdj[$i]]["valeur"] != $tile["valeur"]) {  // test de valeurs différentes
                $flag = true;  // on a detecté une valeur différente, on met le flag a true ...
                break;         // ... et on sort de la boucle car ça ne sert plus à rien
            }
        }
        if (!$flag) return -1;  // Incompatible: toutes les tiles adjacentes ont la meme valeur

        do {
            $chx = $tabTileAdj[rand()%count($tabTileAdj)];         // Choix d'un indice aléatoire ...
        } while ($labyrinthe[$chx]["valeur"] == $tile["valeur"]);  // .. tant qu'il na pas une valeur différente

        return $chx;  // On renvoie l'indice d'une tile compatible adjacente aléatoire
    }

    function fusion($indice1, $indice2) {
        /**
         * Regarde quel mur supprimer entre les deux et mets a jour les valeurs
         * prends aussi en charge le changement des valeurs de toutes les tiles du labyrinthe
         */

        global $labyrinthe;  // récupération de $labyrinthe qui est en dehors de scope de la fonction

        // détérmination du mur à casser en fonction de l'indice des deux tiles
        if ($indice1 + 1 == $indice2) {
            // casser murE à indice1 et murO à indice2
            $labyrinthe[$indice1]["murE"] = 0;
            $labyrinthe[$indice2]["murO"] = 0;
        }
        else if($indice1 -1 == $indice2){
            // casser murO à indice1 et murE à indice2
            $labyrinthe[$indice1]["murO"] = 0;
            $labyrinthe[$indice2]["murE"] = 0;
        }
        else if ($indice1 > $indice2) {
            // casser murN à indice1 et murS à indice2
            $labyrinthe[$indice1]["murN"] = 0;
            $labyrinthe[$indice2]["murS"] = 0;
        }
        else{
            // casser murS à indice1 et murN à indice2
            $labyrinthe[$indice1]["murS"] = 0;
            $labyrinthe[$indice2]["murN"] = 0;
        }

        $valeurAVerif = $labyrinthe[$indice2]["valeur"];     // il faudra écraser les valeurs de toutes les tiles avec cette valeur (celle de tile2) ...
        $valeurAUtiliser = $labyrinthe[$indice1]["valeur"];  // ... et les remplacer par la valeur de tile1

        // MaJ des valeurs des tiles
        for ($i=0; $i<count($labyrinthe); $i++) {
            if ($labyrinthe[$i]["valeur"] == $valeurAVerif) $labyrinthe[$i]["valeur"] = $valeurAUtiliser;
        }
    }

    // Section de la génération du labyrinthe (random path fusion)
    dbg_echo("<details open><summary>");
    dbg_echo('<h2 style="display:inline">Génération</h2>');
    dbg_echo("</summary>");
    dbg_echo("<ul>");

    if ($infosLab["seed"] != 0)
        srand($infosLab["seed"]);  // On utilise la seed spécifiée dans la querystring (une seed qui vaut 0 équivaut à une seed aléatoire dans srand)
    //else 
       //srand(null);

    while($infosLab["nbOpenWalls"] != $infosLab["nbOpenWallsTarget"]) {
        do {
            $indice1 = rand(0, $infosLab["nbTiles"]-1);                   // On récupère un indice aléatoire de tile ...
        } while (($indice2 = choisirTileAdjacenteAlea($indice1)) == -1);  // ... tant qu'on trouve ne trouve pas au moins une case compatible pour une fusion

        // On affiche chaque choix de fusion
        dbg_echo('<li style="list-style: none"><details open style="border-bottom: 1px solid lightgray; width:fit-content"><summary>');
        dbg_echo('<h3 style="display:inline">Choix de fusion n°' . $infosLab["nbOpenWalls"]+1 . '</h3>');
        dbg_echo("</summary>");
        dbg_echo("indice 1: <strong>" . $indice1 . "</strong>");
        dbg_echo_tab($labyrinthe[$indice1]);
        dbg_echo("indice 2: <strong>" . $indice2 . "</strong>");
        dbg_echo_tab($labyrinthe[$indice2]);
        
        // On utilise la fonction de fusion entre deux tiles
        fusion($indice1, $indice2);

        dbg_echo('<h3 style="display:inline">Après fusion :</h3>');
        dbg_echo("<br>indice 1: <strong>" . $indice1 . "</strong>");
        dbg_echo_tab($labyrinthe[$indice1]);
        dbg_echo("indice 2: <strong>" . $indice2 . "</strong>");
        dbg_echo_tab($labyrinthe[$indice2]);
        dbg_echo('</details></li>');
        
        $infosLab["nbOpenWalls"]++;  // On a ouvert un mur entre deux tiles, on incrémente donc la valeur
    }   

    dbg_echo("</ul></details><hr>");

    dbg_echo("<details open><summary>");
    dbg_echo('<h2 style="display:inline"><pre style="display:inline;">$labyrinthe</pre> (après génération)</h2>');
    dbg_echo("</summary>");
    dbg_echo_tab($labyrinthe);
    dbg_echo("</details><hr>");

    // On remet les bordures du labyrinthe
    for ($i=0; $i<$infosLab["nbTiles"]; $i++) {

        if ($i < $infosLab["width"] && $i!=0) {  // Test ...
            $labyrinthe[$i]["murN"]=1;  // ... et ajout de la bordure Nord
        }
        if($i > ($infosLab["nbTiles"]-1)-$infosLab["width"] && $i!=($infosLab["nbTiles"]-1)) {  // Test ...
            $labyrinthe[$i]["murS"]=1;                          // ... et ajout de la bordure Sud
        }
        if($i%$infosLab["width"]==0){  // Test ...
            $labyrinthe[$i]["murO"]=1;  // ... et ajoute de la bordure Ouest
        }
        if($i%$infosLab["width"]==$infosLab["width"]-1){  // Test ...
            $labyrinthe[$i]["murE"]=1;                    // ... et ajout de la bordure Est
        }
    }


    dbg_echo("<details open><summary>");
    dbg_echo('<h2 style="display:inline"><pre style="display:inline;">$labyrinthe</pre> (après ajout des bordures)</h2>');
    dbg_echo("</summary>");
    dbg_echo_tab($labyrinthe);
    dbg_echo("</details><hr>");
        
    



            #################################################################
            ######                                                   ########
            ######                RESOLUTION AVEC A*                 ########
            ######                                                   ########
            #################################################################

            /*
            Le but de l'algorithme A* est d'attribuer des potentiels aux nodes 
            tout en leur appliquant le meme raisonnement que pour l'algorithme 
            de Dijkstra pour avoir une "zone d'exploration" moins importante, 
            et donc trouver la solution plus rapidement.
            */

            function getNeigh($node, $labyrinthe, $infosLab){
                $neigh=[];
                $x = $node[0];
                $y = $node[1];
                $ind = $y*$infosLab["width"]+$y;
                $tile = $labyrinthe[$ind];

                if (!$tile['murN'] && $y > 0) $neigh[] = [$x, $y-1];
                if (!$tile['murS'] && $y < $infosLab['height']-1) $neigh[] = [$x, $y+1];
                if (!$tile['murE'] && $x < $infosLab['width']-1) $neigh[] = [$x+1, $y];
                if (!$tile['murO'] && $x > 0) $neigh[] = [$x-1, $y];

                return $neigh;
            }

            /*
            function heuristic($pA, $pB){
                //fonction mathematique trouvee sur internet: distance de Manhattan
                $res = abs($pA[0]-$pb[0]) + abs($pA[1]-$pB[1]);
                return $res;
            }

            function pythagore($pA, $pB, $pC){
                $res = sqrt($)
            }
            */

            /*
            function AStar($labyrinthe, $nodeStart, $nodeFinish){
                
                Les parametres de la fonction sont:
                - le labyrinthe que l'on souhaite resoudre
                - la case depart et la case arrive (en haut a gauche et en bas a droite dans notre cas)
                - h = heuristique
                - f = cout total du node 

                f le cout total se calcule en faisant la somme de g la distance entre le node de depart
                et h l'heuristique
            }*/

            /*
            function reconstruct_path($parents, $current){
                $start = [0, 0]; // Point de départ
                $goal = [$infosLab['width']-1, $infosLab['height']-1]; // Point d'arrivée
                $path = AStar($labyrinthe, $start, $goal, $infosLab);
                dbg_echo_tab($path);
            }

            function AStar( $start, $finish) {
                global $labyrinthe;
                global $infosLab;

                dbg_echo("<h3> Entree dans Astar avec start=$start et finish=$finish </h3>");

                $openList=array($labyrinthe[$start]); //contient les noeuds a evaluer
                $closedList=array();//contient les noeuds deja evalues
                $currentNode;

                while(!empty($openList)){
                    $currentNode=$openList[];

                }

                

                
            }
                */
        
    if($SOLUTION && $DEBUG){
        AStar(0,$infosLab["width"]*$infosLab["height"]-1);
    }



            #################################################################
            ######                                                   ########
            ######                  RENDU GRAPHIQUE                  ########
            ######                                                   ########
            #################################################################



    if (!$DEBUG) {

        // ici, on n'est pas en mode débug, on génère donc l'image du labyrinthe pour la renvoyer
        $tilesetNormal = imagecreatefrompng($TILESETS_AVAILABLE[$tileSetName][0]);
        $tilesetColored = imagecreatefrompng($TILESETS_AVAILABLE[$tileSetName][1]);

        if ($exportAsPng)  // Header pour indiquer que le contenu est uniquement une image 
            header('Content-Type: image/png');  // type MIME d'une image png     
        else
            header('Content-Type: image/jpeg');  // type MIME d'une image jpg     
        

        $tileSetPath = $TILESETS_AVAILABLE[$tileSetName][0]; // on récupère le chemin vers la tileset dans la banque de tilesets ...
        $tileSet = imagecreatefrompng($tileSetPath);         // ... et on récupère la tileset à partir de ce chemin
        $tileSize =(int) imagesy($tileSet);  // On récupère la taille des tiles en regardant la hauteur du tileset car les tiles sont carrées

        $tiles = array();  // Array qui servira à stocker toutes les tiles de manière indépendante


        for ($i = 0; $i < 5; $i ++) {                                                                                            // On parcourt le set de tiles 
            $tile = imagecrop($tileSet, ['x' => $i*$tileSize, 'y' => 0, 'width' => (int)$tileSize, 'height' => (int)$tileSize]);  //... et on divise le set tiles en éléments de tailles égales 
            if ($tile !== FALSE) {
                $tiles[] = $tile;
                imagedestroy($tile);
            }
        }


        $labImage = imagecreate($tileSize*$infosLab["width"],  $tileSize*$infosLab["height"]);  // on alloue en mémoire de la place pour le rendu de notre labyrinthe
        $couleurDeFond = imagecolorallocate($labImage, 255, 0, 0);

        /**
         * parcours de chaque tiles du labyrinthe
         * selon le nombre de murs on attribue une tile
         * par exemple:
         * 1 mur = tile en position 3 du tableau
         * 2 murs = tile en position 1 ou 4 du tableau (differencier murs adjacents de murs opposés)
         * 3 murs = tile en position 2 du tableau
         * 0 murs (carrefour) = tile en position 0 du tableau
         * Comme les tiles ne sont pas forcement orientees dans le bon sens 
         */
        $dimensionsLab = $infosLab["width"]*$infosLab["height"];
        for($i=0;$i<$dimensionsLab;$i++){
            $murN = $labyrinthe[$i]["murN"];
            $murE = $labyrinthe[$i]["murE"];
            $murS = $labyrinthe[$i]["murS"];
            $murO = $labyrinthe[$i]["murO"];

            $nbMursTile = $murN + $murE + $murS + $murO;

            switch($nbMursTile){
                case 0: //carrefour
                    $indiceTile = 0;
                break;

                case 1:
                    if($murN){
                        $indiceTile = 3;
                        $angle = 90;
                    }else if($murE){
                        $indiceTile = 3;
                        $angle = 0;
                    }else if($murS) {
                        $indiceTile = 3;
                        $angle = 270;
                    }else if($murO) {
                        $indiceTile = 3;
                        $angle = 180;
                    } 
                break; 
                
                case 2:
                    if($murN && $murE){
                        $indiceTile = 1;
                        $angle = 0;
                    }else if($murE && $murS){
                        $indiceTile = 1;
                        $angle = 270;
                    }else if($murS && $murO){
                        $indiceTile = 1;
                        $angle = 180;
                    }else if($murO && $murN){
                        $indiceTile = 1;
                        $angle = 90;
                    }else if($murN && $murS){
                        $indiceTile = 4;
                        $angle = 270;
                    }else{
                        $indiceTile = 4;
                        $angle = 0;
                    }
                break;

                case 3:
                    $indiceTile = 2;
                    if($murO && $murN && $murE){
                        $angle = 0;
                    }else if($murN && $murE && $murS){
                        $angle = 270;
                    }else if($murE && $murS && $murO){
                        $angle = 180;
                    }else{
                        $angle = 90;
                    }
                break;
            }
            $rotatedTile = imagerotate($tiles[$indiceTile], $angle, 0); //on tourne l'image de la tile selon l'angle approprie
            imagecopy($labImage, $rotatedTile, ($i%$infosLab["width"])*$tileSize, (int)($i/$infosLab["width"])*$tileSize, 0, 0, (int)$tileSize, (int)$tileSize);
            imagedestroy($rotatedTile);  // on libère la mémoire
        }


        if ($SOLUTION) {
            //AStar($labyrinthe, $start, $goal, $infosLab);
        
    
        }


        $targetWidth = $_GET["imgWidth"];  // récupération dans la querystring
        if ($targetWidth != 0) {  // si la taille de l'image a été choisie par l'utilisateur plutot que par le programme
            $labImage = imagescale($labImage, $targetWidth);  // alors on redimensionne l'image
        }

        if ($exportAsPng)  // on affiche le labyrinthe suivant le format spécifié
            imagepng($labImage);  // ici en png
        else
            imagejpeg($labImage);  // ici en jpg

        // on libère la mémoire à la fin du rendu
        for ($i=0; $i<$dimensionsLab; $i++) {
            imagedestroy($tiles[$i]);
        }
        imagedestroy($tileSet);
        imagedestroy($labImage);



        
        



    } else {

        // ici, on est en mode debug, on requete donc generator.php avec la même querystring mais sans l'attribut DEBUG
        $solu = ($SOLUTION)?"&SOLUTION=SOLUTION":"";

        dbg_echo("<h3>Résultat du labyrinthe :</h3>");
        dbg_echo('<img src="generator.php?width=' . $infosLab["width"] 
                                    . '&height=' . $infosLab["height"] 
                                    . '&seed=' . $infosLab["seed"] 
                                    . '&tileset=' . $tileSetName
                                    . '&imgWidth=' . $_GET["imgWidth"]
                                    . '&imgFormat=' . $_GET["imgFormat"]
                                    . $solu
                                    . '">');
    }
  
       

?>



