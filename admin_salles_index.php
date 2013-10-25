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
$nom_script = "mod_plugins/gestion_ateliers/admin_salles_index.php";
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
    $test1 = sql_query1("select count(salle) from bas_propositions where salle='".$_GET['id_salle']."' or salle_final='".$_GET['id_salle']."'");
    if ($test1 > 0) {
        $msg = "Impossible de supprimer cette salle : des propositions affectées à cette salle existent.";
    } else {
        $del = sql_query("delete from bas_salles where id_salle='".$_GET['id_salle']."'");
        $msg = "Suppression réussie.";
    }
}


//**************** EN-TETE *****************
$titre_page = "Gestion des salles";
require_once("../../lib/header.inc.php");
//**************** FIN EN-TETE *****************
?>

<p class=bold>
|<a href="./admin_bases.php">Retour</a>
|<a href="admin_salles_modify.php">Ajouter une salle</a>|

</p>
<table width = '100%' border= '1' cellpadding = '5'>
<tr>
    <td><p class='bold'><a href='./admin_salles_index.php?orderby_salle=s.id_salle'>Identifiant salle</a></p></td>
    <td><p class='bold'><a href='./admin_salles_index.php?orderby_salle=s.nom_salle'>Nom complet salle</a></p></td>
    <td><p class='bold'><a href='./admin_salles_index.php?orderby_salle=s.nom_court_salle'>Nom court salle</a></p></td>
    <td><p class='bold'><a href='./admin_salles_index.php?orderby_salle=s.special,s.nom_salle'>Statut</a></p></td>
    <td><p class='bold'><a href='./admin_salles_index.php?orderby_salle=s.nb_places,s.nom_salle'>Nombre de places</a></p></td>
    <td><p class='bold'><a href='./admin_salles_index.php?orderby_salle=s.materiel,s.nom_salle'>Materiel</a></p></td>
    <td><p class='bold'>Supprimer</p></td>
</tr>
<?php
$orderby_salle = isset($_GET['orderby_salle']) ? $_GET['orderby_salle'] : (isset($_POST['orderby_salle']) ? $_POST["orderby_salle"] : 's.nom_salle');

$_SESSION['chemin_retour'] = $_SERVER['REQUEST_URI'];

$call_data = mysql_query("SELECT * FROM bas_salles s ORDER BY $orderby_salle");

$nombre_lignes = mysql_num_rows($call_data);
$i = 0;
while ($i < $nombre_lignes){
    $current_id_salle = mysql_result($call_data, $i, "id_salle");
    $current_nom_salle = mysql_result($call_data, $i, "nom_salle");
    $current_nom_court_salle = mysql_result($call_data, $i, "nom_court_salle");
    $current_special = mysql_result($call_data, $i, "special");
    if ($current_special == 'n')
        $current_special_txt = "non réservable";
    else
        $current_special_txt = "réservable";

    $current_nb_places = mysql_result($call_data, $i, "nb_places");
    $current_materiel = mysql_result($call_data, $i, "materiel");

    echo "<tr><td><a href='admin_salles_modify.php?current_id_salle=$current_id_salle'>$current_id_salle</a></td>";
    echo "<td>".html_entity_decode($current_nom_salle)."</td>";
    echo "<td>".html_entity_decode($current_nom_court_salle)."</td>";
    echo "<td>".html_entity_decode($current_special_txt)."</td>";
    echo "<td>".html_entity_decode($current_nb_places)."</td>";
    echo "<td>".html_entity_decode($current_materiel)."</td>";
    echo "<td><a href=admin_salles_index.php?action=supprimer&amp;id_salle=$current_id_salle onclick=\"return confirmlink(this, 'La suppression d\'une salle est irréversible. Une telle suppression ne devrait pas avoir lieu en cours d\'année. Si c\'est le cas, cela peut entraîner la présence de données orphelines dans la base. Etes-vous sûr de vouloir continuer ?', 'Confirmation de la suppression')\">Supprimer</a></td></tr>";
$i++;
}
?>
</table>
</body>
</html>