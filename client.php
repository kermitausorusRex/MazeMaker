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
        <h1>MazeMaker Client</h1>
        <hr>
        <h2>Personnalisation de votre labyrinthe</h2>
    <?php
        mkForm("generator.php");

        mkLabel("lab_width", "Largeur du labyrinthe : ");
        mkInput("number", "width", "10" , ["id"=>"lab_width", "required"=>"required"]);

        br();

        mkLabel("lab_height", "Longueur du labyrinthe : ");
        mkInput("number", "height", "10" , ["id"=>"lab_height", "required"=>"required"]);

        br();
    
        mkLabel("lab_seed", "Graine du labyrinthe (0=aléatoire) : ");
        mkInput("number", "seed", "0" , ["id"=>"lab_seed", "required"=>"required"]);

        br();

        mkRadioCb("checkbox", "DEBUG", "DEBUG", true);
        mkLabel("DEBUG", "Mode débuggage");

        br();

        mkInput("submit", "", "Générer !");
        
        endForm();
    ?>

    <footer><p>Par VABOIS Juliette et DUTHOIT Thomas</p></footer>
</body>
</html>