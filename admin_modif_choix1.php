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
$nom_script = "mod_plugins/gestion_ateliers/admin_modif_choix1.php";
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
$id_bas1 = isset($_POST['id_bas1']) ? $_POST['id_bas1'] : (isset($_GET['id_bas1']) ? $_GET['id_bas1'] : NULL);
$id_bas2 = isset($_POST['id_bas2']) ? $_POST['id_bas2'] : (isset($_GET['id_bas2']) ? $_GET['id_bas2'] : NULL);

if (isset($_POST['is_posted'])) {
    $num_periode = sql_query1("select num_periode from bas_bas where id_bas='".$numero_bas."'");
    $calldata = mysql_query("select * from bas_j_eleves_bas where id_bas='".$id_bas1."' and num_choix='1'");
    $nb_eleves = mysql_num_rows($calldata);
    $i = 0;
    $msg = '';
    while ($i < $nb_eleves) {
        $login_eleve = mysql_result($calldata,$i,'id_eleve');
        if (isset($_POST[$login_eleve])) {
            $id_filiere = sql_query1("select id_filiere from bas_j_eleves_filieres where id_eleve = '".$login_eleve."'");
            $test = sql_query1("select public_".$id_filiere." from bas_propositions where id_bas='".$id_bas2."'");
            if ($test != '-1') {
                $ma = mysql_query("update bas_j_eleves_bas
                set id_bas='".$id_bas2."'
                where
                id_eleve='".$login_eleve."' and
                id_bas='".$id_bas1."' and
                num_choix='1'
                ");
                $msg .= "Le choix de l'élève ".$login_eleve." a été modifié.<br />";
            } else {
                $msg .= "Le choix de l'élève ".$login_eleve." n'a pu être modifié.<br />";
            }

        }
        $i++;
     }

}


//**************** EN-TETE *****************
$titre_page = "Modification des choix 1";
require_once("../../lib/header.inc.php");
//**************** FIN EN-TETE *************
echo "<p class=bold>| <a href=\"../../accueil.php\">Retour page d'accueil</a>|";
echo "<a href=\"./admin_bas_affectations.php?numero_bas=$numero_bas\">Retour Harmonisation effectifs</a>|";
if ((isset($id_bas1)) or (isset($id_bas2))) {
    echo " <a href=\"admin_modif_choix1.php?numero_bas=$numero_bas\">Retour aux choix des activités</a>|";
}
echo "</p>";

// données sur le bas
$date_bas = sql_query1("select date_bas from bas_bas where id_bas='".$numero_bas."'");
$close_bas = sql_query1("select close_bas from bas_bas where id_bas='".$numero_bas."'");
$date_limite = sql_query1("select date_limite from bas_bas where id_bas='".$numero_bas."'");
echo "<p class='grand'>".ucfirst($NomAtelier_singulier)." N° ".$numero_bas." du ".$date_bas."</p>";


// Choix de la matière
if (!(isset($id_bas1)) and !(isset($id_bas2))) {
    $calldata = mysql_query("SELECT * FROM bas_propositions
    WHERE (num_bas= '".$numero_bas."') ORDER BY id_prop");
    $nombreligne = mysql_num_rows($calldata);

    echo "<p>Choisissez l'activité pour laquelle vous souhaitez désinscrire des élèves :</p>";
    echo "<form action=\"admin_modif_choix1.php\" name=\"modif_choix1\" method=\"post\">\n";
    echo "<select name=\"id_bas1\" size=\"1\">\n";
    echo "<option value=''>(choisissez)</option>\n";
    $i = 0;
    while ($i < $nombreligne){
        $id_prop = @mysql_result($calldata, $i, "id_prop");
        $titre = @mysql_result($calldata, $i, "titre");
        $debut_final = @mysql_result($calldata, $i, "debut_final");
        $responsable = @mysql_result($calldata, $i, "responsable");
        $nom_prof = sql_query1("select nom from utilisateurs where login='".$responsable."'");
        $prenom_prof = sql_query1("select prenom from utilisateurs where login='".$responsable."'");
        $bas_duree = @mysql_result($calldata, $i, "duree");
        $id_bas = @mysql_result($calldata, $i, "id_bas");
        echo "<option value='".$id_bas."'>".$id_prop." ".$titre."</option>\n";
        $i++;
    }
    echo "</select>\n";

    echo "<p>Choisissez l'activité à laquelle vous souhaitez inscrire des élèves :</p>";
    echo "<select name=\"id_bas2\" size=\"1\">\n";
    echo "<option value=''>(choisissez)</option>\n";
    $i = 0;
    while ($i < $nombreligne){
        $id_prop = @mysql_result($calldata, $i, "id_prop");
        $titre = @mysql_result($calldata, $i, "titre");
        $debut_final = @mysql_result($calldata, $i, "debut_final");
        $responsable = @mysql_result($calldata, $i, "responsable");
        $nom_prof = sql_query1("select nom from utilisateurs where login='".$responsable."'");
        $prenom_prof = sql_query1("select prenom from utilisateurs where login='".$responsable."'");
        $bas_duree = @mysql_result($calldata, $i, "duree");
        $id_bas = @mysql_result($calldata, $i, "id_bas");
        echo "<option value='".$id_bas."'>".$id_prop." ".$titre."</option>\n";
        $i++;
    }
    echo "</select>\n";
    echo "<input type=\"hidden\" name=\"numero_bas\" value=\"$numero_bas\" />";
    echo "<br /><center><input type=\"submit\" name=\"ok\" /></center>";
    echo "</form>\n";

} else {
    $num_periode = sql_query1("select num_periode from bas_bas where id_bas='".$numero_bas."'");
    if (($id_bas1== '') or ($id_bas2 == '') or ($id_bas1 == $id_bas2)) {
        echo "<p><b><font color='red'>Impossible de continuer : vous devez choisir deux activités différentes.</font></b></p>";
        echo "</body></html>";
        die();
     }

    $calldata1 = mysql_query("SELECT * FROM bas_propositions WHERE (id_bas= '".$id_bas1."')");
    $id_prop1 = @mysql_result($calldata1, 0, "id_prop");
    $titre1 = @mysql_result($calldata1, 0, "titre");
    $debut_final1 = @mysql_result($calldata1, 0, "debut_final");
    $responsable1 = @mysql_result($calldata1, 0, "responsable");
    $nom_prof1 = sql_query1("select nom from utilisateurs where login='".$responsable1."'");
    $prenom_prof1 = sql_query1("select prenom from utilisateurs where login='".$responsable1."'");
    $bas_duree1 = @mysql_result($calldata1, 0, "duree");

    $calldata2 = mysql_query("SELECT * FROM bas_propositions WHERE (id_bas= '".$id_bas2."')");
    $id_prop2 = @mysql_result($calldata2, 0, "id_prop");
    $titre2 = @mysql_result($calldata2, 0, "titre");
    $debut_final2 = @mysql_result($calldata2, 0, "debut_final");
    $responsable2 = @mysql_result($calldata2, 0, "responsable");
    $nom_prof2 = sql_query1("select nom from utilisateurs where login='".$responsable2."'");
    $prenom_prof2 = sql_query1("select prenom from utilisateurs where login='".$responsable2."'");
    $bas_duree2 = @mysql_result($calldata2, 0, "duree");

    if (($debut_final1 != $debut_final2)) {
        echo "<p><b><font color='red'>Impossible de continuer : Les deux activités ne débutent pas à la même heure.</font></b></p>";
        echo "</body></html>";
        die();
     }
    if (($bas_duree1 != $bas_duree2)) {
        echo "<p><b><font color='red'>Impossible de continuer : Les deux activités n'ont pas la même durée.</font></b></p>";
        echo "</body></html>";
        die();
     }
     echo "<p><b>Activité N° 1 : ".$id_prop1." - ".$titre1." (".$prenom_prof1." ".$nom_prof1.")</b></p>";
     echo "<p><b>Activité N° 2 : ".$id_prop2." - ".$titre2." (".$prenom_prof2." ".$nom_prof2.")</b></p>";
     echo "<p>Liste des élèves ayant actuellement l'activité N° 1 comme choix N° 1.
     <br />Cochez les cases correspondantes aux élèves que vous souhaitez inscrire à l'activité N° 2 comme choix N° 1.</p>";
     echo "<form action=\"admin_modif_choix1.php\" name=\"modif_choix1_reg\" method=\"post\">\n";
     $calldata = mysql_query("select * from bas_j_eleves_bas where id_bas='".$id_bas1."' and num_choix='1' order by id_eleve");
     $nb_eleves = mysql_num_rows($calldata);
     $i = 0;
     echo "<table>";
     while ($i < $nb_eleves) {
         $login_eleve = mysql_result($calldata,$i,'id_eleve');
         $nom_eleve = sql_query1("select nom from eleves where login = '".$login_eleve."'");
         $prenom_eleve = sql_query1("select prenom from eleves where login = '".$login_eleve."'");
         $id_filiere = sql_query1("select id_filiere from bas_j_eleves_filieres where id_eleve = '".$login_eleve."'");
         $nom_filiere = sql_query1("select nom_filiere from bas_filieres where id_filiere='".$id_filiere."'");
         echo "<tr><td>".$nom_eleve." ".$prenom_eleve."</td>\n";
         echo "<td>(".$nom_filiere.")</td>\n";
         echo "<td><input type=\"checkbox\" name=\"".$login_eleve."\" value=\"y\"/></td></tr>";
         $i++;
     }
     echo "</table>";
     echo "<center><input type=\"submit\" name=\"ok\" /></center>";
     echo "<input type=\"hidden\" name=\"id_bas1\" value=\"$id_bas1\" />";
     echo "<input type=\"hidden\" name=\"id_bas2\" value=\"$id_bas2\" />";
     echo "<input type=\"hidden\" name=\"numero_bas\" value=\"$numero_bas\" />";
     echo "<input type=\"hidden\" name=\"is_posted\" value=\"yes\" />";
     echo "</form>\n";

}
?>
</body>
</html>