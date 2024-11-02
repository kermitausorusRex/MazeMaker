<?php


/*
Ce fichier définit diverses fonctions permettant de faciliter la production de mises en formes complexes : 
tableaux, formulaires, ...
*/
// Exemple d'appel :  mkLigneEntete($data,array('pseudo', 'couleur', 'connecte'));
function mkLigneEntete($tabAsso,$listeChamps=false)
{
	// Fonction appelée dans mkTable, produit une ligne d'entête
	// contenant les noms des champs à afficher dans mkTable
	// Les champs à afficher sont définis à partir de la liste listeChamps 
	// si elle est fournie ou du tableau tabAsso

	if (!$listeChamps)	// listeChamps est faux  : on utilise le not : '!'
	{
		// tabAsso est un tableau associatif dont on affiche TOUTES LES CLES
		echo "\t<tr>\n";
		foreach ($tabAsso as $cle => $val)	
		{
			echo "\t\t<th>$cle</th>\n";
		}
		echo "\t</tr>\n";
	}
	else		// Les noms des champs sont dans $listeChamps 	
	{
		echo "\t<tr>\n";
		foreach ($listeChamps as $nomChamp)	
		{
			echo "\t\t<th>$nomChamp</th>\n";
		}
		echo "\t</tr>\n";
	}
}

function mkLigne($tabAsso,$listeChamps=false)
{
	// Fonction appelée dans mkTable, produit une ligne 	
	// contenant les valeurs des champs à afficher dans mkTable
	// Les champs à afficher sont définis à partir de la liste listeChamps 
	// si elle est fournie ou du tableau tabAsso

	if (!$listeChamps)	// listeChamps est faux  : on utilise le not : '!'
	{
		// tabAsso est un tableau associatif
		echo "\t<tr>\n";
		foreach ($tabAsso as $cle => $val)	
		{
			echo "\t\t<td>$val</td>\n";
		}
		echo "\t</tr>\n";
	}
	else	// les champs à afficher sont dans $listeChamps
	{
		echo "\t<tr>\n";
		foreach ($listeChamps as $nomChamp)	
		{
			echo "\t\t<td>$tabAsso[$nomChamp]</td>\n";
		}
		echo "\t</tr>\n";
	}
}

// Exemple d'appel :  mkTable($users,array('pseudo', 'couleur', 'connecte'));	
function mkTable($tabData,$listeChamps=false)
{

	// Attention : le tableau peut etre vide 
	// On produit un code ROBUSTE, donc on teste la taille du tableau
	if (count($tabData) == 0) return;

	echo "<table border=\"1\">\n";
	// afficher une ligne d'entete avec le nom des champs
	mkLigneEntete($tabData[0],$listeChamps);

	//tabData est un tableau indicé par des entier
	foreach ($tabData as $data)	
	{
		// afficher une ligne de données avec les valeurs, à chaque itération
		mkLigne($data,$listeChamps);
	}
	echo "</table>\n";

	// Produit un tableau affichant les données passées en paramètre
	// Si listeChamps est vide, on affiche toutes les données de $tabData
	// S'il est défini, on affiche uniquement les champs listés dans ce tableau, 
	// dans l'ordre du tableau
	
}

// Produit un menu déroulant portant l'attribut name = $nomChampSelect

// Produit les options d'un menu déroulant à partir des données passées en premier paramètre
// $champValue est le nom des cases contenant la valeur à envoyer au serveur
// $champLabel est le nom des cases contenant les labels à afficher dans les options
// $selected contient l'identifiant de l'option à sélectionner par défaut
// si $champLabel2 est défini, il indique le nom d'une autre case du tableau 
// servant à produire les labels des options

// exemple d'appel : 
// $users = listerUtilisateurs("both");
// mkSelect("idUser",$users,"id","pseudo");
// TESTER AVEC mkSelect("idUser",$users,"id","pseudo",2,"couleur");

// V2 : TNE : utilisation de optgroup 
/*
<optgroup label="Swedish Cars">
   <option value="volvo">Volvo</option>
   <option value="saab">Saab</option>
</optgroup>

Exemple : $champCategorie vaudra admin si on veut distinguer les admin des autres 
$tagsLabelsCategories = array("0"=>"Utilisateurs standards", "1"=>"Administrateurs")
admin vaut 0 => label "Utilisateurs standards"
admin vaut 1 => label "Administrateurs"

1) vérifier la présence de ce paramètre
2) trier suivant ce paramètre
3) produire le menu déroulant en ajoutant les balises optgroup 

*/
$critereTriSelect = 0; 

function triSelect($data1,$data2) {
	global $critereTriSelect;
	echo "criterr :$critereTriSelect";
	// renvoyer data1[champ] - data2[champ]
	return $data1[$critereTriSelect] - $data2[$critereTriSelect];

}

