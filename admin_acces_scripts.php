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

$nom_plugin = "gestion_ateliers";
//On vérifie si le module est activé
$test_plugin = sql_query1("select ouvert from plugins where nom='".$nom_plugin."'");
if ($test_plugin!='y') {
    die("Le module n'est pas activé.");
}

// On vérifie que le statut de l'utilisateur permet d'accéder à ce script
$nom_script = "mod_plugins/gestion_ateliers/admin_acces_scripts.php";
if (!checkAccess_Plugin($nom_script)) {
    header("Location: ../../logout.php?auto=1");
    die();
}
// On vérifie que l'utilisateur a les droits spécifiques pour accéder à ce script
if (!calcul_autorisation_gestion_ateliers($_SESSION['login'],$nom_script)){
    header("Location: ../../logout.php?auto=1");
    die();
}

//********************************
unset($reg_prof_login);
$reg_prof_login = isset($_POST["reg_prof_login"]) ? $_POST["reg_prof_login"] : (isset($_GET["reg_prof_login"]) ? $_GET["reg_prof_login"] : NULL);
unset($login_prof);
$login_prof = isset($_POST["login_prof"]) ? $_POST["login_prof"] : (isset($_GET["login_prof"]) ? $_GET["login_prof"] : NULL);
unset($nom_fichier);
$nom_fichier = isset($_POST["nom_fichier"]) ? $_POST["nom_fichier"] : (isset($_GET["nom_fichier"]) ? $_GET["nom_fichier"] : NULL);

//********************************
$msg = '';

if (isset($_GET['action']) and ($_GET['action'] == "del_prof")) {
    $delete_membre = mysql_query("DELETE FROM bas_gestion_acces_scripts WHERE (nom_champ='".$nom_fichier."' and content='".$login_prof."')");
    if (!$delete_membre) { $msg = "Erreur lors de la suppression de l'utilisateur"; } else { $msg = "L'utilisateur a bien été supprimé de la liste."; }
}
if (isset($_POST['add_prof']) and ($_POST['add_prof'] == "yes") and ($reg_prof_login != '')) {
    if ($reg_prof_login=='_tous_') {
      // On efface tous les membres
      $delete_membre = mysql_query("DELETE FROM bas_gestion_acces_scripts WHERE (nom_champ='".$nom_fichier."')");
      // On insère l'enregistrement
      $reg_data = mysql_query("INSERT INTO bas_gestion_acces_scripts SET content='_tous_', nom_champ='".$nom_fichier."'");
      if (!$reg_data) { $msg = "Erreur lors de l'ajout de l'item ".$reg_prof_login." !"; } else { $msg = "A présent, tous les utilisateurs de la liste ont accès au script !"; }

    } else {
      // On efface
      $delete_membre = mysql_query("DELETE FROM bas_gestion_acces_scripts WHERE (nom_champ='".$nom_fichier."' and content='_tous_')");
      // On initialise $flag_stop
      $flag_stop = "n";
      $sql_statut = "select distinct user_statut from plugins_autorisations where fichier='".$nom_fichier."'";
      $res_statut = mysql_query($sql_statut);
      if(mysql_num_rows($res_statut)>0) {
          while($row_statut=mysql_fetch_object($res_statut)) {
            if ($reg_prof_login=="_".$row_statut->user_statut."_") {
              $reg_data = mysql_query("INSERT INTO bas_gestion_acces_scripts SET content='".$reg_prof_login."', nom_champ='".$nom_fichier."'");
              if (!$reg_data) { $msg = "Erreur lors de l'ajout de l'item ".$reg_prof_login." !"; } else { $msg = "A présent, tous les utilisateurs ayant le statut ".$row_statut->user_statut." ont accès au script !"; }
              $flag_stop = "y";
            }
          }
      }
      if ($flag_stop == "n") {
        // On commence par vérifier que le professeur n'est pas déjà présent dans cette liste.
        $test = mysql_query("SELECT * FROM bas_gestion_acces_scripts WHERE (nom_champ='".$nom_fichier."' and content='".$reg_prof_login."')");
        $test2 = mysql_num_rows($test);
        if ($test2 != "0") {
          $msg = "L'utilisateur que vous avez tenté d'ajouter appartient déjà à la liste.";
        } else {
          $reg_data = mysql_query("INSERT INTO bas_gestion_acces_scripts SET content='".$reg_prof_login."', nom_champ='".$nom_fichier."'");
          if (!$reg_data) { $msg = "Erreur lors de l'ajout de l'utilisateur ".$reg_prof_login." !"; } else { $msg = "L'utilisateur a bien été ajouté !"; }
        }
      }
    }
}

$gepiSettings=array();
if (!loadSettings()) {
    die("Erreur chargement settings");
}


//**************** EN-TETE *****************
$titre_page = "Gestion des ateliers - Configuration des autorisations d'accès aux scripts";
require_once("../../lib/header.inc.php");
//**************** FIN EN-TETE *****************

echo "<p class=\"bold\">|<a href='../../accueil.php'>Retour</a>|</p>\n";

echo "<p>Cette page permet de gérer les accès aux différents script du plugin.
<br />Il est ainsi possible de déléguer l'administration des ateliers à un utilisateur particulier en lui autorisant
l'accès aux scripts correspondants</p>";

