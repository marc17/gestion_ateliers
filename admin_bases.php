<?php
/*
 * Last modification  : 29 octobre 2013
 *
 * Copyright 2010 Laurent Delineau, 2013 Marc Leygnac, Eric Lebrun
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
$nom_script = "mod_plugins/gestion_ateliers/admin_bases.php";
if (!checkAccess_Plugin($nom_script)) {
    header("Location: ../../logout.php?auto=1");
    die();
}
// On vérifie que l'utilisateur a les droits spécifiques pour accéder à ce script
if (!calcul_autorisation_gestion_ateliers($_SESSION['login'],$nom_script)){
    header("Location: ../../logout.php?auto=1");
    die();
}

$msg = "";
if (isset($_POST['is_posted'])) {
   $error = "";
   if ($is_LP2I) {
     if ($_POST['reg_acf']!='')
         if (!saveSetting("active_acf_num_aid", $_POST['reg_acf'])) $error = "Erreur lors de l'enregistrement du paramètre active_acf_num_aid !"; else $msg = "Enregistrement effectué.";
      else
         mysql_query("delete from setting where NAME='active_acf_num_aid'");
      if (!saveSetting("bas_cout_resp_acf_prof", $_POST['bas_cout_resp_acf_prof'])) $error .= "Erreur lors de l'enregistrement du paramètre bas_cout_resp_acf_prof !";
      if (!saveSetting("bas_cout_resp_acf_prof2", $_POST['bas_cout_resp_acf_prof2'])) $error .= "Erreur lors de l'enregistrement du paramètre bas_cout_resp_acf_prof2 !";
      if (!saveSetting("bas_cout_jury_acf_prof", $_POST['bas_cout_jury_acf_prof'])) $error .= "Erreur lors de l'enregistrement du paramètre bas_cout_jury_acf_prof !";
      if (!saveSetting("bas_cout_bas_prof", $_POST['bas_cout_bas_prof'])) $error .= "Erreur lors de l'enregistrement du paramètre bas_cout_bas_prof !";
      if (!saveSetting("bas_nb_moyen_bas", $_POST['bas_nb_moyen_bas'])) $error .= "Erreur lors de l'enregistrement du paramètre bas_nb_moyen_bas !";
      if (!saveSetting("bas_nb_semaines", $_POST['bas_nb_semaines'])) $error .= "Erreur lors de l'enregistrement du paramètre bas_nb_semaines !";
      if (!saveSetting("bas_pourcent_bas_annules", $_POST['bas_pourcent_bas_annules'])) $error .= "Erreur lors de l'enregistrement du paramètre bas_pourcent_bas_annules !";
      if (!saveSetting("bas_nb_journees_acf", $_POST['bas_nb_journees_acf'])) $error .= "Erreur lors de l'enregistrement du paramètre bas_nb_journees_acf !";
      if ((isset($_POST['fiche_prof'])) and ($_POST['fiche_prof']=='y'))
          $temp = "y"; else $temp="n";
      if (!saveSetting("fiche_prof", $temp)) $error .= "Erreur lors de l'enregistrement du paramètre fiche_prof !";
      if ($error == "")
          $msg = "Enregistrement effectué.";
      else
          $msg = $error;

      $gepiSettings=array();
      if (!loadSettings()) {
        die("Erreur chargement settings");
      }
    }
    // Les classes
		$classes_list = mysql_query("SELECT DISTINCT c.* FROM classes c, periodes p WHERE p.id_classe = c.id  ORDER BY classe");
		$nb = mysql_num_rows($classes_list);
		$i ='0';
    while ($i < $nb) {
			$id_classe = mysql_result($classes_list, $i, 'id');
			$nom_classe = mysql_result($classes_list, $i, 'classe');
			$nom_complet_classe = mysql_result($classes_list, $i, 'nom_complet');
			$tempo = "case_".$id_classe;
			$temp = isset($_POST[$tempo])?$_POST[$tempo]:NULL;
			if ($temp == 'yes') {
        sql_query("delete from bas_classes where id_classe='".$id_classe."'");
				$call_reg = mysql_query("insert into bas_classes set id_classe='".$id_classe."', nom_classe='".$nom_classe."', nom_complet_classe='".$nom_complet_classe."'");
			} else {
//        $test1 =
//        $test2 =
//        if (($test1>0) or ($test2>0)) {
//            $message_enregistrement .= "Impossible de supprimer la classe ".$id_classe." (données existantes)\\n";
//        } else {
           sql_query("delete from bas_classes where id_classe='".$id_classe."'");
//        }
      }
		  $i++;
		}

}

function affiche_ligne($chemin_,$titre_,$expli_) {
          $temp = mb_substr($chemin_,29);
        echo "<tr>";
        //echo "<td width='30%'><a href=$temp>$titre_</a></span>";
        echo "<td width='30%'><a href=$temp>$titre_</a>";
        echo"</td>";
        echo "<td>$expli_</td>";
        echo "</tr>";
}


//**************** EN-TETE *****************
$titre_page = "Accueil - Administration des ".$NomAtelier_pluriel;
require_once("../../lib/header.inc.php");
//**************** FIN EN-TETE *************


echo "<p class=bold>| <a href=\"../../accueil.php\">Retour Accueil général</a> |</p>";

echo "<br /><center>";
$lien[]="mod_plugins/gestion_ateliers/admin_bas_config.php";
$lien[]="mod_plugins/gestion_ateliers/admin_matiere_index.php";
$lien[]="mod_plugins/gestion_ateliers/admin_user_index.php";
$lien[]="mod_plugins/gestion_ateliers/admin_salles_index.php";
if ($is_LP2I) $lien[]="mod_plugins/gestion_ateliers/admin_stats2.php";

$_titre["mod_plugins/gestion_ateliers/admin_bas_config.php"] = "Configuration générale&nbsp;:";
$_titre["mod_plugins/gestion_ateliers/admin_matiere_index.php"]= "Configuration des matières \"".$NomAtelier_pluriel."\"";
$_titre["mod_plugins/gestion_ateliers/admin_user_index.php"] = "Configuration des professeurs";
$_titre["mod_plugins/gestion_ateliers/admin_salles_index.php"] = "Configuration des salles \"".$NomAtelier_pluriel."\"";
if ($is_LP2I) $_titre["mod_plugins/gestion_ateliers/admin_stats2.php"] = "Statistiques";


$_expli["mod_plugins/gestion_ateliers/admin_bas_config.php"] = "dates des ".$NomAtelier_pluriel.", verrouillage/déverrouillage des différentes phases";
$_expli["mod_plugins/gestion_ateliers/admin_matiere_index.php"] = "Accès administrateur pour la configuration des matières \"".$NomAtelier_pluriel."\"";
$_expli["mod_plugins/gestion_ateliers/admin_user_index.php"] = "Accès administrateur pour la configuration des professeurs";
$_expli["mod_plugins/gestion_ateliers/admin_salles_index.php"] = "Accès administrateur pour la configuration des salles \"".$NomAtelier_pluriel."\"";
if ($is_LP2I) $_expli["mod_plugins/gestion_ateliers/admin_stats2.php"] = "Statistiques sur l'année écoulée (pour rapport de fin d'année)";

$chemin = array();
$titre = array();
$expli = array();
$affiche = 'no';
foreach($lien as $_lien){
  $_statut = sql_query1("select user_statut from plugins_autorisations where user_statut ='".$_SESSION['statut']."' and fichier='".$_lien."'");
  $result_autorisation = calcul_autorisation_gestion_ateliers($_SESSION['login'],$_lien);
  if (($_statut == $_SESSION['statut']) and ($result_autorisation)) {
      $chemin[] = $_lien;
      $titre[]  = $_titre[$_lien];
      $expli[]  = $_expli[$_lien];
      $affiche = 'yes';
    }
}

$nb_ligne = count($chemin);

if ($affiche=='yes') {
    //echo "<table width=750 border=2 cellspacing=1 bordercolor=#330033 cellpadding=5>";
    echo "<table width='80%' class='bordercolor'>";
    echo "<tr>";
    echo "<td width='30%'>&nbsp;</td>";
    echo "<td><b>Administration des ".$NomAtelier_pluriel."</b></td>";
    echo "</tr>";
    for ($i=0;$i<$nb_ligne;$i++) {
        affiche_ligne($chemin[$i],$titre[$i],$expli[$i]);
    }
    echo "</table>";
}

echo "<form action=\"admin_bases.php\" name=\"form1\" method=\"post\">\n";
echo "</center>";
if ($is_LP2I) {
  echo "Sélectionner l'AID correspondant aux ACF : ";
  $call_data = mysql_query("SELECT * FROM aid_config ORDER BY order_display1, order_display2, nom");
  $nb_aid = mysql_num_rows($call_data);
  if ($nb_aid == 0) {
      echo "Il n'y a actuellement aucune catégorie d'AID";
  } else {
      echo "<select name=\"reg_acf\" size=\"1\">";
      echo "<option value=''>(Choisissez)</option>\n";
      $i=0;
      while ($i < $nb_aid) {
          $nom_aid = @mysql_result($call_data, $i, "nom");
          $nom_complet_aid = @mysql_result($call_data, $i, "nom_complet");
          $indice_aid = @mysql_result($call_data, $i, "indice_aid");
          echo "<option value=\"".$indice_aid."\" ";
          if (getSettingValue("active_acf_num_aid") == $indice_aid) echo " selected";
          echo " >".$nom_aid." (".$nom_complet_aid.")</option>\n";
          $i++;
      }
      echo "</select>\n";
  }
  echo "<table>";
  echo "<tr>";
  echo "<td>Coût séquence d'une séquence ACF par professeur (en reponsabilité)</td>\n
  <td><input type=\"text\" name=\"bas_cout_resp_acf_prof\" value=\"".getSettingValue("bas_cout_resp_acf_prof")."\" size=\"20\" /></td></tr>\n";
  echo "<td>Coût séquence d'une  séquence ACF par professeur (en co-reponsabilité)</td>\n
  <td><input type=\"text\" name=\"bas_cout_resp_acf_prof2\" value=\"".getSettingValue("bas_cout_resp_acf_prof2")."\" size=\"20\" /></td></tr>\n";
  echo "<tr><td>Coût séquence d'une participation à un jury ACF par prof par an</td>\n
  <td><input type=\"text\" name=\"bas_cout_jury_acf_prof\" value=\"".getSettingValue("bas_cout_jury_acf_prof")."\" size=\"20\" /></td></tr>\n";
  echo "<tr><td>Coût séquence d'une  séquence BAS par professeur</td>\n
  <td><input type=\"text\" name=\"bas_cout_bas_prof\" value=\"".getSettingValue("bas_cout_bas_prof")."\" size=\"20\" /></td></tr>\n";
  echo "<tr><td>Nombre moyen attendu de propositions par BAS</td>\n
  <td><input type=\"text\" name=\"bas_nb_moyen_bas\" value=\"".getSettingValue("bas_nb_moyen_bas")."\" size=\"20\" /></td></tr>\n";
  echo "<tr><td>Nombre de semaines</td>\n
  <td><input type=\"text\" name=\"bas_nb_semaines\" value=\"".getSettingValue("bas_nb_semaines")."\" size=\"20\" /></td></tr>\n";
  echo "<tr><td>Pourcentage moyen de propositions BAS annulées</td>\n
  <td><input type=\"text\" name=\"bas_pourcent_bas_annules\" value=\"".getSettingValue("bas_pourcent_bas_annules")."\" size=\"20\" /></td></tr>\n";
  echo "<tr><td>Nombre de demi-journées ACF :</td>\n
  <td><input type=\"text\" name=\"bas_nb_journees_acf\" value=\"".getSettingValue("bas_nb_journees_acf")."\" size=\"20\" /></td></tr>\n";
  echo "<tr><td>Nombre de demi-journées BAS :</td>\n";
  $bas_nb_journees_bas = sql_query1("select count(id_bas) from bas_bas where type_bas='n'");
  echo "<td>".$bas_nb_journees_bas."</td></tr>\n";
  echo "<tr><td>Rendre visible la fiche personnelle de chaque professeur : </td>\n";
  echo "<td><input type=\"checkbox\" name=\"fiche_prof\" ";
  if (getSettingValue("fiche_prof")=='y') echo " checked ";   
  echo " value=\"y\" /></td></tr>\n";


  echo "</table>";
}

//
// Ici commence la partie qui concerne les classes
//
echo "<hr />";
echo "<p>Définissez ci-dessous les classes concernées par les ".$NomAtelier_pluriel."&nbsp;:</p>";
$calldata = mysql_query("SELECT DISTINCT c.* FROM classes c, periodes p WHERE p.id_classe = c.id  ORDER BY classe");
$nombreligne = mysql_num_rows($calldata);
$i = 0;
echo "<table width='100%' summary='Choix des classes'>\n";
echo "<tr valign='top' align='center'>\n";
$i = '0';
echo "<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>\n";
echo "<td align='left'>\n";
// $nb_class_par_colonne = 6;
// limitation du nombre de colonnes à 4 ou 5
$nb_class_par_colonne = round($nombreligne/4);
while($i < $nombreligne){
  $id_classe = mysql_result($calldata,$i,"id");
  $classe = mysql_result($calldata,$i,"classe");
	$temp = "case_".$id_classe;
	if(($i>0)&&(round($i/$nb_class_par_colonne)==$i/$nb_class_par_colonne)){
    echo "</td>\n";
    echo "<td align='left'>\n";
  }
  echo "<label for='$temp' style='cursor: pointer;'>";
  echo "<input type='checkbox' name='$temp' id='$temp' value='yes' ";
  $is_classe_obs = sql_query1("select count(id_classe) from bas_classes where id_classe='".$id_classe."'");
  if ($is_classe_obs>0) {
     echo "checked = \"checked\" ";
   }
  echo " />";
  echo "$classe</label><br />\n";
  $i++;
}
echo "</td>\n";
echo "</tr>\n";
echo "</table>\n";





echo "<input type=\"hidden\" name=\"is_posted\" value=\"yes\" />";
echo "<center><input type=\"submit\" name=\"Envoyer\" value=\"Envoyer\" /></center>";
echo "</form>\n";

?>
</body>
</html>