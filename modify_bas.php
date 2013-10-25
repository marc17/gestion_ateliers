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
$nom_script = "mod_plugins/gestion_ateliers/modify_bas.php";
if (!checkAccess_Plugin($nom_script)) {
    header("Location: ../../logout.php?auto=1");
    die();
}
// On vérifie que l'utilisateur a les droits spécifiques pour accéder à ce script
if (!calcul_autorisation_gestion_ateliers($_SESSION['login'],$nom_script)){
    header("Location: ../../logout.php?auto=1");
    die();
}
// Initialisation
$numero_bas = isset($_POST['numero_bas']) ? $_POST['numero_bas'] : (isset($_GET['numero_bas']) ? $_GET['numero_bas'] : NULL);
$id_matiere = isset($_POST['id_matiere']) ? $_POST['id_matiere'] : (isset($_GET['id_matiere']) ? $_GET['id_matiere'] : NULL);
$mode = isset($_POST['mode']) ? $_POST['mode'] : (isset($_GET['mode']) ? $_GET['mode'] : NULL);
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : NULL);
$id_bas = isset($_POST['id_bas']) ? $_POST['id_bas'] : (isset($_GET['id_bas']) ? $_GET['id_bas'] : NULL);
$reg_type = isset($_POST['reg_type']) ? $_POST['reg_type'] : (isset($_GET['reg_type']) ? $_GET['reg_type'] : NULL);
$reg_titre = isset($_POST['reg_titre']) ? $_POST['reg_titre'] : (isset($_GET['reg_titre']) ? $_GET['reg_titre'] : NULL);
$reg_precisions = isset($_POST['reg_precisions']) ? $_POST['reg_precisions'] : (isset($_GET['reg_precisions']) ? $_GET['reg_precisions'] : NULL);
$reg_responsable = isset($_POST['reg_responsable']) ? $_POST['reg_responsable'] : (isset($_GET['reg_responsable']) ? $_GET['reg_responsable'] : NULL);
$reg_responsable2 = isset($_POST['reg_responsable2']) ? $_POST['reg_responsable2'] : (isset($_GET['reg_responsable2']) ? $_GET['reg_responsable2'] : NULL);
$reg_responsable_b = isset($_POST['reg_responsable_b']) ? $_POST['reg_responsable_b'] : (isset($_GET['reg_responsable_b']) ? $_GET['reg_responsable_b'] : NULL);
$reg_responsable2_b = isset($_POST['reg_responsable2_b']) ? $_POST['reg_responsable2_b'] : (isset($_GET['reg_responsable2_b']) ? $_GET['reg_responsable2_b'] : NULL);
$retour = isset($_POST['retour']) ? $_POST['retour'] : (isset($_GET['retour']) ? $_GET['retour'] : NULL);
$reg_nb_max = isset($_POST['reg_nb_max']) ? $_POST['reg_nb_max'] : (isset($_GET['reg_nb_max']) ? $_GET['reg_nb_max'] : NULL);
$reg_salle = isset($_POST['reg_salle']) ? $_POST['reg_salle'] : (isset($_GET['reg_salle']) ? $_GET['reg_salle'] : NULL);
$reg_duree = isset($_POST['reg_duree']) ? $_POST['reg_duree'] : (isset($_GET['reg_duree']) ? $_GET['reg_duree'] : NULL);
$reg_debut_sequence = isset($_POST['reg_debut_sequence']) ? $_POST['reg_debut_sequence'] : (isset($_GET['reg_debut_sequence']) ? $_GET['reg_debut_sequence'] : NULL);
$reg_debut_final = isset($_POST['reg_debut_final']) ? $_POST['reg_debut_final'] : (isset($_GET['reg_debut_final']) ? $_GET['reg_debut_final'] : NULL);
$reg_commentaire = isset($_POST['reg_commentaire']) ? $_POST['reg_commentaire'] : (isset($_GET['reg_commentaire']) ? $_GET['reg_commentaire'] : NULL);

$k=1;
while ($k<NB_FILIERES+1) {
  $temp = "reg_public_".$k;
  $$temp = isset($_POST['reg_public_'.$k]) ? $_POST['reg_public_'.$k] : (isset($_GET['reg_public_'.$k]) ? $_GET['reg_public_'.$k] : NULL);
  $k++;
}

if (isset($retour) and ($retour == 'admin_bas_affectations')) {
    $chemin_retour = "admin_bas_affectations.php?numero_bas=".$numero_bas;
    $chemin_retour2 = $chemin_retour;
} else if (isset($retour) and ($retour == 'admin_bas')) {
    $chemin_retour = "admin_bas.php?numero_bas=".$numero_bas;
    $chemin_retour2 = $chemin_retour;
} else if (isset($retour) and ($retour == 'admin_bas_salles')) {
    $chemin_retour = "admin_bas_salles.php?numero_bas=".$numero_bas;
    $chemin_retour2 = $chemin_retour;
} else {
    $chemin_retour = "index.php?id_matiere=".$id_matiere."&numero_bas=".$numero_bas;
    $chemin_retour2 = "index.php?id_matiere=".$id_matiere."&amp;numero_bas=".$numero_bas;
}


