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
$nom_script = "mod_plugins/gestion_ateliers/admin_index.php";
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
if (!isset($_SESSION['order_by'])) {$_SESSION['order_by'] = "id_prop";}
$_SESSION['order_by'] = isset($_POST['order_by']) ? $_POST['order_by'] : (isset($_GET['order_by']) ? $_GET['order_by'] : $_SESSION['order_by']);
$order_by = $_SESSION['order_by'];
if (!isset($_SESSION['action_ges'])) {$_SESSION['action_ges'] = "";}
$_SESSION['action_ges'] = isset($_POST['action_ges']) ? $_POST['action_ges'] : (isset($_GET['action_ges']) ? $_GET['action_ges'] : $_SESSION['action_ges']);


//**************** EN-TETE *****************
$titre_page = "Gestion des ".$NomAtelier_pluriel;
require_once("../../lib/header.inc");
//**************** FIN EN-TETE *************

// choix du bas
if (!(isset($numero_bas))) {
    $_SESSION['action_ges']=='';
    echo "<p class=bold>| <a href=\"../../accueil.php\">Retour à la page d'accueil</a> |";
    if (calcul_autorisation_gestion_ateliers($_SESSION['login'],"mod_plugins/gestion_ateliers/admin_stats.php"))
      echo "<a href='admin_stats.php'>Statistiques</a> |";
    echo "</p>";
    echo "<p class='grand'>Choisissez la séance et l'opération à effectuer : </p>";
    echo "<table cellpadding=\"4\" border=\"1\">";
    $req = mysql_query("select * from bas_bas order by nom");
    $nb_bas = mysql_num_rows($req);
    $i = 0 ;
    while ($i < $nb_bas) {
        $num_bas = mysql_result($req,$i,'id_bas');
        $date_bas = mysql_result($req,$i,'date_bas');
        $nom_bas = mysql_result($req,$i,'nom');
//        $close_bas = mysql_result($req,$i,'close_bas');
//        if ($close_bas == "y") $close_bas = "<font color='red'>(inscriptions impossibles)</font>"; else $close_bas = "<font color='green'>(inscriptions possibles)</font>";
        echo "<tr><td>".$nom_bas." du ".$date_bas."</td>\n";
        if (calcul_autorisation_gestion_ateliers($_SESSION['login'],"mod_plugins/gestion_ateliers/admin_bas.php"))
          echo "<td><a href='admin_bas.php?numero_bas=".$num_bas."'>Harmonisation horaires</a></td>\n";
        if (calcul_autorisation_gestion_ateliers($_SESSION['login'],"mod_plugins/gestion_ateliers/admin_bas_affectations.php"))
          echo "<td><a href='admin_bas_affectations.php?numero_bas=".$num_bas."'>Harmonisation effectifs</a></td>\n";
        if (calcul_autorisation_gestion_ateliers($_SESSION['login'],"mod_plugins/gestion_ateliers/admin_bas_salles.php"))
          echo "<td><a href='admin_bas_salles.php?numero_bas=".$num_bas."'>Harmonisation salles</a></td>\n";
        if (calcul_autorisation_gestion_ateliers($_SESSION['login'],"mod_plugins/gestion_ateliers/admin_inscrip_rapide.php"))
          echo "<td><a href='admin_inscrip_rapide.php?numero_bas=".$num_bas."'>Inscription rapide</a></td>\n";
        if (calcul_autorisation_gestion_ateliers($_SESSION['login'],"mod_plugins/gestion_ateliers/admin_prof_suivi.php"))
          echo "<td><a href='admin_prof_suivi.php?numero_bas=".$num_bas."'>Groupes suivi</a></td>\n";
        if (calcul_autorisation_gestion_ateliers($_SESSION['login'],"mod_plugins/gestion_ateliers/admin_toutes_feuilles_presence.php"))
         echo "<td><a href='admin_toutes_feuilles_presence.php?numero_bas=".$num_bas."'>Feuilles présence</a></td>\n";
        if (calcul_autorisation_gestion_ateliers($_SESSION['login'],"mod_plugins/gestion_ateliers/admin_toutes_feuilles_presence2.php"))
          echo "<td><a href='admin_toutes_feuilles_presence2.php?numero_bas=".$num_bas."'>Feuilles d'inscription</a></td>\n";
        echo "</tr>";
        $i++;
    }
    echo "</table>";
}
?>
</body>
</html>