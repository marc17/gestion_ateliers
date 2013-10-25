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
$nom_script = "mod_plugins/gestion_ateliers/admin_inscrip_rapide.php";
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
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : NULL);
$cible = isset($_POST['cible']) ? $_POST['cible'] : (isset($_GET['cible']) ? $_GET['cible'] : NULL);
$id_classe = isset($_POST['id_classe']) ? $_POST['id_classe'] : (isset($_GET['id_classe']) ? $_GET['id_classe'] : NULL);
$login_eleve = isset($_POST['login_eleve']) ? $_POST['login_eleve'] : (isset($_GET['login_eleve']) ? $_GET['login_eleve'] : NULL);

// Enregistrement des affecatation
if (isset($action) and ($action=="inscription_filiere") and (isset($_POST['is_posted'])))  {
  $action= NULL;
  $cible=NULL;
  $temp = "choix_filiere_".$login_eleve;
  if (isset($_POST[$temp]))  {
          $req = mysql_query("delete from bas_j_eleves_filieres where id_eleve = '".$login_eleve."'");
          if ($_POST[$temp]!='') {
            $req = mysql_query("insert into bas_j_eleves_filieres set
            id_eleve = '".$login_eleve."',
            id_filiere = '".$_POST[$temp]."'");
          }
    }
    $msg .= "<br />Les Modifications ont été enregistrées.";
}

// Enregistrement des inscriptions
if (isset($action) and ($action=="inscription") and (isset($_POST['is_posted'])))  {
    // Constitution du tableau $per
    $description_bas = sql_query1("select description_bas from bas_bas where id_bas='".$numero_bas."'");
    $per =  tableau_periode($numero_bas);
    $msg = '';
    $nom_eleve = sql_query1("select nom from eleves where login = '".$login_eleve."'");
    $prenom_eleve = sql_query1("select prenom from eleves where login = '".$login_eleve."'");
    $k=1;
    while ($k < count($per)+1) {
         $j = 0;
         while ($j < '3') {
            $old_choix[$k][$j] = sql_query1("select bp.id_prop from bas_j_eleves_bas bjeb, bas_propositions bp where
            bjeb.num_sequence = '".$k."' and
            bjeb.id_eleve = '".$login_eleve."' and
            bjeb.num_bas = '".$numero_bas."' and
            bjeb.num_choix = '".$j."' and
            bp.id_bas = bjeb.id_bas
            ");
            if ($old_choix[$k][$j] == "-1") {
               $old_choix[$k][$j] = sql_query1("select id_bas from bas_j_eleves_bas where
               num_sequence = '".$k."' and
               id_eleve = '".$login_eleve."' and
               num_bas = '".$numero_bas."' and
               num_choix = '".$j."' and
               id_bas = 'abs'
               ");
            }
            $j++;
        }
        $temp = "priorite_".$k;
        if (isset($_POST[$temp])) $reg_priorite[$k] = $_POST[$temp]; else $reg_priorite[$k] = '';
        $n=0;
        while ($n < 3) {
            $temp = "choix_bas".$n."_".$k;
            $reg_choix_bas[$n][$k] = $_POST[$temp];
            if ($reg_choix_bas[$n][$k] == "bloque") {
                $reg_choix_bas[$n][$k] = '';
                $msg .= "Erreur : vous avez tentez d'inscrire un élève à une activité bloquée pour cause de sureffectif.";
            }
            $duree_[$n][$k] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas[$n][$k]."'");
            $n++;
        }

        $req = mysql_query("delete from bas_j_eleves_bas where
        id_eleve = '".$login_eleve."' and num_bas='".$numero_bas."' and num_sequence = '".$k."'");
        $k++;
    }
    // Test de verification sur les choix 1 par rapport à la durée des activités

    if ((isset($duree_[0][1])) and ($duree_[0][1] == 2) and ($reg_choix_bas[0][1] != $reg_choix_bas[0][2])) {
        if (($reg_choix_bas[0][2]) != '')
            $msg .= "<br />Erreur sur le choix final du 2ème créneau. L'erreur a été corrigée. Vérifiez bien que la correction effectuée est exacte." ;
        $reg_choix_bas[0][2] = $reg_choix_bas[0][1];
    }
    if ((isset($duree_[0][1])) and ($duree_[0][1] == 3) and ( ($reg_choix_bas[0][1] != $reg_choix_bas[0][2]) or ($reg_choix_bas[0][1] != $reg_choix_bas[0][3]))) {
        $msg .= "<br />Erreur sur le choix final du 2ème ou 3ème créneau. L'erreur a été corrigée. Vérifiez bien que la correction effectuée est exacte." ;
        $reg_choix_bas[0][2] = $reg_choix_bas[0][1];
        $reg_choix_bas[0][3] = $reg_choix_bas[0][1];
    }
    if ((isset($duree_[0][2])) and ($duree_[0][2] == 2) and ($reg_choix_bas[0][1] != $reg_choix_bas[0][2]) and ($reg_choix_bas[0][2] != $reg_choix_bas[0][3])) {
        $debut_fin = sql_query1("select debut_final from bas_propositions where id_bas = '".$reg_choix_bas[0][2]."'");
        if ($debut_fin == 1) {
            $reg_choix_bas[0][2] = "";
            $msg .= "<br />Erreur sur le choix final du 2ème créneau. Veuillez entrer une nouvelle valeur." ;
        } else {
            if (($reg_choix_bas[0][3]) != '')
                $msg .= "<br />Erreur sur le choix final du 3ème créneau. L'erreur a été corrigée. Vérifiez bien que la correction effectuée est exacte." ;
            $reg_choix_bas[0][3] = $reg_choix_bas[0][2];
        }
    }
    if ((isset($duree_[0][3])) and ($duree_[0][3] == 2) and ( $reg_choix_bas[0][2] != $reg_choix_bas[0][3]) ) {
        $msg .= "<br />Erreur sur le choix final du 3ème créneau. Veuillez entrer une nouvelle valeur." ;
        $reg_choix_bas[0][3] = "";
    }

    // Test de verification sur les choix 1 par rapport à la durée des activités

    if ((isset($duree_[1][1])) and ($duree_[1][1] == 2) and ($reg_choix_bas[1][1] != $reg_choix_bas[1][2])) {
        if (($reg_choix_bas[1][2]) != '')
            $msg .= "<br />Erreur sur le 1er choix du 2ème créneau. L'erreur a été corrigée. Vérifiez bien que la correction effectuée est exacte." ;
        $reg_choix_bas[1][2] = $reg_choix_bas[1][1];
    }
    if ((isset($duree_[1][1])) and ($duree_[1][1] == 3) and ( ($reg_choix_bas[1][1] != $reg_choix_bas[1][2]) or ($reg_choix_bas[1][1] != $reg_choix_bas[1][3]))) {
        $msg .= "<br />Erreur sur le 1er choix du 2ème ou 3ème créneau. L'erreur a été corrigée. Vérifiez bien que la correction effectuée est exacte." ;
        $reg_choix_bas[1][2] = $reg_choix_bas[1][1];
        $reg_choix_bas[1][3] = $reg_choix_bas[1][1];
    }
    if ((isset($duree_[1][2])) and ($duree_[1][2] == 2) and ($reg_choix_bas[1][1] != $reg_choix_bas[1][2]) and ($reg_choix_bas[1][2] != $reg_choix_bas[1][3])) {
        $debut_fin = sql_query1("select debut_final from bas_propositions where id_bas = '".$reg_choix_bas[1][2]."'");
        if ($debut_fin == 1) {
            $reg_choix_bas[1][2] = "";
            $msg .= "<br />Erreur sur le 1er choix du 2ème créneau. Veuillez entrer une nouvelle valeur." ;
        } else {
            if (($reg_choix_bas[1][3]) != '')
                $msg .= "<br />Erreur sur le 1er choix du 3ème créneau. L'erreur a été corrigée. Vérifiez bien que la correction effectuée est exacte." ;
            $reg_choix_bas[1][3] = $reg_choix_bas[1][2];
        }
    }
    if ((isset($duree_[1][3])) and ($duree_[1][3] == 2) and ( $reg_choix_bas[1][2] != $reg_choix_bas[1][3]) ) {
        $msg .= "<br />Erreur sur le 1er choix du 3ème créneau. Veuillez entrer une nouvelle valeur." ;
        $reg_choix_bas[1][3] = "";
    }
     // Test de verification sur le choix 2 par rapport à la durée des activités
    if ((isset($duree_[2][1])) and ($duree_[2][1] == 2) and ($reg_choix_bas[2][1] != $reg_choix_bas[2][2])) {
        if (($reg_choix_bas[2][2]) != '')
            $msg .= "<br />Erreur sur le 2ème choix du 2ème créneau. L'erreur a été corrigée. Vérifiez bien que la correction effectuée est exacte." ;
        $reg_choix_bas[2][2] = $reg_choix_bas[2][1];
    }
    if ((isset($duree_[2][1])) and ($duree_[2][1] == 3) and ( ($reg_choix_bas[2][1] != $reg_choix_bas[2][2]) or ($reg_choix_bas[2][1] != $reg_choix_bas[2][3]))) {
        $msg .= "<br />Erreur sur le 2ème choix du 2ème ou 3ème créneau. L'erreur a été corrigée. Vérifiez bien que la correction effectuée est exacte." ;
        $reg_choix_bas[2][2] = $reg_choix_bas[2][1];
        $reg_choix_bas[2][3] = $reg_choix_bas[2][1];
    }
    if ((isset($duree_[2][2])) and ($duree_[2][2] == 2) and ($reg_choix_bas[2][1] != $reg_choix_bas[2][2]) and ($reg_choix_bas[2][2] != $reg_choix_bas[2][3])) {
        $debut_fin = sql_query1("select debut_final from bas_propositions where id_bas = '".$reg_choix_bas[2][2]."'");
        if ($debut_fin == 1) {
            $reg_choix_bas[2][2] = "";
            $msg .= "<br />Erreur sur le 2ème choix du 2ème créneau. Veuillez entrer une nouvelle valeur." ;
        } else {
            if (($reg_choix_bas[2][3]) != '')
                $msg .= "<br />Erreur sur le 2ème choix du 3ème créneau. L'erreur a été corrigée. Vérifiez bien que la correction effectuée est exacte." ;
            $reg_choix_bas[2][3] = $reg_choix_bas[2][2];
        }
    }
    if ((isset($duree_[2][3])) and ($duree_[2][3] == 2) and ( $reg_choix_bas[2][2] != $reg_choix_bas[2][3]) ) {
        $msg .= "<br />Erreur sur le 2ème choix du 3ème créneau. Veuillez entrer une nouvelle valeur." ;
        $reg_choix_bas[2][3] = "";
    }
    /*
    // Test de verification sur le choix 0
    $k='1';
    while ($k < count($per)+1) {
        if (($reg_choix_bas[0][$k] != '') and ($reg_choix_bas[0][$k] != $reg_choix_bas[1][$k]) and ($reg_choix_bas[0][$k] != $reg_choix_bas[2][$k])) {
            $msg .= "<br />Erreur sur l'affectation finale pour le créneau n° ".$k.". Ce choix ne correspond pas au choix 1 ou au choix 2. Veuillez entrer une nouvelle valeur." ;
            $reg_choix_bas[0][$k] = '';
        }
        $k++;
    }
    */
    // Enregistrement des modifications
    $k=1;
    while ($k < count($per)+1) {
        if ($reg_choix_bas[1][$k] == 'abs') $reg_priorite[$k] = '';
        $n = 0;
        while ($n < 3) {
            if ($reg_choix_bas[$n][$k] != '') $req = mysql_query("insert into bas_j_eleves_bas set
            id_eleve = '".$login_eleve."',
            num_bas='".$numero_bas."',
            num_sequence = '".$k."',
            num_choix = '".$n."',
            id_bas = '".$reg_choix_bas[$n][$k]."',
            priorite = '".$reg_priorite[$k]."'
            ");
            if (($reg_choix_bas[$n][$k] != '') and ($reg_choix_bas[$n][$k] != 'abs'))
                $new_choix[$k][$n] = sql_query1("select id_prop from bas_propositions where id_bas = '".$reg_choix_bas[$n][$k]."'");
            else
                $new_choix[$k][$n] = $reg_choix_bas[$n][$k];
            $n++;
        }
        $k++;
    }
    if ($msg == "")
        $msg .= "Les modifications ont été enregistrées.";
    else
        $msg .= "<br />Les autres modifications ont été enregistrées.";
    if ($_SESSION['statut'] == 'cpe') {
        // Envoi d'un mail à l'administrateur
        $message = ucfirt($NomAtelier_sigulier)." N° ".$numero_bas." : modification d'affectation pour l'élève ".$login_eleve."\r\n";
        $k='1';
        while ($k < count($per)+1) {
            $j = 0;
            while ($j < '3') {
                $message .= "Choix ".$j." - Séquence ".$k." : ".$old_choix[$k][$j]." -> ".$new_choix[$k][$j]."\r\n";
                $j++;
            }
        $k++;
        }

        $email_cpe = sql_query1("select email from utilisateurs where login = '".$_SESSION['login']."'");
        if ($email_cpe != "") {
            $from = $_SESSION['nom']." ".$_SESSION['prenom']." <".$email_cpe.">";
            $replyto = $from;
        } else {
            $from = getSettingValue("gepiAdminAdress");
            $replyto = '';
        }
        $envoi = mail(getSettingValue("gepiAdminAdress"), "[ATELIER-GEPI] Changement d'affectation",
        $message, "From: ".$from."\r\nReply-To: ".$replyto."\r\nX-Mailer: PHP/".phpversion());
    }

}

//**************** EN-TETE *****************
$titre_page = "Inscription rapide des élèves";
require_once("../../lib/header.inc.php");
//**************** FIN EN-TETE *****************
echo "<p class=bold>| <a href=\"../../accueil.php\">Retour à la page d'accueil</a> |";
if (calcul_autorisation_gestion_ateliers($_SESSION['login'],"mod_plugins/gestion_ateliers/droit_special_inscrip_rapide.txt"))
    echo "<a href=\"./admin_index.php\"> Menu de gestion des ".$NomAtelier_pluriel."</a> |";
else
    echo "<a href=\"./index.php\"> Menu de gestion des ".$NomAtelier_pluriel."</a> |";
if ((isset($action)) and ($action == "inscription"))
    if ($cible!= 'unique')
        echo " <a href=\"./admin_inscrip_rapide.php?numero_bas=$numero_bas&amp;cible=$cible\"> Retour </a> |";
    else
        echo " <a href=\"./admin_inscrip_rapide.php?numero_bas=$numero_bas\"> Choisir un autre élève </a> |";
else
    echo " <a href=\"./admin_inscrip_rapide.php?numero_bas=$numero_bas\"> Retour </a> |";
if ($cible== 'unique')
  echo "<a href='admin_inscrip_rapide.php?numero_bas=$numero_bas&amp;cible=unique&amp;action=inscription_filiere&amp;login_eleve=".$login_eleve."' > Affecter l'élève à une filière</a> |</p>\n";
echo "</p>";
// données sur le bas
$date_bas = sql_query1("select date_bas from bas_bas where id_bas='".$numero_bas."'");
$close_bas = sql_query1("select close_bas from bas_bas where id_bas='".$numero_bas."'");
$date_limite = sql_query1("select date_limite from bas_bas where id_bas='".$numero_bas."'");
$description_bas = sql_query1("select description_bas from bas_bas where id_bas='".$numero_bas."'");
$num_periode = sql_query1("select num_periode from bas_bas where id_bas='".$numero_bas."'");
    // Constitution du tableau $per
    $per =  tableau_periode($numero_bas);
echo "<p class='grand'>";
if (!(isset($action) and ($action=="inscription_filiere")))
  echo ucfirst($NomAtelier_singulier)." N° ".$numero_bas." du ".$date_bas ;

if (!isset($cible))  {
    echo "<ul>";
    if (calcul_autorisation_gestion_ateliers($_SESSION['login'],"mod_plugins/gestion_ateliers/droit_special_inscrip_rapide.txt"))
        echo "<li><a href='admin_inscrip_rapide.php?numero_bas=$numero_bas&amp;cible=noninscrits'>Liste des élèves non inscrits (choix n° 1 manquant sur au moins un créneau)</a></li>";
    echo "<li><a href='admin_inscrip_rapide.php?numero_bas=$numero_bas&amp;cible=nonaffect'>Liste des élèves non affectés</a></li>";
    echo "<li><a href='admin_inscrip_rapide.php?numero_bas=$numero_bas&amp;cible=absents'>Liste des élèves absents sur l'un des créneaux</a><br /><br /></li>";
    echo "<li>Choisir l'élève dans la liste :";
    echo "<br /><form action=\"admin_inscrip_rapide.php\" name=\"choix_eleve\" method=\"post\">\n";
   
    echo "<select name=\"login_eleve\" size=\"1\">\n";
    echo "<option value=''>(choisissez)</option>\n";
    $appel_donnees_eleves = mysql_query("SELECT e.*, bc.id_classe, bc.nom_classe
    FROM eleves e, bas_classes bc, j_eleves_classes jec
    WHERE (
       jec.login = e.login AND
       jec.id_classe = bc.id_classe and
       jec.periode='".$num_periode."'
       ) ORDER BY e.nom, e.prenom");
       
    $nombre_lignes = mysql_num_rows($appel_donnees_eleves);
    $i=0;
    while ($i < $nombre_lignes) {
        $login_eleve = mysql_result($appel_donnees_eleves,$i,'login');
        $id_classe = mysql_result($appel_donnees_eleves,$i,'id_classe');
        $nom_eleve = mysql_result($appel_donnees_eleves,$i,'nom');
        $prenom_eleve = mysql_result($appel_donnees_eleves,$i,'prenom');
        $nom_classe = mysql_result($appel_donnees_eleves,$i,'nom_classe');
        echo "<option value='".$login_eleve."'>".$nom_eleve." ".$prenom_eleve."</option>\n";
        $i++;
    }
    echo "</select>";
    echo "<input type=\"hidden\" name=\"numero_bas\" value=\"$numero_bas\" />";
    echo "<input type=\"hidden\" name=\"action\" value=\"inscription\" />";
    echo "<input type=\"hidden\" name=\"cible\" value=\"unique\" />";
    echo "<input type=\"submit\" name=\"ok\" />";
    echo "</form>\n";
    echo "</li>";
    echo "</ul>";
}

// formulaire de saisie des filières
if (isset($action) and ($action=="inscription_filiere")) {
    echo " - Saisie de la filière de l'élève</p>\n";
    echo "<p>Affectez ci-dessous l'élève à la filière suivie</p>\n";
    echo "<form action=\"admin_inscrip_rapide.php\" name=\"inscription_filiere\" method=\"post\">\n";
    
    echo "<table>\n";
    $nom_eleve = sql_query1("select nom from eleves where login = '".$login_eleve."'");
    $prenom_eleve = sql_query1("select prenom from eleves where login = '".$login_eleve."'");
    $nom_classe = sql_query1("select classe from classes where id='".$id_classe."'");
        echo "<tr><td>$nom_eleve $prenom_eleve</td>\n";
        $filiere = sql_query1("select id_filiere from bas_j_eleves_filieres where id_eleve='".$login_eleve."'");
        echo "<td><select name=\"choix_filiere_".$login_eleve."\" size=\"1\">\n";
        echo "<option value=''>(choisissez)</option>\n";
        $n=1;
        while ($n<NB_NIVEAUX_FILIERES+1) {
          foreach($tab_filière[$n]["id"] as $key => $_id){
            echo "<option value='".$_id."' ";
            if ($filiere == $_id) echo " selected=\"selected\" ";
            echo " />".$tab_filière[$n]["nom"][$key]."</option>\n";
          }
          $n++;        
        }
        echo "</select></td>\n";
        echo "</tr>";

    echo "</table>";
    echo "<div id=\"fixe\">";
    echo "<center><input type=\"submit\" name=\"ok\" value=\"Enregistrer\" /></center>";
    echo "<input type=\"hidden\" name=\"is_posted\" value=\"yes\" />";
    echo "<input type=\"hidden\" name=\"action\" value=\"inscription_filiere\" />";
    echo "<input type=\"hidden\" name=\"login_eleve\" value=\"".$login_eleve."\" />";
    echo "<input type=\"hidden\" name=\"numero_bas\" value=\"".$numero_bas."\" />";
    echo "<input type=\"hidden\" name=\"cible\" value=\"unique\" />";

    echo "</div></form>\n";
}




if ((!isset($action)) and (isset($cible))) {
    if ($cible == "noninscrits")
        echo " - Liste des élèves non inscrits (au moins un choix n° 1 manquant)</p>\n";
    if ($cible == "nonaffect")
        echo " - Liste des élèves non affectés</p>\n";
    if ($cible == "absents")
        echo " - Liste des élèves absents sur l'un des trois créneaux</p>\n";

    // Appel des élèves
    $appel_donnees_eleves = mysql_query("SELECT e.*, bc.id_classe, bc.nom_classe
    FROM eleves e, bas_classes bc, j_eleves_classes jec
        WHERE (
           jec.login = e.login AND
           jec.id_classe = bc.id_classe and
           jec.periode='".$num_periode."'
           ) ORDER BY e.nom");
    $nombre_lignes = mysql_num_rows($appel_donnees_eleves);
    echo "<table border=\"1\" cellpadding=\"2\">";
    echo "<tr><td><b>Nom Prénom</b></td><td><b>Classe</b></td><td><b>Filière</b></td><td><b>Professeur de suivi</b></td>";
    $k = 1;
    while ($k < count($per)+1) {
        echo "<td colspan=\"3\"><b>".$per[$k]."</b></td>";
        $k++;
    }
    echo "</tr>";
    echo "<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>";
    $k = 1;
    while ($k < count($per)+1) {
        echo "<td><b>Affectation finale</b></td><td><b>Choix&nbsp;N°&nbsp;1</b></td><td><b>Choix&nbsp;N°&nbsp;2</b></td>";
        $k++;
    }
    echo "</tr>";


    $i = 0;
    while ($i < $nombre_lignes) {
        $login_eleve = mysql_result($appel_donnees_eleves,$i,'login');
        $id_classe = mysql_result($appel_donnees_eleves,$i,'id_classe');
        $id_filiere=sql_query1("select id_filiere from bas_j_eleves_filieres where id_eleve='".$login_eleve."'");
        if ($id_filiere!=-1)
         $nom_filiere=sql_query1("select nom_filiere from bas_filieres where id_filiere='".$id_filiere."'");
        else
         $nom_filiere='-';
        if ($cible == "noninscrits") {
            // On affiche les élèves pour lesquels il manque au moins un choix N° 1
            $test = sql_query("select num_choix from bas_j_eleves_bas where
                num_choix = '1' and
                id_eleve = '".$login_eleve."' and
                num_bas = '".$numero_bas."' and
                id_bas  != 'abs'
                ");
        } else if ($cible == "nonaffect") {
            // On affiche les élèves pour lesquels il manque au moins un choix N° 0
            $test = sql_query("select num_choix from bas_j_eleves_bas where
                num_choix = '0' and
                id_eleve = '".$login_eleve."' and
                num_bas = '".$numero_bas."' and
                id_bas  != 'abs'
                ");
        } else if ($cible == "absents") {
            // On affiche les élèves pour lesquels il manque au moins un choix N° 0
            $test = sql_query("select num_choix from bas_j_eleves_bas where
                num_choix = '0' and
                id_eleve = '".$login_eleve."' and
                num_bas = '".$numero_bas."' and
                id_bas  = 'abs'
                ");
        } else {
            echo "Erreur de manipulation !</body></html>";
            die();
        }
        if (((sql_count($test) < count($per) ) and ($cible != "absents")) or ((sql_count($test) >= 1) and ($cible == "absents"))) {
            $nom_eleve = mysql_result($appel_donnees_eleves,$i,'nom');
            $prenom_eleve = mysql_result($appel_donnees_eleves,$i,'prenom');
            $nom_classe = mysql_result($appel_donnees_eleves,$i,'nom_classe');
            $nom_prof = sql_query1("select u.nom from utilisateurs u, j_eleves_professeurs jep where
                u.login = jep.professeur and
                jep.login = '".$login_eleve."' and
                jep.id_classe = '".$id_classe."'
                ");
            echo "<tr><td><a href='admin_inscrip_rapide.php?numero_bas=$numero_bas&amp;login_eleve=$login_eleve&amp;id_classe=$id_classe&amp;action=inscription&amp;cible=$cible' title='Modifier les choix de cet élève'>".$nom_eleve." ".$prenom_eleve."</a></td><td>".$nom_classe."</td>";
            if ($id_filiere!=-1)
              echo "<td>".$nom_filiere."</td>\n";
            else
             echo "<td bgcolor=\"#FF0000\"><a href = 'admin_inscrip_rapide.php?numero_bas=$numero_bas&amp;login_eleve=$login_eleve&amp;cible=unique&amp;action=inscription_filiere' target='_blank'>Filière non affectée</a></td>";
            echo "<td>".$nom_prof."</td>";
            $j = 1;
            while ($j < count($per)+1) {
                $k = 0;
                while ($k < 3) {
                    $temp = sql_query1("select bp.id_prop from bas_j_eleves_bas bjeb, bas_propositions bp where
                    bjeb.num_sequence = '".$j."' and
                    bjeb.id_eleve = '".$login_eleve."' and
                    bjeb.num_bas = '".$numero_bas."' and
                    bjeb.num_choix = '".$k."' and
                    bp.id_bas = bjeb.id_bas
                    ");
                    if ($temp == "-1") {
                        $temp2 = sql_query1("select id_bas from bas_j_eleves_bas where
                        num_sequence = '".$j."' and
                        id_eleve = '".$login_eleve."' and
                        num_bas = '".$numero_bas."' and
                        num_choix = '".$k."' and
                        id_bas = 'abs'
                        ");
                        if ($temp2 == "-1")
                            $id_prop[$k] = "-";
                        else
                            $id_prop[$k] = "Absent";
                    } else $id_prop[$k] = $temp;
                    if (($k == 0) and ($id_prop[$k] == "-"))
                        echo "<td bgcolor=\"#FF0000\">".$id_prop[$k]."</td>";
                    else
                        echo "<td>".$id_prop[$k]."</td>";
                    $k++;
                }
                $j++;
            }
            echo "</tr>";
        }
        $i++;
    }
    echo "</table>";

}
// formulaire de saisie des inscriptions
if (isset($action) and ($action=="inscription")) {
    if (!isset($id_classe)) $id_classe = sql_query1("select id_classe from j_eleves_classes where login = '".$login_eleve."' and periode = '".$num_periode."'");

    $nom_eleve = sql_query1("select nom from eleves where login = '".$login_eleve."'");
    $prenom_eleve = sql_query1("select prenom from eleves where login = '".$login_eleve."'");
    $nom_classe = sql_query1("select classe from classes where id='".$id_classe."'");
    $nom_prof = sql_query1("select u.nom from utilisateurs u, j_eleves_professeurs jep where
        u.login = jep.professeur and
        jep.login = '".$login_eleve."' and
        jep.id_classe = '".$id_classe."'
        ");
    $prenom_prof = sql_query1("select u.prenom from utilisateurs u, j_eleves_professeurs jep where
        u.login = jep.professeur and
        jep.login = '".$login_eleve."' and
        jep.id_classe = '".$id_classe."'
        ");
    $id_filiere=sql_query1("select id_filiere from bas_j_eleves_filieres where id_eleve='".$login_eleve."'");


    echo " - Saisie des inscriptions<br />".$prenom_eleve." ".$nom_eleve." - ".$nom_classe." - Suivi par : ".$prenom_prof." ".$nom_prof."</p>\n";


    echo "Reportez ci-dessous les choix effectués par l'élève et le cas échéant, l'affectation définitive.";

    // Les propositions :
    if ($id_filiere!=-1) {
     $req_prop1 = mysql_query("select id_prop, id_bas, statut, nb_bloque from bas_propositions where
    public_".$id_filiere." != '' and debut_final = '1' and num_bas = '".$numero_bas."'  and statut!='a' order by id_prop");
    $req_prop2 = mysql_query("select id_prop, id_bas, statut, nb_bloque from bas_propositions where
    public_".$id_filiere." != '' and (debut_final = '2' or (debut_final = '1' and duree = '2') or (debut_final = '1' and duree = '3')) and num_bas = '".$numero_bas."'  and statut!='a' order by id_prop");
    $req_prop3 = mysql_query("select id_prop, id_bas, statut, nb_bloque from bas_propositions where
    public_".$id_filiere." != '' and (debut_final = '3' or (debut_final = '2' and duree = '2') or (debut_final = '1' and duree = '3')) and num_bas = '".$numero_bas."'  and statut!='a'  order by id_prop");
    $nb_prop[1] = mysql_num_rows($req_prop1);
    $k = 0;
    while ($k < $nb_prop[1]) {
        $id_bas[1][$k] = mysql_result($req_prop1,$k,'id_bas');
        $id_propo[1][$k] = mysql_result($req_prop1,$k,'id_prop');
        $statut_[1][$k] = mysql_result($req_prop1,$k,'statut');
        $nb_bloque = mysql_result($req_prop1,$k,'nb_bloque');
        $nb_inscrit = sql_query1("select count(id_eleve) from bas_j_eleves_bas where
        num_bas = '".$numero_bas."' and  id_bas = '".$id_bas[1][$k]."' and num_choix='0' and num_sequence='1'");
        if (($nb_bloque != -1) and ($nb_bloque <= $nb_inscrit)) $bloque_[1][$k] = 'y'; else $bloque_[1][$k] = 'n';
        $k++;
    }
    $nb_prop[2] = mysql_num_rows($req_prop2);
    $k = 0;
    while ($k < $nb_prop[2]) {
        $id_bas[2][$k] = mysql_result($req_prop2,$k,'id_bas');
        $id_propo[2][$k] = mysql_result($req_prop2,$k,'id_prop');
        $statut_[2][$k] = mysql_result($req_prop2,$k,'statut');
        $nb_bloque = mysql_result($req_prop2,$k,'nb_bloque');
        $nb_inscrit = sql_query1("select count(id_eleve) from bas_j_eleves_bas where
        num_bas = '".$numero_bas."' and  id_bas = '".$id_bas[2][$k]."' and num_choix='0' and num_sequence='2'");
        if (($nb_bloque != -1) and ($nb_bloque <= $nb_inscrit)) $bloque_[2][$k] = 'y'; else $bloque_[2][$k] = 'n';
        $k++;
    }
    $nb_prop[3] = mysql_num_rows($req_prop3);
    $k = 0;
    while ($k < $nb_prop[3]) {
        $id_bas[3][$k] = mysql_result($req_prop3,$k,'id_bas');
        $id_propo[3][$k] = mysql_result($req_prop3,$k,'id_prop');
        $statut_[3][$k] = mysql_result($req_prop3,$k,'statut');
        $nb_bloque = mysql_result($req_prop3,$k,'nb_bloque');
        $nb_inscrit = sql_query1("select count(id_eleve) from bas_j_eleves_bas where
        num_bas = '".$numero_bas."' and  id_bas = '".$id_bas[3][$k]."' and num_choix='0' and num_sequence='3'");
        if (($nb_bloque != -1) and ($nb_bloque <= $nb_inscrit)) $bloque_[3][$k] = 'y'; else $bloque_[3][$k] = 'n';
        $k++;
    }
    } 

    if ($id_filiere!=-1)
     echo "<form action=\"admin_inscrip_rapide.php\" name=\"inscription\" method=\"post\">\n";
    echo "<table border=\"1\" cellspacing=\"1\" cellpadding=\"5\">
    <tr>";
    $k='1';
    while ($k < count($per)+1) {
        echo "<td width=\"100\" colspan=\"3\"><b>Heure : ".$per[$k]."</b></td>";
        $k++;
    }
    echo "</tr>";
    echo "<tr>";
    $k = 1;
    while ($k < count($per)+1) {
        echo "<td width=\"100\">Affectation finale</td>\n";
        echo "<td width=\"100\">Choix N° 1</td >\n";
        echo "<td width=\"100\">Choix N° 2</td>\n";
        $k++;
    }
    echo "</tr>";
    echo "<tr>\n";
    $k='1';
    while ($k < count($per)+1) {
        $n = 0;
        while ($n < 3) {
            $reg_choix_eleve[$n] = sql_query1("select id_bas from bas_j_eleves_bas where
            id_eleve = '".$login_eleve."' and
            num_bas='".$numero_bas."' and
            num_sequence = '".$k."' and
            num_choix = '".$n."'
            ");
            $n++;
        }
        $reg_priorite = sql_query1("select priorite from bas_j_eleves_bas where (
        id_eleve = '".$login_eleve."' and
        num_bas='".$numero_bas."' and
        num_sequence = ".$k." and
        num_choix = '1')
        ");
        $n = 0;
        while ($n < 3) {
            echo "<td>";
            if ($n == 0) {
                if ($reg_priorite=='-1') $reg_priorite='';
            }
            if (($n == 0) or (($n != 0) and (calcul_autorisation_gestion_ateliers($_SESSION['login'],"mod_plugins/gestion_ateliers/droit_special_inscrip_rapide.txt")))) {
                echo "<input type=\"hidden\" name=\"priorite_".$k."\" value=\"".$reg_priorite."\" />";
                if ($id_filiere!=-1) {
                echo "<select name=\"choix_bas".$n."_".$k."\" size=\"1\">\n";
                echo "<option value=''>(choisissez)</option>\n";
                echo "<option value='abs' ";
                if ($reg_choix_eleve[$n] == 'abs') echo "selected";
                echo ">(Absent)</option>\n";
                $m = 0;
                while ($m < $nb_prop[$k]) {
                    // si l'activité est bloqué
                    if ((($reg_choix_eleve[$n] != $id_bas[$k][$m]) and ($bloque_[$k][$m] == 'y')) and !(calcul_autorisation_gestion_ateliers($_SESSION['login'],"mod_plugins/gestion_ateliers/droit_special_inscrip_rapide.txt")) and ($_SESSION['statut'] != 'cpe'))
                        echo "<option value='bloque' ";
                    else
                         echo "<option value='".$id_bas[$k][$m]."' ";
                     if ($reg_choix_eleve[$n] == $id_bas[$k][$m]) echo "selected";
                    echo ">".$id_propo[$k][$m];
                    if (($reg_choix_eleve[$n] != $id_bas[$k][$m]) and ($bloque_[$k][$m] == 'y'))
                         echo " **Bloqué**";

                     echo "</option>\n";
                    $m++;
                }
                echo "</select>";
                } else {
                        echo "<font color='red'>Filière non affectée</font>";
                }
            } else {
                $id_prop = sql_query1("select id_prop from bas_propositions where id_bas='".$reg_choix_eleve[$n]."'");
                if ($id_prop == -1) $id_prop = "-";
                echo $id_prop;
                echo "<input type=\"hidden\" name=\"choix_bas".$n."_".$k."\" value=\"".$reg_choix_eleve[$n]."\" />";
            }
            echo "</td>\n";
            $n++;
        }
        $k++;
    }
    echo "</tr>";
    echo "</table>";
    // Code d'affichage de la liste des activités par classe
    $affiche_titre = 0;
    if ($id_filiere!=-1) {
    include "./code_liste_bas_par_classe.php";
    echo "<div id=\"fixe\">";
    echo "<center><input type=\"submit\" name=\"ok\" value=\"Enregistrer\" /></center></div>";
    echo "<input type=\"hidden\" name=\"is_posted\" value=\"yes\" />";
    echo "<input type=\"hidden\" name=\"action\" value=\"inscription\" />";
    echo "<input type=\"hidden\" name=\"numero_bas\" value=\"".$numero_bas."\" />";
    echo "<input type=\"hidden\" name=\"login_eleve\" value=\"".$login_eleve."\" />";
    echo "<input type=\"hidden\" name=\"id_classe\" value=\"".$id_classe."\" />";
    echo "<input type=\"hidden\" name=\"cible\" value=\"".$cible."\" />";   
    echo "</form>\n";
    }

}

?>
<br />&nbsp;
<br />&nbsp;
<br />&nbsp;
<br />&nbsp;
<br />&nbsp;
<br />&nbsp;
</body>
</html>