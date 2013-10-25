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
$nom_script = "mod_plugins/gestion_ateliers/admin_matiere_modify.php";
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
    if (isset($_POST['reg_current_matiere'])) {
        // On vérifie d'abord que l'identifiant est constitué uniquement de lettres et de chiffres :
        $matiere_name = $_POST['reg_current_matiere'];
        if (mb_ereg ("^[a-zA-Z_]{1}[a-zA-Z0-9_]{1,19}$", $matiere_name)) {
            $verify_query = mysql_query("SELECT * from bas_matieres WHERE matiere='$matiere_name'");
            $verify = mysql_num_rows($verify_query);
            if ($verify == 0) {
                $matiere_nom_complet = html_entity_decode($_POST['matiere_nom_complet']);
                //========================
                $register_matiere = mysql_query("INSERT INTO bas_matieres SET matiere='".$matiere_name."', nom_complet='".$matiere_nom_complet."'");
                if (!$register_matiere) {
                    $msg = rawurlencode("Une erreur s'est produite lors de l'enregistrement de la nouvelle matière.");
                    $ok = 'no';
                } else {
                    $msg = rawurlencode("La nouvelle matière a bien été enregistrée.");
                }
            } else {
                $msg = rawurlencode("Cette matière existe déjà !!");
                $ok = 'no';
            }
        } else {
            $msg = "L'identifiant de matière doit être constitué uniquement de lettres et de chiffres !";
            $ok = 'no';
        }
    } else {
        $matiere_nom_complet = $_POST['matiere_nom_complet'];
        $matiere_name = $_POST['matiere_name'];
        $register_matiere = mysql_query("UPDATE bas_matieres SET nom_complet='".$matiere_nom_complet."' WHERE matiere='".$matiere_name."'");
        if (!$register_matiere) {
            $msg = rawurlencode("Une erreur s'est produite lors de la modification de la matière");
            $ok = 'no';
        } else {
            $msg = rawurlencode("Les modifications ont été enregistrées ! ");
        }
    }
    header("location: admin_matiere_index.php?msg=$msg");
    die();

}
//**************** EN-TETE *******************************
$titre_page = "Gestion des matières | Modifier une matière";
require_once("../../lib/header.inc.php");
//**************** FIN EN-TETE ****************************
?>
<form enctype="multipart/form-data" action="admin_matiere_modify.php" method=post>
<p class=bold>
|<a href="admin_matiere_index.php">Retour</a>|<input type="submit" value="Enregistrer" />

</p>
<?php
// On va chercher les infos de la matière que l'on souhaite modifier
if (isset($_GET['current_matiere'])) {
    $call_data = mysql_query("SELECT nom_complet from bas_matieres WHERE matiere='".$_GET['current_matiere']."'");
    $matiere_nom_complet = mysql_result($call_data, 0, "nom_complet");
    $current_matiere = $_GET['current_matiere'];
} else {
    $matiere_nom_complet = "";
    $current_matiere = "";
}
?>


<table><tr>
<td>Nom de matière : </td>
<td>
<?php
if (!isset($_GET['current_matiere'])) {
    echo "<input type=text size=15 name=reg_current_matiere />";
} else {
    echo "<input type=hidden name=matiere_name value=\"".$current_matiere."\" />".$current_matiere;
}
?>
</td></tr>
<tr>
<td>Nom complet : </td>
<td><input type=text name=matiere_nom_complet value="<?php echo $matiere_nom_complet;?>" /></td>
</tr>
</table>
<input type="hidden" name="isposted" value="yes" />
</form>

</body>
</html>