$appel_classes = mysql_query("select * from bas_classes");
$nb_appel_classes = mysql_num_rows($appel_classes);
$n = 0;
while ($n < $nb_appel_classes) {
    $idclasse = mysql_result($appel_classes,$n,'id_classe');
    $nom_court = mysql_result($appel_classes,$n,'nom_classe');
    $classe[$idclasse] = $nom_court;
    $n++;
}

$description_bas = sql_query1("select description_bas from bas_bas where id_bas='".$numero_bas."'");
// Constitution du tableau $per
$per =  tableau_periode($numero_bas);

$message_pb_salle = "ATTENTION ! La salle que vous avez choisie est déjà prise sur le créneau que vous avez choisi. Vous devez choisir une autre salle, ou bien un autre horaire.";
$message_pb_salle_2 = "ATTENTION ! Il ne reste pas suffisamment de créneaux libres pour la salle que vous avez choisie. Vous devez choisir une autre salle, ou bien un autre horaire.";
if (count($per) == 1) {
    $message_pb_prof = "ATTENTION ! Le responsable de cette activité est déjà pris sur le même créneau horaire pour une autre activité. Vous devez choisir un autre animateur.";
    $message_pb_prof_b = "ATTENTION ! Le co-responsable de cette activité est déjà pris sur le même créneau horaire pour une autre activité. Vous devez choisir un autre co-animateur.";
} else {
    $message_pb_prof = "ATTENTION ! Le responsable de cette activité est déjà pris sur le même créneau horaire pour une autre activité. Vous devez choisir un autre animateur, ou bien un autre horaire.";
    $message_pb_prof_b = "ATTENTION ! Le co-responsable de cette activité est déjà pris sur le même créneau horaire pour une autre activité. Vous devez choisir un autre co-animateur, ou bien un autre horaire.";
}

// fonctions

