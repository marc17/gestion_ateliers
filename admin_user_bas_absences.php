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
$nom_script = "mod_plugins/gestion_ateliers/admin_user_bas_absences.php";
if (!checkAccess_Plugin($nom_script)) {
    header("Location: ../../logout.php?auto=1");
    die();
}
// On vérifie que l'utilisateur a les droits spécifiques pour accéder à ce script
if (!calcul_autorisation_gestion_ateliers($_SESSION['login'],$nom_script)){
    header("Location: ../../logout.php?auto=1");
    die();
}


unset($id_bas);
$id_bas = isset($_POST["id_bas"]) ? $_POST["id_bas"] : (isset($_GET["id_bas"]) ? $_GET["id_bas"] : NULL);

if (isset($_POST["is_posted"])) {
    $del = mysql_query("delete from bas_j_professeurs_absences where id_bas='".$id_bas."'");
    $calldata = mysql_query("SELECT * FROM utilisateurs ORDER BY nom");
    $nombreligne = mysql_num_rows($calldata);
    $i = 0;
    while ($i < $nombreligne){
        $user_login = mysql_result($calldata, $i, "login");
        $temp = "absence_".$user_login;
        if (isset($_POST[$temp])) {
            $req = mysql_query("insert into bas_j_professeurs_absences set id_bas='".$id_bas."', id_professeur='".$user_login."'");
        }
        $i++;
    }
    $msg = "Enregistrement réussi !";
}

//**************** EN-TETE *****************************
$titre_page = "Gestion des absences par atelier";
require_once("../../lib/header.inc");
//**************** FIN EN-TETE *****************


?>
<p class=bold>
|<a href="./admin_user_index.php">Retour</a>
|</p>


<form action="admin_user_bas_absences.php" method="post">

<?php
$nom_bas = sql_query1("select nom from bas_bas where id_bas='".$id_bas."'");
echo "<H2>".$nom_bas." - Professeurs excusés</H2>";

// Affichage du tableau
echo "<table border=1 cellpadding=3>";
echo "<tr>";
// ecgo "<td><p class=small><b><a href='admin_user_index.php?order_by_user=login&amp;display=$display'>Nom de login</a></b></p></td>";
echo "<td><p class=small><b><a href='admin_user_index.php?order_by_user=nom,prenom&amp;display=$display'>Nom et prénom</a></b></p></td>";
echo "<td><p class=small><b>Cochez ci-dessous les professeurs excusés</b></p></td>";
echo "</tr>";
$calldata = mysql_query("SELECT * FROM utilisateurs ORDER BY nom");
$nombreligne = mysql_num_rows($calldata);
$i = 0;

while ($i < $nombreligne){
    $user_login = mysql_result($calldata, $i, "login");
    $user_nom = sql_query1("select nom from utilisateurs where login='".$user_login."'");
    $user_prenom = sql_query1("select prenom from utilisateurs where login='".$user_login."'");
    $user_statut = sql_query1("select statut from utilisateurs where login='".$user_login."'");
    $abs = sql_query1("select count(id_professeur) from bas_j_professeurs_absences where id_professeur='".$user_login."' and id_bas='".$id_bas."'");

    $call_matieres = mysql_query("SELECT * FROM bas_j_matieres_profs j WHERE j.id_professeur = '$user_login' ORDER BY id_matiere");
    $nb_mat = mysql_num_rows($call_matieres);
    if ($nb_mat > 0) {
        if ($abs >=1)
            echo "<tr bgcolor=\"#FFC0C0\">";
        else
            echo "<tr bgcolor=\"#C0FFC0\">";
        echo "<td><span class=small><span class=bold>".$user_nom." ".$user_prenom."</span></span></td>";
        echo "<td><input type=\"checkbox\" name=\"absence_".$user_login."\" value=\"y\" ";
        if ($abs >=1)
            echo " checked ";
        echo "/></td>";
        echo "</tr>";
    }
    $i++;
}
echo "</table>";
echo "<input type=\"hidden\" name=\"id_bas\" value=\"$id_bas\" />";
echo "<input type=\"hidden\" name=\"is_posted\" value=\"y\" />";
echo "<center><div id=\"fixe\">";
echo "<input type=\"submit\" name=\"ok\" value=\"Enregistrer\" /></div></center>";

?>

</form></div></body>
</html>