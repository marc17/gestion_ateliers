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
$nom_script = "mod_plugins/gestion_ateliers/bas_par_classes.php";
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


//**************** EN-TETE *****************
//$titre_page = "Gestion des Ateliers";
require_once("../../lib/header.inc.php");
//**************** FIN EN-TETE *************

if (isset($numero_bas)) {
    $date_bas = sql_query1("select date_bas from bas_bas where id_bas='".$numero_bas."'");
    $inscription_bas = sql_query1("select inscription_bas from bas_bas where id_bas='".$numero_bas."'");
    $num_periode = sql_query1("select num_periode from bas_bas where id_bas='".$numero_bas."'");
    $nom_bas = sql_query1("select nom from bas_bas where id_bas='".$numero_bas."'");
    $req = mysql_query("select * from bas_classes order by 'nom_classe'");
    $nb_classes = mysql_num_rows($req);
    // Constitution du tableau $per
    $description_bas = sql_query1("select description_bas from bas_bas where id_bas='".$numero_bas."'");
    $per =  tableau_periode($numero_bas);

    $i = 0 ;
    while ($i < $nb_classes) {
        $id_classe2 = mysql_result($req,$i,'id_classe');
        $nom_classe = mysql_result($req,$i,'nom_classe');
        $nom_complet_classe = mysql_result($req,$i,'nom_complet_classe');
        echo "<p class='grand'>".$nom_bas." - ".$date_bas." - ".$nom_classe."</p>";
        if ($inscription_bas != "n") echo "<p class='grand'><font color='red'>*** Liste temporaire ***</font></p>";

        $call_eleves = mysql_query("SELECT DISTINCT j.login FROM j_eleves_classes j WHERE (j.id_classe = '$id_classe2') ORDER BY login");
        $nombreligne = mysql_num_rows($call_eleves);
        $k = '0';
        echo "<table BORDER = '1' CELLPADDING = '2'><tr>
        <td><b>Nom Prénom</b></td>";
        $d = 1;
        while ($d < count($per)+1) {
            echo "<td><span class='style_bas'><b>".$per[$d]."</b></span></td><td><span class='style_bas'><b>Salle</b></span></td>";
            $d++;
        }
        echo "</tr>";
        While ($k < $nombreligne) {
            $login_eleve = mysql_result($call_eleves, $k, 'login');
            $call_data_eleves = mysql_query("SELECT * FROM eleves WHERE (login = '$login_eleve')");
            $nom_eleve = @mysql_result($call_data_eleves, '0', 'nom');
            $prenom_eleve = @mysql_result($call_data_eleves, '0', 'prenom');
            $call_profsuivi_eleve = mysql_query("SELECT * FROM j_eleves_professeurs WHERE (login = '$login_eleve' and id_classe='$id_classe2')");
            $eleve_profsuivi = @mysql_result($call_profsuivi_eleve, '0', 'professeur');

            echo "<tr><td><span class='style_bas'>".$nom_eleve." ".$prenom_eleve."</span></td>";
            $d = 1;
            while ($d < count($per)+1) {
                $bas[$d] = sql_query1("select id_bas from bas_j_eleves_bas
                where id_eleve = '".$login_eleve."' and
                num_bas = '".$numero_bas."' and
                num_choix = '0' and
                num_sequence = '".$d."'
                ");
                $id_prop[$d] = sql_query1("select id_prop from bas_propositions where id_bas='".$bas[$d]."'");
                $salle[$d] = sql_query1("select salle from bas_propositions where id_bas='".$bas[$d]."'");
                $animateur = sql_query1("select responsable from bas_propositions where id_bas='".$bas[$d]."'");
                $civilite[$d] = sql_query1("select civilite from utilisateurs where login = '".$animateur."'");
                $nom_prof[$d] = sql_query1("select nom from utilisateurs where login = '".$animateur."'");
                $nom_salle[$d] = sql_query1("select nom_court_salle from bas_salles where id_salle='".$salle[$d]."'");
                $d++;
            }
            if (isset($id_prop[1]))
            if ($id_prop[1] != '-1') {
                echo "<td><span class='style_bas'>".$id_prop[1];
                if ($nom_prof[1] != -1) echo "<br />(".$civilite[1]." ".$nom_prof[1].")";
                echo "</span></td><td><span class='style_bas'>$nom_salle[1]</span></td>";
            } else
                echo "<td><span class='style_bas'>-</span></td><td><span class='style_bas'>-</span></td>";
            if (isset($id_prop[2]))
            if ($id_prop[2] != '-1') {
               echo "<td><span class='style_bas'>".$id_prop[2];
               if ($nom_prof[2] != -1) echo "<br />(".$civilite[2]." ".$nom_prof[2].")";
               echo "</span></td><td><span class='style_bas'>$nom_salle[2]</span></td>";
            } else
                echo "<td><span class='style_bas'>-</span></td><td><span class='style_bas'>-</span></td>";
            if (isset($id_prop[3]))
            if ($id_prop[3] != '-1') {
                echo "<td><span class='style_bas'>".$id_prop[3];
                if ($nom_prof[3] != -1) echo "<br />(".$civilite[3]." ".$nom_prof[3].")";
                echo "</span></td><td><span class='style_bas'>$nom_salle[3]</span></td>";
            } else
                echo "<td><span class='style_bas'>-</span></td><td><span class='style_bas'>-</span></td>";

            echo "</tr>";
            $k++;
        }
        $i++;
    echo "</table>";
    echo "<p class='saut'></p>";
    }

}


?>
</body>
</html>