// Test de cohérence d'occupation des salles
function test_occupation_salle($reg_salle,$reg_debut_sequence,$reg_duree,$numero_bas,$id_temp) {
    if (($reg_debut_sequence >= 1) and ($reg_salle != '')) {
        // Si deux activités demandent une salle à la même heure, echec
        $test = mysql_num_rows(mysql_query("select salle from bas_propositions where
        (salle = '".$reg_salle."' and debut_sequence = '".$reg_debut_sequence."' and num_bas='".$numero_bas."')"));
        if ($test >= 2) {
            return 1;
            die();
        }
        $test2 = mysql_query("select id_bas, duree, debut_sequence from bas_propositions where
        (salle = '".$reg_salle."' and num_bas='".$numero_bas."' and debut_sequence != '0' and id_bas!='".$id_temp."')");
        $nb_test = mysql_num_rows($test2);
        //echo "select duree, debut_sequence from bas_propositions where (salle = '".$reg_salle."' and num_bas='".$numero_bas."' and debut_sequence != '0')";
        //echo $nb_test;
        $i = 0;
        while ($i < $nb_test) {
        //echo mysql_result($test2,$i,'id_bas');
            $duree = mysql_result($test2,$i,'duree');
            $debut_sequence = mysql_result($test2,$i,'debut_sequence');
            $result = 1;
            if ($reg_debut_sequence+$reg_duree<=$debut_sequence)
                $result = 0;
            if ($debut_sequence+$duree<=$reg_debut_sequence)
                $result = 0;
            if ($result == 1) {
                return 1;
                die();
            }

            $i++;
        }

    } else {
        return 0;
    }
    return 0;
}

// fonction vérifiant si une salle n'est pas occupée sur plus de coun($per) créneaux horaires
function test_occupation_salle_2($reg_salle,$numero_bas,$nb_periode) {
    if  ($reg_salle != '') {
        $test = mysql_query("select duree from bas_propositions where
        (salle = '".$reg_salle."' and num_bas='".$numero_bas."')");
        $nb_test = mysql_num_rows($test);
        $i = 0;
        $total = 0;
        while ($i < $nb_test) {
            $duree = mysql_result($test,$i,'duree');
            $total += $duree;
            $i++;
        }
        if ($total > $nb_periode) {
            return 1;
            die();
        }
    } else {
        return 0;
    }
    return 0;
}


// Test de cohérence prof-horaire
function test_prof_horaire($login_prof,$reg_debut_sequence,$reg_duree,$numero_bas,$nb_periode,$id_temp) {
    $test2 = mysql_query("select duree, debut_sequence from bas_propositions where
    ((responsable = '".$login_prof."' or coresponsable = '".$login_prof."')  and num_bas='".$numero_bas."'  and id_bas!='".$id_temp."')");
    $nb_test = mysql_num_rows($test2);

/*    $total = 0;
    $i=0;
    while ($i < $nb_test) {
            $duree = mysql_result($test2,$i,'duree');
            $total += $duree;
            $i++;
    }
    if ($total > $nb_periode) {
        return 1;
        die();
    }
*/
    if ($reg_debut_sequence >= 1) {
        if ($login_prof != '') {
            $test = mysql_num_rows(mysql_query("select debut_sequence from bas_propositions where
            (debut_sequence = '".$reg_debut_sequence."' and
            (responsable = '".$login_prof."' or coresponsable = '".$login_prof."') and
            num_bas='".$numero_bas."')"));
            if ($test >= 2) {
                return 1;
                die();
            }
        }
        $i = 0;
        while ($i < $nb_test) {
            $duree = mysql_result($test2,$i,'duree');
            $debut_sequence = mysql_result($test2,$i,'debut_sequence');
            $result = 1;
            if ($reg_debut_sequence+$reg_duree<=$debut_sequence)
                $result = 0;
            if ($debut_sequence+$duree<=$reg_debut_sequence)
                $result = 0;
            if ($result == 1) {
                return 1;
                die();
            }
            $i++;
        }
    } else {
        return 0;
    }
    return 0;
}


if (isset($_POST['is_posted']) and ($_POST['is_posted'] =="1")) {
    // On verifie si l'atelier est ouvert
    $close_bas = sql_query1("select close_bas from bas_bas where id_bas='".$numero_bas."'");
    if (($close_bas == "y") and !(calcul_autorisation_gestion_ateliers($_SESSION['login'],"mod_plugins/gestion_ateliers/droit_special_modify_bas.txt"))) {
        echo "Enregistrement impossible : les inscriptions sont closes.";
        echo "</body></html>";
        die();
    }
    // On verifie que c'est la bonne matiere
    $test_matiere = sql_query1("select id_matiere from bas_j_matieres_profs
    where ( id_professeur = '".$_SESSION['login']."' and id_matiere='".$id_matiere."') ");
    if (($test_matiere == "-1") and !(calcul_autorisation_gestion_ateliers($_SESSION['login'],"mod_plugins/gestion_ateliers/droit_special_modify_bas.txt")))  {
        echo "Enregistrement impossible : problème de droits.";
        echo "</body></html>";
        die();
    }

    if ($reg_responsable == '') $reg_responsable = $reg_responsable2;
    if ($reg_responsable_b == '') $reg_responsable_b = $reg_responsable2_b;
    if (($reg_nb_max == '0') or ($reg_nb_max == '')) $nb_bloque = '40'; else {
        $temp = $reg_nb_max*25/100;
        $temp = max(5,$temp);
        $nb_bloque = $reg_nb_max + $temp;
    }

    if (!isset($_POST['id_bas'])) {
        // Détermination du numéro d'identification de la proposition
        $test = mysql_query("select id_prop from bas_propositions where (id_matiere = '".$id_matiere."' and num_bas= '".$numero_bas."') order by id_prop DESC");
        $nb_test = mysql_num_rows($test);
        if ($nb_test == 0) {
            $id_prop = $id_matiere."-01";
        } else {
            $id_prop = mysql_result($test, 0, 'id_prop');
            $tab = explode("-", $id_prop);
            $num = $tab[1];
            settype($num,"integer");
            $num++;
             ;
            $id_prop = $id_matiere."-".str_pad($num, 2, 0, STR_PAD_LEFT);
        }

        $sql_data = "INSERT INTO bas_propositions SET
        type = '".$reg_type."',
        titre = '".$reg_titre."',
        precisions = '".$reg_precisions."',
        responsable = '".$reg_responsable."',
        coresponsable = '".$reg_responsable_b."',
        id_matiere = '".$id_matiere."',
        proprietaire  = '".$_SESSION['login']."',
        num_bas = '".$numero_bas."',
        nb_max = '".$reg_nb_max."',
        salle = '".$reg_salle."',
        duree = '".$reg_duree."',
        debut_sequence = '".$reg_debut_sequence."',";
       if (isset($reg_debut_final))
           $sql_data .= "debut_final = '".$reg_debut_final."',";
        $sql_data .=  "commentaire = '".$reg_commentaire."', ";
        $k=1;
        while ($k<NB_FILIERES+1) {
          $temp = "reg_public_".$k;
          $sql_data .= " public_".$k." = '".$$temp."', "; 
          $k++;
        }
        $sql_data .= " nb_bloque = '".$nb_bloque."',
        id_prop = '".$id_prop."'
        ";
        $reg_data = mysql_query($sql_data);
        $id_temp = mysql_insert_id();
        if (!$reg_data) {
           $mess = rawurlencode("Erreur lors de l'enregistrement des données.");
           header("Location: ".$chemin_retour."&msg=".$mess."");
           die();
        } else if (test_occupation_salle($reg_salle,$reg_debut_sequence,$reg_duree,$numero_bas,$id_temp) == 1) {
            // Mise à jour de l'enregistrement
            $reg_data = mysql_query("UPDATE bas_propositions SET
            salle = '' WHERE id_bas = '".$id_temp."'");
            $msg = $message_pb_salle;
            $mess = rawurlencode($msg);
            header("Location: ".$chemin_retour."&msg=".$mess."");
            die();

        } else if (test_occupation_salle_2($reg_salle,$numero_bas,count($per)) == 1) {
            // Mise à jour de l'enregistrement
            $reg_data = mysql_query("UPDATE bas_propositions SET
            salle = '' WHERE id_bas = '".$id_temp."'");
            $msg = $message_pb_salle_2;
            $mess = rawurlencode($msg);
            header("Location: ".$chemin_retour."&msg=".$mess."");
            die();
        } else if (test_prof_horaire($reg_responsable,$reg_debut_sequence,$reg_duree,$numero_bas,count($per),$id_temp) == 1) {
            // Mise à jour de l'enregistrement
            if (count($per) == 1)
                $reg_data = mysql_query("UPDATE bas_propositions SET responsable = 'A DEFINIR' WHERE id_bas = '".$id_temp."'");
            else
                $reg_data = mysql_query("UPDATE bas_propositions SET debut_sequence = '' WHERE id_bas = '".$id_temp."'");

            $msg = $message_pb_prof;
            $mess = rawurlencode($msg);
            header("Location: ".$chemin_retour."&msg=".$mess."");
            die();
        } else if (($reg_responsable_b != '') and (test_prof_horaire($reg_responsable_b,$reg_debut_sequence,$reg_duree,$numero_bas,count($per),$id_temp) == 1)) {
            // Mise à jour de l'enregistrement
            if (count($per) == 1)
                $reg_data = mysql_query("UPDATE bas_propositions SET coresponsable = 'A DEFINIR' WHERE id_bas = '".$id_temp."'");
            else
                $reg_data = mysql_query("UPDATE bas_propositions SET debut_sequence = '' WHERE id_bas = '".$id_temp."'");
            $msg = $message_pb_prof_b;
            $mess = rawurlencode($msg);
            header("Location: ".$chemin_retour."&msg=".$mess."");
            die();
        } else if ($mode == "multiple") {
           $msg = "L'activité a été enregistrée. Vous pouvez entrer l'activité suivante !";
           $mess = rawurlencode($msg);
           header("Location: modify_bas.php?mode=multiple&msg=$mess&id_matiere=$id_matiere&numero_bas=$numero_bas");
           die();
        } else {
           $msg = "L'activité a été enregistrée !";
           $mess = rawurlencode($msg);
           header("Location: ".$chemin_retour."&msg=".$mess."");
           die();
        }
    } else {
        // On verifie que le prof à bien le droit de modifier ce bas
        $bas_matiere = sql_query1("select id_matiere from bas_propositions where id_bas='".$_POST['id_bas']."'");
        if (($bas_matiere != $id_matiere)  and !(calcul_autorisation_gestion_ateliers($_SESSION['login'],"mod_plugins/gestion_ateliers/droit_special_modify_bas.txt"))) {
           $mess = rawurlencode("Vous n'avez pas les droits suffisants pour modifier cet enregistrement.");
           header("Location: ".$chemin_retour."&msg=".$mess."");
           die();
        }
        // Mise à jour de l'enregistrement
        $sql_data =  "UPDATE bas_propositions SET
        type = '".$reg_type."',
        titre = '".$reg_titre."',
        precisions = '".$reg_precisions."',
        responsable = '".$reg_responsable."',
        coresponsable = '".$reg_responsable_b."',
        num_bas = '".$numero_bas."',
        nb_max = '".$reg_nb_max."',
        salle = '".$reg_salle."',
        duree = '".$reg_duree."',
        debut_sequence = '".$reg_debut_sequence."',";
       if (isset($reg_debut_final))
           $sql_data .= "debut_final = '".$reg_debut_final."',";
        $sql_data .=  "commentaire = '".$reg_commentaire."', ";
        $k=1;
        while ($k<NB_FILIERES+1) {
          $temp = "reg_public_".$k;
          $sql_data .= " public_".$k." = '".$$temp."', "; 
          $k++;
        }
        $sql_data .= "nb_bloque = '".$nb_bloque."'
        WHERE id_bas = '".$_POST['id_bas']."'
        ";
        $reg_data = mysql_query($sql_data);
        if (!$reg_data) {
           $mess = rawurlencode("Erreur lors de l'enregistrement des données.");
           header("Location: ".$chemin_retour."&msg=".$mess."");
           die();
        } else if (test_occupation_salle($reg_salle,$reg_debut_sequence,$reg_duree,$numero_bas,$_POST['id_bas']) == 1) {
            // Mise à jour de l'enregistrement
            $reg_data = mysql_query("UPDATE bas_propositions SET
            salle = '' WHERE id_bas = '".$_POST['id_bas']."'");
            $msg = $message_pb_salle;
            $mess = rawurlencode($msg);
           header("Location: ".$chemin_retour."&msg=".$mess."");
            die();
        } else if (test_occupation_salle_2($reg_salle,$numero_bas,count($per)) == 1) {
            // Mise à jour de l'enregistrement
            $reg_data = mysql_query("UPDATE bas_propositions SET
            salle = '' WHERE id_bas = '".$_POST['id_bas']."'");
            $msg = $message_pb_salle_2;
            $mess = rawurlencode($msg);
           header("Location: ".$chemin_retour."&msg=".$mess."");
            die();

        } else if (test_prof_horaire($reg_responsable,$reg_debut_sequence,$reg_duree,$numero_bas,count($per),$_POST['id_bas']) == 1) {
            // Mise à jour de l'enregistrement
            if (count($per) == 1)
                $reg_data = mysql_query("UPDATE bas_propositions SET responsable = '' WHERE id_bas = '".$_POST['id_bas']."'");
            else
                $reg_data = mysql_query("UPDATE bas_propositions SET debut_sequence = '0' WHERE id_bas = '".$_POST['id_bas']."'");
            $msg = $message_pb_prof;
            $mess = rawurlencode($msg);
            header("Location: ".$chemin_retour."&msg=".$mess."");
            die();
        } else if (($reg_responsable_b != '') and (test_prof_horaire($reg_responsable_b,$reg_debut_sequence,$reg_duree,$numero_bas,count($per),$_POST['id_bas']) == 1)) {
            // Mise à jour de l'enregistrement
            if (count($per) == 1)
                $reg_data = mysql_query("UPDATE bas_propositions SET coresponsable = '' WHERE id_bas = '".$_POST['id_bas']."'");
            else
                $reg_data = mysql_query("UPDATE bas_propositions SET debut_sequence = '0' WHERE id_bas = '".$_POST['id_bas']."'");
            $msg = $message_pb_prof_b;
            $mess = rawurlencode($msg);
            header("Location: ".$chemin_retour."&msg=".$mess."");
            die();

        } else {
            $msg = "L'activité a été modifiée !";
            $mess = rawurlencode($msg);
            header("Location: ".$chemin_retour."&msg=".$mess."");
            die();
        }

    }

}

if (isset($id_bas) and ($id_bas!='') and (!isset($action))) {
    $type = sql_query1("select type from bas_propositions where id_bas='".$id_bas."'");
    if ($type == 'R') $action = "type_remediation";
    if ($type == 'D') $action = "type_public_designe";
}

//**************** EN-TETE *********************
if ($action == 'dedoublement')
    $titre_page = "Gestion des Ateliers | Dédoublement d'une activité";
else
    $titre_page = "Gestion des ".$NomAtelier_pluriel."<br />Ajouter/Modifier une activité";


require_once("../../lib/header.inc");
//**************** FIN EN-TETE *****************

// Choix de la matière
if ((calcul_autorisation_gestion_ateliers($_SESSION['login'],"mod_plugins/gestion_ateliers/droit_special_modify_bas.txt")) and !isset($id_bas) and ($id_matiere=='')){
    echo "<form action=\"modify_bas.php\" name=\"matiere\" method=\"post\">\n";
    echo "<p class='grand'>Choisissez la matière</p>";
    $req = mysql_query("select matiere, nom_complet from bas_matieres order by nom_complet");
    $nb_req = mysql_num_rows($req);
    $i = 0;
    echo "<select name=\"id_matiere\" size=\"1\">\n";
    while ($i < $nb_req) {
        $id_matiere2 = mysql_result($req,$i,'matiere');
        $nom_matiere2 = mysql_result($req,$i,'nom_complet');
        echo "<option value=".$id_matiere2.">".$nom_matiere2."</option>\n";
        $i++;
    }
    echo "</select>\n";
    echo "<input type=\"hidden\" name=\"numero_bas\" value=\"$numero_bas\" />\n";
    echo "<input type=\"hidden\" name=\"mode\" value=\"$mode\" />\n";
    if (isset($action))
        echo "<input type=\"hidden\" name=\"action\" value=\"$action\" />\n";
    if (isset($retour))
        echo "<input type=hidden name=retour value=\"$retour\" />\n";
    echo "<input type=\"submit\" name=\"ok\" value=\"Envoyer\" />\n";
    echo "</form>\n";
    die();
    echo "</body></html>";
}

?>

<script type="text/javascript">

function avertissement ()
{
  if(document.forms["main"].reg_type.value == "D")
  {
    alert ( "Avertissement : vous avez choisi un activité à public désigné.\n\rCe type d\'activité est normalement destiné à la Vie Scolaire.\n\rPour une activité à public désigné, les élèves ne sont pas libres de s\'inscrire.\n\rSeuls les élèves que vous aurez choisi et indiqué aux professeurs de suivi pourront être inscrits.");
  }
}

function validate_and_submit ()
{
  if(document.forms["main"].reg_titre.value == "")
  {
    alert ( "Donnée manquante : intitulé de l'activité");
    return false;
  }
  if(document.forms["main"].reg_responsable.value=="" && document.forms["main"].reg_responsable2.value== "")
  {
    alert ( "Donnée manquante : animateur de l'activité");
    return false;
  }

  if(document.forms["main"].reg_responsable.value!="" && document.forms["main"].reg_responsable2.value!= "")
  {
    alert ( "Erreur : actuellement, deux animateurs sont définis. Vous ne devez indiquer qu'un seul animateur.");
    return false;
  }
  if(document.forms["main"].reg_responsable_b.value!="" && document.forms["main"].reg_responsable2_b.value!= "")
  {
    alert ( "Erreur : actuellement, deux co-animateurs sont définis. Vous ne devez indiquer qu'un seul co-animateur.");
    return false;
  }


  if(document.forms["main"].reg_type.value == "")
  {
    alert ( "Vous devez spécifier le type de l'activité (Soutien ou Approfondissement)");
    return false;
  }

  if(document.forms["main"].reg_nb_max.value == "")
  {
    alert ( "Vous devez spécifier le nombre maximum d'élèves souhaité.");
    return false;
  }

if (<?php
$k=1;
while ($k<NB_FILIERES+1) {
   echo "!(document.forms[\"main\"].reg_public_".$k.".checked)";
   if ($k<NB_FILIERES) echo " && ";
   $k++;
}
?>
)
  {
    alert ( "Vous devez spécifié le public visé. ");
    return false;
  }

document.forms["main"].submit();
return true;
}
</SCRIPT>
<?php
echo "<p class=bold>| <a href=\"".($chemin_retour2)."\">Retour au tableau général</a> |";

if (isset($id_bas) and ($id_bas!='')) {
    $call_bas_info = mysql_query("SELECT * FROM bas_propositions WHERE id_bas='$id_bas'");
    $reg_type = mysql_result($call_bas_info, "0", "type");
    $reg_titre = mysql_result($call_bas_info, "0", "titre");
    $reg_precisions = mysql_result($call_bas_info, "0", "precisions");
    $k=1;
    while ($k<NB_FILIERES+1) {
      $temp = "reg_public_".$k;
      $$temp = mysql_result($call_bas_info, "0", "public_".$k);
      $k++;
    }
    $reg_responsable = mysql_result($call_bas_info, "0", "responsable");
    $reg_responsable_b = mysql_result($call_bas_info, "0", "coresponsable");
    $reg_matiere = mysql_result($call_bas_info, "0", "id_matiere");
    $reg_proprietaire = mysql_result($call_bas_info, "0", "proprietaire");
    $reg_nb_max = mysql_result($call_bas_info, "0", "nb_max");
    $reg_salle = mysql_result($call_bas_info, "0", "salle");
    $reg_duree = mysql_result($call_bas_info, "0", "duree");
    $reg_commentaire = mysql_result($call_bas_info, "0", "commentaire");
    if ($action == 'dedoublement')
        $reg_debut_sequence = mysql_result($call_bas_info, "0", "debut_final");
    else
        $reg_debut_sequence = mysql_result($call_bas_info, "0", "debut_sequence");
} else {
    $reg_nb_max = '';
    $reg_responsable = $_SESSION['login'];
    $reg_matiere = $id_matiere;
}

if ($action == 'dedoublement') {
    unset($id_bas);
    $id_matiere = $reg_matiere;
    $reg_debut_final = mysql_result($call_bas_info, "0", "debut_final");
    $reg_id_prop = mysql_result($call_bas_info, "0", "id_prop");
}
// données sur le bas
$date_bas = sql_query1("select date_bas from bas_bas where id_bas='".$numero_bas."'");
$nom_bas = sql_query1("select nom from bas_bas where id_bas='".$numero_bas."'");
$nom_matiere = sql_query1("select nom_complet from bas_matieres where matiere = '".$id_matiere."'");
if ($nom_matiere == "-1")  $nom_matiere = "";
$type_activite = "Proposition d'activité";

echo " <b>".$nom_matiere." - ".$type_activite." - ".$nom_bas." du ".$date_bas." (".$description_bas.")</b></p>";

    echo "<form enctype=\"multipart/form-data\" name=\"main\"  action=\"modify_bas.php\" method=post>\n";

    echo "<table cellpadding=\"5\" cellspacing=\"5\">\n";

    echo "<tr><td valign=\"top\"><b>Intitulé de l'activité : </b>
    <br />Donnez un titre explicite
    <br />au contenu de l'activité</td>
    <td valign=\"top\"><textarea rows=\"2\" cols=\"80\" name=\"reg_titre\" >\n";
    if (isset($reg_titre)) { echo $reg_titre;}
    if ($action == 'dedoublement') echo " (Dédoublement de ".$reg_id_prop.")";
    echo "</textarea></td></tr>\n";

    echo "<tr><td valign=\"top\"><b>Description brève</b> (facultatif) :
    <br />Précisions sur l'activité
    <br />à destination des élèves.</td>
    <td valign=\"top\"><textarea rows=\"2\" cols=\"80\" name=\"reg_precisions\" >\n";
    if (isset($reg_precisions)) { echo $reg_precisions;}
    echo "</textarea></td></tr>\n";


    echo "<tr><td valign=\"top\"><b>Type : </b></td><td valign=\"top\">\n";
    echo "<select name=\"reg_type\" size=\"1\" onchange=\"avertissement()\" >\n";
    echo "<option value=\"\">(choisissez)</option>\n";
    echo "<option value=\"S\" ";
    if ((isset($reg_type)) and ($reg_type=='S')) { echo "selected";}
    echo ">Soutien</option>\n";
    echo "<option value=\"A\" ";
    if ((isset($reg_type)) and ($reg_type=='A')) { echo "selected";}
    echo ">Approfondissement</option>\n";
    echo "<option value=\"D\" ";
    if ((isset($reg_type)) and ($reg_type=='D')) { echo "selected";}
    echo ">Public désigné</option>\n";
    echo "<option value=\"R\" ";
    if ((isset($reg_type)) and ($reg_type=='R')) { echo "selected";}
    echo ">Remédiation</option>\n";
    echo "</select>";
    echo "</td></tr>";

    echo "<tr><td valign=\"top\"><b>Public concerné : </b>";
    if ($action == "type_public_designe")
        echo "<br />Seuls les élèves des classes cochées <br />pourront être inscrits à cette activité.";
    else
        echo "<br />Seuls les élèves des classes cochées <br />peuvent s'inscrire à cette activité.";
    echo "</td><td valign=\"top\">\n";
    echo "<table cellspacing=\"8\" border =0>\n";

    $n=1;
    while ($n<NB_NIVEAUX_FILIERES+1) {
      echo "\n<tr>";
      foreach($tab_filière[$n]["id"] as $key => $_id){
        $temp = "reg_public_".$_id;
        echo "<td><input type=\"checkbox\" name=\"reg_public_".$_id."\" value=\"y\" ";
        if ((isset($$temp)) and ($$temp=="y")) { echo "checked";}
        echo " /> ".$tab_filière[$n]["nom"][$key]."</td>\n";
      }
      echo "</tr>\n";
      $n++;        
    }
    echo "</table>\n";

    echo "</td></tr>\n";

    // Premier animateur
    echo "<tr><td valign=\"top\"><b>Animateur de l'activité : </b>
    <br />Indiquez ici les nom et prénom du professeur
    <br />qui encadre l'activité proposée.</td>\n";
    echo "<td valign=\"top\">";

    echo "<table><tr><td><select name=\"reg_responsable\" size=\"1\">\n";
    echo "<option value=\"\">(Choix)</option>\n";
    $k = 0;
    $req_prof = mysql_query("select * from bas_j_matieres_profs where id_matiere = '".$reg_matiere."' order by 'id_professeur'");
    $nb_prof = mysql_num_rows($req_prof);
    while ($k < $nb_prof) {
        $id_prof = mysql_result($req_prof,$k,'id_professeur');
        $nom_prof = sql_query1("select nom from utilisateurs where login='".$id_prof."'");
        $prenom_prof = sql_query1("select prenom from utilisateurs where login='".$id_prof."'");
        echo "<option value=\"".$id_prof."\" ";
        if ((isset($reg_responsable)) and ($reg_responsable==$id_prof)) {
            echo "selected";
            $in_liste = 'yes';
        }
        echo ">".$nom_prof." ".$prenom_prof."</option>\n";
        $k++;
    }
    echo "</select></td>";

    echo "<td><input type=\"text\" name=\"reg_responsable2\" size=\"30\" ";
    if (isset($reg_responsable) and !(isset($in_liste))) { echo "value=\"".$reg_responsable."\"";}
    echo "/></td></tr><tr><td></td><td>Remplissez le champ ci-dessus uniquement <br />si l'animateur ne figure pas dans la liste ci-contre.</td></tr></table>";

    echo "</td></tr>\n";

    // Deuxième animateur
    echo "<tr><td valign=\"top\"><b>Co-animateur de l'activité : </b>
    <br />S'il s'agit d'une activité encadrée par deux intervenants,
    <br />indiquez ici les nom et prénom du 2ème intervenant.
    </td>\n";
    echo "<td valign=\"top\">";

    echo "<table><tr><td><select name=\"reg_responsable_b\" size=\"1\">\n";
    echo "<option value=\"\">(Choix)</option>\n";
    $k = 0;
    $req_prof = mysql_query("select * from bas_j_matieres_profs where id_matiere = '".$reg_matiere."' order by 'id_professeur'");
    $nb_prof = mysql_num_rows($req_prof);
    while ($k < $nb_prof) {
        $id_prof = mysql_result($req_prof,$k,'id_professeur');
        $nom_prof = sql_query1("select nom from utilisateurs where login='".$id_prof."'");
        $prenom_prof = sql_query1("select prenom from utilisateurs where login='".$id_prof."'");
        echo "<option value=\"".$id_prof."\" ";
        if ((isset($reg_responsable_b)) and ($reg_responsable_b==$id_prof)) {
            echo "selected";
            $in_liste_b = 'yes';
        }
        echo ">".$nom_prof." ".$prenom_prof."</option>\n";
        $k++;
    }
    echo "</select></td>";

    echo "<td><input type=\"text\" name=\"reg_responsable2_b\" size=\"30\" ";
    if (isset($reg_responsable_b) and !(isset($in_liste_b))) { echo "value=\"".$reg_responsable_b."\"";}
    echo "/></td></tr><tr><td></td><td>Remplissez le champ ci-dessus uniquement <br />si l'animateur ne figure pas dans la liste ci-contre.</td></tr></table>";

    echo "</td></tr>\n";


    echo "<tr><td valign=\"top\"><b>Nombre maximum d'élèves acceptés pour cette actvité : </b></td>
    <td valign=\"top\"><select name=\"reg_nb_max\" size=\"1\">\n";
    echo "<option value=\"\">(choisissez)</option>\n";
    $k = 1;
    while ($k < 51) {
       echo "<option value=\"".$k."\" ";
        if ((isset($reg_nb_max)) and ($reg_nb_max==$k)) { echo "selected";}
        echo ">".$k."</option>\n";
        $k++;
    }
    echo "<option value=\"0\" ";
    if ((isset($reg_nb_max)) and ($reg_nb_max=='0')) { echo "selected";}
    echo ">Indifférent</option>\n";

    echo "</select></td></tr>\n";

    echo "<tr><td valign=\"top\"><b>Salle souhaitée : </b>
    <br />A préciser uniquement si l'activité
    <br />proposée nécessite une salle particulière.</td>
    <td valign=\"top\"><select name=\"reg_salle\" size=\"1\">\n";
    echo "<option value=\"\">(sans objet)</option>\n";
    $k = 0;
    $flag = 0;
    $req_salles = mysql_query("select * from bas_salles where special = 'y' order by 'nom_salle'");
    $nb_salles = mysql_num_rows($req_salles);
    while ($k < $nb_salles) {
        $id_salle = mysql_result($req_salles,$k,'id_salle');
        $nom_salle = mysql_result($req_salles,$k,'nom_salle');
        echo "<option value=\"".$id_salle."\" ";
        if ((isset($reg_salle)) and ($reg_salle==$id_salle)) { echo "selected"; $flag = 1;}
        echo ">".$nom_salle."</option>\n";
        $k++;
    }
    if ((isset($reg_salle)) and ($flag == 0))
        echo "<option value=\"".$reg_salle."\"selected >".$reg_salle."</option>\n";
    echo "</select></td></tr>";

    if (count($per) == 1) {
        echo "<input type=\"hidden\" name=\"reg_duree\" value=\"1\" />";
    } else {
        echo "<tr><td valign=\"top\"><b>Durée de l'activité </b>(en nombres de séquences) :";

        echo "<br />Nombre max de séquences : <b>".count($per).".</b><br />Détails des séquences : ";
        $k = 1;
        while ($k < count($per)+1) {
            echo "<b>[".$per[$k]."]</b>";
            if ($k < count($per)) echo ", ";
            $k++;
        }
        echo "</td><td valign=\"top\"><select name=\"reg_duree\" size=\"1\">\n";
        $p = 1;
        while ($p < count($per) + 1) {
            echo "<option value=\"".$p."\" ";
            if ((isset($reg_duree)) and ($reg_duree==$p)) { echo "selected";}
            echo ">".$p."</option>\n";
            $p++;
        }
        echo "</select></td></tr>\n";
    }

    if (count($per) == 1) {
        echo "<tr><td valign=\"top\"><b>Heure de début de l'activité : </b></td>
        <td valign=\"top\">".$per[1]."<input type=\"hidden\" name=\"reg_debut_sequence\" value=\"1\" />
        <input type=\"hidden\" name=\"reg_debut_final\" value=\"1\" />
        </td></tr>\n";
    } else if ($action == 'dedoublement') {
        echo "<tr><td valign=\"top\"><b>Heure de début de l'activité : </b></td>
        <td valign=\"top\">".$per[1]."<input type=\"hidden\" name=\"reg_debut_sequence\" value=\"".$reg_debut_final."\" />
        <input type=\"hidden\" name=\"reg_debut_final\" value=\"".$reg_debut_final."\" />
        </td></tr>\n";
    } else {
        echo "<tr><td valign=\"top\"><b>Horaires de l'activité : </b><br />(Merci de justifier votre souhait dans <br />le champ \"commentaires/observations\" ci-dessous.)</td><td valign=\"top\"><select name=\"reg_debut_sequence\" size=\"1\">\n";
        echo "<option value=\"0\">Indifférent</option>\n";
        $k = 1;
        while ($k < count($per)+1) {
            echo "<option value=\"".$k."\" ";
            if ((isset($reg_debut_sequence)) and ($reg_debut_sequence==$k)) { echo "selected";}
            echo ">".$per[$k]."</option>\n";
            $k++;
        }
        echo "</select></td></tr>\n";
    }
    echo "<tr><td valign=\"top\"><b>Commentaires / observations : </b>
    <br />Mettez ici des remarques à destination
    <br />du gestionnaire des ".$NomAtelier_pluriel.".</td><td valign=\"top\"><textarea rows=\"4\" cols=\"80\" name=\"reg_commentaire\" >";
    if (isset($reg_commentaire)) { echo $reg_commentaire;}
    echo "</textarea></td></tr>\n";

    echo "</table>";

    if (isset($id_bas)) echo "<input type=\"hidden\" name=\"id_bas\" value=\"".$id_bas."\" />";
    ?>
    <input type="hidden" name="is_posted" value="1" />
    <input type=hidden name=id_matiere value=<?php echo $id_matiere;?> />
    <input type=hidden name=numero_bas value=<?php echo $numero_bas;?> />
    <input type=hidden name=mode value=<?php echo $mode;?> />
    <input type=hidden name=retour value=<?php echo $retour;?> />
    <center><div id="fixe">
    <script type="text/javascript">
        document.writeln ( '<INPUT TYPE="button" VALUE="Enregistrer" ONCLICK="validate_and_submit()">' );
    </SCRIPT>
    </div></center>
    </form>

</body>
</html>