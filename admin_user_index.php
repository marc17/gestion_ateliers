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
$nom_script = "mod_plugins/gestion_ateliers/admin_user_index.php";
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
if (isset($_GET['action']) and ($_GET['action']=="del_utilisateur")) {
    $test1 = sql_query1("select count(id_bas) from bas_propositions where
    responsable='".$_GET['id_user']."' or coresponsable='".$_GET['id_user']."'");
    if ($test1 > 0) {
        $msg = "Impossible de sortir cet utilisateur : des propositions affectées à cet utilisateur existent.";
    } else {
        $del = sql_query("delete from bas_j_matieres_profs where id_professeur='".$_GET['id_user']."'");
        $del = sql_query("delete from bas_utilisateurs where login='".$_GET['id_user']."'");
        $msg = "Suppression réussie.";
    }
}



//**************** EN-TETE *****************************
$titre_page = "Gestion des utilisateurs";
require_once("../../lib/header.inc.php");
//**************** FIN EN-TETE *****************
unset($display);
$display = isset($_POST["display"]) ? $_POST["display"] : (isset($_GET["display"]) ? $_GET["display"] : (getSettingValue("lpi_display_users")!='' ? getSettingValue("lpi_display_users"): 'tous'));
// on sauve le choix par défaut
saveSetting("lpi_display_users", $display);

unset($order_by_user);
$order_by_user = isset($_POST["order_by_user"]) ? $_POST["order_by_user"] : (isset($_GET["order_by_user"]) ? $_GET["order_by_user"] : 'login');


?>
<p class=bold>
|<a href="./admin_bases.php">Retour</a>
|<a href="admin_tab_profs_matieres.php">Affecter les matières aux professeurs</a>
|</p>
<?php
if ($is_LP2I) {
  echo "<table border=1><tr><td>";
  echo "<form action=\"admin_user_bas_absences.php\" method=\"post\">\n";
  echo "Gérer les absences pour l'atelier :";
  $req = sql_query("select * from bas_bas where type_bas='n' order by id_bas");
  $nb_req = sql_count($req);
  echo "<select name=\"id_bas\" size=\"1\">\n";
  echo "<option value=''>(choisissez)</option>\n";
  $k = 0;
  while($k < $nb_req) {
      $id_bas = mysql_result($req,$k,"id_bas");
      $nom_bas = mysql_result($req,$k,"nom");
      echo "<option value='".$id_bas."'>".$nom_bas."</option>\n";
      $k++;
  }
  echo "</select>";
  echo "<input type=submit value=Valider />";
  echo "</form></td></tr></table>";
}
?>
<form action="admin_user_index.php" method="post">
<table border=0>
<tr>
<td><p>Afficher : </p></td>
<td><p>tous les utilisateurs <INPUT TYPE="radio" NAME="display" value='tous' <?php if ($display=='tous') {echo " CHECKED";} ?> /></p></td>
<td><p>
 &nbsp;&nbsp;les utilisateurs "Atelier" (pouvant proposer des activités)<INPUT TYPE="radio" NAME="display" value='bas' <?php if ($display=='bas') {echo " CHECKED";} ?> /></p></td>
 <td><p>
 &nbsp;&nbsp;les utilisateurs "Hors Atelier"<INPUT TYPE="radio" NAME="display" value='horsbas' <?php if ($display=='horsbas') {echo " CHECKED";} ?> /></p></td>
 <td><p><input type=submit value=Valider /></p></td>
 </tr>
 </table>

<input type=hidden name=order_by value=<?php echo $order_by_user; ?> />
</form>

<?php


// Affichage du tableau
echo "<table border=1 cellpadding=3>";
echo "<tr>";
// ecgo "<td><p class=small><b><a href='admin_user_index.php?order_by_user=login&amp;display=$display'>Nom de login</a></b></p></td>";
echo "<td><p class=small><b><a href='admin_user_index.php?order_by_user=nom,prenom&amp;display=$display'>Nom et prénom</a></b></p></td>";
//echo "<td><p class=small><b><a href='admin_user_index.php?order_by_user=statut,nom,prenom&amp;display=$display'>Statut</a></b></p></td>";
echo "<td><p class=small><b>matière(s)</b></p></td>";
if ($is_LP2I) echo "<td><p class=small><b>Nb. séquences enseignement \"PRE-BAC\"</b></p></td>";
if ($is_LP2I) echo "<td><p class=small><b>Nb. séquences enseignement \"POST-BAC\"</b></p></td>";
if ($is_LP2I) echo "<td><p class=small><b>Nb. séquences sous-service (non utilisé par ailleurs)</b></p></td>";
if ($is_LP2I) echo "<td><p class=small><b>Nb. absences justifiées pour les séances type BAS</b></p></td>";
if ($is_LP2I) echo "<td><p class=small><b>Nb. resp. ACF</b></p></td>";
if ($is_LP2I) echo "<td><p class=small><b>Nb. co-resp. ACF</b></p></td>";
if ($is_LP2I) echo "<td><p class=small><b>Nb. jury ACF</b></p></td>";
if ($is_LP2I) echo "<td><p class=small><b>Nb. moyen séq. BAS dues (\"PRE-BAC\")</b></p></td>";
if ($is_LP2I) echo "<td><p class=small><b>Nb. moyen séq. BAS théoriquement effectuées</b></p></td>";
echo "<td><p class=small><b>-</b></p></td>";
echo "</tr>";
$calldata = mysql_query("SELECT * FROM utilisateurs where (statut='professeur' or statut='cpe') ORDER BY $order_by_user");
$nombreligne = mysql_num_rows($calldata);
$i = 0;

// Nombre de bas archivés :
$nb_bas_clos = sql_query1("select count(bas_passee) from bas_bas where bas_passee='y' and type_bas='n'");
// Nombre de bas :
$reqbas = sql_query1("select count(id_bas) from bas_bas where type_bas='n'");

while ($i < $nombreligne){
    $user_login = mysql_result($calldata, $i, "login");
    $user_nom = sql_query1("select nom from utilisateurs where login='".$user_login."'");
    $user_prenom = sql_query1("select prenom from utilisateurs where login='".$user_login."'");
    $user_statut = sql_query1("select statut from utilisateurs where login='".$user_login."'");
    $nb_jury = sql_query1("select nb_jury from bas_utilisateurs where login='".$user_login."'");
    if ($nb_jury == -1) $nb_jury = 0;
    $user_service = sql_query1("select service from bas_utilisateurs where login='".$user_login."'");
    if ($user_service == -1) $user_service = "<font color='red'>ND</font>";
    $user_service_pb = sql_query1("select service_pb from bas_utilisateurs where login='".$user_login."'");
    if ($user_service_pb == -1) $user_service_pb = "<font color='red'>ND</font>";

    $user_sous_service = sql_query1("select sous_service from bas_utilisateurs where login='".$user_login."'");
    if ($user_sous_service == -1) $user_sous_service = "<font color='red'>ND</font>";
    $nb_abs = sql_query1("select count(id_professeur) from bas_j_professeurs_absences where id_professeur='".$user_login."'");
    $nb_abs_past = sql_query1("select count(bjpa.id_professeur) from bas_j_professeurs_absences bjpa, bas_bas bb
    where
    bjpa.id_professeur='".$user_login."' and
    bb.bas_passee ='y' and
    bb.id_bas=bjpa.id_bas
    ");
    $resp_acf1  = sql_query1("select count(id_utilisateur) from j_aid_utilisateurs where indice_aid = '".getSettingValue("active_acf_num_aid")."' and id_utilisateur='".$user_login."' and ordre='1'");
    $resp_acf2  = sql_query1("select count(id_utilisateur) from j_aid_utilisateurs where indice_aid = '".getSettingValue("active_acf_num_aid")."' and id_utilisateur='".$user_login."' and ordre='2'");
    $user_etat[$i] = sql_query1("select etat from utilisateurs where login='".$user_login."'");
    // Affichage des login, noms et prénoms
    $col[$i][1] = $user_login;
    $col[$i][2] = "$user_nom $user_prenom";

    $call_matieres = mysql_query("SELECT * FROM bas_j_matieres_profs j WHERE j.id_professeur = '$user_login' ORDER BY id_matiere");
    $nb_mat = mysql_num_rows($call_matieres);
    $k = 0;
    while ($k < $nb_mat) {
        $user_matiere[$k] = mysql_result($call_matieres, $k, "id_matiere");
        $user_matiere_nom_complet[$k] = sql_query1("SELECT nom_complet FROM bas_matieres WHERE matiere='".$user_matiere[$k]."'");
        $k++;
    }

    // Affichage du statut
    $col[$i][3]=$user_statut;
    if ($user_statut == "administrateur") { $color_='red';}
    if ($user_statut == "secours") { $color_='red';}
    if ($user_statut == "professeur") { $color_='green'; }
    if ($user_statut != "administrateur" AND $user_statut != "professeur" AND $user_statut != "secours") { $color_='blue';}
    $col[$i][3] = "<font color=".$color_.">".$col[$i][3]."</font>";

    if ($user_etat[$i]=='inactif') $col[$i][3] .= '<br />(inactif)';

    // Affichage des enseignements
    $k = 0;
    $col[$i][4] = '';
    while ($k < $nb_mat) {
        $col[$i][4]=$col[$i][4]." $user_matiere_nom_complet[$k] ($user_matiere[$k]) - ";
        $k++;
    }
    if ($nb_mat == 0) $col[$i][4] = "<font color='red'>Hors Ateliers</font>";
    if ($col[$i][4]=='') {$col[$i][4] = "&nbsp;";}
    $col[$i][5]=$user_service;
    $col[$i][6]=$user_service_pb;
    $col[$i][7]=$user_sous_service;
    $col[$i][8]=$nb_abs;
    $col[$i][9]=$resp_acf1;
    $col[$i][10]=$resp_acf2;

    //Nb. séquences dépensées en responsabilité ACF
    if ($resp_acf1 > 1) {
        $resp_acf1_pris_en_compte = 1;
        $col[$i][9].=" (2&nbsp;pris&nbsp;en&nbsp;compte)";
    } else
        $resp_acf1_pris_en_compte = $resp_acf1;
    //Nb. séquences dépensées en co-responsabilité ACF
    if (($resp_acf2 >= 1) and ($resp_acf1 >= 1)) {
        $resp_acf2_pris_en_compte = 0;
        $col[$i][10].=" (0&nbsp;pris&nbsp;en&nbsp;compte)";
    } else if ($resp_acf2 > 1) {
        $resp_acf2_pris_en_compte = 1;
        $col[$i][10].=" (1&nbsp;pris&nbsp;en&nbsp;compte)";
    } else
        $resp_acf2_pris_en_compte = $resp_acf2;
        
        
    $col[$i][11]=$nb_jury;
    if ($nb_jury > 1)
        $nb_jury_pris_en_compte = 1;
    else
        $nb_jury_pris_en_compte = $nb_jury;
//$nb_jury_pris_en_compte = $nb_jury - max(($nb_jury + $resp_acf1_pris_en_compte - 2),0);
    if ($nb_jury_pris_en_compte < $nb_jury)
        $col[$i][11].=" (".$nb_jury_pris_en_compte."&nbsp;pris&nbsp;en&nbsp;compte)";
    if ($user_etat[$i] == 'actif') {
        $bgcolor = '#E9E9E4';
    } else {
        $bgcolor = '#A9A9A9';
    }

    if ($col[$i][4] == "<font color='red'>Hors Ateliers</font>") {
        $bgcolor = '#A9A9A9';
    }

    if (($col[$i][4] != "<font color='red'>Hors Ateliers</font>") and (($display == 'tous') or ($display == 'bas'))) {
        $affiche = 'yes';
    } else if (($col[$i][4] == "<font color='red'>Hors Ateliers</font>") and (($display == 'tous') or ($display == 'horsbas'))) {
        $affiche = 'yes';
    } else {
        $affiche = 'no';
    }
    if ($affiche == 'yes') {
        if ($is_LP2I) {
          // Calcul du nombre total de bas dus

          // Nombre de séquences (50 minutes) dues par an
          $total1 = $col[$i][5]*5*getSettingValue("bas_nb_semaines")/50;
          //Nb. séquences (50 min.) dues par an induit par les sous-services
          $total2 = $col[$i][7]*getSettingValue("bas_nb_semaines")*55/50;
          //Nb. séquences dépensées en responsabilité ACF
          $total3 = getSettingValue("bas_cout_resp_acf_prof")*getSettingValue("bas_nb_journees_acf")*$resp_acf1_pris_en_compte*4;
          //Nb. séquences dépensées en co-responsabilité ACF
          $total4 = getSettingValue("bas_cout_resp_acf_prof2")*getSettingValue("bas_nb_journees_acf")*$resp_acf2_pris_en_compte*4;
          //Nb. séquences dépensées en participation à des jury ACF
          $total5 = getSettingValue("bas_cout_jury_acf_prof")*$nb_jury_pris_en_compte;
          //Nb séquences 50 minutes à effectuer dans l'année
          $total_restant_du = $total1 + $total2 - $total3 -$total4 - $total5;
          //Nb théorique moyen de séquences dues par BAS
          $bas_nb_journees_bas = sql_query1("select count(id_bas) from bas_bas where type_bas='n'");
          $total_moyenne_bas_theorique_dus = max (0,round(($total_restant_du/getSettingValue("bas_cout_bas_prof")) / $bas_nb_journees_bas,1));
          // Nb de bas effectués
          $total_bas_effectues = sql_query1("SELECT SUM(duree)
          FROM bas_propositions bp, bas_bas bb
          where (
          (bp.responsable = '".$col[$i][1]."' or
          bp.coresponsable = '".$col[$i][1]."') and
          bp.statut != 'a' and
          bp.num_bas = bb.id_bas and
          bb.bas_passee = 'y'
          )");
          if ($total_bas_effectues == -1) $total_bas_effectues = 0;
          // On tient compte des absences justifiées
          $total_bas_effectues += $nb_abs_past*min(NB_SEQ_BAS_PAR_AM,$total_moyenne_bas_theorique_dus);
    
          // Nb moyen de bas effectués
          if ($nb_bas_clos!=0) {
              $total_moyen_bas_effectues = round($total_bas_effectues / $nb_bas_clos,1);
              if ($total_moyen_bas_effectues < 0) $total_moyen_bas_effectues = 0;
              if (($total_moyen_bas_effectues > 0.90*min($total_moyenne_bas_theorique_dus,3)) or ($total_moyenne_bas_theorique_dus==0))
                  $bgcolor2 = "#D4FF52";
              else
                  $bgcolor2 = "#FF989A";
          } else {
              $total_moyen_bas_effectues = "-";
              $bgcolor2 = "#999999";
          }   
          if ($nb_abs > 0)  $bgcolor3 = "#FF989A"; else $bgcolor3 = $bgcolor;
        }
        echo "<tr>";
        echo "<td bgcolor='$bgcolor'><p class=small><span class=bold><a href='admin_user_modify.php?user_login=$user_login'>{$col[$i][2]}</a></span></p></td>";
        echo "<td bgcolor='$bgcolor'><p class=small><span class=bold>{$col[$i][4]}</span></p></td>";
        if ($is_LP2I) echo "<td bgcolor='$bgcolor'><p class=small><span class=bold>{$col[$i][5]}</span></p></td>";
        if ($is_LP2I) echo "<td bgcolor='$bgcolor'><p class=small><span class=bold>{$col[$i][6]}</span></p></td>";
        if ($is_LP2I) echo "<td bgcolor='$bgcolor3'><p class=small><span class=bold>{$col[$i][7]}</span></p></td>";
        if ($is_LP2I) echo "<td bgcolor='$bgcolor'><p class=small><span class=bold>{$col[$i][8]}</span></p></td>";
        if ($is_LP2I) echo "<td bgcolor='$bgcolor'><p class=small><span class=bold>{$col[$i][9]}</span></p></td>";
        if ($is_LP2I) echo "<td bgcolor='$bgcolor'><p class=small><span class=bold>{$col[$i][10]}</span></p></td>";
        if ($is_LP2I) echo "<td bgcolor='$bgcolor'><p class=small><span class=bold>{$col[$i][11]}</span></p></td>";
        if ($is_LP2I) {
        echo "<td bgcolor='$bgcolor2' title=\"soit un total de ".$total_restant_du." séquences 50 minutes dues\"><p class=small><span class=bold>".$total_moyenne_bas_theorique_dus;
        if ($total_moyenne_bas_theorique_dus > NB_SEQ_BAS_PAR_AM) echo " (".NB_SEQ_BAS_PAR_AM." en pratique)";
        echo "</span></p></td>";
        }
        if ($is_LP2I) {
        echo "<td bgcolor='$bgcolor2' title=\"soit un total de ".$total_bas_effectues." séquences théoriquement effectuées\"><p class=small><span class=bold>$total_moyen_bas_effectues";
        if ($nb_abs > 0) echo " *";
        echo " - <a href='admin_user_details.php?user_login={$col[$i][1]}'>détails</a></span></p></td>";
        }
        // Affichage du lien 'supprimer'
        if ($nb_mat != 0)
           echo "<td bgcolor='$bgcolor'><p class=small><span class=bold><a href='admin_user_index.php?id_user={$col[$i][1]}&amp;action=del_utilisateur' onclick=\"return confirmlink(this, 'La suppression d\'un utilisateur est irréversible. Une telle suppression ne devrait pas avoir lieu en cours d\'année. Si c\'est le cas, cela peut entraîner la présence de données orphelines dans la base. Etes-vous sûr de vouloir continuer ?', 'Confirmation de la suppression')\">Sortir des Ateliers</a></span></p></td>";
        else
           echo "<td bgcolor='$bgcolor'><p class=small><span class=bold> - </span></p></td>";
        // Fin de la ligne courante
        echo "</tr>";
    }
    $i++;
}
echo "</table>";
?>
</div></body>
</html>