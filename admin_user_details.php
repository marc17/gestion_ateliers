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
$nom_script = "mod_plugins/gestion_ateliers/admin_user_details.php";
if (!checkAccess_Plugin($nom_script)) {
    header("Location: ../../logout.php?auto=1");
    die();
}
// On vérifie que l'utilisateur a les droits spécifiques pour accéder à ce script
if (!calcul_autorisation_gestion_ateliers($_SESSION['login'],$nom_script)){
    header("Location: ../../logout.php?auto=1");
    die();
}

if (!isset($_GET['user_login'])) {
   $_GET['user_login'] = $_SESSION['login'];
} else if (mb_strtolower($_GET['user_login']) != mb_strtolower($_SESSION['login'])) {
  // On vérifie que l'utilisateur peut accéder à d'autres fiches que la sienne
  $test = sql_query1("select count(acces) from  bas_gestion_acces_scripts where acces='".$_SESSION['login']."' and script ='".$nom_script."'");
  if ($test < 1) {
    header("Location: ../../logout.php?auto=1");
    die();
  }
}
// On appelle les informations de l'utilisateur pour les afficher :
$call_user_info = mysql_query("SELECT * FROM utilisateurs WHERE login='".$_GET['user_login']."'");
$nb_jury = sql_query1("select nb_jury from bas_utilisateurs WHERE login='".$_GET['user_login']."'");
if ($nb_jury == -1) $nb_jury = "0";
$service = sql_query1("select service from bas_utilisateurs WHERE login='".$_GET['user_login']."'");
if ($service == -1) {$texte_service = "Néant";$service=0;}  else $texte_service = $service;
$service_pb = sql_query1("select service_pb from bas_utilisateurs WHERE login='".$_GET['user_login']."'");
if ($service_pb == -1) {$texte_service_pb = "Néant";$service_pb=0;}  else $texte_service_pb = $service_pb;

$sous_service = sql_query1("select sous_service from bas_utilisateurs WHERE login='".$_GET['user_login']."'");
if ($sous_service == -1) {$texte_sous_service = "Néant";$sous_service=0;} else $texte_sous_service = $sous_service;
$user_nom = @mysql_result($call_user_info, "0", "nom");
$user_prenom = @mysql_result($call_user_info, "0", "prenom");
$user_email = @mysql_result($call_user_info, "0", "email");
$resp_acf1  = sql_query1("select count(id_utilisateur) from j_aid_utilisateurs where indice_aid = '".getSettingValue("active_acf_num_aid")."' and id_utilisateur='".$_GET['user_login']."' and ordre='1'");
$resp_acf2  = sql_query1("select count(id_utilisateur) from j_aid_utilisateurs where indice_aid = '".getSettingValue("active_acf_num_aid")."' and id_utilisateur='".$_GET['user_login']."' and ordre='2'");
$call_matieres = mysql_query("SELECT * FROM bas_j_matieres_profs j WHERE j.id_professeur = '".$_GET['user_login']."' ORDER BY id_matiere");
$nb_mat = mysql_num_rows($call_matieres);                                   
$k = 0;
$list_matiere = "";
while ($k < $nb_mat) {
    $user_matiere = mysql_result($call_matieres, $k, "id_matiere");
    $user_matiere_nom_complet = sql_query1("SELECT nom_complet FROM bas_matieres WHERE matiere='".$user_matiere."'");
    $list_matiere .= $user_matiere_nom_complet;
    if ($k < $nb_mat-1) $list_matiere .= ", ";
    $k++;
}
// Nombre de bas pour lesquels la collecte des propositions est close :
$nb_bas_clos = sql_query1("select count(bas_passee) from bas_bas where bas_passee='y' and type_bas='n'");
// Nombre de séquences (50 minutes) dues par an
$total1 = $service*5*getSettingValue("bas_nb_semaines")/50;
$total_bts = $service_pb*5*getSettingValue("bas_nb_semaines")/50;
//Nb. séquences (50 min.) dues par an induit par les sous-services
$total2 = $sous_service*getSettingValue("bas_nb_semaines")*55/50;
//Nb. séquences dépensées en responsabilité ACF
if ($resp_acf1 > 1)
    $resp_acf1_pris_en_compte = 1;