$sql = "select distinct pa.fichier as nom_fichier, pa.user_statut as user_statut from plugins p, plugins_autorisations pa
where p.id = pa.plugin_id and p.nom = '".$nom_plugin."' group by pa.fichier order by p.nom";
$res = mysql_query($sql);
if(mysql_num_rows($res)>0) {
  $num_form = 0;
  while($row=mysql_fetch_object($res)) {
    $num_form++;
    $nom_fichier = $row->nom_fichier;
    $user_statut = $row->user_statut;
    $description = sql_query1("select distinct titre_item from plugins_menus pm where pm.lien_item='".$nom_fichier."'");
    echo "<h2>Accès au script ".$nom_fichier."<br />\n";
    if ($description != -1) echo "(Module : <b>".$description."</b>)";
    echo "</h2>\n";
    echo "<p><span class='bold'>Liste des utilisateurs ayant accès à ce script :</span>\n";
    $test = sql_query1("SELECT count(nom_champ) FROM bas_gestion_acces_scripts WHERE (content = '_tous_' and nom_champ = '".$nom_fichier."')");
    if ($test == 1) {
        echo "<br /><font size=+1><span style=\"color:red;\">Actuellement, tous les utilisateurs de la liste ci-dessous ont accès à ce script !</span> - <a href='admin_acces_scripts.php?action=del_prof&amp;login_prof=_tous_&amp;nom_fichier=$nom_fichier'>supprimer</a></font>\n";
        $sql_statut = "select distinct user_statut from plugins_autorisations where fichier='".$nom_fichier."'";
        $res_statut = mysql_query($sql_statut);
        if(mysql_num_rows($res_statut)>0) {
          echo "<br >Il s'agit des utilisateurs ayant l'un des statuts suivants : ";
          while($row_statut=mysql_fetch_object($res_statut)) {
            echo "<b>".$row_statut->user_statut."</b> - ";
          }
        }

    } else {
      $vide = 1;
      $sql_statut = "select distinct user_statut from plugins_autorisations where fichier='".$nom_fichier."'";
      $res_statut = mysql_query($sql_statut);
      if(mysql_num_rows($res_statut)>0) {
          while($row_statut=mysql_fetch_object($res_statut)) {
            $test2 = sql_query1("SELECT count(nom_champ) FROM bas_gestion_acces_scripts WHERE (content = '_".$row_statut->user_statut."_' and nom_champ = '".$nom_fichier."')");
            if ($test2 == 1) {
              echo "<br /><font size=+1><span style=\"color:red;\">Actuellement, tous les utilisateurs de la liste ayant le statut ".$row_statut->user_statut." ont accès à ce script !</span> - <a href='admin_acces_scripts.php?action=del_prof&amp;login_prof=_".$row_statut->user_statut."_&amp;nom_fichier=$nom_fichier'>supprimer</a></font>\n";
              $vide = 0;
            }
          }
      }

      $call_liste_data = mysql_query("SELECT distinct u.* FROM utilisateurs u, bas_gestion_acces_scripts p
      WHERE ( p.nom_champ='".$nom_fichier."' and u.login=p.content ) order by u.nom, u.prenom");
      $nombre = mysql_num_rows($call_liste_data);
      $i = "0";
      while ($i < $nombre) {
        $vide = 0;
        $login_prof = mysql_result($call_liste_data, $i, "login");
        $nom_prof = mysql_result($call_liste_data, $i, "nom");
        $prenom_prof = @mysql_result($call_liste_data, $i, "prenom");
        echo "<br /><b>";
        echo "$nom_prof $prenom_prof</b> | <a href='admin_acces_scripts.php?action=del_prof&amp;login_prof=$login_prof&amp;nom_fichier=$nom_fichier'><font size=2>supprimer</font></a>\n";
        $i++;
      }
      if ($vide == 1) {
        echo "<br /><span style=\"color:red;\">Il n'y a pas actuellement d'utilisateur ayant accès à ce script !</span>\n";
      }
    }
    echo "<br /><br /><span class='bold'>Ajouter un utilisateur :</span>\n";
    echo "</p>\n";
    echo "<form action=\"admin_acces_scripts.php\" id=\"form".$num_form."\" method=\"post\">\n";
    echo "<p><select size=\"1\" name=\"reg_prof_login\">\n";
    echo "<option value=''>(aucun)</option>\n";
    echo "<option value='_tous_'>Tous les utilisateurs</option>\n";
    $sql_statut = "select distinct user_statut from plugins_autorisations where fichier='".$nom_fichier."'";
    $res_statut = mysql_query($sql_statut);
    if(mysql_num_rows($res_statut)>0) {
      while($row_statut=mysql_fetch_object($res_statut)) {
        echo "<option value='_".$row_statut->user_statut."_'>Tous les utilisateurs ayant le statut ".$row_statut->user_statut."</option>\n";
      }
    }
    $call_prof = mysql_query("SELECT distinct u.login, u.nom, u.prenom FROM utilisateurs u , plugins_autorisations pa, plugins pl
    WHERE  (
    u.etat!='inactif' and
    pa.user_statut=u.statut and
    pa.plugin_id=pl.id and
    pl.nom='".$nom_plugin."' and
    pa.fichier='".$nom_fichier."'
    ) order by nom");
    $nombreligne = mysql_num_rows($call_prof);
    $i = "0" ;
    while ($i < $nombreligne) {
      $login_prof = mysql_result($call_prof, $i, 'u.login');
      $nom_el = mysql_result($call_prof, $i, 'u.nom');
      $prenom_el = mysql_result($call_prof, $i, 'u.prenom');
      echo "<option value=\"$login_prof\">$nom_el  $prenom_el</option>\n";
      $i++;
    }
    echo "</select>\n";
    echo "<input type=\"hidden\" name=\"add_prof\" value=\"yes\" />\n";
    echo "<input type=\"hidden\" name=\"nom_fichier\" value=\"$nom_fichier\" />\n";
    echo "<input type=\"submit\" value=\"Enregistrer\" />\n";
    echo "</p></form>\n";
    echo "<hr />";
  }
}

include "./footer.inc.php";
?>