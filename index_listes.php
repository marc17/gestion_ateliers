<?php
/*
 * Last modification  : 14 mai 2010
 *
 * Copyright 2010 Laurent Delineau
 *
 * This file is part of "gestion_ateliers" a plugin of GEPI.
 * It's a free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This file is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 */
$niveau_arbo = "2";
// Initialisations files (Attention au chemin des fichiers en fonction de l'arborescence)
include("../../lib/initialisationsPropel.inc.php");
include("../../lib/initialisations.inc.php");
include("../plugins.class.php");
include("./functions_gestion_ateliers.php");

// Resume session
$resultat_session = $session_gepi->security_check();
if ($resultat_session == 'c') {
    header("Location: ../utilisateurs/mon_compte.php?change_mdp=yes");
    die();
} else if ($resultat_session == '0') {
    header("Location: ../../logout.php?auto=1");
    die();
}

//On vérifie si le module est activé
$test_plugin = sql_query1("select ouvert from plugins where nom='gestion_ateliers'");
if ($test_plugin!='y') {
    die("Le module n'est pas activé.");
}

// On vérifie que le statut de l'utilisateur permet d'accéder à ce script
$nom_script = "mod_plugins/gestion_ateliers/index_listes.php";
if (!checkAccess_Plugin($nom_script)) {
    header("Location: ../../logout.php?auto=1");
    die();
}
// On vérifie que l'utilisateur a les droits spécifiques pour accéder à ce script
if (!calcul_autorisation_gestion_ateliers($_SESSION['login'],$nom_script)){
    header("Location: ../../logout.php?auto=1");
    die();
}

// initialisation
$numero_bas = isset($_POST['numero_bas']) ? $_POST['numero_bas'] : (isset($_GET['numero_bas']) ? $_GET['numero_bas'] : NULL);
$id_filiere = isset($_POST['id_filiere']) ? $_POST['id_filiere'] : (isset($_GET['id_filiere']) ? $_GET['id_filiere'] : NULL);
$en_tete = isset($_POST['en_tete']) ? $_POST['en_tete'] : (isset($_GET['en_tete']) ? $_GET['en_tete'] : NULL);

//**************** EN-TETE *****************
if (!isset($en_tete)) $titre_page = ucfirst($NomAtelier_singulier)." - Listes des propositions";
require_once("../../lib/header.inc");
//**************** FIN EN-TETE *************

// Choix de la filiere
if (isset($numero_bas) and (!(isset($id_filiere)))) {
    $req = mysql_query("select * from bas_filieres order by niveau_filiere");
    $nb_filieres = mysql_num_rows($req);
    echo "<p class=bold>| <a href=\"../../accueil.php\">Retour à la page d'accueil</a> |
    <a href=\"index.php\"> Choisir un autre ".$NomAtelier_singulier."</a> |</p>";
    $i = 0 ;
    echo "<p><b>Choisissez la filière pour laquelle vous souhaitez visualiser les propositions : </b></p>";
    while ($i < $nb_filieres) {
        $id_filiere2 = mysql_result($req,$i,'id_filiere');
        $nom_filiere = mysql_result($req,$i,'nom_filiere');
        echo "<a href='index_listes.php?id_filiere=".$id_filiere2."&amp;numero_bas=$numero_bas'>".$nom_filiere."</a><br />";
        $i++;
    }
}


if (isset($numero_bas)  and (!(isset($id_filiere)))  and !(isset($_POST['is_posted'])) ) {

    echo "<hr /><H3>Edition personnalisée</H3>";
    echo "<form name=\"main\" action=\"index_listes.php\" method=\"post\" target=\"_blank\">\n";
    echo "<table cellspacing=\"8\" border =0>\n";

    $n=1;
    while ($n<NB_NIVEAUX_FILIERES+1) {
      echo "\n<tr>";
      foreach($tab_filière[$n]["id"] as $key => $_id){
        $temp = "reg_public_".$_id;
        echo "<td><input type=\"checkbox\" name=\"reg_public_".$_id."\" value=\"y\" ";
        if ((isset($$temp)) and ($$temp=="y")) { echo "checked";}
        echo " /> ".$tab_filière[$n]["nom"][$key]."</td>\n";
      }
      echo "</tr>\n";
      $n++;        
    }
    echo "</table>\n";

    echo "<input type=\"hidden\" name=\"numero_bas\" value=\"".$numero_bas."\" />";
    echo "<input type=\"hidden\" name=\"is_posted\" value=\"1\" />";
    echo "<input type=\"hidden\" name=\"id_filiere\" value=\"-1\" />";
    echo "<input type=\"hidden\" name=\"en_tete\" value=\"no\" />\n";
    echo "<center><div id=\"fixe\">";
    echo "<input type=\"submit\" name=\"Valider\" value=\"Valider\" />";
    echo "</div></center></form><hr />";
}

// La filiere est définie ainsi que le numéro de bas
if (isset($numero_bas) and (isset($id_filiere))) {
    echo "<form action=\"index_listes.php\" method=\"post\" name=\"formulaire1\" target=\"_blank\">\n";
    if (!isset($en_tete)) echo "<table><tr><td><b>| <a href=\"../../accueil.php\">Retour à la page d'accueil</a></b></td><td><b>|<a href=\"./index_listes.php?numero_bas=$numero_bas\"> Choisir une autre filiere</a></b></td><td> |";
    if (!isset($en_tete)) echo "<input type=\"submit\" value=\"Format imprimable\" /></td></tr></table>\n";
    echo "<input type=\"hidden\" name=\"id_filiere\" value=\"".$id_filiere."\" />\n";
    echo "<input type=\"hidden\" name=\"numero_bas\" value=\"".$numero_bas."\" />\n";
    echo "<input type=\"hidden\" name=\"en_tete\" value=\"no\" />\n";
    echo "</form>\n";



    // Code d'affichage de la liste des actvités par filiere
    include "./code_liste_bas_par_classe.php";

} else if (isset($numero_bas) and (isset($_POST['is_posted']))) {
    // Code d'affichage de la liste des actvités par filiere
    include "./code_liste_bas_par_classe.php";

}
?>
</body>
</html>