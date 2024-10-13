<?php
    # : name : generator.php
    # : authors : VABOIS Juliette & DUTHOIT Thomas
    # : function : generate perfect mazes
    # : usage : request to get an image, arguments to use are specified in README.md
    # : principle : random path fusion (https://fr.wikipedia.org/wiki/Modélisation_mathématique_d%27un_labyrinthe)
    //http://www.apache.org/licenses/LICENSE-2.0 












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

    if (isset($_GET["DEBUG"])) {  // récupération du mode DEBUG
        $DEBUG = true;
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














            ################################################################
            ######                                                  ########
            ######                  INITIALISATION                  ########
            ######                                                  ########
            ################################################################
    

    dbg_echo('<h1 style="width:100%;text-align:center">generator.php - mode DEBUG</h1>');

    
    $infosLab = array(                                 // Structure contenant toutes les informations importantes sur le labyrinthe à génrérer
        'height'=> $_GET["height"],                    // largeur du labyrinthe
        'width'=> $_GET["width"],                      // hauteur du labyrinthe
        'nbTiles'=> $_GET["width"] * $_GET["height"],  // nombre de tuiles
        'nbOpenWalls' => 0,                            // initialement, tous les murs sont fermés
                                                       // le labyrinthe est complet quand cette valeur vaut width*height -1
        'nbOpenWallsTarget' => $_GET["width"] * $_GET["height"] - 1,
        'seed' => $_GET["seed"]                        // seed du labyrinthe
    );

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

    dbg_echo("<details open><summary>");
    dbg_echo('<h2 style="display:inline"><pre style="display:inline;">$objTile</pre> (par défaut)</h2>');
    dbg_echo("</summary>");
    dbg_echo_tab($objTile);
    dbg_echo("</details><hr>");

    $labyrinthe = array();

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
                $flag = true;
                break;
            }
        }
        if (!$flag) return -1;  // Incompatible: toutes les tiles adjacentes ont la meme valeur

        do {
            $chx = $tabTileAdj[rand()%count($tabTileAdj)];         // Choix d'un indice aléatoire ...
        } while ($labyrinthe[$chx]["valeur"] == $tile["valeur"]);  // .. tant qu'il na pas une valeur différente

        return $chx;  // On renvoie l'indice d'une tile compatible adjacente aléatoire
    }

    dbg_echo("<details open><summary>");
    dbg_echo('<h2 style="display:inline">Génération :</h2>');
    dbg_echo("</summary>");
    dbg_echo("<ul>");
    
    while($infosLab["nbOpenWalls"] != $infosLab["nbOpenWallsTarget"]) {
        do {
            $indice1 = rand(0, $infosLab["nbTiles"]-1);                   // On récupère un indice aléatoire de tile ...
        } while (($indice2 = choisirTileAdjacenteAlea($indice1)) == -1);  // ... tant qu'on trouve ne trouve pas au moins une case compatible pour une fusion

        dbg_echo('<li style="list-style: none"><details open style="border-bottom: 1px solid lightgray; width:fit-content"><summary>');
        dbg_echo('<h3 style="display:inline">Choix de fusion n°' . $infosLab["nbOpenWalls"]+1 . '</h3>');
        dbg_echo("</summary>");
        dbg_echo_tab($labyrinthe[$indice1]);
        dbg_echo_tab($labyrinthe[$indice2]);
        dbg_echo('</details></li>');
        
        // TODO: supprimer la ligne (utilisée uniquement pour tester sans rester bloqué dans le while)
        $infosLab["nbOpenWalls"]++;
    }   

    dbg_echo("</ul></details><hr>");


?>



