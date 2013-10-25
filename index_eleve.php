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
$nom_script = "mod_plugins/gestion_ateliers/index_eleve.php";
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
$active_blocage = sql_query1("select active_blocage from bas_bas where id_bas='".$numero_bas."'");
$aut_insc_eleve = sql_query1("select aut_insc_eleve from bas_bas where id_bas='".$numero_bas."'");
// Enregistrement des inscriptions
if (isset($action) and ($action=="inscription") and (isset($_POST['is_posted'])) and ($aut_insc_eleve=='y'))  {
    $inscription_bas = sql_query1("select inscription_bas from bas_bas where id_bas='".$numero_bas."'");

    // Constitution du tableau $per
    $description_bas = sql_query1("select description_bas from bas_bas where id_bas='".$numero_bas."'");
    $per =  tableau_periode($numero_bas);

    $msg = '';
    $current_eleve_login = $_SESSION['login'];
    $current_eleve_nom = $_SESSION['nom'];
    $current_eleve_prenom = $_SESSION['prenom'];
    $k=1;
    while ($k < count($per)+1) {
            // Choix N° 0
            $temp = "choix_bas0_".$k."_".$current_eleve_login;
            $reg_choix_bas0_old =  sql_query1("select id_bas from bas_j_eleves_bas where
            id_eleve = '".$current_eleve_login."' and
            num_bas='".$numero_bas."' and
            num_sequence = '".$k."' and
            num_choix = '0'
            ");
            if ((isset($_POST[$temp])) and ($reg_choix_bas0_old != $_POST[$temp])) {
                $reg_choix_bas0[$k] = $_POST[$temp];
                $statut_eleve0[$k] = '';
            } else {
                $reg_choix_bas0[$k] =  $reg_choix_bas0_old;
                $statut_eleve0[$k] = "deja_affecte";
            }

            // Choix N° 1
            $temp = "choix_bas1_".$k."_".$current_eleve_login;
            $reg_choix_bas1_old =  sql_query1("select id_bas from bas_j_eleves_bas where
                id_eleve = '".$current_eleve_login."' and
                num_bas='".$numero_bas."' and
                num_sequence = '".$k."' and
                num_choix = '1'
                ");
            if ((isset($_POST[$temp])) and ($reg_choix_bas1_old != $_POST[$temp])) {
                $reg_choix_bas1[$k] = $_POST[$temp];
                $statut_eleve1[$k] = '';
            } else {
                $reg_choix_bas1[$k] = $reg_choix_bas1_old;
                $statut_eleve1[$k] = "deja_affecte";
            }
            // Choix N° 2
            $temp = "choix_bas2_".$k."_".$current_eleve_login;
            $reg_choix_bas2_old = sql_query1("select id_bas from bas_j_eleves_bas where
                id_eleve = '".$current_eleve_login."' and
                num_bas='".$numero_bas."' and
                num_sequence = '".$k."' and
                num_choix = '2'
                ");
            if ((isset($_POST[$temp])) and ($reg_choix_bas2_old != $_POST[$temp])) {
                $reg_choix_bas2[$k] = $_POST[$temp];
                $statut_eleve2[$k] = '';
            } else {
                $reg_choix_bas2[$k] = $reg_choix_bas2_old;
                $statut_eleve2[$k] = "deja_affecte";
            }


            if ($reg_choix_bas0[$k] == "bloque") {
                $reg_choix_bas0[$k] = '';
                $msg .= "<br />Erreur : vous avez tenté de vous inscrire à une activité bloquée pour cause de sureffectif.";
            }

            if ($reg_choix_bas1[$k] == "bloque") {
                $reg_choix_bas1[$k] = '';
                $msg .= "<br />Erreur : vous avez tenté de vous inscrire à une activité bloquée pour cause de sureffectif.";
            }
            if ($reg_choix_bas2[$k] == "bloque") {
                $reg_choix_bas2[$k] = '';
                $msg .= "<br />Erreur : vous avez tenté de vous inscrire à une activité bloquée pour cause de sureffectif.";
            }
            $duree_0[$k] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas0[$k]."'");
            $duree_1[$k] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas1[$k]."'");
            $duree_2[$k] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas2[$k]."'");
            $k++;
    }
    // Test de verification sur le choix 0 par rapport à la durée des activités
    if ((isset($duree_0[1])) and ($duree_0[1] == 2) and ($reg_choix_bas0[1] != $reg_choix_bas0[2])) {
            $test_aut_insc_eleve = sql_query1("select aut_insc_eleve from bas_j_eleves_bas_insc where id_eleve = '".$current_eleve_login."' and num_bas='".$numero_bas."' and num_sequence = '2' and num_choix = '0'");
            if ($test_aut_insc_eleve=='y') { // l'élève peut modifier son choix
                if (($reg_choix_bas0[2]) != '') 
                   $msg .= "<br />Erreur sur le choix du 2ème créneau. L'erreur a été corrigée. Vérifiez bien que la correction effectuée est exacte." ;
                $reg_choix_bas0[2] = $reg_choix_bas0[1];
                $statut_eleve0[2] = '';
                $duree_0[2] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas0[2]."'");
            } else {
                $msg .= "<br />Erreur sur le choix du 1er créneau : cette activité est sur deux séquences et vous ne pouvez modifier le choix du 2ème créneau." ;
                $reg_choix_bas0[1] = '';
                $statut_eleve0[1] = '';
            }
    } 
    if ((isset($duree_0[1])) and ($duree_0[1] == 3) and ( ($reg_choix_bas0[1] != $reg_choix_bas0[2]) or ($reg_choix_bas0[1] != $reg_choix_bas0[3]))) {
       $test_aut_insc_eleve2 = sql_query1("select aut_insc_eleve from bas_j_eleves_bas_insc where id_eleve = '".$current_eleve_login."' and num_bas='".$numero_bas."' and num_sequence = '2' and num_choix = '0'");
       $test_aut_insc_eleve3 = sql_query1("select aut_insc_eleve from bas_j_eleves_bas_insc where id_eleve = '".$current_eleve_login."' and num_bas='".$numero_bas."' and num_sequence = '3' and num_choix = '0'");
       if (($test_aut_insc_eleve2=='y') and ($test_aut_insc_eleve3=='y'))  { // l'élève peut modifier son choix
          $msg .= "<br />Erreur sur le choix du 2ème créneau ou 3ème créneau. L'erreur a été corrigée. Vérifiez bien que la correction effectuée est exacte." ;
          $reg_choix_bas0[2] = $reg_choix_bas0[1];
          $reg_choix_bas0[3] = $reg_choix_bas0[1];
          $statut_eleve0[2] = '';
          $statut_eleve0[3] = '';
          $duree_0[2] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas0[2]."'");
          $duree_0[3] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas0[3]."'");
       } else {
          $msg .= "<br />Erreur sur le choix du 1er créneau : cette activité est sur 3 séquences et vous ne pouvez modifier le choix du 2ème créneau ou du 3ème créneau." ;
          $reg_choix_bas0[1] = '';
          $statut_eleve0[1] = '';
       }
    }
    if ((isset($duree_0[2])) and  ($duree_0[2] == 2) and ($reg_choix_bas0[1] != $reg_choix_bas0[2]) and ($reg_choix_bas0[2] != $reg_choix_bas0[3])) {
            $debut_fin = sql_query1("select debut_final from bas_propositions where id_bas = '".$reg_choix_bas0[2]."'");
            if ($debut_fin == 1) {
                $reg_choix_bas0[2] = "";
                $statut_eleve0[2] = '';
                $msg .= "<br />Erreur sur le choix final du 2ème créneau. Veuillez entrer une nouvelle valeur." ;
            } else {
               $test_aut_insc_eleve = sql_query1("select aut_insc_eleve from bas_j_eleves_bas_insc where id_eleve = '".$current_eleve_login."' and num_bas='".$numero_bas."' and num_sequence = '3' and num_choix = '0'");
               if ($test_aut_insc_eleve=='y') { // l'élève peut modifier son choix
               if (($reg_choix_bas0[3]) != '') 
                   $msg .= "<br />Erreur sur le choix du 3ème créneau. L'erreur a été corrigée. Vérifiez bien que la correction effectuée est exacte." ;
                $reg_choix_bas0[3] = $reg_choix_bas0[2];
                $statut_eleve0[3] = '';
                $duree_0[3] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas0[3]."'");
               } else {
                $msg .= "<br />Erreur sur le choix du 2ème créneau : cette activité est sur deux séquences et vous ne pouvez modifier le choix du 3ème créneau." ;
                $reg_choix_bas0[2] = '';
                $statut_eleve0[2] = '';
               }
            }
    }
    if ((isset($duree_0[3])) and ($duree_0[3] == 2) and ( $reg_choix_bas0[2] != $reg_choix_bas0[3])) {
            $msg .= "<br />Erreur sur le choix final du 3ème créneau. Veuillez entrer une nouvelle valeur.";
            $reg_choix_bas0[3] = "";
            $statut_eleve0[3] = '';
    }

    if ((isset($duree_0[2])) and ($duree_0[2] == 3) and (($reg_choix_bas0[1] != $reg_choix_bas0[2]) or ($reg_choix_bas0[2] != $reg_choix_bas0[3]))) {
            $test_aut_insc_eleve1 = sql_query1("select aut_insc_eleve from bas_j_eleves_bas_insc where id_eleve = '".$current_eleve_login."' and num_bas='".$numero_bas."' and num_sequence = '1' and num_choix = '0'");
            $test_aut_insc_eleve3 = sql_query1("select aut_insc_eleve from bas_j_eleves_bas_insc where id_eleve = '".$current_eleve_login."' and num_bas='".$numero_bas."' and num_sequence = '3' and num_choix = '0'");
            if (($test_aut_insc_eleve1=='y') and ($test_aut_insc_eleve3=='y')) { // l'élève peut modifier son choix
                $msg .= "<br />Erreur sur le choix due à une activité sur 3 séquences. L'erreur a été corrigée. Vérifiez bien que la correction effectuée est exacte." ;
                $reg_choix_bas0[1] = $reg_choix_bas0[2];
                $statut_eleve0[1] = '';
                $reg_choix_bas0[3] = $reg_choix_bas0[2];
                $statut_eleve0[3] = '';
                $duree_0[1] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas0[1]."'");
                $duree_0[3] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas0[3]."'");
            } else {
                $msg .= "<br />Erreur sur le choix du 2ème créneau : cette activité est sur 3 séquences et vous ne pouvez modifier le choix du 1er créneau ou le choix du 3ème créneau." ;
                $reg_choix_bas0[2] = '';
                $statut_eleve0[2] = '';
            }
    }
    if ((isset($duree_0[3])) and ($duree_0[3] == 3) and (( $reg_choix_bas0[2] != $reg_choix_bas0[3]) or ( $reg_choix_bas0[1] != $reg_choix_bas0[3]))  ) {
            $test_aut_insc_eleve1 = sql_query1("select aut_insc_eleve from bas_j_eleves_bas_insc where id_eleve = '".$current_eleve_login."' and num_bas='".$numero_bas."' and num_sequence = '1' and num_choix = '0'");
            $test_aut_insc_eleve2 = sql_query1("select aut_insc_eleve from bas_j_eleves_bas_insc where id_eleve = '".$current_eleve_login."' and num_bas='".$numero_bas."' and num_sequence = '2' and num_choix = '0'");
            if (($test_aut_insc_eleve1=='y') and ($test_aut_insc_eleve2=='y')) { // l'élève peut modifier son choix
                $msg .= "<br />Erreur sur le choix due à une activité sur 3 séquences. L'erreur a été corrigée. Vérifiez bien que la correction effectuée est exacte." ;
                $reg_choix_bas0[1] = $reg_choix_bas0[3];
                $statut_eleve0[1] = '';
                $reg_choix_bas0[2] = $reg_choix_bas0[3];
                $statut_eleve0[2] = '';
                $duree_0[1] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas0[1]."'");
                $duree_0[2] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas0[2]."'");
            } else {
                $msg .= "<br />Erreur sur le choix du 3ème créneau : cette activité est sur 3 séquences et vous ne pouvez modifier le choix du 1er créneau ou le choix du 2ème créneau." ;
                $reg_choix_bas0[3] = '';
                $statut_eleve0[3] = '';
            }
    }

    // Test de verification sur le choix 1 par rapport à la durée des activités
    if ((isset($duree_1[1])) and ($duree_1[1] == 2) and ($reg_choix_bas1[1] != $reg_choix_bas1[2])) {
            $test_aut_insc_eleve = sql_query1("select aut_insc_eleve from bas_j_eleves_bas_insc where id_eleve = '".$current_eleve_login."' and num_bas='".$numero_bas."' and num_sequence = '2' and num_choix = '1'");
            if ($test_aut_insc_eleve!='y') { // l'élève peut modifier son choix
                if (($reg_choix_bas1[2]) != '') 
                   $msg .= "<br />Erreur sur le choix du 2ème créneau. L'erreur a été corrigée. Vérifiez bien que la correction effectuée est exacte." ;
                $reg_choix_bas1[2] = $reg_choix_bas1[1];
                $statut_eleve1[2] = '';
                $duree_1[2] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas0[2]."'");
            } else {
                $msg .= "<br />Erreur le choix du 1er créneau : cette activité est sur deux séquences et vous ne pouvez modifier le choix du 2ème créneau." ;
                $reg_choix_bas1[1] = '';
                $statut_eleve1[1] = '';
            }
    }
    if ((isset($duree_1[1])) and ($duree_1[1] == 3) and ( ($reg_choix_bas1[1] != $reg_choix_bas1[2]) or ($reg_choix_bas1[1] != $reg_choix_bas1[3]))) {
       $test_aut_insc_eleve2 = sql_query1("select aut_insc_eleve from bas_j_eleves_bas_insc where id_eleve = '".$current_eleve_login."' and num_bas='".$numero_bas."' and num_sequence = '2' and num_choix = '1'");
       $test_aut_insc_eleve3 = sql_query1("select aut_insc_eleve from bas_j_eleves_bas_insc where id_eleve = '".$current_eleve_login."' and num_bas='".$numero_bas."' and num_sequence = '3' and num_choix = '1'");
       if (($test_aut_insc_eleve2!='y') and ($test_aut_insc_eleve3!='y'))  { // l'élève peut modifier son choix
          $msg .= "<br />Erreur sur le choix du 2ème créneau ou 3ème créneau. L'erreur a été corrigée. Vérifiez bien que la correction effectuée est exacte." ;
          $reg_choix_bas1[2] = $reg_choix_bas1[1];
          $reg_choix_bas1[3] = $reg_choix_bas1[1];
          $statut_eleve1[2] = '';
          $statut_eleve1[3] = '';
          $duree_1[2] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas1[2]."'");
          $duree_1[3] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas1[3]."'");
       } else {
          $msg .= "<br />Erreur sur le choix du 1er créneau : cette activité est sur 3 séquences et vous ne pouvez modifier le choix du 2ème créneau ou du 3ème créneau." ;
          $reg_choix_bas1[1] = '';
          $statut_eleve1[1] = '';
       }
    }
    if ((isset($duree_1[2])) and ($duree_1[2] == 2) and ($reg_choix_bas1[1] != $reg_choix_bas1[2]) and ($reg_choix_bas1[2] != $reg_choix_bas1[3])) {
            $debut_fin = sql_query1("select debut_final from bas_propositions where id_bas = '".$reg_choix_bas1[2]."'");
            if ($debut_fin == 1) {
                $reg_choix_bas1[2] = "";
                $statut_eleve1[2] = '';
                $msg .= "<br />Erreur sur le 1er choix du 2ème créneau. Veuillez entrer une nouvelle valeur." ;
            } else {
               $test_aut_insc_eleve = sql_query1("select aut_insc_eleve from bas_j_eleves_bas_insc where id_eleve = '".$current_eleve_login."' and num_bas='".$numero_bas."' and num_sequence = '3' and num_choix = '1'");
               if ($test_aut_insc_eleve=='y') { // l'élève peut modifier son choix
               if (($reg_choix_bas1[3]) != '') 
                   $msg .= "<br />Erreur sur le choix du 3ème créneau. L'erreur a été corrigée. Vérifiez bien que la correction effectuée est exacte." ;
                $reg_choix_bas1[3] = $reg_choix_bas1[2];
                $statut_eleve1[3] = '';
                $duree_1[3] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas1[3]."'");
               } else {
                $msg .= "<br />Erreur sur le choix du 2ème créneau : cette activité est sur deux séquences et vous ne pouvez modifier le choix du 3ème créneau." ;
                $reg_choix_bas1[2] = '';
                $statut_eleve1[2] = '';
               }
            }
    }
    if ((isset($duree_1[3])) and ($duree_1[3] == 2) and ( $reg_choix_bas1[2] != $reg_choix_bas1[3]) ) {
            $msg .= "<br />Erreur sur le 1er choix du 3ème créneau. Veuillez entrer une nouvelle valeur." ;
            $reg_choix_bas1[3] = "";
            $statut_eleve1[3] = '';
    }

    if ((isset($duree_1[2])) and ($duree_1[2] == 3) and (($reg_choix_bas1[1] != $reg_choix_bas1[2]) or ($reg_choix_bas1[2] != $reg_choix_bas1[3]))) {
            $test_aut_insc_eleve1 = sql_query1("select aut_insc_eleve from bas_j_eleves_bas_insc where id_eleve = '".$current_eleve_login."' and num_bas='".$numero_bas."' and num_sequence = '1' and num_choix = '1'");
            $test_aut_insc_eleve3 = sql_query1("select aut_insc_eleve from bas_j_eleves_bas_insc where id_eleve = '".$current_eleve_login."' and num_bas='".$numero_bas."' and num_sequence = '3' and num_choix = '1'");
            if (($test_aut_insc_eleve1!='y') and ($test_aut_insc_eleve3!='y')) { // l'élève peut modifier son choix
                $msg .= "<br />Erreur sur le choix due à une activité sur 3 séquences. L'erreur a été corrigée. Vérifiez bien que la correction effectuée est exacte." ;
                $reg_choix_bas1[1] = $reg_choix_bas1[2];
                $statut_eleve1[1] = '';
                $reg_choix_bas1[3] = $reg_choix_bas1[2];
                $statut_eleve1[3] = '';
                $duree_1[1] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas1[1]."'");
                $duree_1[3] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas1[3]."'");
            } else {
                $msg .= "<br />Erreur sur le choix du 2ème créneau : cette activité est sur 3 séquences et vous ne pouvez modifier le choix du 1er créneau ou le choix du 3ème créneau." ;
                $reg_choix_bas1[2] = '';
                $statut_eleve1[2] = '';
            }
    }
    if ((isset($duree_1[3])) and ($duree_1[3] == 3) and (( $reg_choix_bas1[2] != $reg_choix_bas1[3]) or ( $reg_choix_bas1[1] != $reg_choix_bas1[3]))  ) {
            $test_aut_insc_eleve1 = sql_query1("select aut_insc_eleve from bas_j_eleves_bas_insc where id_eleve = '".$current_eleve_login."' and num_bas='".$numero_bas."' and num_sequence = '1' and num_choix = '1'");
            $test_aut_insc_eleve2 = sql_query1("select aut_insc_eleve from bas_j_eleves_bas_insc where id_eleve = '".$current_eleve_login."' and num_bas='".$numero_bas."' and num_sequence = '2' and num_choix = '1'");
            if (($test_aut_insc_eleve1=='y') and ($test_aut_insc_eleve2=='y')) { // l'élève peut modifier son choix
                $msg .= "<br />Erreur sur le choix due à une activité sur 3 séquences. L'erreur a été corrigée. Vérifiez bien que la correction effectuée est exacte." ;
                $reg_choix_bas1[1] = $reg_choix_bas1[3];
                $statut_eleve1[1] = '';
                $reg_choix_bas1[2] = $reg_choix_bas1[3];
                $statut_eleve1[2] = '';
                $duree_1[1] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas1[1]."'");
                $duree_1[2] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas1[2]."'");
            } else {
                $msg .= "<br />Erreur sur le choix du 3ème créneau : cette activité est sur 3 séquences et vous ne pouvez modifier le choix du 1er créneau ou le choix du 2ème créneau." ;
                $reg_choix_bas1[3] = '';
                $statut_eleve1[3] = '';
            }
    }



    // Test de verification sur le choix 2 par rapport à la durée des activités
    if ((isset($duree_2[1])) and ($duree_2[1] == 2) and ($reg_choix_bas2[1] != $reg_choix_bas2[2])) {
            $test_aut_insc_eleve = sql_query1("select aut_insc_eleve from bas_j_eleves_bas_insc where id_eleve = '".$current_eleve_login."' and num_bas='".$numero_bas."' and num_sequence = '2' and num_choix = '2'");
            if ($test_aut_insc_eleve!='y') { // l'élève peut modifier son choix
                if (($reg_choix_bas2[2]) != '') 
                   $msg .= "<br />Erreur sur le choix du 2ème créneau. L'erreur a été corrigée. Vérifiez bien que la correction effectuée est exacte." ;
                $reg_choix_bas2[2] = $reg_choix_bas2[1];
                $statut_eleve2[2] = '';
                $duree_2[2] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas0[2]."'");
            } else {
                $msg .= "<br />Erreur le choix du 1er créneau : cette activité est sur deux séquences et vous ne pouvez modifier le choix du 2ème créneau." ;
                $reg_choix_bas2[1] = '';
                $statut_eleve2[1] = '';
            }
    }
    if ((isset($duree_2[1])) and ($duree_2[1] == 3) and ( ($reg_choix_bas2[1] != $reg_choix_bas2[2]) or ($reg_choix_bas2[1] != $reg_choix_bas2[3]))) {
       $test_aut_insc_eleve2 = sql_query1("select aut_insc_eleve from bas_j_eleves_bas_insc where id_eleve = '".$current_eleve_login."' and num_bas='".$numero_bas."' and num_sequence = '2' and num_choix = '2'");
       $test_aut_insc_eleve3 = sql_query1("select aut_insc_eleve from bas_j_eleves_bas_insc where id_eleve = '".$current_eleve_login."' and num_bas='".$numero_bas."' and num_sequence = '3' and num_choix = '2'");
       if (($test_aut_insc_eleve2!='y') and ($test_aut_insc_eleve3!='y'))  { // l'élève peut modifier son choix
          $msg .= "<br />Erreur sur le choix du 2ème créneau ou 3ème créneau. L'erreur a été corrigée. Vérifiez bien que la correction effectuée est exacte." ;
          $reg_choix_bas2[2] = $reg_choix_bas2[1];
          $reg_choix_bas2[3] = $reg_choix_bas2[1];
          $statut_eleve2[2] = '';
          $statut_eleve2[3] = '';
          $duree_2[2] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas2[2]."'");
          $duree_2[3] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas2[3]."'");
       } else {
          $msg .= "<br />Erreur sur le choix du 1er créneau : cette activité est sur 3 séquences et vous ne pouvez modifier le choix du 2ème créneau ou du 3ème créneau." ;
          $reg_choix_bas2[1] = '';
          $statut_eleve2[1] = '';
       }
    }
    if ((isset($duree_2[2])) and ($duree_2[2] == 2) and ($reg_choix_bas2[1] != $reg_choix_bas2[2]) and ($reg_choix_bas2[2] != $reg_choix_bas2[3])) {
            $debut_fin = sql_query1("select debut_final from bas_propositions where id_bas = '".$reg_choix_bas2[2]."'");
            if ($debut_fin == 1) {
                $reg_choix_bas2[2] = "";
                $statut_eleve2[2] = '';
                $msg .= "<br />Erreur sur le 2ème choix du 2ème créneau. Veuillez entrer une nouvelle valeur." ;
            } else {
               $test_aut_insc_eleve = sql_query1("select aut_insc_eleve from bas_j_eleves_bas_insc where id_eleve = '".$current_eleve_login."' and num_bas='".$numero_bas."' and num_sequence = '3' and num_choix = '2'");
               if ($test_aut_insc_eleve=='y') { // l'élève peut modifier son choix
               if (($reg_choix_bas2[3]) != '') 
                   $msg .= "<br />Erreur sur le choix du 3ème créneau. L'erreur a été corrigée. Vérifiez bien que la correction effectuée est exacte." ;
                $reg_choix_bas2[3] = $reg_choix_bas2[2];
                $statut_eleve2[3] = '';
                $duree_2[3] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas2[3]."'");
               } else {
                $msg .= "<br />Erreur sur le choix du 2ème créneau : cette activité est sur deux séquences et vous ne pouvez modifier le choix du 3ème créneau." ;
                $reg_choix_bas2[2] = '';
                $statut_eleve2[2] = '';
               }
            }
    }
    if ((isset($duree_2[3])) and ($duree_2[3] == 2) and ( $reg_choix_bas2[2] != $reg_choix_bas2[3]) ) {
            $msg .= "<br />Erreur sur le 2ème choix du 3ème créneau. Veuillez entrer une nouvelle valeur." ;
            $reg_choix_bas2[3] = "";
            $statut_eleve2[3] = '';

    }

    if ((isset($duree_2[2])) and ($duree_2[2] == 3) and (($reg_choix_bas2[1] != $reg_choix_bas2[2]) or ($reg_choix_bas2[2] != $reg_choix_bas2[3]))) {
            $test_aut_insc_eleve1 = sql_query1("select aut_insc_eleve from bas_j_eleves_bas_insc where id_eleve = '".$current_eleve_login."' and num_bas='".$numero_bas."' and num_sequence = '1' and num_choix = '2'");
            $test_aut_insc_eleve3 = sql_query1("select aut_insc_eleve from bas_j_eleves_bas_insc where id_eleve = '".$current_eleve_login."' and num_bas='".$numero_bas."' and num_sequence = '3' and num_choix = '2'");
            if (($test_aut_insc_eleve1!='y') and ($test_aut_insc_eleve3!='y')) { // l'élève peut modifier son choix
                $msg .= "<br />Erreur sur le choix due à une activité sur 3 séquences. L'erreur a été corrigée. Vérifiez bien que la correction effectuée est exacte." ;
                $reg_choix_bas2[1] = $reg_choix_bas2[2];
                $statut_eleve2[1] = '';
                $reg_choix_bas2[3] = $reg_choix_bas2[2];
                $statut_eleve2[3] = '';
                $duree_2[1] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas2[1]."'");
                $duree_2[3] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas2[3]."'");
            } else {
                $msg .= "<br />Erreur sur le choix du 2ème créneau : cette activité est sur 3 séquences et vous ne pouvez modifier le choix du 1er créneau ou le choix du 3ème créneau." ;
                $reg_choix_bas2[2] = '';
                $statut_eleve2[2] = '';
            }
    }
    if ((isset($duree_2[3])) and ($duree_2[3] == 3) and (( $reg_choix_bas2[2] != $reg_choix_bas2[3]) or ( $reg_choix_bas2[1] != $reg_choix_bas2[3]))  ) {
            $test_aut_insc_eleve1 = sql_query1("select aut_insc_eleve from bas_j_eleves_bas_insc where id_eleve = '".$current_eleve_login."' and num_bas='".$numero_bas."' and num_sequence = '1' and num_choix = '2'");
            $test_aut_insc_eleve2 = sql_query1("select aut_insc_eleve from bas_j_eleves_bas_insc where id_eleve = '".$current_eleve_login."' and num_bas='".$numero_bas."' and num_sequence = '2' and num_choix = '2'");
            if (($test_aut_insc_eleve1=='y') and ($test_aut_insc_eleve2=='y')) { // l'élève peut modifier son choix
                $msg .= "<br />Erreur sur le choix due à une activité sur 3 séquences. L'erreur a été corrigée. Vérifiez bien que la correction effectuée est exacte." ;
                $reg_choix_bas2[1] = $reg_choix_bas2[3];
                $statut_eleve2[1] = '';
                $reg_choix_bas2[2] = $reg_choix_bas2[3];
                $statut_eleve2[2] = '';
                $duree_2[1] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas2[1]."'");
                $duree_2[2] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas2[2]."'");
            } else {
                $msg .= "<br />Erreur sur le choix du 3ème créneau : cette activité est sur 3 séquences et vous ne pouvez modifier le choix du 1er créneau ou le choix du 2ème créneau." ;
                $reg_choix_bas2[3] = '';
                $statut_eleve2[3] = '';
            }
    }


    // Enregistrement des modifications
    $k=1;
    while ($k < count($per)+1) {
            // On enregistre l'élève s'il n'est pas déjà affecté
            if ($statut_eleve0[$k] == '') {
            // On teste si il y a sureffectif
                $nb_bloque = sql_query1("select nb_bloque from bas_propositions where id_bas = '".$reg_choix_bas0[$k]."'");
                $nb_inscrit = sql_query1("select count(id_eleve) from bas_j_eleves_bas where
                num_bas = '".$numero_bas."' and  id_bas = '".$reg_choix_bas0[$k]."' and num_choix='0' and num_sequence='".$k."'");
                if (($nb_bloque != -1) and ($nb_bloque <= $nb_inscrit) and ($active_blocage=='y')) {
                    $msg .= "<br />Erreur : vous avez tenté de vous inscrire à une activité bloquée pour cause de sureffectif.";
                } else {
                    $test_aut_insc_eleve0 = sql_query1("select aut_insc_eleve from bas_j_eleves_bas_insc where id_eleve = '".$current_eleve_login."' and num_bas='".$numero_bas."' and num_sequence = '".$k."' and num_choix = '0'");
                    if (($test_aut_insc_eleve0=='y') and ($inscription_bas=='a')) {
                      // Suppression
                      $req = mysql_query("delete from bas_j_eleves_bas where
                      id_eleve = '".$current_eleve_login."' and num_bas='".$numero_bas."' and num_sequence = '".$k."' and num_choix = '0'");
                      // Enregistrement
                      if ($reg_choix_bas0[$k] != '') $req = mysql_query("insert into bas_j_eleves_bas set
                      id_eleve = '".$current_eleve_login."',
                      num_bas='".$numero_bas."',
                      num_sequence = '".$k."',
                      num_choix = '0',
                      id_bas = '".$reg_choix_bas0[$k]."',
                      priorite = ''
                      ");
                    }
                }
            }
            if ($statut_eleve1[$k] == '') {
                // On teste si il y a sureffectif
                $nb_bloque = sql_query1("select nb_bloque from bas_propositions where id_bas = '".$reg_choix_bas1[$k]."'");
                $nb_inscrit = sql_query1("select count(id_eleve) from bas_j_eleves_bas where
                num_bas = '".$numero_bas."' and  id_bas = '".$reg_choix_bas1[$k]."' and num_choix='1' and num_sequence='".$k."'");
                if (($nb_bloque != -1) and ($nb_bloque <= $nb_inscrit)  and ($active_blocage=='y')) {
                    $msg .= "<br />Erreur : vous avez tenté de vous inscrire à une activité bloquée pour cause de sureffectif.";
                } else {
                    $test_aut_insc_eleve1 = sql_query1("select aut_insc_eleve from bas_j_eleves_bas_insc where id_eleve = '".$current_eleve_login."' and num_bas='".$numero_bas."' and num_sequence = '".$k."' and num_choix = '1'");
                    if (($test_aut_insc_eleve1!='y') and ($inscription_bas=='y')) {
                      // Suppression
                      $req = mysql_query("delete from bas_j_eleves_bas where
                      id_eleve = '".$current_eleve_login."' and
                      num_bas='".$numero_bas."' and
                      num_sequence = '".$k."'
                      and num_choix = '1'");
                      // Enregistrement
                      if ($reg_choix_bas1[$k] != '') $req = mysql_query("insert into bas_j_eleves_bas set
                      id_eleve = '".$current_eleve_login."',
                      num_bas='".$numero_bas."',
                      num_sequence = '".$k."',
                      num_choix = '1',
                      id_bas = '".$reg_choix_bas1[$k]."'
                      ");
                    }
               }
            }

            if ($statut_eleve2[$k] == '') {
            // On enregistre que l'élève n'est pas déjà affecté
                // On teste si il y a sureffectif
                $nb_bloque = sql_query1("select nb_bloque from bas_propositions where id_bas = '".$reg_choix_bas2[$k]."'");
                $nb_inscrit = sql_query1("select count(id_eleve) from bas_j_eleves_bas where
                num_bas = '".$numero_bas."' and  id_bas = '".$reg_choix_bas2[$k]."' and num_choix='1' and num_sequence='".$k."'");
                if (($nb_bloque != -1) and ($nb_bloque <= $nb_inscrit)  and ($active_blocage=='y')) {
                    $msg .= "<br />Erreur : vous avez tenté de vous inscrire à une activité bloquée pour cause de sureffectif.";
                } else {
                    $test_aut_insc_eleve2 = sql_query1("select aut_insc_eleve from bas_j_eleves_bas_insc where id_eleve = '".$current_eleve_login."' and num_bas='".$numero_bas."' and num_sequence = '".$k."' and num_choix = '2'");
                    if (($test_aut_insc_eleve2!='y') and ($inscription_bas=='y')) {
                      // Suppression
                      $req = mysql_query("delete from bas_j_eleves_bas where
                      id_eleve = '".$current_eleve_login."' and num_bas='".$numero_bas."' and num_sequence = '".$k."' and num_choix = '2'");
                      // Enregistrement
                      if ($reg_choix_bas2[$k] != '')
                          $req = mysql_query("insert into bas_j_eleves_bas set
                      id_eleve = '".$current_eleve_login."',
                      num_bas='".$numero_bas."',
                      num_sequence = '".$k."',
                      num_choix = '2',
                      id_bas = '".$reg_choix_bas2[$k]."',
                      priorite = ''
                      ");
                    }
                }
            }
            $k++;
    }

    $msg .= "<br />Les Modifications ont été enregistrées.";
}



//**************** EN-TETE *****************
if (!isset($numero_bas) or (isset($action))) $titre_page = "Gestion de mes activités BAS";
require_once("../../lib/header.inc.php");
//**************** FIN EN-TETE *****************


// choix du bas
if (!(isset($numero_bas))) {
    echo "<p class=bold>| <a href=\"../../accueil.php\">Retour à la page d'accueil</a> |";
    echo "<p class='grand'>Effectuez votre choix : </p>";
    echo "<table cellpadding=\"3\" border=\"1\">";
    $req = mysql_query("select * from bas_bas order by nom");
    $nb_bas = mysql_num_rows($req);
    $i = 0 ;
    while ($i < $nb_bas) {
        $num_bas = mysql_result($req,$i,'id_bas');
        $date_bas = mysql_result($req,$i,'date_bas');
        $nom_bas = mysql_result($req,$i,'nom');
        $description_bas = mysql_result($req,$i,'description_bas');
        $close_bas = mysql_result($req,$i,'close_bas');
        $aff_affectations_eleves = mysql_result($req,$i,'aff_affectations_eleves');
        $inscription_bas = mysql_result($req,$i,'inscription_bas');
        $aut_insc_eleve = mysql_result($req,$i,'aut_insc_eleve');
        echo "<tr><td><b>".$nom_bas."</b> du ".$date_bas."</td>\n";
        if (($inscription_bas == "y") or ($inscription_bas == "a") or ($inscription_bas == "r") or ($aff_affectations_eleves == "y"))
          echo "<td><a href='index_eleve.php?numero_bas=".$num_bas."&amp;action=inscription&amp;login_prof=".$_SESSION['login']."' > M'inscrire - Voir mes choix</a></td>\n";
        else 
          echo "<td>&nbsp;</td>\n";
        echo "</tr>";
        $i++;
    }
    echo "</table>";
}

if (isset($numero_bas)) {
    if (isset($action)) {
        echo "<p class=bold>| <a href=\"../../accueil.php\">Retour à la page d'accueil</a> |
        <a href=\"./index_eleve.php\">Retour au choix du BAS</a> |
        </p>";
    }
    // données sur le bas
    $date_bas = sql_query1("select date_bas from bas_bas where id_bas='".$numero_bas."'");
    $nom_bas = sql_query1("select nom from bas_bas where id_bas='".$numero_bas."'");
    $num_periode = sql_query1("select num_periode from bas_bas where id_bas='".$numero_bas."'");
    $close_bas = sql_query1("select close_bas from bas_bas where id_bas='".$numero_bas."'");
    $date_limite = sql_query1("select date_limite from bas_bas where id_bas='".$numero_bas."'");
    $description_bas = sql_query1("select description_bas from bas_bas where id_bas='".$numero_bas."'");
    $inscription_bas = sql_query1("select inscription_bas from bas_bas where id_bas='".$numero_bas."'");
    // $inscription_bas= a -> Affectations uniquement
    // $inscription_bas= y -> Inscriptions possibles
    // $inscription_bas = r -> Inscriptions reméd. uniquement
    // $inscription_bas= n -> Inscriptions et affectations fermées
    $aut_insc_eleve = sql_query1("select aut_insc_eleve from bas_bas where id_bas='".$numero_bas."'");
    // $aut_insc_eleve = y -> l'élève est autorisé à s'inscrire
    // $aut_insc_eleve = n -> l'élève n'est pas autorisé à s'inscrire
    
    // Constitution du tableau $per
    $per =  tableau_periode($numero_bas);
    $appel_donnees_eleves = mysql_query("SELECT DISTINCT e.* FROM eleves e WHERE (e.login = '".$_SESSION['login']."')");
    $current_eleve_nom = mysql_result($appel_donnees_eleves, 0, "nom");
    $current_eleve_prenom = mysql_result($appel_donnees_eleves, 0, "prenom");
    $id_filiere = sql_query1("SELECT DISTINCT id_filiere FROM bas_j_eleves_filieres WHERE (id_eleve='".$_SESSION['login']."') ");

    // Détermination de la classe
    $stop = 0;
    $id_classe = sql_query1("select c.id from classes c, j_eleves_classes j where
    (j.id_classe=c.id and j.login='".$_SESSION['login']."' and periode='".$num_periode."')");
    if ($id_classe != -1) $stop = 1;
    if ($stop == 0) echo "<p>Un problème est survenu. Veuillez contacter l'administrateur.</p></body></html>";

    // Détermination du professeur de suivi
    $login_prof = sql_query1("select DISTINCT professeur from j_eleves_professeurs j  where (j.login='".$_SESSION['login']."' and j.id_classe='".$id_classe."')");
    $nom_prof = sql_query1("select nom from utilisateurs u where (login = '".$login_prof."')");
    echo "<p class='grand'>";
    echo $nom_bas." du ".$date_bas." - ";
    $civilite = sql_query1("select civilite from utilisateurs where login = '".$login_prof."'");

    echo "Groupe de suivi de ".$civilite." ".$nom_prof;
}



// formulaire de saisie des inscriptions
if (isset($action) and ($action=="inscription") ) {
    $flag_bouton='n';
    if (($inscription_bas == "a") and ($aut_insc_eleve=='y'))
        echo " - Saisie des affectations finales</p>\n";
    else if (($inscription_bas == "y")  and ($aut_insc_eleve=='y'))
        echo " - Saisie des inscriptions</p>\n";
    else 
        echo " - Visualisation des inscriptions</p>\n";

    if (($inscription_bas == "a")   and ($aut_insc_eleve=='y')) {
        echo "<ul>
        <li>Choisissez ci-dessous une activité.</li>
        <li>Les activités suivies de **Bloqué** correspondent à des activités en sureffectif pour lesquelles l'affectation est impossible.</li>
        </ul>";
    } else if (($inscription_bas == "y")   and ($aut_insc_eleve=='y')) {    
        echo "<ul>
        <li>Reportez ci-dessous vos choix.</li>
        <li>Vous devez effectuer deux choix pour chaque créneau horaire : en fonction du nombre de places disponibles dans l'activité, si le premier choix ne peut être respecté, c'est le deuxième choix qui sera retenu, toujours dans la limite des possibilités.</li>
        <li>Les activités suivies de **D** correspondent à des activités de remédiation à public désigné. Seuls les élèves listés par le professeur encadrant peuvent être inscrits à ces activités.</li>";
        if ($active_blocage == "y")
            echo "<li>Les activités suivies de **Bloqué** correspondent à des activités en sureffectif pour lesquelles l'inscription est impossible.</li>";
        else
            echo "<li>Les activités suivies de **Surnombre** correspondent à des activités en sureffectif pour lesquelles l'inscription est néanmoins possible.</li>";
        echo "</ul>";
    }
    
    // En-tete du tableau
    echo "<form action=\"index_eleve.php\" name=\"inscription\" method=\"post\">\n";
    echo "<table border=\"1\" cellspacing=\"1\" cellpadding=\"5\">
    <tr>
    <td><b><span class='style_bas'>Nom Prénom</span></b></td>";
    $k = 1;
    while ($k < count($per)+1) {
        echo "<td width=\"100\" colspan=\"3\"><b>Heure : ".$per[$k]."</b></td>";
        $k++;
    }
    echo "</tr>";
    echo "<tr><td>&nbsp;</td>";
    $k = 1;
    while ($k < count($per)+1) {
        echo "<td>Affectation finale</td>";
        echo "<td width=\"100\">Choix N°1</td><td width=\"100\">Choix N°2</td>";
        $k++;
    }
    echo "</tr>";
    // Fin En-tete du tableau

    $nb_prop=array();
    if ($id_filiere!=-1) {
      // Les propositions :
      $req_prop1 = mysql_query("select id_prop, id_bas, type, statut, nb_bloque from bas_propositions
      where public_".$id_filiere." != '' and debut_final = '1' and num_bas = '".$numero_bas."' and statut!='a' order by id_prop");
      $req_prop2 = mysql_query("select id_prop, id_bas, type, statut, nb_bloque from bas_propositions where
      public_".$id_filiere." != '' and (debut_final = '2' or (debut_final = '1' and duree = '2') OR (debut_final = '1' and duree = '3')) and num_bas = '".$numero_bas."'  and statut!='a' order by id_prop");
      $req_prop3 = mysql_query("select id_prop, id_bas, type, statut, nb_bloque from bas_propositions where
      public_".$id_filiere." != '' and (debut_final = '3' or (debut_final = '2' and duree = '2') or (debut_final = '1' and duree = '3')) and num_bas = '".$numero_bas."'  and statut!='a' order by id_prop");
      $nb_prop[1] = mysql_num_rows($req_prop1);
      $k = 0;
      while ($k < $nb_prop[1]) {
          $id_bas[1][$k] = mysql_result($req_prop1,$k,'id_bas');
          $id_propo[1][$k] = mysql_result($req_prop1,$k,'id_prop');
          $type_[1][$k] = mysql_result($req_prop1,$k,'type');
          $statut_[1][$k] = mysql_result($req_prop1,$k,'statut');
          $nb_bloque = mysql_result($req_prop1,$k,'nb_bloque');
          // Cas des affectation
          $nb_inscrit = sql_query1("select count(id_eleve) from bas_j_eleves_bas where
          num_bas = '".$numero_bas."' and  id_bas = '".$id_bas[1][$k]."' and num_choix='0' and num_sequence='1'");
          // Cas des affectation
          $nb_affect = sql_query1("select count(id_eleve) from bas_j_eleves_bas where
          num_bas = '".$numero_bas."' and  id_bas = '".$id_bas[1][$k]."' and num_choix='1' and num_sequence='1'");
          if ($inscription_bas == "a")
              // Cas des affectation
              if (($nb_bloque != -1) and ($nb_bloque <= $nb_inscrit)) $bloque_[1][$k] = 'y'; else $bloque_[1][$k] = 'n';
          else
              // Cas des inscription
              if (($nb_bloque != -1) and ($nb_bloque <= $nb_affect)) $bloque_[1][$k] = 'y'; else $bloque_[1][$k] = 'n';
          $k++;
      }
      $nb_prop[2] = mysql_num_rows($req_prop2);
      $k = 0;
      while ($k < $nb_prop[2]) {
          $id_bas[2][$k] = mysql_result($req_prop2,$k,'id_bas');
          $id_propo[2][$k] = mysql_result($req_prop2,$k,'id_prop');
          $type_[2][$k] = mysql_result($req_prop2,$k,'type');
          $statut_[2][$k] = mysql_result($req_prop2,$k,'statut');
          $nb_bloque = mysql_result($req_prop2,$k,'nb_bloque');
          // Cas des affectation
          $nb_inscrit = sql_query1("select count(id_eleve) from bas_j_eleves_bas where
          num_bas = '".$numero_bas."' and  id_bas = '".$id_bas[2][$k]."' and num_choix='0' and num_sequence='2'");
          // Cas des inscription
          $nb_affect = sql_query1("select count(id_eleve) from bas_j_eleves_bas where
          num_bas = '".$numero_bas."' and  id_bas = '".$id_bas[2][$k]."' and num_choix='1' and num_sequence='2'");
          if ($inscription_bas == "a")
              // Cas des affectation
             if (($nb_bloque != -1) and ($nb_bloque <= $nb_inscrit)) $bloque_[2][$k] = 'y'; else $bloque_[2][$k] = 'n';
          else
              // Cas des inscription
              if (($nb_bloque != -1) and ($nb_bloque <= $nb_affect)) $bloque_[2][$k] = 'y'; else $bloque_[2][$k] = 'n';
          $k++;
      }
      $nb_prop[3] = mysql_num_rows($req_prop3);
      $k = 0;
      while ($k < $nb_prop[3]) {
          $id_bas[3][$k] = mysql_result($req_prop3,$k,'id_bas');
          $id_propo[3][$k] = mysql_result($req_prop3,$k,'id_prop');
          $type_[3][$k] = mysql_result($req_prop3,$k,'type');
          $statut_[3][$k] = mysql_result($req_prop3,$k,'statut');
          $nb_bloque = mysql_result($req_prop3,$k,'nb_bloque');
          // Cas des affectation
          $nb_inscrit = sql_query1("select count(id_eleve) from bas_j_eleves_bas where
          num_bas = '".$numero_bas."' and  id_bas = '".$id_bas[3][$k]."' and num_choix='0' and num_sequence='3'");
          // Cas des inscription
          $nb_affect = sql_query1("select count(id_eleve) from bas_j_eleves_bas where
          num_bas = '".$numero_bas."' and  id_bas = '".$id_bas[3][$k]."' and num_choix='1' and num_sequence='3'");
          if ($inscription_bas == "a")
              // Cas des affectation
              if (($nb_bloque != -1) and ($nb_bloque <= $nb_inscrit)) $bloque_[3][$k] = 'y'; else $bloque_[3][$k] = 'n';
          else
              // Cas des inscription
              if (($nb_bloque != -1) and ($nb_bloque <= $nb_affect)) $bloque_[3][$k] = 'y'; else $bloque_[3][$k] = 'n';
          $k++;
      }
    } else {
        echo "<p>Vous n'êtes actuellement affecté à aucune filière. Signalez ce problème à votre professeur de suivi afin qu'il règle ce problème";
    }
        
        
        // on écrit le tableau
        echo "<tr><td>$current_eleve_nom $current_eleve_prenom</td>\n";
        $k = 1;
        while ($k < count($per)+1) {
            $reg_choix_eleve0 = sql_query1("select id_bas from bas_j_eleves_bas where
            id_eleve = '".$_SESSION['login']."' and
            num_bas='".$numero_bas."' and
            num_sequence = '".$k."' and
            num_choix = '0'
            ");

            // Attention : le paramètre aut_insc_eleve fonctionne comme suit :
            // pour le choix "0" : aut_insc_eleve=y -> l'élève peut modifier
            //                     aut_insc_eleve!=y -> l'élève ne peut pas modifier
            // pour les choix "1" et "2" : aut_insc_eleve=y -> l'élève ne peut pas modifier
            //                            aut_insc_eleve!=y -> l'élève peut modifier

            $reg_aut_insc_eleve0 = sql_query1("select aut_insc_eleve from bas_j_eleves_bas_insc where
            id_eleve = '".$_SESSION['login']."' and
            num_bas='".$numero_bas."' and
            num_sequence = '".$k."' and
            num_choix = '0'
            ");

            $reg_choix_eleve1 = sql_query1("select id_bas from bas_j_eleves_bas where
            id_eleve = '".$_SESSION['login']."' and
            num_bas='".$numero_bas."' and
            num_sequence = '".$k."' and
            num_choix = '1'
            ");
            $reg_aut_insc_eleve1 = sql_query1("select aut_insc_eleve from bas_j_eleves_bas_insc where
            id_eleve = '".$_SESSION['login']."' and
            num_bas='".$numero_bas."' and
            num_sequence = '".$k."' and
            num_choix = '1'
            ");
            
            $reg_choix_eleve2 = sql_query1("select id_bas from bas_j_eleves_bas where
            id_eleve = '".$_SESSION['login']."' and
            num_bas='".$numero_bas."' and
            num_sequence = ".$k." and
            num_choix = '2'
            ");

            $reg_aut_insc_eleve2 = sql_query1("select aut_insc_eleve from bas_j_eleves_bas_insc where
            id_eleve = '".$_SESSION['login']."' and
            num_bas='".$numero_bas."' and
            num_sequence = '".$k."' and
            num_choix = '2'
            ");

            // affectation (choix 0))
            echo "<td>";
            if (($inscription_bas=='a') and ($reg_aut_insc_eleve0=='y')   and ($aut_insc_eleve=='y')) {
                $flag_bouton='y';
                // L'élève peut procéder à son affectation
                echo "<select name=\"choix_bas0_".$k."_".$_SESSION['login']."\" size=\"1\">\n";
                echo "<option value=''>(choisissez)</option>\n";
                echo "<option value='abs' ";
                if ($reg_choix_eleve0 == 'abs') echo "selected";
                echo ">(Absent)</option>\n";
                $n = 0;
                while ($n < $nb_prop[$k]) {
                    // si l'activité est bloqué
                    if (($reg_choix_eleve0 != $id_bas[$k][$n]) and ($bloque_[$k][$n] == 'y') )
                        echo "<option value='bloque' ";
                    else
                        echo "<option value='".$id_bas[$k][$n]."' ";

                    if ($reg_choix_eleve0 == $id_bas[$k][$n]) echo "selected";
                    echo ">".$id_propo[$k][$n];
                    if ($type_[$k][$n] == 'R') echo " **D**";
                    if (($reg_choix_eleve0 != $id_bas[$k][$n]) and ($bloque_[$k][$n] == 'y'))
                        echo " **Bloqué**";
                    echo "</option>\n";
                    $n++;
                }
                echo "</select>";
            } else {
               // On affiche 
              $id_prop0 = sql_query1("select id_prop from bas_propositions where id_bas='".$reg_choix_eleve0."'");
              if ($id_prop0 != "-1") echo $id_prop0; else echo "&nbsp;";
            }
            echo "</td>\n";


            // Inscription choix 1
            $id_prop1 = sql_query1("select id_prop from bas_propositions where id_bas='".$reg_choix_eleve1."'");
            echo "<td>";
            if (($inscription_bas=='y') and ($reg_aut_insc_eleve1!='y') and ($aut_insc_eleve=='y')) {
                    $flag_bouton='y';                    
                    // L'élève n'est pas encore affecté
                    echo "<select name=\"choix_bas1_".$k."_".$_SESSION['login']."\" size=\"1\">\n";
                    echo "<option value=''>(choisissez)</option>\n";
                    echo "<option value='abs' ";
                    if ($reg_choix_eleve1 == 'abs') echo "selected";
                    echo ">(Absent)</option>\n";
                    $n = 0;
                    while ($n < $nb_prop[$k]) {
                        // si l'activité est bloqué
                        if (($reg_choix_eleve1 != $id_bas[$k][$n]) and ($bloque_[$k][$n] == 'y')  and ($active_blocage=='y'))
                            echo "<option value='bloque' ";
                        else
                            echo "<option value='".$id_bas[$k][$n]."' ";
                        if ($reg_choix_eleve1 == $id_bas[$k][$n]) echo "selected";
                        echo ">".$id_propo[$k][$n];
                        if ($type_[$k][$n] == 'R') echo " **D**";
                        if (($bloque_[$k][$n] == 'y'))
                          if ($active_blocage=='y')
                            echo " **Bloqué**";
                          else
                            echo " **Surnombre**";
                        echo "</option>\n";
                        $n++;
                    }
                    echo "</select>";
                  } else {
                    if ($id_prop1 != "-1") echo $id_prop1; else echo "&nbsp;";
                  }
                echo "</td>\n";
                echo "<td>";
                $id_prop2 = sql_query1("select id_prop from bas_propositions where id_bas='".$reg_choix_eleve2."'");
            if (($inscription_bas=='y') and ($reg_aut_insc_eleve2!='y') and ($aut_insc_eleve=='y')) {
                    $flag_bouton='y';
                    // L'élève n'est pas encore affecté
                    echo "<select name=\"choix_bas2_".$k."_".$_SESSION['login']."\" size=\"1\">\n";
                    echo "<option value=''>(choisissez)</option>\n";
                    echo "<option value='abs' ";
                    if ($reg_choix_eleve1 == 'abs') echo "selected";
                    echo ">(Absent)</option>\n";
                    $n = 0;
                    while ($n < $nb_prop[$k]) {
                        if ($type_[$k][$n] != 'R') {
                        // si l'activité est bloqué
                        if (($reg_choix_eleve2 != $id_bas[$k][$n]) and ($bloque_[$k][$n] == 'y')  and ($active_blocage=='y'))
                            echo "<option value='bloque' ";
                        else
                            echo "<option value='".$id_bas[$k][$n]."' ";

                        if ($reg_choix_eleve2 == $id_bas[$k][$n]) echo "selected";
                        echo ">".$id_propo[$k][$n];
                        if ($type_[$k][$n] == 'R') echo " **D**";
                        if (($reg_choix_eleve2 != $id_bas[$k][$n]) and ($bloque_[$k][$n] == 'y'))
                        if ($active_blocage=='y')
                            echo " **Bloqué**";
                        else
                            echo " **Surnombre**";
                        echo "</option>\n";
                        }
                        $n++;
                    }

                    echo "</select>";
                  } else {
                    if ($id_prop2 != "-1") echo $id_prop2; else echo "&nbsp;";
                  }
                echo "</td>\n";
            $k++;
        }
        echo "</tr>";

    echo "</table>";
    echo "<div id=\"fixe\">";
    if ($flag_bouton=='y') {    
      echo "<center><input type=\"submit\" name=\"ok\" value=\"Enregistrer\" /></center>";
      echo "<input type=\"hidden\" name=\"is_posted\" value=\"yes\" />";
      echo "<input type=\"hidden\" name=\"action\" value=\"inscription\" />";
      echo "<input type=\"hidden\" name=\"numero_bas\" value=\"".$numero_bas."\" />";
    }
    echo "</div></form>\n";
    // Affichage de la liste des actvités
    include "./code_liste_bas_par_classe.php";
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