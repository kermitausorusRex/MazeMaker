<?php
    # : name : generator.php
    # : authors : VABOIS Juliette & DUTHOIT Thomas
    # : function : generate perfect mazes
    # : usage : request to get an image, arguments to use are specified in README.md
    # : principle : random path fusion (https://fr.wikipedia.org/wiki/Modélisation_mathématique_d%27un_labyrinthe)












            ###############################################################
            ######                                                 ########
            ######                  CONFIGURATION                  ########
            ######                                                 ########
            ###############################################################

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
        "dark" => [
            "ressources/dark.png",
            "ressources/dark_colored.png",
        ]
    ];


    // structure qui contient les valeurs par défaut des paramètres passés à l'aide de la querystring
    // si ces paramètres ne sont pas présents dans la querystring, alors la valeur présente dans la structure sera utilisée
    $DEFAULT_VALUES = [
        "width" => 10,
        "height" => 10,
        "seed" => 0,      // = random
        "imgWidth" => 0,  // = auto
        "imgFormat" => "png",
        "tileset" => "default",
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
            ######                  VÉRIFICATIONS GET                  ########
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

    if (!isset($_GET["width"]) || intval($_GET["width"]) <1) {
        $_GET["width"] = $DEFAULT_VALUES["width"];
        // die("Erreur: Largeur inconnue");  // vérification de la présence de la largeur dans la querystring
    }
    if (!isset($_GET["height"]) || intval($_GET["height"]) <1) {
        $_GET["height"] = $DEFAULT_VALUES["height"];
        // die("Erreur: Longueur inconnue");  // vérification de la présence de la hauteur dans la querystring
    }

    if(intval($_GET["height"])*intval($_GET["width"])<=1){
        $_GET["height"] = $DEFAULT_VALUES["height"];
        $_GET["width"] = $DEFAULT_VALUES["width"];
    }

    if (!isset($_GET["seed"])) {
        $_GET["seed"] = $DEFAULT_VALUES["seed"];
        // die("Erreur: Graine inconnue");  // vérification de la présence de la graine dans la querystring
    }

    if (!isset($_GET["imgWidth"])) {
        $_GET["imgWidth"] = $DEFAULT_VALUES["imgWidth"];
        // die("Erreur: Taile de l'image inconnue");  // vérification de la présence de la largeur d'image dans la querystring
    }

    if (!isset($_GET["imgFormat"])) {
        $_GET["imgFormat"] = $DEFAULT_VALUES["imgFormat"];
        // die("Erreur: Format inconnu");  // vérification de la présence du format de l'image dans la querystring
    }
    $exportAsPng = ($_GET["imgFormat"] == "png");

    if (!isset($_GET["tileset"])) {
        $_GET["tileset"] = $DEFAULT_VALUES["tileset"];
        // die("Erreur: Tileset inconnu");  // vérification de la présence du tileset à utiliser dans la querystring
    }

    $tileSetName = $_GET["tileset"];  // on récupère le nom du tileSet
    $flag = false;                    // flag utilisé pour la vérification de la compatibilité du tileSet
    foreach ($TILESETS_AVAILABLE as $ts => $_) {
        if ($ts == $tileSetName) {
            $flag = true;  // tileSet trouvé
            break;
        }
    }
    if (!$flag) {
        die("Erreur: Tileset incorrect");  // incompatibilité du tileset (pas dans la banque de tilesets)
    }

    if($SOLUTION && !isset($_GET["start"])) {
        die("Erreur: entrée inconnue");  // vérification de la présence de l'entrée du labyrinthe dans la querystring
    }

    if ($SOLUTION && ($_GET["start"] < 0 || $_GET["start"] > $_GET["width"]*$_GET["height"]-1)) {
        die("Erreur: indice d'entrée incorrect");
    }

    if($SOLUTION && !isset($_GET["finish"])) {
        die("Erreur: sortie inconnue");  // vérification de la présence de la sortie du labyrinthe dans la querystring
    }

    if ($SOLUTION && ($_GET["finish"] < 0 || $_GET["finish"] > $_GET["width"]*$_GET["height"]-1)) {
        die("Erreur: indice de sortie incorrect");
    }























            ################################################################
            ######                                                  ########
            ######                  INITIALISATION                  ########
            ######                                                  ########
            ################################################################
    

    set_time_limit(300);  // on met la limite de timeout à 5 minutes 
                          // au cas où l'on rencontre une génération d'un gros labyrinthe
                          // afin d'éviter d'être timeout et donc d'obtenir une erreur
    

    dbg_echo('<h1 style="width:100%;text-align:center">generator.php - mode DEBUG</h1>');
    
    dbg_echo("<details><summary>");  // affichage des données reçues avec la querystring
    dbg_echo('<h2 style="display:inline"><pre style="display:inline">$_GET</pre></h2>');
    dbg_echo("</summary>");
    dbg_echo_tab($_GET);
    dbg_echo("</details><hr>");

    
    $infosLab = array(                                 // Structure contenant toutes les informations importantes sur le labyrinthe à générer
        'height'=> $_GET["height"],                    // largeur du labyrinthe
        'width'=> $_GET["width"],                      // hauteur du labyrinthe
        'nbTiles'=> $_GET["width"] * $_GET["height"],  // nombre de tuiles
        'nbOpenWalls' => 0,                            // initialement, tous les murs sont fermés
                                                       // le labyrinthe est complet quand cette valeur vaut width*height -1
        'nbOpenWallsTarget' => $_GET["width"] * $_GET["height"] - 1,
        'seed' => $_GET["seed"]                        // seed du labyrinthe
    );

    if ($SOLUTION) {
        $infosLab["start"] = $_GET["start"];
        $infosLab["finish"] = $_GET["finish"];
    }

    if ($DEBUG && $infosLab["seed"] == 0) $infosLab["seed"] = rand();  // pour le mode débug, on a une seed aléatoire mais elle est enregistrée pour être transmise dans la querystring de l'affichage final pour que les données restent cohérentes

    // affichage de la structure infosLab si on est en mode debug sous la forme d'une section repliable avec la balise <details>
    dbg_echo("<details><summary>");
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
    dbg_echo("<details><summary>");
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
        if($i%$infosLab["width"]==0) {  // Test ...
            $labyrinthe[$i]["murO"]=0;  // ... et suppression pour la bordure Ouest
        }
        if($i%$infosLab["width"]==$infosLab["width"]-1) { // Test ...
            $labyrinthe[$i]["murE"]=0;                    // ... et suppression pour la bordure Est
        }
    }

    // affichage du labyrinthe après son remplissage avec des objTiles, pour vérifier si il est correctement rempli
    // et si les valeurs des tiles sont correctes
    dbg_echo("<details><summary>");
    dbg_echo('<h2 style="display:inline"><pre style="display:inline;">$labyrinthe</pre> (après remplissage)</h2>');
    dbg_echo("</summary>");
    dbg_echo_tab($labyrinthe);
    dbg_echo("</details><hr>");
    
    


















    
    


            ############################################################
            ######                                              ########
            ######                  GÉNÉRATION                  ########
            ######                                              ########
            ############################################################

    // FUSION ALEATOIRE

    // On a un labyrinthe de dimensions l*L
    // Chaque cas a un identifiant unique, ces identifiants sont stockés dans un tableau
    // On accède à deux id au hasard et on ouvre leur mur s'il n'est pas déjà ouvert
    // ça forme un couloir qui prend le même id que l'un des ids d'origine
    // on répète le processus jusqu'à ce que tous les id aient au moins un mur ouvert
    // quand toutes les cases ont le même id on ouvre des murs exterieurs pour l'entrée et la sortie


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
        global $labyrinthe;  // | => On récupère les variables qui sont en dehors du scope de la fonction
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

        // détermination du mur à casser en fonction de l'indice des deux tiles
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
    dbg_echo("<details><summary>");
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

    dbg_echo("<details><summary>");
    dbg_echo('<h2 style="display:inline"><pre style="display:inline;">$labyrinthe</pre> (après génération)</h2>');
    dbg_echo("</summary>");
    dbg_echo_tab($labyrinthe);
    dbg_echo("</details><hr>");

    // On remet les bordures du labyrinthe
    for ($i=0; $i<$infosLab["nbTiles"]; $i++) {

        if ($i < $infosLab["width"]) {  // Test ...
            $labyrinthe[$i]["murN"]=1;  // ... et ajout de la bordure Nord
        }
        if($i > ($infosLab["nbTiles"]-1)-$infosLab["width"]) {  // Test ...
            $labyrinthe[$i]["murS"]=1;                          // ... et ajout de la bordure Sud
        }
        if($i%$infosLab["width"]==0){   // Test ...
            $labyrinthe[$i]["murO"]=1;  // ... et ajoute de la bordure Ouest
        }
        if($i%$infosLab["width"]==$infosLab["width"]-1){  // Test ...
            $labyrinthe[$i]["murE"]=1;                    // ... et ajout de la bordure Est
        }
    }


    dbg_echo("<details><summary>");
    dbg_echo('<h2 style="display:inline"><pre style="display:inline;">$labyrinthe</pre> (après ajout des bordures)</h2>');
    dbg_echo("</summary>");
    dbg_echo_tab($labyrinthe);
    dbg_echo("</details><hr>");
        
    































            #################################################################
            ######                                                   ########
            ######                RÉSOLUTION AVEC A*                 ########
            ######                                                   ########
            #################################################################

            /*
            Le but de l'algorithme A* est d'attribuer des potentiels aux nodes 
            tout en leur appliquant le même raisonnement que pour l'algorithme 
            de Dijkstra pour avoir une "zone d'exploration" moins importante, 
            et donc trouver la solution plus rapidement.
            */

            


    function AStar($start, $finish) {
        /**
         * Fonction qui permet d'analyser le labyrinthe pour trouver un chemin entre la case d'indice `$start`et la case d'indice `$finish`
         * Elle utilise l'algorithme A*
         * Elle finie par renvoyer une structure qui contient tous les éléments nécessaires à la reconstruction du chemin pour "sortir" du labyrinthe
         */
        global $labyrinthe;
        global $infosLab;
        $gMap = mapDistancesReelles($start);

        dbg_echo("<h3> Entree dans Astar avec start=$start et finish=$finish </h3>");

        $listOuverte=array($start); // contient les noeuds à évaluer
        $listeFermee=array();       // contient les noeuds déjà évalués
        $noeudActuel;
        $noeudSuivant;
        $parents=array();  // indice du parent de chaque noeud pour pouvoir remonter la solution du labyrinthe
        for ($i=0;$i<$infosLab["width"]*$infosLab["height"];$i++)  // initialisation des parents
            $parents[]=-1;  // tous les noeuds n'ont pas de parents au début de la résolution

        while(!empty($listOuverte)){

            $minIdx = 0;                                                  // | => on récupère le noeud (l'indice de case) avec le plus petit f (f=distance réelle + heuristique)
            $minVal = heuristique($minIdx, $finish) + $gMap[$minIdx];     // |
            for($i=1;$i<sizeof($listOuverte);$i++){                       // |
                $h = heuristique($listOuverte[$i], $finish);              // |
                $f = $h + $gMap[$listOuverte[$i]];                        // |
                if($f<$minVal){                                           // |
                    $minIdx=$i;                                           // |
                    $minVal=$f;                                           // |
                }                                                         // |
            }                                                             // |
            $noeudActuel = $listOuverte[$minIdx];                         // |

            array_splice($listOuverte, $minIdx, 1);  // on retire le noeud actuel de la liste ouverte

            if($noeudActuel==$finish){  // on est arrivé sur le noeud de destination
                $objSolution =array(  // structure contenant toutes les données necéssaire pour remonter la solution du labyrinthe de la fin jusqu'au début
                    "start" => $start,
                    "finish" => $finish,
                    "parents" => $parents,
                );
                return $objSolution;  // on renvoie cette structure pour résoudre le labyrinthe
            }

            foreach(getVoisins($noeudActuel) as $noeudSuivant){

                if (in_array($noeudSuivant, $listOuverte)) continue;      // cas où l'on ne doit pas prendre en compte le noeud suivant
                else if(in_array($noeudSuivant, $listeFermee)) continue;  // idem
                else {
                    $listOuverte[]=$noeudSuivant;  // il n'a jamais été traité, on l'ajoute dans la liste ouverte
                }
                $parents[$noeudSuivant] = $noeudActuel;  // et on spécifie son parent
            }

            $listeFermee[]=$noeudActuel;  // on ajoute le noeud actuel à la liste fermée et on le marque donc comme entièrement exploré
        }
        if ($noeudActuel != $finish) {
            dbg_echo("Erreur de résolution A*");  // problème de résolution (ne devrait jamais arriver car labyrinthe parfait)
        }
        

    }
    
    function getVoisins($idx){
        /**
         * Renvoie les indices des tiles avec lesquelles la case d'indice `$idx` a une connection (=pas de mur entre les deux)
         */
        global $infosLab;  // récupération des variables
        global $labyrinthe;
        $voisins=[];
        $x = $idx%$infosLab["width"];
        $y = floor($idx/$infosLab["width"]);
        $tile = $labyrinthe[$idx];

        if (!$tile['murN'] && $y > 0) $voisins[] = $idx-$infosLab["width"];
        if (!$tile['murS'] && $y < $infosLab['height']-1) $voisins[] = $idx+$infosLab["width"];
        if (!$tile['murE'] && $x < $infosLab['width']-1) $voisins[] = $idx+1;
        if (!$tile['murO'] && $x > 0) $voisins[] = $idx-1;

        return $voisins;
    }

    function heuristique($idx, $finish_idx) {
        /**
         * Fonction heuristique qui renvoie une estimation de la distance entre une case et la case de sortie en utilisant la formule de la distance Manhattan
         */
        global $infosLab;  // on récupère les variables 
        $dx = abs($idx%$infosLab["width"] - $finish_idx%$infosLab["width"]);  // idx%width permet de récupérer une composante X dans le labyrinthe
        $dy = abs(floor($idx/$infosLab["width"]) - floor($finish_idx/$infosLab["width"]));  // floor(idx/width) permet de récupérer la composante Y d'un indice
        return $dx + $dy;
    }
    
    function mapDistancesReelles($start) {
        /**
         * Renvoie une liste associative sous la forme:
         *      indice_de_case => distance_réelle_par_rapport_au_depart
         * pour chaque indice de case du labyrinthe
         */
        global $labyrinthe;  // récupération des variables qui sont en dehors du scope de la fonction
        global $infosLab;

        // distance réelle entre deux points qui sera le paramètre g de notre fonction A*
        $cpt=0;  // nombre de cases déjà modifiées
        $val=0;  // valeur de distance actuelle
        $map=array();  // tableau qui contiendra les distances réelles de chaque case
        for ($i=0; $i<$infosLab["width"]*$infosLab["height"]; $i++) $map[]=-1;  // -1 est la valeur à remplacer

        $tmp=array();  // tableau temporaire
        $aChanger=array($start);  // liste des indices où l'on doit changer la valeur


        while($cpt!=$infosLab["width"]*$infosLab["height"]-1){  // tant que le bon nombre de case n'a pas été changé
            $tmp=array();  // on vide le tableau temporaire
            for($i=0;$i<sizeof($aChanger);$i++){           // pour chaque case à changer:
                $map[$aChanger[$i]]=$val;                  // - on lui assigne la bonne valeur de distance
                foreach(getVoisins($aChanger[$i]) as $v){  // - on ajoute ses voisins dans le tableau temporaire
                    // si on à -1 dans la map, alors on l'ajoute au tableau temporaire
                    if($map[$v]==-1) $tmp[]=$v;
                }
                $cpt++;  // on a fini le traitement sur une case, sa valeur est correcte, on augmente le compteur
            }
            $aChanger=$tmp;  // on assigne les nouveaux indices pour lesquels changer la valeur de distance
            $val++;  // on augmente la valeur de distance d'une unité
        }
        return $map;  // on renvoie la map des distances
    }

    function reconstruireSolution($objSolution) {
        /**
         * $objSolution est la structure renvoyée par l'appel à AStar
         * Renvoie un tableau avec les indices des tiles par lesquelles passe la solution
         */
        $solution=array();
        $solution[] = $objSolution["start"];
        $noeudActuel = $objSolution["finish"];
        while ($noeudActuel !=  $objSolution["start"]) {
            $solution[] = $noeudActuel;  // on sauvegarde le noeud actuel
            $noeudActuel = $objSolution["parents"][$noeudActuel];  // on remonte de parent en parent
        }
        return $solution;
    }


    if($SOLUTION && $DEBUG){

        // Affichage des étapes de résolution en mode DEBUG
        dbg_echo("<details><summary>");
        dbg_echo('<h2 style="display:inline">Résolution du labyrinthe</h2>');
        dbg_echo("</summary>");
        $objSolution = AStar($infosLab["start"],$infosLab["finish"]);
        dbg_echo('<h3>$objSolution</h3>');
        dbg_echo_tab($objSolution);
        dbg_echo('<h3>$solution</h3>');
        $solution = reconstruireSolution($objSolution);
        dbg_echo_tab($solution);
        dbg_echo("</details><hr>");

    }

























    

            #################################################################
            ######                                                   ########
            ######                  RENDU GRAPHIQUE                  ########
            ######                                                   ########
            #################################################################



    if (!$DEBUG) {

        if ($SOLUTION) {
            $solution = reconstruireSolution(AStar($infosLab["start"],$infosLab["finish"]));  // on résout le labyrinthe
        } else {
            $solution = [];  // aucune case n'est à colorier comme étant une case de solution
        }




        // ici, on n'est pas en mode débug, on génère donc l'image du labyrinthe pour la renvoyer
       
        if ($exportAsPng)  // Header pour indiquer que le contenu est uniquement une image 
            header('Content-Type: image/png');  // type MIME d'une image png     
        else
            header('Content-Type: image/jpeg');  // type MIME d'une image jpg     
        




        $tilesetNormal = imagecreatefrompng($TILESETS_AVAILABLE[$tileSetName][0]);   // on récupère le tileset à partir du chemin de la banque de tilesets ...
        $tilesetColored = imagecreatefrompng($TILESETS_AVAILABLE[$tileSetName][1]);  // ... idem ...
    
        $tileSize =(int) imagesy($tilesetNormal);  // On récupère la taille des tiles en regardant la hauteur du tileset car les tiles sont carrées

        $tilesNormal = array();  // Array qui servira à stocker toutes les tiles de manière indépendante
        $tilesColored = array();  // Array qui servira à stocker toutes les tiles de manière indépendante


        for ($i = 0; $i < 5; $i ++) {  // On parcourt le set de tiles 
            // tileset normal
            $tile = imagecrop($tilesetNormal, ['x' => $i*$tileSize, 'y' => 0, 'width' => (int)$tileSize, 'height' => (int)$tileSize]);  //... et on divise le set tiles en éléments de tailles égales 
            if ($tile !== FALSE) {
                $tilesNormal[] = $tile;
                imagedestroy($tile);
            }

            // Tileset coloré
            $tile = imagecrop($tilesetColored, ['x' => $i*$tileSize, 'y' => 0, 'width' => (int)$tileSize, 'height' => (int)$tileSize]);  //... et on divise le set tiles en éléments de tailles égales 
            if ($tile !== FALSE) {
                $tilesColored[] = $tile;
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
         * 2 murs = tile en position 1 ou 4 du tableau (différencier murs adjacents de murs opposés)
         * 3 murs = tile en position 2 du tableau
         * 0 murs (carrefour) = tile en position 0 du tableau
         * Comme les tiles ne sont pas forcément orientées dans le bon sens on peut être amenés à utiliser imagerotate de la libraire gd2
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
            if (in_array($i, $solution))
                $rotatedTile = imagerotate($tilesColored[$indiceTile], $angle, 0); //on tourne l'image de la tile normale selon l'angle approprié
            else
                $rotatedTile = imagerotate($tilesNormal[$indiceTile], $angle, 0); //on tourne l'image de la tile colorée selon l'angle approprié
        
            imagecopy($labImage, $rotatedTile, ($i%$infosLab["width"])*$tileSize, (int)($i/$infosLab["width"])*$tileSize, 0, 0, (int)$tileSize, (int)$tileSize);
            imagedestroy($rotatedTile);  // on libère la mémoire
        }


        $targetWidth = $_GET["imgWidth"];  // récupération dans la querystring
        if ($targetWidth != 0) {  // si la taille de l'image a été choisie par l'utilisateur plutôt que par le programme
            $labImage = imagescale($labImage, $targetWidth);  // alors on redimensionne l'image
        }

        if ($exportAsPng)  // on affiche le labyrinthe suivant le format spécifié
            imagepng($labImage);  // ici en png
        else
            imagejpeg($labImage);  // ici en jpg

        // on libère la mémoire à la fin du rendu
        for ($i=0; $i<5; $i++) {
            imagedestroy($tilesNormal[$i]);
            imagedestroy($tilesColored[$i]);
        }
        imagedestroy($tilesetNormal);
        imagedestroy($tilesetColored);
        imagedestroy($labImage);



        
        



    } else {

        // ici, on est en mode debug, on requête donc generator.php avec la même querystring mais sans l'attribut DEBUG

        // on envoie les données relatives à la solution ou non
        $solu = ($SOLUTION)?"&SOLUTION=SOLUTION" . "&start=" . $infosLab["start"] . "&finish=" . $infosLab["finish"] :"";

        dbg_echo("<h3>Résultat du labyrinthe :</h3>");

        // on requête notre script
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