else
    $resp_acf1_pris_en_compte = $resp_acf1;
//Nb. séquences dépensées en co-responsabilité ACF
if ($resp_acf1 >= 1)
    $resp_acf2_pris_en_compte = 0;
else if ($resp_acf2 > 1)
    $resp_acf2_pris_en_compte = 1;
else
    $resp_acf2_pris_en_compte = $resp_acf2;

$total3 = getSettingValue("bas_cout_resp_acf_prof")*getSettingValue("bas_nb_journees_acf")*$resp_acf1_pris_en_compte *4;
$total4 = getSettingValue("bas_cout_resp_acf_prof2")*getSettingValue("bas_nb_journees_acf")*$resp_acf2_pris_en_compte *4;

//Nb. séquences dépensées en participation à des jury ACF
//Nb. séquences dépensées en responsabilité ACF
if ($nb_jury > 1)
    $nb_jury_pris_en_compte = 1;
else
    $nb_jury_pris_en_compte = $nb_jury;
// $nb_jury_pris_en_compte = $nb_jury - max(($nb_jury + $resp_acf1_pris_en_compte - 2),0);
$total5 = getSettingValue("bas_cout_jury_acf_prof")*$nb_jury_pris_en_compte;

//Nb séquences 50 minutes à effectuer dans l'année
$total_du = $total1 + $total2;
if ($total_du < 0)
    $texte_total_du = "Néant";
else
    $texte_total_du = $total_du;


//Nb séquences 50 minutes restant (BAS et autres) 
$total_restant_du = $total1 + $total2 - $total3 - $total4 - $total5;
if ($total_restant_du < 0)
    $texte_total_restant_du = "Néant";
else
    $texte_total_restant_du = $total_restant_du;
//Nb théorique moyen de séquences dues par BAS
$bas_nb_journees_bas = sql_query1("select count(id_bas) from bas_bas where type_bas='n'");
$total_moyenne_bas_theorique_dus = max (0,round(($total_restant_du/getSettingValue("bas_cout_bas_prof")) / $bas_nb_journees_bas,1));

