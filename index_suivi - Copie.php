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
$nom_script = "mod_plugins/gestion_ateliers/index_suivi.php";
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
$login_prof = isset($_POST['login_prof']) ? $_POST['login_prof'] : (isset($_GET['login_prof']) ? $_GET['login_prof'] : NULL);
$active_blocage = sql_query1("select active_blocage from bas_bas where id_bas='".$numero_bas."'");

// Enregistrement des inscriptions
if (isset($action) and ($action=="inscription") and (isset($_POST['is_posted'])))  {
    $inscription_bas = sql_query1("select inscription_bas from bas_bas where id_bas='".$numero_bas."'");
    // Constitution du tableau $per
    $description_bas = sql_query1("select description_bas from bas_bas where id_bas='".$numero_bas."'");
    $per =  tableau_periode($numero_bas);

    $msg = '';
    $appel_donnees_eleves = mysql_query("SELECT DISTINCT e.* FROM eleves e, j_eleves_professeurs p
        WHERE (
           p.login = e.login AND
           p.professeur = '".$login_prof."'
           ) ORDER BY 'e.nom'");
    $nombre_lignes = mysql_num_rows($appel_donnees_eleves);
    $i = 0;
    while($i < $nombre_lignes) {
        $current_eleve_login = mysql_result($appel_donnees_eleves, $i, "login");
        $current_eleve_nom = mysql_result($appel_donnees_eleves, $i, "nom");
        $current_eleve_prenom = mysql_result($appel_donnees_eleves, $i, "prenom");
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
            // Les élèves peuvent-ils s'inscrire ?
            
            // Attention : le paramètre aut_insc_eleve fonctionne comme suit :
            // pour le choix "0" : aut_insc_eleve=y -> l'élève peut modifier
            //                     aut_insc_eleve!=y -> l'élève ne peut pas modifier
            // pour les choix "1" et "2" : aut_insc_eleve=y -> l'élève ne peut pas modifier
            //                            aut_insc_eleve!=y -> l'élève peut modifier
            if ($inscription_bas==a) {
              $temp = "aut_insc_eleve0_".$k."_".$current_eleve_login;
              if (isset($_POST[$temp])) $reg_aut_insc_eleve0[$k] = $_POST[$temp]; else $reg_aut_insc_eleve0[$k] = 'y';
            } else {
              $temp = "aut_insc_eleve1_".$k."_".$current_eleve_login;
              if (isset($_POST[$temp])) $reg_aut_insc_eleve1[$k] = $_POST[$temp]; else $reg_aut_insc_eleve1[$k] = '';
              $temp = "aut_insc_eleve2_".$k."_".$current_eleve_login;
              if (isset($_POST[$temp])) $reg_aut_insc_eleve2[$k] = $_POST[$temp]; else $reg_aut_insc_eleve2[$k] = '';
            }
            // Priorité
            $temp = "priorite_".$k."_".$current_eleve_login;
            if (isset($_POST[$temp])) $reg_priorite[$k] = $_POST[$temp]; else $reg_priorite[$k] = '';
            // Choix N° 1
            $temp = "choix_bas1_".$k."_".$current_eleve_login;
            $reg_choix_bas1_old =  sql_query1("select id_bas from bas_j_eleves_bas where
                id_eleve = '".$current_eleve_login."' and
                num_bas='".$numero_bas."' and
                num_sequence = '".$k."' and
                num_choix = '1'
                ");

            // On récupère la valeur mémorisée de la proposition
            $temp2= "choix_bas1_".$k."_".$current_eleve_login."_mem";
            if ((isset($_POST[$temp])) and (isset($_POST[$temp2])) and ($_POST[$temp]!=$_POST[$temp2])) {
                $reg_choix_bas1[$k] = $_POST[$temp];
                $statut_eleve1[$k] = '';
            } else {
                $reg_choix_bas1[$k] = $_POST[$temp2];
                $statut_eleve1[$k] = "deja_affecte";
            }


/*            if ((isset($_POST[$temp])) and ($reg_choix_bas1_old != $_POST[$temp])) {
                $reg_choix_bas1[$k] = $_POST[$temp];
                $statut_eleve1[$k] = '';
            } else {
                $reg_choix_bas1[$k] = $reg_choix_bas1_old;
                $statut_eleve1[$k] = "deja_affecte";
            }
*/            
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
                $msg .= "<br />Erreur : vous avez tenté d'inscrire un élève à une activité bloquée pour cause de sureffectif.";
            }

            if ($reg_choix_bas1[$k] == "bloque") {
                $reg_choix_bas1[$k] = '';
                $msg .= "<br />Erreur : vous avez tenté d'inscrire un élève à une activité bloquée pour cause de sureffectif.";
            }
            if ($reg_choix_bas2[$k] == "bloque") {
                $reg_choix_bas2[$k] = '';
                $msg .= "<br />Erreur : vous avez tenté d'inscrire un élève à une activité bloquée pour cause de sureffectif.";
            }
            $duree_0[$k] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas0[$k]."'");
            $duree_1[$k] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas1[$k]."'");
            $duree_2[$k] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas2[$k]."'");
            $k++;
        }
        // Test de verification sur le choix 0 par rapport à la durée des activités
        if ((isset($duree_0[1])) and ($duree_0[1] == 2) and ($reg_choix_bas0[1] != $reg_choix_bas0[2])) {
            if (($reg_choix_bas0[2]) != '')
                $msg .= "<br />Erreur sur le choix final du 2ème créneau de l'élève ".$current_eleve_prenom." ".$current_eleve_nom." L'erreur a été corrigée. Vérifiez bien que la correction effectuée est exacte." ;
            $reg_choix_bas0[2] = $reg_choix_bas0[1];
            $statut_eleve0[2] = '';
            $duree_0[2] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas0[2]."'");
        }
        if ((isset($duree_0[1])) and ($duree_0[1] == 3) and ( ($reg_choix_bas0[1] != $reg_choix_bas0[2]) or ($reg_choix_bas0[1] != $reg_choix_bas0[3]))) {
            $msg .= "<br />Erreur sur le choix final du 2ème ou 3ème créneau de l'élève ".$current_eleve_prenom." ".$current_eleve_nom." L'erreur a été corrigée. Vérifiez bien que la correction effectuée est exacte." ;
            $reg_choix_bas0[2] = $reg_choix_bas0[1];
            $reg_choix_bas0[3] = $reg_choix_bas0[1];
            $statut_eleve0[2] = '';
            $statut_eleve0[3] = '';
            $duree_0[2] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas0[2]."'");
            $duree_0[3] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas0[3]."'");
        }
        if ((isset($duree_0[2])) and  ($duree_0[2] == 2) and ($reg_choix_bas0[1] != $reg_choix_bas0[2]) and ($reg_choix_bas0[2] != $reg_choix_bas0[3])) {
            $debut_fin = sql_query1("select debut_final from bas_propositions where id_bas = '".$reg_choix_bas0[2]."'");
            if ($debut_fin == 1) {
                $reg_choix_bas0[2] = "";
                $statut_eleve0[2] = '';
                $msg .= "<br />Erreur sur le choix final du 2ème créneau de l'élève ".$current_eleve_prenom." ".$current_eleve_nom." Veuillez entrer une nouvelle valeur." ;
            } else {
                if (($reg_choix_bas0[3]) != '')
                    $msg .= "<br />Erreur sur le choix final du 3ème créneau de l'élève ".$current_eleve_prenom." ".$current_eleve_nom." L'erreur a été corrigée. Vérifiez bien que la correction effectuée est exacte." ;
                $reg_choix_bas0[3] = $reg_choix_bas0[2];
                $statut_eleve0[3] = '';
                $duree_0[3] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas0[3]."'");
            }
        }
        if ((isset($duree_0[3])) and ($duree_0[3] == 2) and ( $reg_choix_bas0[2] != $reg_choix_bas0[3])) {
            $msg .= "<br />Erreur sur le choix final du 3ème créneau de l'élève ".$current_eleve_prenom." ".$current_eleve_nom." Veuillez entrer une nouvelle valeur.";
            $reg_choix_bas0[3] = "";
            $statut_eleve0[3] = '';
        }

        if ((isset($duree_0[2])) and ($duree_0[2] == 3) and (($reg_choix_bas0[1] != $reg_choix_bas0[2]) or ($reg_choix_bas0[2] != $reg_choix_bas0[3]))) {
            $msg .= "<br />Erreur sur le choix final de l'élève ".$current_eleve_prenom." ".$current_eleve_nom." due à une activité de 3 heures. L'erreur a été corrigée." ;
           if ($reg_choix_bas0[1] != $reg_choix_bas0[2]) {
               $reg_choix_bas0[1] = $reg_choix_bas0[2];
               $statut_eleve0[1] = '';
               $duree_0[1] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas0[1]."'");
           }
           if ($reg_choix_bas0[2] != $reg_choix_bas0[3]) {
               $reg_choix_bas0[3] = $reg_choix_bas0[2];
               $statut_eleve0[3] = '';
               $duree_0[3] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas0[3]."'");
           }
        }
        if ((isset($duree_0[3])) and ($duree_0[3] == 3) and (( $reg_choix_bas0[2] != $reg_choix_bas0[3]) or ( $reg_choix_bas0[1] != $reg_choix_bas0[3]))  ) {
            $msg .= "<br />Erreur sur le choix final de l'élève ".$current_eleve_prenom." ".$current_eleve_nom." due à une activité de 3 heures. L'erreur a été corrigée." ;
            if ($reg_choix_bas0[2] != $reg_choix_bas0[3]) {
                 $reg_choix_bas0[2] = $reg_choix_bas0[3];
                 $statut_eleve0[2] = '';
                 $duree_0[2] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas0[2]."'");
            }
            if ($reg_choix_bas0[1] != $reg_choix_bas0[3]) {
                $reg_choix_bas0[1] = $reg_choix_bas0[3];
                $statut_eleve0[1] = '';
                $duree_0[1] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas0[1]."'");
            }
        }

        // Test de verification sur le choix 1 par rapport à la durée des activités
        if ((isset($duree_1[1])) and ($duree_1[1] == 2) and ($reg_choix_bas1[1] != $reg_choix_bas1[2])) {
            if (($reg_choix_bas1[2]) != '')
                $msg .= "<br />Erreur sur le 1er choix du 2ème créneau de l'élève ".$current_eleve_prenom." ".$current_eleve_nom." L'erreur a été corrigée. Vérifiez bien que la correction effectuée est exacte." ;
            $reg_choix_bas1[2] = $reg_choix_bas1[1];
            $statut_eleve1[2] = '';
            $duree_1[2] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas1[2]."'");

        }
        if ((isset($duree_1[1])) and ($duree_1[1] == 3) and ( ($reg_choix_bas1[1] != $reg_choix_bas1[2]) or ($reg_choix_bas1[1] != $reg_choix_bas1[3]))) {
            $msg .= "<br />Erreur sur le 1er choix du 2ème ou 3ème créneau de l'élève ".$current_eleve_prenom." ".$current_eleve_nom." L'erreur a été corrigée. Vérifiez bien que la correction effectuée est exacte." ;
            $reg_choix_bas1[2] = $reg_choix_bas1[1];
            $reg_choix_bas1[3] = $reg_choix_bas1[1];
            $statut_eleve1[2] = '';
            $duree_1[2] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas1[2]."'");
            $statut_eleve1[3] = '';
            $duree_1[3] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas1[3]."'");
        }
        if ((isset($duree_1[2])) and ($duree_1[2] == 2) and ($reg_choix_bas1[1] != $reg_choix_bas1[2]) and ($reg_choix_bas1[2] != $reg_choix_bas1[3])) {
            $debut_fin = sql_query1("select debut_final from bas_propositions where id_bas = '".$reg_choix_bas1[2]."'");
            if ($debut_fin == 1) {
                $reg_choix_bas1[2] = "";
                $statut_eleve1[2] = '';
                $msg .= "<br />Erreur sur le 1er choix du 2ème créneau de l'élève ".$current_eleve_prenom." ".$current_eleve_nom." Veuillez entrer une nouvelle valeur." ;
            } else {
                if (($reg_choix_bas1[3]) != '')
                    $msg .= "<br />Erreur sur le 1er choix du 3ème créneau de l'élève ".$current_eleve_prenom." ".$current_eleve_nom." L'erreur a été corrigée. Vérifiez bien que la correction effectuée est exacte." ;
                $reg_choix_bas1[3] = $reg_choix_bas1[2];
                $statut_eleve1[3] = '';
                $duree_1[3] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas1[3]."'");
            }
        }
        if ((isset($duree_1[3])) and ($duree_1[3] == 2) and ( $reg_choix_bas1[2] != $reg_choix_bas1[3]) ) {
            $msg .= "<br />Erreur sur le 1er choix du 3ème créneau de l'élève ".$current_eleve_prenom." ".$current_eleve_nom." Veuillez entrer une nouvelle valeur." ;
            $reg_choix_bas1[3] = "";
            $statut_eleve1[3] = '';
        }

        if ((isset($duree_1[2])) and ($duree_1[2] == 3) and (($reg_choix_bas1[1] != $reg_choix_bas1[2]) or ($reg_choix_bas1[2] != $reg_choix_bas1[3]))) {
            $msg .= "<br />Erreur sur le 1er choix de l'élève ".$current_eleve_prenom." ".$current_eleve_nom." due à une activité de 3 heures. L'erreur a été corrigée." ;
           if ($reg_choix_bas1[1] != $reg_choix_bas1[2]) {
               $reg_choix_bas1[1] = $reg_choix_bas1[2];
               $statut_eleve1[1] = '';
               $duree_1[1] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas1[1]."'");
           }
           if ($reg_choix_bas1[2] != $reg_choix_bas1[3]) {
               $reg_choix_bas1[3] = $reg_choix_bas1[2];
               $statut_eleve1[3] = '';
               $duree_1[3] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas1[3]."'");
            }
        }
        if ((isset($duree_1[3])) and ($duree_1[3] == 3) and (( $reg_choix_bas1[2] != $reg_choix_bas1[3]) or ( $reg_choix_bas1[1] != $reg_choix_bas1[3]))  ) {
            $msg .= "<br />Erreur sur le 1er choix de l'élève ".$current_eleve_prenom." ".$current_eleve_nom." due à une activité de 3 heures. L'erreur a été corrigée." ;
            if ($reg_choix_bas1[2] != $reg_choix_bas1[3]) {
                 $reg_choix_bas1[2] = $reg_choix_bas1[3];
                 $statut_eleve1[2] = '';
                 $duree_1[2] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas1[2]."'");
            }
            if ($reg_choix_bas1[1] != $reg_choix_bas1[3]) {
                $reg_choix_bas1[1] = $reg_choix_bas1[3];
                $statut_eleve1[1] = '';
                $duree_1[1] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas1[1]."'");
            }
        }



        // Test de verification sur le choix 2 par rapport à la durée des activités
        if ((isset($duree_2[1])) and ($duree_2[1] == 2) and ($reg_choix_bas2[1] != $reg_choix_bas2[2])) {
            if (($reg_choix_bas2[2]) != '')
                $msg .= "<br />Erreur sur le 2ème choix du 2ème créneau de l'élève ".$current_eleve_prenom." ".$current_eleve_nom." L'erreur a été corrigée. Vérifiez bien que la correction effectuée est exacte." ;
            $reg_choix_bas2[2] = $reg_choix_bas2[1];
            $statut_eleve2[2] = '';
            $duree_2[2] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas2[2]."'");
        }
        if ((isset($duree_2[1])) and ($duree_2[1] == 3) and ( ($reg_choix_bas2[1] != $reg_choix_bas2[2]) or ($reg_choix_bas2[1] != $reg_choix_bas2[3]))) {
            $msg .= "<br />Erreur sur le 2ème choix du 2ème ou 3ème créneau de l'élève ".$current_eleve_prenom." ".$current_eleve_nom." L'erreur a été corrigée. Vérifiez bien que la correction effectuée est exacte." ;
            $reg_choix_bas2[2] = $reg_choix_bas2[1];
            $reg_choix_bas2[3] = $reg_choix_bas2[1];
            $statut_eleve2[2] = '';
            $duree_2[2] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas2[2]."'");
            $statut_eleve2[3] = '';
            $duree_2[3] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas2[3]."'");

        }
        if ((isset($duree_2[2])) and ($duree_2[2] == 2) and ($reg_choix_bas2[1] != $reg_choix_bas2[2]) and ($reg_choix_bas2[2] != $reg_choix_bas2[3])) {
            $debut_fin = sql_query1("select debut_final from bas_propositions where id_bas = '".$reg_choix_bas2[2]."'");
            if ($debut_fin == 1) {
                $reg_choix_bas2[2] = "";
                $statut_eleve2[2] = '';
                $msg .= "<br />Erreur sur le 2ème choix du 2ème créneau de l'élève ".$current_eleve_prenom." ".$current_eleve_nom." Veuillez entrer une nouvelle valeur." ;
            } else {
                if (($reg_choix_bas2[3]) != '')
                    $msg .= "<br />Erreur sur le 2ème choix du 3ème créneau de l'élève ".$current_eleve_prenom." ".$current_eleve_nom." L'erreur a été corrigée. Vérifiez bien que la correction effectuée est exacte." ;
                $reg_choix_bas2[3] = $reg_choix_bas2[2];
                $statut_eleve2[3] = '';
                $duree_2[3] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas2[3]."'");

            }
        }
        if ((isset($duree_2[3])) and ($duree_2[3] == 2) and ( $reg_choix_bas2[2] != $reg_choix_bas2[3]) ) {
            $msg .= "<br />Erreur sur le 2ème choix du 3ème créneau de l'élève ".$current_eleve_prenom." ".$current_eleve_nom." Veuillez entrer une nouvelle valeur." ;
            $reg_choix_bas2[3] = "";
            $statut_eleve2[3] = '';

        }

        if ((isset($duree_2[2])) and ($duree_2[2] == 3) and (($reg_choix_bas2[1] != $reg_choix_bas2[2]) or ($reg_choix_bas2[2] != $reg_choix_bas2[3]))) {
            $msg .= "<br />Erreur sur le 2ème choix de l'élève ".$current_eleve_prenom." ".$current_eleve_nom." due à une activité de 3 heures. L'erreur a été corrigée." ;
           if ($reg_choix_bas2[1] != $reg_choix_bas2[2]) {
               $reg_choix_bas2[1] = $reg_choix_bas2[2];
               $statut_eleve2[1] = '';
               $duree_2[1] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas2[1]."'");
           }
           if ($reg_choix_bas2[2] != $reg_choix_bas2[3]) {
               $reg_choix_bas2[3] = $reg_choix_bas2[2];
               $statut_eleve2[3] = '';
               $duree_2[3] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas2[3]."'");
            }
        }
        if ((isset($duree_2[3])) and ($duree_2[3] == 3) and (( $reg_choix_bas2[2] != $reg_choix_bas2[3]) or ( $reg_choix_bas2[1] != $reg_choix_bas2[3]))  ) {
            $msg .= "<br />Erreur sur le 2ème choix de l'élève ".$current_eleve_prenom." ".$current_eleve_nom." due à une activité de 3 heures. L'erreur a été corrigée." ;
            if ($reg_choix_bas2[2] != $reg_choix_bas2[3]) {
                 $reg_choix_bas2[2] = $reg_choix_bas2[3];
                 $statut_eleve2[2] = '';
                 $duree_2[2] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas2[2]."'");
            }
            if ($reg_choix_bas2[1] != $reg_choix_bas2[3]) {
                $reg_choix_bas2[1] = $reg_choix_bas2[3];
                $statut_eleve2[1] = '';
                $duree_2[1] = sql_query1("select duree from bas_propositions where id_bas = '".$reg_choix_bas2[1]."'");
            }
        }


        // Enregistrement des modifications
        $k=1;
        
        while ($k < count($per)+1) {
            // On enregistre l'élève s'il n'est pas déjà affecté
            if ($inscription_bas==a) {
             if ($statut_eleve0[$k] == '') {
             // On teste si il y a sureffectif
                $nb_bloque = sql_query1("select nb_bloque from bas_propositions where id_bas = '".$reg_choix_bas0[$k]."'");
                $nb_inscrit = sql_query1("select count(id_eleve) from bas_j_eleves_bas where
                num_bas = '".$numero_bas."' and  id_bas = '".$reg_choix_bas0[$k]."' and num_choix='0' and num_sequence='".$k."'");
                if (($nb_bloque != -1) and ($nb_bloque <= $nb_inscrit) and ($active_blocage=='y')) {
                    $msg .= "<br />Erreur : vous avez tenté d'inscrire un élève à une activité bloquée pour cause de sureffectif.";
                } else {
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
            
             // Enregistrement de aut_insc_eleve
             // Suppression
             $req = mysql_query("delete from bas_j_eleves_bas_insc where
              id_eleve = '".$current_eleve_login."' and num_bas='".$numero_bas."' and num_sequence = '".$k."' and num_choix = '0'");
              // Enregistrement
              if ($reg_aut_insc_eleve0[$k] != '') $req = mysql_query("insert into bas_j_eleves_bas_insc set
              id_eleve = '".$current_eleve_login."',
              num_bas='".$numero_bas."',
              num_sequence = '".$k."',
              num_choix = '0',
              aut_insc_eleve = '".$reg_aut_insc_eleve0[$k]."'
              ");
            }
            if ($inscription_bas!=a) {
             if ($statut_eleve1[$k] == '') {
                // On teste si il y a sureffectif
                $nb_bloque = sql_query1("select nb_bloque from bas_propositions where id_bas = '".$reg_choix_bas1[$k]."'");
                $nb_inscrit = sql_query1("select count(id_eleve) from bas_j_eleves_bas where
                num_bas = '".$numero_bas."' and  id_bas = '".$reg_choix_bas1[$k]."' and num_choix='1' and num_sequence='".$k."'");
                if (($nb_bloque != -1) and ($nb_bloque <= $nb_inscrit)  and ($active_blocage=='y')) {
                    $msg .= "<br />Erreur : vous avez tenté d'inscrire un élève à une activité bloquée pour cause de sureffectif.";
                } else {
                    // Suppression
                    $req = mysql_query("delete from bas_j_eleves_bas where
                    id_eleve = '".$current_eleve_login."' and
                    num_bas='".$numero_bas."' and
                    num_sequence = '".$k."'
                    and num_choix = '1'");
                    if ($reg_choix_bas1[$k] == 'abs') $reg_priorite[$k] = '';
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

             // Enregistrement de la priorité
             $req = mysql_query("update bas_j_eleves_bas set
             priorite = '".$reg_priorite[$k]."',
             where
             id_eleve = '".$current_eleve_login."' and
             num_bas='".$numero_bas."' and
             num_sequence = '".$k."'
             and num_choix = '1'");
           
             // Enregistrement de aut_insc_eleve
             // Suppression
             $req = mysql_query("delete from bas_j_eleves_bas_insc where
             id_eleve = '".$current_eleve_login."' and num_bas='".$numero_bas."' and num_sequence = '".$k."' and num_choix = '1'");
             // Enregistrement
             if ($reg_aut_insc_eleve1[$k] != '') $req = mysql_query("insert into bas_j_eleves_bas_insc set
             id_eleve = '".$current_eleve_login."',
             num_bas='".$numero_bas."',
             num_sequence = '".$k."',
             num_choix = '1',
             aut_insc_eleve = '".$reg_aut_insc_eleve1[$k]."'
             ");

             if ($statut_eleve2[$k] == '') {
             // On enregistre que l'élève n'est pas déjà affecté
                // On teste si il y a sureffectif
                $nb_bloque = sql_query1("select nb_bloque from bas_propositions where id_bas = '".$reg_choix_bas2[$k]."'");
                $nb_inscrit = sql_query1("select count(id_eleve) from bas_j_eleves_bas where
                num_bas = '".$numero_bas."' and  id_bas = '".$reg_choix_bas2[$k]."' and num_choix='1' and num_sequence='".$k."'");
                if (($nb_bloque != -1) and ($nb_bloque <= $nb_inscrit)  and ($active_blocage=='y')) {
                    $msg .= "<br />Erreur : vous avez tenté d'inscrire un élève à une activité bloquée pour cause de sureffectif.";
                } else {
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
            
             // Enregistrement de aut_insc_eleve
             // Suppression
             $req = mysql_query("delete from bas_j_eleves_bas_insc where
             id_eleve = '".$current_eleve_login."' and num_bas='".$numero_bas."' and num_sequence = '".$k."' and num_choix = '2'");
             // Enregistrement
             if ($reg_aut_insc_eleve2[$k] != '') $req = mysql_query("insert into bas_j_eleves_bas_insc set
             id_eleve = '".$current_eleve_login."',
             num_bas='".$numero_bas."',
             num_sequence = '".$k."',
             num_choix = '2',
             aut_insc_eleve = '".$reg_aut_insc_eleve2[$k]."'
             ");
            }            
            $k++;
        }

        $i++;
    }
    $msg .= "<br />Les Modifications ont été enregistrées.";
}

// Enregistrement des filières
if (isset($action) and ($action=="inscription_filiere") and (isset($_POST['is_posted'])))  {
    $msg = '';
    $appel_donnees_eleves = mysql_query("SELECT DISTINCT e.* FROM eleves e, j_eleves_professeurs p
        WHERE (
           p.login = e.login AND
           p.professeur = '".$login_prof."'
           ) ORDER BY 'e.nom'");
    $nombre_lignes = mysql_num_rows($appel_donnees_eleves);
    $i = 0;
    while($i < $nombre_lignes) {
        $current_eleve_login = mysql_result($appel_donnees_eleves, $i, "login");
        $current_eleve_nom = mysql_result($appel_donnees_eleves, $i, "nom");
        $current_eleve_prenom = mysql_result($appel_donnees_eleves, $i, "prenom");
        $temp = "choix_filiere_".$current_eleve_login;
        if (isset($_POST[$temp]))  {
          $req = mysql_query("delete from bas_j_eleves_filieres where id_eleve = '".$current_eleve_login."'");
          if ($_POST[$temp]!='') {
            $req = mysql_query("insert into bas_j_eleves_filieres set
            id_eleve = '".$current_eleve_login."',
            id_filiere = '".$_POST[$temp]."'");
          }
        }
        $i++;
    }
    $msg .= "<br />Les Modifications ont été enregistrées.";
}


//**************** EN-TETE *****************
if (!isset($numero_bas) or (isset($action) and ($action != 'edit_feuille'))) $titre_page = "Gestion des activités pour mon groupe de suivi";
require_once("../../lib/header.inc.php");
//**************** FIN EN-TETE *****************

// On teste si le l'utilisateur est prof de suivi.
if ($_SESSION['statut']!="administrateur") {
 $test_prof_suivi = sql_query1("SELECT count(professeur) FROM j_eleves_professeurs  WHERE professeur = '".$_SESSION['login']."'");
  if ($test_prof_suivi <= "0") {
    echo "<p>Vous n'êtes pas professeur de suivi !</p>";
    die();
  }
}

// choix du bas
if (!(isset($numero_bas))) {
    echo "<p class='bold'>| <a href=\"../../accueil.php\">Retour à la page d'accueil</a> |";
    echo "<a href='index_suivi.php?numero_bas=1&amp;action=inscription_filiere&amp;login_prof=".$_SESSION['login']."' > Affecter mes élèves aux filières</a> |</p>\n";

    echo "<p class='grand'>Effectuez votre choix : </p>";
    echo "<table cellpadding=\"3\" border=\"1\">";
    $req = mysql_query("select * from bas_bas order by nom");
    $nb_bas = mysql_num_rows($req);
    $i = 0 ;
    while ($i < $nb_bas) {
        $num_bas = mysql_result($req,$i,'id_bas');
        $date_bas = mysql_result($req,$i,'date_bas');
        $nom_bas = mysql_result($req,$i,'nom');
        $inscription_bas = mysql_result($req,$i,'inscription_bas');
        $description_bas = mysql_result($req,$i,'description_bas');
        $close_bas = mysql_result($req,$i,'close_bas');
        $aff_affectations_eleves = mysql_result($req,$i,'aff_affectations_eleves');
        $aff_liste_par_classe = mysql_result($req,$i,'aff_liste_par_classe');
        echo "<tr><td><b>".$nom_bas."</b> du ".$date_bas."</td>\n";
        if ($inscription_bas != "n")
            echo "<td><a href='index_suivi.php?numero_bas=".$num_bas."&amp;action=edit_feuille&amp;login_prof=".$_SESSION['login']."' title='ce lien ouvre une nouvelle fenêtre dans votre navigateur' target='_blank'>Feuille d'inscription</a></td>\n";
        else
            echo "<td style='text-align:center'>-</td>";

        if ($aff_liste_par_classe == "y")
            echo "<td><a href='index_listes.php?numero_bas=".$num_bas."' title='ce lien ouvre une nouvelle fenêtre dans votre navigateur' target='_blank'>Les propositions</a></td>\n";
        else
            echo "<td style='text-align:center'>-</td>";

        echo "<td>";

        if ($inscription_bas == "y")
            echo "<a href='index_suivi.php?numero_bas=".$num_bas."&amp;action=inscription&amp;login_prof=".$_SESSION['login']."' > - Inscrire mes élèves</a>\n";
        if ($inscription_bas == "a")
            echo "<a href='index_suivi.php?numero_bas=".$num_bas."&amp;action=inscription&amp;login_prof=".$_SESSION['login']."' > - Affecter mes élèves</a>\n";
        else if ($inscription_bas == "r")
            echo "<a href='index_suivi.php?numero_bas=".$num_bas."&amp;action=inscription&amp;login_prof=".$_SESSION['login']."' > - Inscrire mes élèves</a> (remédiation uniquement)\n";
        if ($aff_affectations_eleves == "y")
            echo "<a href='index_suivi.php?numero_bas=".$num_bas."&amp;action=visu_choix_finaux&amp;login_prof=".$_SESSION['login']."' > - Choix finaux de mes élèves</a>\n";
        echo "&nbsp;</td>";


        echo "</tr>";
        $i++;
    }
    echo "</table>";
}

if (isset($numero_bas)) {
    if (!isset($numero_bas) or (isset($action) and ($action != 'edit_feuille'))) {
        echo "<p class='bold'>| <a href=\"../../accueil.php\">Retour à la page d'accueil</a> |
        <a href=\"./index_suivi.php\">Retour à la page de suivi</a> |
        </p>";
    }
    // données sur le bas
    $date_bas = sql_query1("select date_bas from bas_bas where id_bas='".$numero_bas."'");
    $nom_bas = sql_query1("select nom from bas_bas where id_bas='".$numero_bas."'");
    $num_periode = sql_query1("select num_periode from bas_bas where id_bas='".$numero_bas."'");
    $close_bas = sql_query1("select close_bas from bas_bas where id_bas='".$numero_bas."'");
    $date_limite = sql_query1("select date_limite from bas_bas where id_bas='".$numero_bas."'");
    $description_bas = sql_query1("select description_bas from bas_bas where id_bas='".$numero_bas."'");
    $civilite = sql_query1("select civilite from utilisateurs where login = '".$login_prof."'");
    $inscription_bas = sql_query1("select inscription_bas from bas_bas where id_bas='".$numero_bas."'");
    $aut_insc_eleve = sql_query1("select aut_insc_eleve from bas_bas where id_bas='".$numero_bas."'");
//    if ($_SESSION['login']!="delineal") $aut_insc_eleve = 'n';

    // Constitution du tableau $per
    $per =  tableau_periode($numero_bas);

    $appel_donnees_eleves = mysql_query("SELECT DISTINCT e.* FROM eleves e, j_eleves_professeurs p, j_eleves_classes jec
        WHERE (
           p.login = e.login AND
           p.professeur = '".$login_prof."' and
           jec.login = e.login
           ) ORDER BY 'e.nom'");
    $nombre_lignes = mysql_num_rows($appel_donnees_eleves);

    // Détermination de la classe
    // Dans le cas des bas, il suffit de prendre la classe du 1er élève qui convient car tous les élèves doivent appartenir à la même classe
    $m = 0;
    $stop = 0;
    while (($m < $nombre_lignes) and ($stop < 1)) {
        $temp = mysql_result($appel_donnees_eleves, $m, "login");
        $id_classe = sql_query1("select c.id from classes c, j_eleves_classes j where
        (j.id_classe=c.id and j.login='".$temp."' and periode='".$num_periode."')");
        if ($id_classe != -1) $stop = 1;
        $m++;
    }

    if ($stop == 0) echo "<p>Un problème est survenu. Veuillez contacter l'administrateur.</p></body></html>";

    $nom_prof = sql_query1("select nom from utilisateurs where login = '".$login_prof."'");
    echo "<p class='grand'>";
    if (!(isset($action) and ($action=="inscription_filiere")))
      echo $nom_bas." du ".$date_bas." - ";
    echo "Groupe de suivi de ".$civilite." ".$nom_prof;
}
if (isset($action) and ($action=="edit_feuille")) {
    echo " - Feuille d'inscription</p>\n";
    echo "<ul>
    <li>Veuillez indiquer dans chaque cellule du tableau l'identifiant de la proposition choisie.</li>
    <li>Vous devez <b>obligatoirement</b> effectuer deux choix (différents !) pour chaque créneau horaire :
    en fonction du nombre de places disponibles, si le premier choix ne peut être respecté, c'est le deuxième choix qui sera retenu, toujours dans la limite des possibilités.</li>
    <li>Le professeur de suivi est invité à reporter, au plus tard le lendemain de la collecte manuelle, le résultat du tableau ci-dessous dans le module Atelier de GEPI.</li>
    </ul>";

    echo "<table border=\"1\" cellspacing=\"1\" cellpadding=\"5\">
    <tr>
    <td><b>Nom Prénom</b></td>";
    $k = 1;
    while ($k < count($per)+1) {
        echo "<td style='width:100px' colspan=\"2\"><b>Heure : ".$per[$k]."</b></td>";
        $k++;
    }
    echo "</tr>";
    echo "<tr><td>&nbsp;</td>";
    $k = 1;
    while ($k < count($per)+1) {
        echo "<td style='width:100px'>1er choix</td><td style='width:100px'>2ème choix</td>";
        $k++;
    }
    echo "</tr>";
    $i = "0";
    while($i < $nombre_lignes) {
        $current_eleve_login = mysql_result($appel_donnees_eleves, $i, "login");
        $current_eleve_nom = mysql_result($appel_donnees_eleves, $i, "nom");
        $current_eleve_prenom = mysql_result($appel_donnees_eleves, $i, "prenom");

        echo "<tr><td>$current_eleve_nom $current_eleve_prenom<br /><a href='liste_bas_par_eleve.php?current_eleve_login=".$current_eleve_login."' target='_blank' title='Ouvre dans une nouvelle fenêtre un tableau récapitulatif de toutes les activités de cet élève'><b>(Toutes&nbsp;les&nbsp;activités)</b></a></td>\n";
        $k=1;
        while ($k < count($per)+1) {
            $bas1 = sql_query1("select id_bas from bas_j_eleves_bas
            where id_eleve = '".$current_eleve_login."' and
            num_bas = '".$numero_bas."' and
            num_choix = '1' and
            num_sequence = '".$k."'
            ");
            $id_prop1 = sql_query1("select id_prop from bas_propositions where id_bas='".$bas1."'");
            if ($id_prop1 != "-1") {
                $type_id_prop1 = sql_query1("select type from bas_propositions where id_bas='".$bas1."'");
                echo "<td>".$id_prop1;
                if ($type_id_prop1 == 'R') echo " (R)";
                echo "</td>\n";

            } else {
                echo "<td>&nbsp;</td>\n";
            }

            $bas2 = sql_query1("select id_bas from bas_j_eleves_bas
            where id_eleve = '".$current_eleve_login."' and
            num_bas = '".$numero_bas."' and
            num_choix = '2' and
            num_sequence = '".$k."'
            ");

            $id_prop2 = sql_query1("select id_prop from bas_propositions where id_bas='".$bas2."'");
            if ($id_prop2 != "-1") {
                $type_id_prop2 = sql_query1("select type from bas_propositions where id_bas='".$bas2."'");
                echo "<td>".$id_prop2;
                echo "</td>\n";

            } else {
                echo "<td>&nbsp;</td>\n";
            }

            $k++;
        }
        echo "</tr>";
        $i++;
    }

    echo "</table>";

}
// Visualisation des choix finaux des élèves
if (isset($action) and ($action=="visu_choix_finaux")) {
    echo " - Activités suivies</p>\n";

    echo "<table border=\"1\" cellspacing=\"1\" cellpadding=\"5\">
    <tr>
    <td><b>Nom Prénom</b></td>\n";
    $k = 1;
    while ($k < count($per)+1) {
        echo "<td style='width:100px' colspan=\"2\"><b>Heure : ".$per[$k]."</b></td>\n";
        $k++;
    }
    echo "</tr>\n";
    echo "<tr><td>&nbsp;</td>\n";
    $k = 1;
    while ($k < count($per)+1) {
      echo "<td style='width:100px'>Activité</td><td style='width:100px'>Salle</td>\n";
      $k++;
    }
    echo "</tr>";

    $i = "0";
    while($i < $nombre_lignes) {
        $current_eleve_login = mysql_result($appel_donnees_eleves, $i, "login");
        $current_eleve_nom = mysql_result($appel_donnees_eleves, $i, "nom");
        $current_eleve_prenom = mysql_result($appel_donnees_eleves, $i, "prenom");

        echo "<tr><td>$current_eleve_nom $current_eleve_prenom<br /><a href='liste_bas_par_eleve.php?current_eleve_login=".$current_eleve_login."' target='_blank' title='Ouvre dans une nouvelle fenêtre un tableau récapitulatif de toutes les activités de cet élève'><b>(Toutes&nbsp;les&nbsp;activités)</b></a></td>\n";
        $d=1;
        while ($d < count($per)+1) {
            $bas[$d] = sql_query1("select id_bas from bas_j_eleves_bas
            where id_eleve = '".$current_eleve_login."' and
            num_bas = '".$numero_bas."' and
                        num_choix = '0' and
            num_sequence = '".$d."'
            ");
            $id_prop[$d] = sql_query1("select id_prop from bas_propositions where id_bas='".$bas[$d]."'");
            $type_[$d] = sql_query1("select type from bas_propositions where id_bas='".$bas[$d]."'");
            $salle[$d] = sql_query1("select salle from bas_propositions where id_bas='".$bas[$d]."'");
            $titre[$d] = sql_query1("select titre from bas_propositions where id_bas='".$bas[$d]."'");
            $animateur = sql_query1("select responsable from bas_propositions where id_bas='".$bas[$d]."'");
            $civilite_[$d] = sql_query1("select civilite from utilisateurs where login = '".$animateur."'");
            $nom_prof_[$d] = sql_query1("select nom from utilisateurs where login = '".$animateur."'");
            $nom_salle[$d] = sql_query1("select nom_court_salle from bas_salles where id_salle='".$salle[$d]."'");
            $d++;
         }
        $d=1;
        while ($d < count($per)+1) {
            if ($id_prop[$d] != '-1') {
                echo "<td><span class='style_bas'><b>".$id_prop[$d]."</b> : ".$titre[$d]."<br />(".$civilite_[$d]." ".$nom_prof_[$d].")</span>";
                if ($type_[$d] == 'R') echo "<br />** REMEDIATION **";
                if ($type_[$d] == 'D') echo "<br />** PUBLIC DESIGNE **";
                echo "</td><td><span class='style_bas'>$nom_salle[$d]</span></td>";
            } else
                echo "<td><span class='style_bas'>-</span></td><td><span class='style_bas'>-</span></td>";
            $d++;
         }
        echo "</tr>";
        $i++;
    }

    echo "</table>";

}



// formulaire de saisie des inscriptions
if (isset($action) and ($action=="inscription")) {
    if ($inscription_bas == "a")
        echo " - Saisie des affectations finales</p>\n";
    else
        echo " - Saisie des inscriptions</p>\n";
    if ($inscription_bas == "a") {
        echo "<ul>
        <li>Affectez ci-dessous vos élèves.</li>
        <li><b>Une cellule à fond rouge signale un élève non affecté : vous devez alors choisir une activité pour cet élève.</b></li>
        <li>Une ligne orange signale un élève affecté à une activité qui ne correspond pas à l'un de ses choix. Il s'agit d'une simple avertissement.</li>
        <li>Les activités suivies de **D** correspondent à des activités à public désigné. Seuls les élèves listés par les professeurs encadrant l'activité peuvent être affectés à ces activités.</li>
        <li>Les activités suivies de **Bloqué** correspondent à des activités en sureffectif pour lesquelles l'affectation est impossible.</li>
        </ul>";
    } else if ($inscription_bas == "y") {
        echo "<ul>
        <li>Reportez ci-dessous les choix effectués par vos élèves.</li>
        <li>Vous devez ci-possible effectuer deux choix pour chaque créneau horaire : en fonction du nombre de places disponibles dans l'activité, si le premier choix ne peut être respecté, c'est le deuxième choix qui sera retenu, toujours dans la limite des possibilités.</li>
        <li>Les activités suivies de **D** correspondent à des activités à public désigné. Seuls les élèves listés par les professeurs encadrant l'activité peuvent être affectés à ces activités.</li>";
        if ($active_blocage == "y")
            echo "<li>Les activités suivies de **Bloqué** correspondent à des activités en sureffectif pour lesquelles l'inscription est impossible.</li>";
        else
            echo "<li>Les activités suivies de **Surnombre** correspondent à des activités en sureffectif pour lesquelles l'inscription est néanmoins possible.</li>";
        echo "</ul>";
    } else {
        echo "<ul>
        <li>Affectez ci-dessous uniquement les élèves devant effectuer une activité à public désigné.</li>
        <li>Les activités listées (suivies de **D**) correspondent à des activités à public désigné. Seuls les élèves listés par les professeur encadrant l'activité peuvent être inscrits à ces activités.</li>
        <li>Dans un second temps, l'administrateur ouvrira la possibilité de saisir les autres propositions.</li>";
        if ($active_blocage == "y")
            echo "<li>Les activités suivies de **Bloqué** correspondent à des activités en sureffectif pour lesquelles l'inscription est impossible.</li>";
        else
            echo "<li>Les activités suivies de **Surnombre** correspondent à des activités en sureffectif pour lesquelles l'inscription est néanmoins possible.</li>";
        echo "</ul>";
    }
    
    // En-tete du tableau
    echo "<form action=\"index_suivi.php\" id=\"inscription\" method=\"post\">\n";
    echo "<table border=\"1\" cellspacing=\"1\" cellpadding=\"5\">
    <tr>
    <td><b><span class='style_bas'>Nom Prénom</span></b></td>";
    $k = 1;
    while ($k < count($per)+1) {
        if ($aut_insc_eleve=='y')
            if ($inscription_bas == "a")
                echo "<td style='width:100px' colspan=\"4\"><b>Heure : ".$per[$k]."</b></td>";
            else if ($inscription_bas == "y")
                echo "<td style='width:100px' colspan=\"5\"><b>Heure : ".$per[$k]."</b></td>";
            else //  ($inscription_bas == "r")
                echo "<td style='width:100px' colspan=\"3\"><b>Heure : ".$per[$k]."</b></td>";
        else 
            echo "<td style='width:100px' colspan=\"3\"><b>Heure : ".$per[$k]."</b></td>";

        $k++;
    }
    echo "</tr>\n";
    echo "<tr>\n<td>&nbsp;</td>\n";
    $k = 1;
    while ($k < count($per)+1) {
        if ($inscription_bas == "a") {
            if ($aut_insc_eleve=='y') {
                echo "<td style='width:100px; text-align:center' colspan=\"2\" >Affectation finale</td>\n";
            } else {
                echo "<td style='width:100px; text-align:center' >Affectation finale</td>\n";
            }
            echo "<td style='width:100px;text-align:center'>Choix N°1</td>\n";
            echo "<td style='width:100px;text-align:center'>Choix N°2</td>\n";
        } else if ($inscription_bas == "y") {
            if ($aut_insc_eleve=='y')
               echo "<td>&nbsp;</td>\n";            
            else
               echo "<td><span class='style_bas'>Choix&nbsp;N°&nbsp;1<br />prioritaire</span></td>\n";
            if ($aut_insc_eleve=='y') {
                echo "<td style='width:100px; text-align:center' colspan=\"2\" >Choix N°1</td>\n";
                echo "<td style='width:100px; text-align:center' colspan=\"2\" >Choix N°2</td>\n";
            } else {
                echo "<td style='width:100px; text-align:center'>Choix N°1</td>\n";
                echo "<td style='width:100px; text-align:center'>Choix N°1</td>\n";
            }
        } else {
            if ($aut_insc_eleve=='y')
               echo "<td>&nbsp;</td>\n";            
            else
               echo "<td><span class='style_bas'>Choix&nbsp;N°&nbsp;1<br />prioritaire</span></td>\n";

            echo "<td style='width:100px;text-align:center'>Choix N°1</td>\n";
            echo "<td style='width:100px;text-align:center'>Choix N°2</td>\n";
        }
        $k++;
    }
    echo "</tr>\n";

  if ($aut_insc_eleve=='y') {  // Pour l'interface dans le cas où les élèves peuvent s'incrire, on ajoute une ligne

    echo "<tr>\n<td>&nbsp;</td>\n";
    $k = 1;
    while ($k < count($per)+1) {
        if ($inscription_bas == "a") {
            echo "<td style='text-align:center;'>Affectation finale</td>\n";
            echo "<td><span class='style_bas'>Empêcher&nbsp;la<br />modification<br />par&nbsp;l'élève</span></td>";
            echo "<td style='width:100px'>&nbsp;</td>\n";
            echo "<td style='width:100px'>&nbsp;</td>\n";
        } else if ($inscription_bas == "y") {
            echo "<td><span class='style_bas'>Choix&nbsp;N°&nbsp;1<br />prioritaire</span></td>\n";
            echo "<td style='width:100px'>Choix N°1</td>\n";
            echo "<td><span class='style_bas'>Empêcher&nbsp;la<br />modification<br />par&nbsp;l'élève</span></td>\n";
            echo "<td style='width:100px'>Choix N°2</td>\n";
            echo "<td><span class='style_bas'>Empêcher&nbsp;la<br />modification<br />par&nbsp;l'élève</span></td>\n";
        } else {
            echo "<td><span class='style_bas'>Choix&nbsp;N°&nbsp;1<br />prioritaire</span></td>\n";
            echo "<td style='width:100px'>Choix N°1</td>\n";
            echo "<td style='width:100px'>Choix N°2</td>\n";
        }
        $k++;
    }
    echo "</tr>\n";
  }
    // Fin En-tete du tableau

    
    $i = "0";
    while($i < $nombre_lignes) {
        $nb_prop=array();
        $current_eleve_login = mysql_result($appel_donnees_eleves, $i, "login");
        $current_eleve_nom = mysql_result($appel_donnees_eleves, $i, "nom");
        $current_eleve_prenom = mysql_result($appel_donnees_eleves, $i, "prenom");
        $current_filiere=sql_query1("select id_filiere from bas_j_eleves_filieres where id_eleve='".$current_eleve_login."'");
    if ($current_filiere!=-1) {
      // Les propositions :
      if (($inscription_bas == "y") or ($inscription_bas == "a") or (calcul_autorisation_gestion_ateliers($_SESSION['login'],"mod_plugins/gestion_ateliers/droit_special_index_suivi.txt"))) {
          $req_prop1 = mysql_query("select id_prop, id_bas, type, statut, nb_bloque from bas_propositions
          where public_".$current_filiere." != '' and debut_final = '1' and num_bas = '".$numero_bas."' and statut!='a' order by id_prop");
          $req_prop2 = mysql_query("select id_prop, id_bas, type, statut, nb_bloque from bas_propositions where
          public_".$current_filiere." != '' and (debut_final = '2' or (debut_final = '1' and duree = '2') OR (debut_final = '1' and duree = '3')) and num_bas = '".$numero_bas."'  and statut!='a' order by id_prop");
          $req_prop3 = mysql_query("select id_prop, id_bas, type, statut, nb_bloque from bas_propositions where
         public_".$current_filiere." != '' and (debut_final = '3' or (debut_final = '2' and duree = '2') or (debut_final = '1' and duree = '3')) and num_bas = '".$numero_bas."'  and statut!='a' order by id_prop");
      } else if ($inscription_bas == "r") {
          // On n'affiche que les propositions en remediation ou a public désigné
          $req_prop1 = mysql_query("select id_prop, id_bas, type, statut, nb_bloque from bas_propositions where
          public_".$current_filiere." != '' and debut_final = '1' and num_bas = '".$numero_bas."' and statut!='a' and (type = 'R' or type = 'D') order by id_prop");
          $req_prop2 = mysql_query("select id_prop, id_bas, type, statut, nb_bloque from bas_propositions where
          public_".$current_filiere." != '' and (debut_final = '2' or (debut_final = '1' and duree = '2') or (debut_final = '1' and duree = '3')) and num_bas = '".$numero_bas."'  and statut!='a'   and (type = 'R' or type = 'D')  order by id_prop");
          $req_prop3 = mysql_query("select id_prop, id_bas, type, statut, nb_bloque from bas_propositions where
          public_".$current_filiere." != '' and (debut_final = '3' or (debut_final = '2' and duree = '2') or (debut_final = '1' and duree = '3')) and num_bas = '".$numero_bas."'   and statut!='a'  and (type = 'R' or type = 'D')  order by id_prop");
      }
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
    }
        
        
        // on écrit le tableau
        echo "<tr><td><span class='style_bas'>$current_eleve_nom $current_eleve_prenom<br /><a href='liste_bas_par_eleve.php?current_eleve_login=".$current_eleve_login."' target='_blank' title='Ouvre dans une nouvelle fenêtre un tableau récapitulatif de toutes les activités de cet élève'><b>(Toutes&nbsp;les&nbsp;activités)</b></a></span></td>\n";
        $k = 1;
        while ($k < count($per)+1) {
            $reg_choix_eleve0 = sql_query1("select id_bas from bas_j_eleves_bas where
            id_eleve = '".$current_eleve_login."' and
            num_bas='".$numero_bas."' and
            num_sequence = '".$k."' and
            num_choix = '0'
            ");
            
            $reg_aut_insc_eleve0 = sql_query1("select aut_insc_eleve from bas_j_eleves_bas_insc where (
            id_eleve = '".$current_eleve_login."' and
            num_bas='".$numero_bas."' and
            num_sequence = ".$k." and
            num_choix = '0')
            ");

            $reg_choix_eleve1 = sql_query1("select id_bas from bas_j_eleves_bas where
            id_eleve = '".$current_eleve_login."' and
            num_bas='".$numero_bas."' and
            num_sequence = '".$k."' and
            num_choix = '1'
            ");

            $reg_aut_insc_eleve1 = sql_query1("select aut_insc_eleve from bas_j_eleves_bas_insc where (
            id_eleve = '".$current_eleve_login."' and
            num_bas='".$numero_bas."' and
            num_sequence = ".$k." and
            num_choix = '1')
            ");

            $reg_choix_eleve2 = sql_query1("select id_bas from bas_j_eleves_bas where
            id_eleve = '".$current_eleve_login."' and
            num_bas='".$numero_bas."' and
            num_sequence = ".$k." and
            num_choix = '2'
            ");

            $reg_aut_insc_eleve2 = sql_query1("select aut_insc_eleve from bas_j_eleves_bas_insc where (
            id_eleve = '".$current_eleve_login."' and
            num_bas='".$numero_bas."' and
            num_sequence = ".$k." and
            num_choix = '2')
            ");

            $reg_priorite = sql_query1("select priorite from bas_j_eleves_bas where (
            id_eleve = '".$current_eleve_login."' and
            num_bas='".$numero_bas."' and
            num_sequence = ".$k." and
            num_choix = '1')
            ");
            // Le professeur peut uniquement affecter un élève
            if ($inscription_bas == "a") {
                if ($k == 1)
                    echo "<input type=\"hidden\" name=\"priorite_".$k."_".$current_eleve_login."\" value=\"".$reg_priorite."\" />";
                $fond = "";
                if ($reg_choix_eleve0 == -1)
                    echo "<td style=\"background:#FF0000\" >";
                else if (($reg_choix_eleve0 != $reg_choix_eleve1) and ($reg_choix_eleve0 != $reg_choix_eleve2)) {
                    echo "<td style=\"background:#FF8000\" >";
                    $fond = "orange";
                }
                else
                    echo "<td style=\"background:#D8FF64\" >";
                echo "<select name=\"choix_bas0_".$k."_".$current_eleve_login."\" size=\"1\">\n";
                echo "<option value=''>(choisissez)</option>\n";
                echo "<option value='abs' ";
                if ($reg_choix_eleve0 == 'abs') echo "selected=\"selected\" ";
                echo ">(Absent)</option>\n";
                $n = 0;
                while ($n < $nb_prop[$k]) {
                    // si l'activité est bloqué
                    if (($reg_choix_eleve0 != $id_bas[$k][$n]) and ($bloque_[$k][$n] == 'y') and !(calcul_autorisation_gestion_ateliers($_SESSION['login'],"mod_plugins/gestion_ateliers/droit_special_index_suivi.txt")))
                        echo "<option value='bloque' ";
                    else
                        echo "<option value='".$id_bas[$k][$n]."' ";

                    if ($reg_choix_eleve0 == $id_bas[$k][$n]) echo "selected=\"selected\" ";
                    echo ">".$id_propo[$k][$n];
                    if ($type_[$k][$n] == 'R') echo " **D**";
                    if (($reg_choix_eleve0 != $id_bas[$k][$n]) and ($bloque_[$k][$n] == 'y'))
                        echo " **Bloqué**";
                    echo "</option>\n";
                    $n++;
                }
                echo "</select>";
                echo "</td>\n";
            // Les élèves peuvent-ils s'insrire ?
                if ($aut_insc_eleve=='y') {
                    if (isset($reg_aut_insc_eleve0) and ($reg_aut_insc_eleve0=='y'))
                       echo "<td style=\"background:#AAFFD4\">\n";
                    else
                       echo "<td style=\"background:#FFAAD4\">\n";                    
                    echo "<input type=\"checkbox\" name=\"aut_insc_eleve0_".$k."_".$current_eleve_login."\" value=\"\" ";
                    if (isset($reg_aut_insc_eleve0) and ($reg_aut_insc_eleve0!='y')) echo "checked=\"checked\" ";
                    echo " />";
                    echo "</td>\n";          
                }
                
                // On affiche le choix n° 1
                if ($fond == "orange")
                    echo "<td style=\"background:#FF8000\" >";
                else
                    echo "<td>";
                    $id_prop = sql_query1("select id_prop from bas_propositions where id_bas='".$reg_choix_eleve1."'");
                    if ($id_prop != -1) echo $id_prop; else echo "-";
                echo "</td>\n";
                // On affiche le choix n° 2
                if ($fond == "orange")
                    echo "<td style=\"background:#FF8000\" >";
                else
                    echo "<td>";
                    $id_prop = sql_query1("select id_prop from bas_propositions where id_bas='".$reg_choix_eleve2."'");
                    if ($id_prop != -1) echo $id_prop; else echo "-";

                echo "</td>\n";
            } else {  // $inscription_bas=='y' ou  $inscription_bas=='r'
            // L'utilisateur peut inscrire les élèves
                //Priorité
                echo "<td>";
                if ($reg_choix_eleve0 == "-1") {
                     // L'élève n'est pas encore affecté
                     echo "<input type=\"checkbox\" name=\"priorite_".$k."_".$current_eleve_login."\" value=\"y\" ";
                     if (isset($reg_priorite) and ($reg_priorite=='y')) echo "checked=\"checked\" ";
                     echo " />";
                } else {
                    if ($reg_priorite=='y')
                        echo "Oui";
                    else
                        echo "&nbsp;";
                }
                echo "</td>\n";
                if (($reg_choix_eleve0 == "-1") or (calcul_autorisation_gestion_ateliers($_SESSION['login'],"mod_plugins/gestion_ateliers/droit_special_index_suivi.txt"))) {
                    echo "<td>";
                    // L'élève n'est pas encore affecté
                    echo "<select name=\"choix_bas1_".$k."_".$current_eleve_login."\" size=\"1\">\n";
                    echo "<option value=''>(choisissez)</option>\n";
                    echo "<option value='abs' ";
                    if ($reg_choix_eleve1 == 'abs') echo "selected=\"selected\" ";
                    echo ">(Absent)</option>\n";
                    $n = 0;
                    while ($n < $nb_prop[$k]) {
                        // si l'activité est bloqué
                        if (($reg_choix_eleve1 != $id_bas[$k][$n]) and ($bloque_[$k][$n] == 'y') and !(calcul_autorisation_gestion_ateliers($_SESSION['login'],"mod_plugins/gestion_ateliers/droit_special_index_suivi.txt")) and ($active_blocage=='y'))
                            echo "<option value='bloque' ";
                        else
                            echo "<option value='".$id_bas[$k][$n]."' ";
                        if ($reg_choix_eleve1 == $id_bas[$k][$n]) echo "selected=\"selected\" ";
                        echo ">".$id_propo[$k][$n];
                        if ($type_[$k][$n] == 'R') echo " **D**";
//                        if (($reg_choix_eleve1 != $id_bas[$k][$n]) and ($bloque_[$k][$n] == 'y'))
                        if (($bloque_[$k][$n] == 'y'))
                          if ($active_blocage=='y')
                            echo " **Bloqué**";
                          else
                            echo " **Surnombre**";
                        echo "</option>\n";
                        $n++;
                    }
                    echo "</select>";
                    // On mémorise l'ancienne valeur
                    echo "<input type=\"hidden\" name=\"choix_bas1_".$k."_".$current_eleve_login."_mem\" value=\"".$reg_choix_eleve1  ."\" />";

                    echo "</td>";
                    if (($aut_insc_eleve=='y') and ($inscription_bas == "y")) {  // dans le cas $inscription_bas=='r', on n'affiche pas la colonne de "blocage élève"
                    if (isset($reg_aut_insc_eleve1) and ($reg_aut_insc_eleve1!='y'))
                       echo "<td style=\"background:#AAFFD4\">\n";
                    else
                       echo "<td style=\"background:#FFAAD4\">\n";                    
                      echo "<input type=\"checkbox\" name=\"aut_insc_eleve1_".$k."_".$current_eleve_login."\" value=\"y\" ";
                      if (isset($reg_aut_insc_eleve1) and ($reg_aut_insc_eleve1=='y')) echo "checked=\"checked\" ";
                      echo " title=\"Cochez la case afin que l'élève ne puisse pas modifier le choix\"  />";
                      echo "</td>\n";          
                    }
                   
                } else {
                    // L'élève est déjà affecté
                    echo "<td>";
                    $id_prop1 = sql_query1("select id_prop from bas_propositions where id_bas='".$reg_choix_eleve1."'");
                    echo $id_prop1;
                    if ($reg_choix_eleve0 == $reg_choix_eleve1) echo " <b>(Aff.&nbsp;finale)</b>";
                    echo "</td>";
                    if (($aut_insc_eleve=='y') and ($inscription_bas == "y"))   // dans le cas $inscription_bas=='r', on n'affiche pas la colonne de "blocage élève"
                      echo "<td>&nbsp;</td>";

                }
                if (($reg_choix_eleve0 == "-1")  or (calcul_autorisation_gestion_ateliers($_SESSION['login'],"mod_plugins/gestion_ateliers/droit_special_index_suivi.txt"))) {
                    // L'élève n'est pas encore affecté
                    echo "<td>";
                    echo "<select name=\"choix_bas2_".$k."_".$current_eleve_login."\" size=\"1\">\n";
                    echo "<option value=''>(choisissez)</option>\n";
                    echo "<option value='abs' ";
                    if ($reg_choix_eleve1 == 'abs') echo "selected=\"selected\" ";
                    echo ">(Absent)</option>\n";
                    $n = 0;
                    while ($n < $nb_prop[$k]) {
                        if ($type_[$k][$n] != 'R') {
                        // si l'activité est bloqué
                        if (($reg_choix_eleve2 != $id_bas[$k][$n]) and ($bloque_[$k][$n] == 'y') and !(calcul_autorisation_gestion_ateliers($_SESSION['login'],"mod_plugins/gestion_ateliers/droit_special_index_suivi.txt")) and ($active_blocage=='y'))
                            echo "<option value='bloque' ";
                        else
                            echo "<option value='".$id_bas[$k][$n]."' ";

                        if ($reg_choix_eleve2 == $id_bas[$k][$n]) echo "selected=\"selected\" ";
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

                    echo "</select></td>";
                    if (($aut_insc_eleve=='y') and ($inscription_bas == "y")) {  // dans le cas $inscription_bas=='r', on n'affiche pas la colonne de "blocage élève"
                    if (isset($reg_aut_insc_eleve2) and ($reg_aut_insc_eleve2!='y'))
                       echo "<td style=\"background:#AAFFD4\">\n";
                    else
                       echo "<td style=\"background:#FFAAD4\">\n";                    
                      echo "<input type=\"checkbox\" name=\"aut_insc_eleve2_".$k."_".$current_eleve_login."\" value=\"y\" ";
                      if (isset($reg_aut_insc_eleve2) and ($reg_aut_insc_eleve2=='y')) echo "checked=\"checked\" ";
                      echo " />";
                      echo "</td>\n";          
                    }
                    
                } else {
                    echo "<td>";
                    // L'élève est déjà affecté
                    $id_prop2 = sql_query1("select id_prop from bas_propositions where id_bas='".$reg_choix_eleve2."'");
                    if ($id_prop2 != "-1") echo $id_prop2; else echo "&nbsp;";
                    if ($reg_choix_eleve0 == $reg_choix_eleve2) echo " <b>(Aff.&nbsp;finale)</b>";
                    echo "</td>\n";
                    if (($aut_insc_eleve=='y') and ($inscription_bas == "y"))   // dans le cas $inscription_bas=='r', on n'affiche pas la colonne de "blocage élève"
                      echo "<td>&nbsp;</td>";

                }
            }
            $k++;
        }
        echo "</tr>";
        $i++;
    }

    echo "</table>";
    echo "<div id=\"fixe\">";
    echo "<div style='text-align:center'><input type=\"submit\" name=\"ok\" value=\"Enregistrer\" /></div>";
    echo "<input type=\"hidden\" name=\"is_posted\" value=\"yes\" />";
    echo "<input type=\"hidden\" name=\"action\" value=\"inscription\" />";
    echo "<input type=\"hidden\" name=\"numero_bas\" value=\"".$numero_bas."\" />";
    echo "<input type=\"hidden\" name=\"login_prof\" value=\"".$login_prof."\" />";

    echo "</div></form>\n";
    // Affichage de la liste des actvités par filire

    $appel_donnees_filieres = mysql_query("SELECT DISTINCT f.id_filiere FROM eleves e, j_eleves_professeurs p, bas_j_eleves_filieres f
        WHERE (
           p.login = e.login AND
           p.professeur = '".$login_prof."' AND
           f.id_eleve=e.login
           ) ORDER BY f.id_filiere");
    $nombre_lignes = mysql_num_rows($appel_donnees_filieres);
    $nbf = 0;
    while($nbf < $nombre_lignes) {
        $id_filiere = mysql_result($appel_donnees_filieres, $nbf, "id_filiere");
        //$affiche_titre = 0;
        include "./code_liste_bas_par_classe.php";
        $nbf++;
    }

}

// formulaire de saisie des filières
if (isset($action) and ($action=="inscription_filiere")) {
    echo " - Saisie des filières des élèves</p>\n";
    echo "<p>Affectez ci-dessous chacun de vos élèves à la filière suivie</p>\n";
    echo "<form action=\"index_suivi.php\" name=\"inscription_filiere\" method=\"post\">\n";
    
    echo "<table>\n";

    $i = "0";
    while($i < $nombre_lignes) {
        $current_eleve_login = mysql_result($appel_donnees_eleves, $i, "login");
        $current_eleve_nom = mysql_result($appel_donnees_eleves, $i, "nom");
        $current_eleve_prenom = mysql_result($appel_donnees_eleves, $i, "prenom");
        echo "<tr><td><span class='style_bas'>$current_eleve_nom $current_eleve_prenom<br /></td>\n";
        $filiere = sql_query1("select id_filiere from bas_j_eleves_filieres where id_eleve='".$current_eleve_login."'");
        echo "<td><select name=\"choix_filiere_".$current_eleve_login."\" size=\"1\">\n";
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
        $i++;
    }

    echo "</table>";
    echo "<div id=\"fixe\">";
    echo "<center><input type=\"submit\" name=\"ok\" value=\"Enregistrer\" /></center>";
    echo "<input type=\"hidden\" name=\"is_posted\" value=\"yes\" />";
    echo "<input type=\"hidden\" name=\"action\" value=\"inscription_filiere\" />";
    echo "<input type=\"hidden\" name=\"numero_bas\" value=\"".$numero_bas."\" />";
    echo "<input type=\"hidden\" name=\"login_prof\" value=\"".$login_prof."\" />";

    echo "</div></form>\n";
}

?>
<br />&nbsp;
<br />&nbsp;
<br />&nbsp;
<br />&nbsp;
<br />&nbsp;
<br />&nbsp;
</div>
</body>
</html>