function mkSelect($nomChampSelect, $tabData,$champValue, $champLabel,$selected=false,$champLabel2=false,$champCategorie=false,$tagsLabelsCategories=false)
{
	global $critereTriSelect;

	$multiple=""; 
	if (preg_match('/.*\[\]$/',$nomChampSelect)) $multiple =" multiple =\"multiple\" ";

	echo "<select $multiple name=\"$nomChampSelect\">\n";
	
	// trier suivant le champ catégorie 
	// => à faire en SQL ?? trop tard 
	// => plus tard en js ?? difficile en LE1, facile en LE2 
	
	// ICI : en php 
	// si un paramètre est fourni 
	if ($champCategorie) {
		$critereTriSelect = $champCategorie; 
		uasort($tabData, 'triSelect');
		$lastChampCat = ""; 
		$hasStarted = false;
		echo $tagsLabelsCategories[0]; 
	}
	
	foreach ($tabData as $data)
	{
		// produire une éventuelle balise optgroup
		if ($champCategorie) {
			// détecter un changement de valeur pour le champCategorie
			// si sa valeur change, on ajoute un nouvel optgroup
			if ($data[$champCategorie] != $lastChampCat) {
				if ($hasStarted) {
					echo "</optgroup>";
				} 
				$hasStarted = true; 	
				$lastChampCat = $data[$champCategorie];
				echo '<optgroup label="' . $tagsLabelsCategories[$lastChampCat] . '">'; 
			}
		}
		
		
		// produire la balise option 
	
		$sel = "";	// par défaut, aucune option n'est préselectionnée 
		// MAIS SI le champ selected est fourni
		// on teste s'il est égal à l'identifiant de l'élément en cours d'affichage
		// cet identifiant est celui qui est affiché dans le champ value des options
		// i.e. $data[$champValue]
		if ( ($selected) && ($selected == $data[$champValue]) )
			$sel = "selected=\"selected\"";

		echo "<option $sel value=\"$data[$champValue]\">\n";
		echo  $data[$champLabel] . "\n";
		if ($champLabel2) 	// SI on demande d'afficher un second label
			echo  " ($data[$champLabel2])\n";
		echo "</option>\n";
	}
	
	if ($champCategorie) {
		echo "</optgroup>";
	}
	echo "</select>\n";
}
function mkForm($action="",$method="get")
{
	// Produit une balise de formulaire NB : penser à la balise fermante !!
	echo "<form action=\"$action\" method=\"$method\" >\n";
}
function endForm()
{
	// produit la balise fermante
	echo "</form>\n";
}

/*
Appels possibles : 
mkInput("text","pseudo","",array("id" => "inputPseudo",
													"label" => "Pseudo :",
													"positionLabel" => "avant")); 
													
ou 
mkInput("text","pseudo","",'id="inputPasse"'); 
*/
function mkInput($type,$name,$value="",$misc=false)
{
	$attrs = "";
	$lblAfter = ""; 
	$lblBefore = "";  
	
	if ($misc) {
		if (is_array($misc)) {
			
			if (isset($misc["id"]))
				$attrs = 'id="' . $misc["id"] . '"'; 
				
			if (isset($misc["required"])) $attrs = $attrs . " required ";
		} else {
			$attrs = $misc;
		}
	}
	
	// Produit un champ formulaire
	echo $lblBefore; 
	echo "<input $attrs type=\"$type\" name=\"$name\" value=\"$value\"/>\n";
	echo $lblAfter; 
}



function mkRadioCb($type,$name,$value,$checked=false, $id=false)
{
	// Produit un champ formulaire de type radio ou checkbox
	// Et sélectionne cet élément si le quatrième argument est vrai
	$selectionne = "";	
	if ($checked) 
		$selectionne = "checked=\"checked\"";
	$idradiocb = "";
	if ($id)
		$idradiocb = "id=\"$id\"";
	echo "<input type=\"$type\" name=\"$name\" value=\"$value\"  $selectionne $idradiocb/>\n";
}

function mkLien($url,$label, $qs="",$attrs="")
{
	echo "<a $attrs href=\"$url?$qs\">$label</a>\n";
}

function mkLiens($tabData,$champLabel, $champCible, $urlBase=false, $nomCible="")
{
	// produit une liste de liens (plus facile à styliser)
	// A partir de données fournies dans un tableau associatif	
	// Chaque lien pointe vers une url définie par le champ $champCible
	
	// SI urlBase n'est pas false, on utilise  l'url de base 
	// (avec son point d'interrogation) à laquelle on ajoute le champ cible 
	// dans la chaîne de requête, associé au paramètre $nomCible, après un '&' 

	// Exemples d'appels : 
	// mkLiens($conversations,"id","theme");
	// produira <a href="1">Multimédia</a> ...

	// mkLiens($conversations,"theme","id","index.php?view=chat","idConv");
	// produira <a href="index.php?view=chat&idConv=1">Multimédia</a> ...

	// parcourir les données de tabData 
	foreach($tabData as $data) {
		// on parcourt uniquement les valeurs
		// a chaque itération, les valeurs sont dans 
		// le tableau $data
		echo '<a href="';
		echo $urlBase . "&" . $nomCible . "=" ;
		echo $data[$champCible];
		echo '">';
		echo $data[$champLabel];
		echo "</a>\n<br />\n";
	}
}

function mkLabel($id, $valeur){
	echo "<label for=\"$id\"> $valeur</label>";
}


function br() {
	echo "<br />\n"; 
}

function hr() {
	echo "<hr />\n"; 
}
?>

