// Nb de bas effectués
$total_bas_effectues = sql_query1("SELECT SUM(duree)
FROM bas_propositions bp, bas_bas bb
   where (
   (bp.responsable = '".$_GET['user_login']."' or
    bp.coresponsable = '".$_GET['user_login']."') and
    bp.statut != 'a' and
    bp.num_bas = bb.id_bas and
    bb.bas_passee = 'y'
    )");
if ($total_bas_effectues == -1) $total_bas_effectues = 0;
// Calcul des absences :

$nb_abs = sql_query1("select count(id_professeur) from bas_j_professeurs_absences where id_professeur='".$_GET['user_login']."'");
$nb_abs_past = sql_query1("select count(bjpa.id_professeur) from bas_j_professeurs_absences bjpa, bas_bas bb
    where
    bjpa.id_professeur='".$_GET['user_login']."' and
    bb.bas_passee ='y' and
    bb.id_bas=bjpa.id_bas
    ");
// On tient compte des absences justifiées
$total_bas_effectues += $nb_abs_past*min(NB_SEQ_BAS_PAR_AM,$total_moyenne_bas_theorique_dus);
if ($nb_abs_past > 0) {
    $texte_1 = "Equivalent théorique du nombre moyen de séquences déjà effectuées (compte-tenu des absences) : ";
    $texte_2 = "Equivalent théorique du nombre séquences déjà effectuées (compte-tenu des absences) : ";
} else {
    $texte_1 = "Nombre moyen de séquences déjà effectuées : ";
    $texte_2 = "Nombre de séquences déjà effectuées : ";
}    
    
// Nb moyen de bas effectués
if ($nb_bas_clos!=0)
  $total_moyen_bas_effectues = round($total_bas_effectues / $nb_bas_clos,1);
else
  $total_moyen_bas_effectues = 0;
if ($total_moyen_bas_effectues < 0) $total_moyen_bas_effectues = 0;


//**************** EN-TETE *****************************
$titre_page = "Informations personnelles et détails des propositions BAS de ".$user_nom." ".$user_prenom;
require_once("../../lib/header.inc.php");
//**************** FIN EN-TETE *****************

if (isset($_GET['admin']))
    echo "<p class=bold> |<a href=\"./admin_user_index.php\">Retour</a> |</p>";
else
    echo "<p class=bold> |<a href=\"../../accueil.php\">Retour</a> |</p>";
    
echo "<h2>Informations personnelles</h2>";
if (getSettingValue("fiche_prof")!='y') {
  echo "<p>Le module n'est pas encore activé, informations non disponibles !</p>";
  echo "</body></html>";
  die();
}   


echo "<div class = \"norme\">\n";
echo "<table border=\"1\" cellpadding=\"3\">";
echo "<tr><td>Nom : </td><td><b>".$user_nom."</b></td></tr>";
echo "<tr><td>Prénom : </td><td><b>".$user_prenom."</b></td></tr>";
echo "<tr><td>Identifiant : </td><td><b>".$_GET['user_login']."</b></td></tr>";
echo "<tr><td>Email : </td><td><b>".$user_email."</b></td></tr>";
echo "<tr><td>Nombre de séquences d'enseignement effectif dans le second degré : </td>
<td><b>".$texte_service."</b></td></tr>";
echo "<tr><td>Nombre de séquences d'enseignement effectif en classe de BTS : </td>
<td><b>".$texte_service_pb."</b></td></tr>";
echo "<tr><td>Nombre de séquences de sous-service effectif : </td>
<td><b>".$texte_sous_service."</b></td></tr>";
echo "<tr><td>Nombre de participation à des jurys ACF : </td>
<td><b>".$nb_jury."</b></td></tr>";
echo "<tr><td>Nombre d'ACF en responsabilité : </td><td><b>".$resp_acf1."</b></td></tr>";
echo "<tr><td>Nombre d'ACF en co-responsabilité : </td><td><b>".$resp_acf2."</b></td></tr>";
echo "<tr><td>Discipline(s) affectée(s) (pour les propositions BAS) : </td><td><b>".$list_matiere."</b></td></tr>";
if ($nb_abs > 0) {
    $liste_absence = "";
    $req_absence = sql_query("select * from bas_j_professeurs_absences where id_professeur='".$_GET['user_login']."'");
    $nb_query = sql_count($req_absence);
    $k = 0;
    while ($k < $nb_query) {
        $id_bas = mysql_result($req_absence,$k,"id_bas");
        $nom_bas = sql_query1("select nom from bas_bas where id_bas='".$id_bas."'");
        $liste_absence .= $nom_bas;
        if ($k < $nb_query-1) $liste_absence .= " - ";
        $k++;
    }
    echo "<tr><td>Absence justifiée pour les BAS suivants :</td>
    <td>".$liste_absence."</td></tr>";

}



echo "</table>";

echo "<br /><table border=\"1\" cellpadding=\"3\">";
echo "<tr><td>Nombre total de séquences de 50 minutes à effectuer dans l'année ";
if ($total_bts>0) echo " (dans le cadre du service hors-BTS) ";
echo ": </td><td><b>".$texte_total_du."</b></td></tr>";
if ($total_bts>0)
  echo "<tr><td>Nombre total de séquences de 50 minutes à effectuer dans l'année (dans le cadre du service en BTS) : </td><td><b>".$total_bts."</b></td></tr>";

if ($total3>0)
  echo "<tr><td>Nombre total de séquences ACF décomptées (responsabilité) : </td><td><b>".$total3."</b></td></tr>";
if ($total4>0)
  echo "<tr><td>Nombre total de séquences ACF décomptées (co-responsabilité) : </td><td><b>".$total4."</b></td></tr>";
if ($total5>0)
  echo "<tr><td>Nombre total de séquences Jury d'ACF décomptées : </td><td><b>".$total5."</b></td></tr>";


if (($total_restant_du>0) or ($total_bts>0)) {
  echo "<tr><td>Nombre total de séquences de 50 minutes restant dues (BAS, surveillances, ...) : </td>";
  if ($total_bts<=0) {
    echo "<td><b>".($total_restant_du)."</b></td></tr>";
  } else {
    echo "<td><b>".$total_restant_du. " (hors-BTS)<br />".$total_bts." (BTS)</b></td></tr>";
  }
}
if ($total_restant_du > 0) {
    echo "<tr><td>Equivalent en nombre de séquences BAS ";
    if ($total_bts > 0) echo " (hors-BTS) ";
    echo ": </td><td><b>".round(($total_restant_du/getSettingValue("bas_cout_bas_prof")))."</b></td></tr>";

  if ($total_moyenne_bas_theorique_dus > NB_SEQ_BAS_PAR_AM) {
    echo "<tr><td>Nombre moyen théorique de séquences à effectuer par BAS ";
    if ($total_bts > 0) echo " (hors-BTS) ";
     echo ": </td><td><b>".$total_moyenne_bas_theorique_dus."</b>, soit en pratique, <b>".NB_SEQ_BAS_PAR_AM."</b> séquences à effectuer.</td></tr>";
  } else {
    echo "<tr><td>Nombre moyen de séquences à effectuer par BAS ";
    if ($total_bts > 0) echo " (hors-BTS) ";
    echo ": </td><td><b>".$total_moyenne_bas_theorique_dus."</b></td></tr>";
  }
  echo "<tr><td>".$texte_1."</td><td><b>".$total_moyen_bas_effectues."</b></td></tr>";
  echo "<tr><td>".$texte_2."</td><td><b>".$total_bas_effectues."</b></td></tr>";
}
echo "</table>";
echo "Pour que le dispositif mis en place soit viable et efficace, chaque professeur est tenu de proposer dans la mesure du possible, des activités tout au long de l'année, de façon régulière.";
// if ($total_moyen_bas_effectues < 0.90*min($total_moyenne_bas_theorique_dus,NB_SEQ_BAS_PAR_AM))
    // echo "<p><font color='red'><b>Il vous reste Sauf erreur de calcul, vous avez un déficit de ".($nb_bas_clos*min($total_moyenne_bas_theorique_dus,2) -$total_bas_effectues)." séquences BAS.</b></font></p>";
if (($total_restant_du+$total_bts-($total_bas_effectues)*getSettingValue("bas_cout_bas_prof")  )>0 )
    echo "<p><font color='red'><b>Il vous reste ".($total_restant_du+$total_bts-($total_bas_effectues)*getSettingValue("bas_cout_bas_prof")  )." séquences de 50 minutes à effectuer, soit un équivalent de ".(round((($total_restant_du+$total_bts)/getSettingValue("bas_cout_bas_prof")))-$total_bas_effectues)." séquences BAS à effectuer.</b></font></p>";
echo "</div><br />";

// Affichage du tableau des propositions
$calldata = mysql_query("SELECT * FROM bas_propositions
where responsable = '".$_GET['user_login']."' or coresponsable = '".$_GET['user_login']."'
order by num_bas");
$nombreligne = mysql_num_rows($calldata);
if ($nombreligne > 0) {
    echo "<h2>Détails des propositions BAS</h2>";
    echo "<table width = 100% cellpadding=3 border=1>";
    echo "<tr>";
    echo "<td>N° Bas</td>\n";
    echo "<td>N°<br /><i>Matière</i></td>\n";
    echo "<td>Type</td>\n";
    echo "<td>Intitulé de l'activité</td>";
    echo "<td>Brève description</td>";
    echo "<td>Heure</td>";
    echo "<td>Public</td>";
    echo "<td>Animateur(s)</td>";
    echo "<td>Nb. max.<br />élèves</td>";
    echo "<td>Nb. élèves</td>";
    echo "<td>Durée</td>";
    echo "<td>Salle</td>";
    echo "</tr>";

    $i = 0;
    while ($i < $nombreligne){
        $numero_bas = @mysql_result($calldata, $i, "num_bas");
        $description_bas = sql_query1("select description_bas from bas_bas where id_bas='".$numero_bas."'");
        // Constitution du tableau $per
        $per =  tableau_periode($numero_bas);
        $bas_statut = @mysql_result($calldata, $i, "statut");
        $bas_id_prop = @mysql_result($calldata, $i, "id_prop");
        $bas_type = @mysql_result($calldata, $i, "type");
        if ($bas_type == "S") {
            $bas_type = "<img src=\"./images/s.gif\" alt=\"Soutien\" border=\"0\" title=\"Soutien\" />";
        } else if ($bas_type == "A") {
            $bas_type = "<img src=\"./images/a.gif\" alt=\"Approfondissement\" border=\"0\" title=\"Approfondissement\" />";
        } else if ($bas_type == "R") {
            $bas_type = "<img src=\"./images/r.gif\" alt=\"Remédiation\" border=\"0\" title=\"Remediation\" />";
        } else if ($bas_type == "D") {
            $bas_type = "<img src=\"./images/d.gif\" alt=\"Remédiation\" border=\"0\" title=\"Public désigné\" />";
        } else  {
            $bas_type = "-";
        }
        $bas_titre = @mysql_result($calldata, $i, "titre");
        $bas_precisions = @mysql_result($calldata, $i, "precisions");
        $bas_debut_final = @mysql_result($calldata, $i, "debut_final");
        if ($bas_precisions == '') $bas_precisions = "&nbsp;";
        $k=1;
        while ($k<NB_FILIERES+1) {
          $temp = "public_".$k;
          $$temp = mysql_result($calldata, $i, "public_".$k);
          $k++;
        }
        $public = '';
        $n=1;
        while ($n<NB_NIVEAUX_FILIERES+1) {
          $flag=1;
          $pub="";
          foreach($tab_filière[$n]["id"] as $key => $_id){
              $temp = "public_".$_id;
              if ($$temp=='') $flag=0;
              if ($$temp!='') $pub .= $tab_filière[$n]["nom"][$key]."<br />"; 
          }
          if ($flag==1) {
              $public .= $intitule_filiere[$n]."<br />";
          } else {
              $public .=$pub;
          }
          $n++;        
        }
        $bas_matiere = mysql_result($calldata, $i, "id_matiere");
        $nom_matiere_prop = sql_query1("select nom_complet from bas_matieres where matiere = '".$bas_matiere."'");
        $debut_sequence = @mysql_result($calldata, $i, "debut_sequence");
        $bas_responsable = @mysql_result($calldata, $i, "responsable");
        if ($bas_responsable == '')
            $bas_responsable = "<font color=\"#FF0000\">*** A DEFINIR ***</font>";
        else {
            $nom_prof = sql_query1("select nom from utilisateurs where login='".$bas_responsable."'");
            $prenom_prof = sql_query1("select prenom from utilisateurs where login='".$bas_responsable."'");
            $email_prof = sql_query1("select email from utilisateurs where login='".$bas_responsable."'");
            if (($nom_prof != -1) and ($prenom_prof != -1)) $bas_responsable = $nom_prof." ".$prenom_prof;
        }
        $bas_coresponsable = @mysql_result($calldata, $i, "coresponsable");
        $nom_prof_b = sql_query1("select nom from utilisateurs where login='".$bas_coresponsable."'");
        $prenom_prof_b = sql_query1("select prenom from utilisateurs where login='".$bas_coresponsable."'");
        if (($nom_prof_b != -1) and ($prenom_prof_b != -1)) $bas_coresponsable = $nom_prof_b." ".$prenom_prof_b;
        $bas_duree = @mysql_result($calldata, $i, "duree");
        $bas_salle = @mysql_result($calldata, $i, "salle");
        if ($bas_salle=='') $bas_salle= '-';
        $bas_nb_max = @mysql_result($calldata, $i, "nb_max");
        if ($bas_nb_max == 0) $bas_nb_max_titre = "-"; else $bas_nb_max_titre = $bas_nb_max;
        $id_bas = @mysql_result($calldata, $i, "id_bas");
        // Calcul du nombre d'inscrits
        $nb_inscrit_0 = sql_count(sql_query("select id_eleve from bas_j_eleves_bas where
        num_bas = '".$numero_bas."' and
        id_bas = '".$id_bas."' and
        num_choix='0' and
        num_sequence='".$bas_debut_final."'
        "));
        $nb_inscrit_0 = "<td><b>".$nb_inscrit_0."</b></td>";

        if ($bas_statut == 'a') {
            $bas_salle = "<font color='red'><b>Annulé</b></font>";
            echo "<tr bgcolor=\"#C0C0C0\">";
        } else
            echo "<tr>";
        echo "<td><span class='small'><b>N° $numero_bas</b></span></td>";
        echo "<td><span class='small'>$bas_id_prop<br /><i>$nom_matiere_prop</i>";
        echo "<br />[<a href='index_inscrits.php?numero_bas=$numero_bas&amp;id_bas=$id_bas' title='Afficher la liste des élèves inscrits' target='_blank'><b>Liste&nbsp;élèves</b></a>";
        echo "]\n";
        echo "</span></td>\n";
        echo "<td>$bas_type</td>\n";
        echo "<td><b>$bas_titre</b></td>\n";
        echo "<td>$bas_precisions</td>\n";
        if ($bas_statut == 'a')
            echo "<td><font color='red'><b>Annulé</b></font></td>";
        else if (isset($per[$bas_debut_final]))
            echo "<td>".$per[$bas_debut_final]."</td>\n";
        else
            echo "<td>-</td>\n";
        echo "<td>$public</td>\n";
        if ($bas_coresponsable != '') $responsables = $bas_responsable."<br />".$bas_coresponsable; else $responsables = $bas_responsable;
        echo "<td>$responsables</td>\n";
        echo "<td>$bas_nb_max_titre</td>\n";
        echo $nb_inscrit_0;
        echo "<td>".$bas_duree." séq.</td>\n";
        echo "<td>$bas_salle</td>\n";
        echo "</tr>";
        $i++;
    }
    echo "</table>";
}

echo "<hr /><h2>Détails des calculs</h2>";

echo "<H3>Service \"PRE-BAC\"</H3>";
echo "Nombre de séquences de cours hebdomadaires figurant sur votre V.S. et effectuées en classe de seconde, première ou terminale : <b>".$service."</b>\n";
if (($service > 0) or ($total_bts > 0)) {
  echo "<br />Chaque séquence équivaut normalement à 55 minutes et se déroule sur ".getSettingValue("bas_nb_semaines")." semaines.";
  echo "<br />La formule suivante permet de calculer le \"nombre de séquences de 50 minutes dues par an\" :";
  echo "<br /><b>Total1 = (".$service."* 5 minutes * ".getSettingValue("bas_nb_semaines")." semaines)/(50 minutes)</b>";
  if ($total_bts==0) {
    echo "<br /><br />Dans votre cas, le résultat est : <b>". $total1. " séquence(s) due(s).</b>";
  } else {
    echo "<br /><br />Dans votre cas, le résultat est : <b>". $total1. " séquence(s) due(s) (hors-BTS) et ". $total_bts. " séquence(s) due(s) (BTS)</b>";  
  }
}
echo "<H3>Service \"POST-BAC\"</H3>";
echo "Nombre de séquences de cours hebdomadaires figurant sur votre V.S. et effectuées en classe de BTS : <b>".$service_pb."</b>\n";
if ($service_pb > 0) {
  echo "<br />Chaque séquence équivaut normalement à 55 minutes et se déroule sur ".getSettingValue("bas_nb_semaines")." semaines.";
  echo "<br />La formule suivante permet de calculer le \"nombre de séquences de 50 minutes dues par an\" :";
  echo "<br /><b>Total_BTS = (".$service_pb."* 5 minutes * ".getSettingValue("bas_nb_semaines")." semaines)/(50 minutes)</b>";
  echo "<br /><br />Dans votre cas, le résultat est : <b>". $total_bts. " séquence(s) due(s).</b>";
}
echo "<H3>Sous-service</H3>";
echo "Nombre de séquences de sous-service hebdomadaires : <b>".$sous_service."</b>\n";
if ($sous_service > 0) {
  echo "<br />La formule suivante permet de calculer le \"nombre de séquences de 50 minutes dues par an\" au titre de ce sous-service :";
  echo "<br /><b>Total2 = (".$sous_service." * ".getSettingValue("bas_nb_semaines")." * 55) / 50</b>";
  echo "<br /><br />Dans votre cas, le résultat est <b>: ". $total2. " séquence(s) due(s).</b>";
}
echo "<H3>ACF</H3>";
echo "Nombre d'ACF en responsabilité : <b>".$resp_acf1."</b>";
echo "<br />Nombre d'ACF en co-responsabilité : <b>".$resp_acf2."</b>";
if ($resp_acf1_pris_en_compte < $resp_acf1)
echo "<br />Nombre d'ACF en responsabilité, pris en compte dans le calcul : <b>".$resp_acf1_pris_en_compte."</b>";
if ($resp_acf2_pris_en_compte < $resp_acf2)
echo "<br />Nombre d'ACF en co-responsabilité, pris en compte dans le calcul : <b>".$resp_acf2_pris_en_compte."</b>";
echo "<br />Par ailleurs :";
if ($resp_acf1_pris_en_compte>0)
  echo "<br />- Coût séquence d'une séquence ACF par professeur (en responsabilité) : <b>".getSettingValue("bas_cout_resp_acf_prof")."</b>";
if ($resp_acf2_pris_en_compte>0)
  echo "<br />- Coût séquence d'une séquence ACF par professeur (en co-responsabilité) : <b>".getSettingValue("bas_cout_resp_acf_prof2")."</b>";
echo "<br />- Nombre de séquences par demi-journée ACF : <b>4</b>
<br />- Nombre de demi-journées ACF dans l'année : <b>".getSettingValue("bas_nb_journees_acf")."</b>";
echo "<br />Le nombre de séquences dépensées est donc :";
if ($resp_acf1_pris_en_compte>0)
  echo "<br /><b>- Responsabilité : Total3 = ".$resp_acf1_pris_en_compte." * ".getSettingValue("bas_cout_resp_acf_prof")." * ".getSettingValue("bas_nb_journees_acf")." * 4</b>";
else
  echo "<br /><b>- Responsabilité : Total3 = 0</b>";

if ($resp_acf2_pris_en_compte>0)
  echo "<br /><b>- Co-responsabilité : Total4 = ".$resp_acf2_pris_en_compte." * ".getSettingValue("bas_cout_resp_acf_prof2")." * ".getSettingValue("bas_nb_journees_acf")." * 4</b>";
else
  echo "<br /><b>- Co-responsabilité : Total4 = 0</b>";

echo "<br /><br />Dans votre cas, le résultat est : <b>". ($total3+$total4). " séquence(s) dépensée(s).</b>";

echo "<H3>Participation à un jury ACF</H3>";
echo "Nombre de participation à des jurys ACF : <b>".$nb_jury."</b>";
if ($nb_jury_pris_en_compte < $nb_jury)
    echo "<br />Nombre de participation à des jurys ACF, pris en compte dans le calcul : <b>".$nb_jury_pris_en_compte."</b>";
if ($nb_jury>0) {
  echo "<br />par ailleurs, le coût séquence d'une participation à un jury ACF par prof par an est : <b>".getSettingValue("bas_cout_jury_acf_prof")."</b>";
  echo "<br />Le nombre de séquences dépensées est donc :
  <br /><b>Total5 = ".$nb_jury_pris_en_compte." * ".getSettingValue("bas_cout_jury_acf_prof")."</b>";
  echo "<br /><br />Dans votre cas, le résultat est : <b> ". $total5. " séquence(s).</b>";
}
echo "<H3>Récapitulatif</H3>";
echo "Le Nombre total de séquences 50 minutes à effectuer dans l'année est alors calculé selon la formule :
<br /><b>Total = Total1 + Total_BTS + Total2 - Total3 -Total4 - Total5</b>";
echo "<br /><br />Dans votre cas, le résultat est : <b>". max($total_restant_du+$total_bts,0). " séquence(s) de 50 minutes.</b>";
if ($total_restant_du+$total_bts > 0)
    echo "<br />Soit un équivalent en nombre de séquences BAS de <b>".round((($total_restant_du+$total_bts)/getSettingValue("bas_cout_bas_prof")))." séquence(s) BAS.</b>";
   
    
//echo "<br /><br />Nombre théorique moyen de séquences dues par BAS (sur un total de ".$bas_nb_journees_bas.") : ". max($total_moyenne_bas_theorique_dus,0). " séquence(s) par BAS.";

echo "<H3>Nombre moyen de séquences déjà effectuées</H3>";
echo "Le \"<i>nombre moyen de séquences déjà effectuées</i>\" est calculé de la façon suivante :
<br />Nombre de propositions effectuées (les activités annulées ne sont pas comptabilisées)
auquel on ajoute le cas échéant un nombre de séquences correspondant aux absences justifiées lors des demi-journées.
<br />ce nombre est égal au nombre de demi-journées d'absence multiplié par le nombre moyen de séquences dues par BAS.";
?>
</body>
</html>