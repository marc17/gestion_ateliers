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
$nom_script = "mod_plugins/gestion_ateliers/admin_toutes_feuilles_presence2.php";
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
$id_bas = isset($_POST['id_bas']) ? $_POST['id_bas'] : (isset($_GET['id_bas']) ? $_GET['id_bas'] : NULL);


//**************** EN-TETE *****************
require_once("../../lib/header.inc.php");
//**************** FIN EN-TETE *************

// Si le numério n'est pas défini, on arrete tout
if (!(isset($numero_bas))) die();
$calldata = mysql_query("SELECT distinct * FROM bas_propositions
WHERE (
num_bas= '".$numero_bas."'
) ORDER BY responsable");

$nombreligne = mysql_num_rows($calldata);
$m = 0;


while ($m < $nombreligne){
    $id_bas = @mysql_result($calldata, $m, "id_bas");

    // Informations sur la proposition
    $call_bas_info = mysql_query("SELECT * FROM bas_propositions WHERE id_bas='$id_bas'");
    $responsable = mysql_result($call_bas_info, "0", "responsable");
    $test_imprime_feuille = sql_query1("select imprime_feuilles_presence from bas_imprime_feuilles_presence
    where login = '".$responsable."'");
    // Modif temporaire
    $test_imprime_feuille = 'y';

    if ($test_imprime_feuille == 'y') {

    $titre = mysql_result($call_bas_info, "0", "titre");
    $id_prop = mysql_result($call_bas_info, "0", "id_prop");


    $civilite = sql_query1("select civilite from utilisateurs where login = '".$responsable."'");
    $nom_prof = sql_query1("select nom from utilisateurs where login = '".$responsable."'");

    $matiere = mysql_result($call_bas_info, "0", "id_matiere");
    $nom_matiere = sql_query1("select nom_complet from matieres where matiere = '".$matiere."'");

    $salle = mysql_result($call_bas_info, "0", "salle");
    if ($salle == "") $salle = " NON DEFINIE";
    $duree = mysql_result($call_bas_info, "0", "duree");
    $debut_final = mysql_result($call_bas_info, "0", "debut_final");
    $debut_sequence  = mysql_result($call_bas_info, "0", "debut_sequence");
    $nb_max = mysql_result($call_bas_info, "0", "nb_max");
    settype($nb_max,"integer");
    if ($nb_max < 1) $nb_max = 40;


    // données sur le bas
    $description_bas = sql_query1("select description_bas from bas_bas where id_bas='".$numero_bas."'");
    $num_periode = sql_query1("select num_periode from bas_bas where id_bas='".$numero_bas."'");
    $nom_bas = sql_query1("select nom from bas_bas where id_bas='".$numero_bas."'");
    // Constitution du tableau $per
    $per =  tableau_periode($numero_bas);

    $type_bas = sql_query1("select type_bas from bas_bas where id_bas='".$numero_bas."'");
    if ($type_bas == "s") {
        echo "<p class='grand'>".$nom_bas." - ".$id_prop." - ";
        if ($debut_final != 0)
            $debut = $debut_final;
        else if ($debut_sequence != 0)
            $debut = $debut_sequence;
        else
            $debut = -1;
        if ($debut != -1) {
          echo "Horaire : ";
          $k = $debut;
          while ($k < $debut+$duree) {
              echo "[".$per[$k]."]";
              if ($k < $debut+$duree-1) echo " - ";
              $k++;
          }
        } else
          echo "Horaire non défini";
        echo " - Salle : ".$salle;
    } else {
        echo "<p class='grand'>".$nom_bas." - ".$id_prop." - ".$per[$debut_final]." - Durée : ".$duree." seq. - Salle : ".$salle;
    }

    echo "<br />".$titre." <br />Animateur ou responsable : ";
    if ($nom_prof != "-1")
        echo $civilite." ".$nom_prof;
    else
        echo $responsable;
    if ($nom_matiere != -1) echo " (".$nom_matiere.")";
    echo "</p>";
    $i = 0;

    echo "<table  style=\"width:100%\" border=\"1\" cellpadding=\"4\"><tbody><tr>";
    echo "<td width=\"30%\"><b>Nom prénom</b></td><td width=\"20%\"><b>Classe</b></td></tr>";
    while ($i < $nb_max) {
        echo "<tr><td>&nbsp;</td>
        <td>&nbsp;</td>";
        echo "</tr>";
        $i++;
    }
    echo "</tbody></table>";
    echo "<p class='saut'></p>";
    }
    $m++;
}

?>
</body>
</html>