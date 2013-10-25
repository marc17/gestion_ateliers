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
$nom_script = "mod_plugins/gestion_ateliers/admin_salles_modify.php";
if (!checkAccess_Plugin($nom_script)) {
    header("Location: ../../logout.php?auto=1");
    die();
}
// On vérifie que l'utilisateur a les droits spécifiques pour accéder à ce script
if (!calcul_autorisation_gestion_ateliers($_SESSION['login'],$nom_script)){
    header("Location: ../../logout.php?auto=1");
    die();
}

if (isset($_POST['isposted'])) {
    $ok = 'yes';
    if (isset($_POST['reg_current_id_salle'])) {
        // On vérifie d'abord que l'identifiant est constitué uniquement de lettres et de chiffres :
        $salle_id = $_POST['reg_current_id_salle'];
        if (mb_ereg ("^[a-zA-Z0-9_]{1}[a-zA-Z0-9_]{1,19}$", $salle_id)) {
            $verify_query = mysql_query("SELECT * from bas_salles WHERE id_salle='".$salle_id."'");
            $verify = mysql_num_rows($verify_query);
            if ($verify == 0) {
                $current_nom_salle = html_entity_decode($_POST['current_nom_salle']);
                $current_nom_court_salle = html_entity_decode($_POST['current_nom_court_salle']);
                $current_special = html_entity_decode($_POST['current_special']);
                $current_nb_places = html_entity_decode($_POST['current_nb_places']);
                $current_materiel = html_entity_decode($_POST['current_materiel']);
                //========================
                $register_salle = mysql_query("INSERT INTO bas_salles
                SET
                id_salle='".$salle_id."',
                nom_court_salle='".$current_nom_court_salle."',
                nom_salle='".$current_nom_salle."',
                special='".$current_special."',
                nb_places='".$current_nb_places."',
                materiel='".$current_materiel."'
                ");
                if (!$register_salle) {
                    $msg = rawurlencode("Une erreur s'est produite lors de l'enregistrement de la nouvelle salle.");
                    $ok = 'no';
                } else {
                    $msg = rawurlencode("La nouvelle salle a bien été enregistrée.");
                }
            } else {
                $msg = rawurlencode("Cette salle existe déjà !!");
                $ok = 'no';
            }
        } else {
            $msg = "L'identifiant de salle doit être constitué uniquement de lettres et de chiffres !";
            $ok = 'no';
        }
    } else {
        $current_nom_salle = html_entity_decode($_POST['current_nom_salle']);
        $current_nom_court_salle = html_entity_decode($_POST['current_nom_court_salle']);
        $current_special = html_entity_decode($_POST['current_special']);
        $current_nb_places = html_entity_decode($_POST['current_nb_places']);
        $current_materiel = html_entity_decode($_POST['current_materiel']);
        $salle_id = $_POST['salle_id'];
        $register_salle = mysql_query("UPDATE bas_salles SET
                nom_court_salle='".$current_nom_court_salle."',
                nom_salle='".$current_nom_salle."',
                special='".$current_special."',
                nb_places='".$current_nb_places."',
                materiel='".$current_materiel."'
         WHERE id_salle='".$salle_id."'");
        if (!$register_salle) {
            $msg = rawurlencode("Une erreur s'est produite lors de la modification de la salle");
            $ok = 'no';
        } else {
            $msg = rawurlencode("Les modifications ont été enregistrées ! ");
        }
    }
    header("location: admin_salles_index.php?msg=$msg");
    die();

}
//**************** EN-TETE *******************************
$titre_page = "Gestion des salles | Modifier une salle";
require_once("../../lib/header.inc.php");
//**************** FIN EN-TETE ****************************
?>
<form enctype="multipart/form-data" action="admin_salles_modify.php" method=post>
<p class=bold>
|<a href="admin_salles_index.php">Retour</a>|<input type="submit" value="Enregistrer" />

</p>
<?php
// On va chercher les infos de la matière que l'on souhaite modifier
if (isset($_GET['current_id_salle'])) {
    $call_data = mysql_query("SELECT * from bas_salles WHERE id_salle='".$_GET['current_id_salle']."'");
    $current_nom_salle = mysql_result($call_data, 0, "nom_salle");
    $current_nom_court_salle = mysql_result($call_data, 0, "nom_court_salle");
    $current_special = mysql_result($call_data, 0, "special");
    $current_nb_places = mysql_result($call_data, 0, "nb_places");
    $current_materiel = mysql_result($call_data, 0, "materiel");
    $current_id_salle = $_GET['current_id_salle'];
} else {
    $current_nom_salle = "";
    $current_nom_court_salle = "";
    $current_special = "n";
    $current_nb_places = "";
    $current_materiel = "";
    $current_id_salle = "";
}

echo "<table>";
echo "<tr><td>Identifiant de la salle : </td>";
echo "<td>";
if (!isset($_GET['current_id_salle'])) {
    echo "<input type=text size=15 name=reg_current_id_salle />";
} else {
    echo "<input type=hidden name=salle_id value=\"".$current_id_salle."\" />".$current_id_salle;
}
echo "</td></tr>";
echo "<tr><td>Nom complet : </td>";
echo "<td><input type=text name=current_nom_salle value=\"".$current_nom_salle."\" /></td></tr>";

echo "<tr><td>Nom court : </td>";
echo "<td><input type=text name=current_nom_court_salle value=\"".$current_nom_court_salle."\" /></td></tr>";

echo "<tr><td>Statut : </td>";
echo "<td><input type=\"radio\" name=\"current_special\" value=\"n\" ";
if ($current_special == 'n') echo "checked";
echo " /> Non réservable - ";
echo "<input type=\"radio\" name=\"current_special\" value=\"y\" ";
if ($current_special == 'y') echo "checked";
echo " /> réservable ";
echo "</td></tr>";

echo "<tr><td>Nombre de places : </td>";
echo "<td><input type=text name=current_nb_places value=\"".$current_nb_places."\" /></td></tr>";

echo "<tr><td>Materiel : </td>";
echo "<td><input type=text name=current_materiel value=\"".$current_materiel."\" /></td></tr>";


echo "</table>
<input type=\"hidden\" name=\"isposted\" value=\"yes\" />
</form>
<br /><b>Légende pour le matériel : </b>
<br />M : Magnétoscope VHS
<br />V : Video-projecteur
<br />T : Téléviseur
<br />D : Lecteur de DVD
</body>
</html>";
?>