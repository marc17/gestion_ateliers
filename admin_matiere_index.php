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
$nom_script = "mod_plugins/gestion_ateliers/admin_matiere_index.php";
if (!checkAccess_Plugin($nom_script)) {
    header("Location: ../../logout.php?auto=1");
    die();
}
// On vérifie que l'utilisateur a les droits spécifiques pour accéder à ce script
if (!calcul_autorisation_gestion_ateliers($_SESSION['login'],$nom_script)){
    header("Location: ../../logout.php?auto=1");
    die();
}

$msg = '';
if (isset($_GET['action']) and ($_GET['action']=="supprimer")) {
    $test1 = sql_query1("select count(id_matiere) from bas_propositions where id_matiere='".$_GET['id_matiere']."'");
    if ($test1 > 0) {
        $msg = "Impossible de supprimer cette matière : des propositions affectées à cette matières existent.";
    } else {
        $del = sql_query("delete from bas_j_matieres_profs where id_matiere='".$_GET['id_matiere']."'");
        $del = sql_query("delete from bas_matieres where matiere='".$_GET['id_matiere']."'");
        $msg = "Suppression réussie.";
    }
}


//**************** EN-TETE *****************
$titre_page = "Gestion des matières";
require_once("../../lib/header.inc.php");
//**************** FIN EN-TETE *****************
?>

<p class=bold>
|<a href="./admin_bases.php">Retour</a>
|<a href="admin_matiere_modify.php">Ajouter matière</a>|

</p>
<table width = '100%' border= '1' cellpadding = '5'>
<tr>
    <td><p class='bold'><a href='./admin_matiere_index.php?orderby=m.matiere'>Identifiant matière</a></p></td>
    <td><p class='bold'><a href='./admin_matiere_index.php?orderby=m.nom_complet'>Nom complet</a></p></td>
    <td><p class='bold'>Supprimer</p></td>
</tr>
<?php
$orderby = isset($_GET['orderby']) ? $_GET['orderby'] : (isset($_POST['orderby']) ? $_POST["orderby"] : 'm.nom_complet');
if ($orderby != "m.matiere" AND $orderby != "m.nom_complet") {
    $orderby = "m.nom_complet";
}
$_SESSION['chemin_retour'] = $_SERVER['REQUEST_URI'];

$call_data = mysql_query("SELECT m.matiere, m.nom_complet FROM bas_matieres m ORDER BY $orderby");

$nombre_lignes = mysql_num_rows($call_data);
$i = 0;
while ($i < $nombre_lignes){
    $current_matiere = mysql_result($call_data, $i, "matiere");
    $current_matiere_nom = mysql_result($call_data, $i, "nom_complet");

    echo "<tr><td><a href='admin_matiere_modify.php?current_matiere=$current_matiere'>$current_matiere</a></td>";
    echo "<td>".html_entity_decode($current_matiere_nom)."</td>";
    echo "<td><a href=admin_matiere_index.php?action=supprimer&amp;id_matiere=$current_matiere onclick=\"return confirmlink(this, 'La suppression d\'une matière est irréversible. Une telle suppression ne devrait pas avoir lieu en cours d\'année. Si c\'est le cas, cela peut entraîner la présence de données orphelines dans la base. Etes-vous sûr de vouloir continuer ?', 'Confirmation de la suppression')\">Supprimer</a></td></tr>";
$i++;
}
?>
</table>
</body>
</html>