<?php
    # : name : client.php
    # : authors : VABOIS Juliette & DUTHOIT Thomas
    # : function : GUI to generate perfect mazes
    # : usage : fill in the form then submit to get your perfect maze image


    include_once("libs/maLibForms.php");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>MazeMaker Client</title>
    <link rel="stylesheet" href="style/client.css">
</head>
<body>
    <div id="bg_lab"></div>

    <div id="main">
    <h1>MazeMaker Client</h1>
    <h2>Personnalisation de votre labyrinthe</h2>
    <?php
        mkForm("generator.php");

        echo("<h3>Paramètres de génération</h3>");
        mkLabel("lab_width", "Largeur du labyrinthe : ");
        mkInput("number", "width", "10" , ["id"=>"lab_width", "required"=>"required"]);

        br();

        mkLabel("lab_height", "Longueur du labyrinthe : ");
        mkInput("number", "height", "10" , ["id"=>"lab_height", "required"=>"required"]);

        br();
    
        mkLabel("lab_seed", "Graine du labyrinthe (0=aléatoire) : ");
        mkInput("number", "seed", "0" , ["id"=>"lab_seed", "required"=>"required"]);

        hr();

        echo("<h3>Paramètres d'image</h3>");
        mkLabel("img_width", "Largeur de l'image en px (0=automatique) : ");
        mkInput("number", "imgWidth", "0" , ["id"=>"img_width", "required"=>"required"]);
    
        br();

        mkLabel("imgFormat", "Format de l'image à génerer");
    ?>
        <select required name="imgFormat" id="imgFormat">
            <option value="png">PNG</option>
            <option value="jpg">JPG</option>
        </select>
    <?php
        hr();
        echo("<h3>Paramètres du jeu de tuiles</h3>");
        mkLabel("tileset", "Jeu de tuiles à utiliser");
    ?>
        <select required name="tileset" id="tileset">
            <option value="default">Défaut</option>
            <option value="pixel">Pixel</option>
            <option value="pacman">Pacman</option>
        </select>
    <?php

        hr();
        echo("<h3>Paramètres de résolution</h3>");

        mkRadioCb("checkbox", "SOLUTION", "SOLUTION", true);
        mkLabel("SOLUTION", "Affichage de la solution");

        br();

    ?>

    <p>Comment spécifier un indice:</p>
    <img src="ressources/aideIndices.png" alt="aide indices">

    <?php

        br();


        mkLabel("finish", "Indice d'entrée du labyrinthe : ");
        mkInput("number", "start", "0" , ["id"=>"start"]);

        br();

        mkLabel("start", "Indice de sortie du labyrinthe : ");
        mkInput("number", "finish", "0" , ["id"=>"finish"]);


        br();





        hr();

        mkRadioCb("checkbox", "DEBUG", "DEBUG", false);
        mkLabel("DEBUG", "Mode débuggage");

        hr();

        mkInput("submit", "", "Générer !");
        
        endForm();
    ?>

    <footer><p>Par DUTHOIT Thomas et VABOIS Juliette</p></footer>
</body>
</html>
