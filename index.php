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
$nom_script = "mod_plugins/gestion_ateliers/index.php";
if (!checkAccess_Plugin($nom_script)) {
    header("Location: ../../logout.php?auto=1");
    die();
}
// On vérifie que l'utilisateur a les droits spécifiques pour accéder à ce script
if (!calcul_autorisation_gestion_ateliers($_SESSION['login'],$nom_script)){
    header("Location: ../../logout.php?auto=1");
    die();
}


if (isset($_POST['action']) and ($_POST['action'] == "imprime_feuilles")) {
    $del_imprime_feuille = mysql_query("delete from bas_imprime_feuilles_presence where login = '".$_SESSION['login']."'");
    if ((isset($_POST['imprime_feuilles_presence'])) and ($_POST['imprime_feuilles_presence']=='y'))
        $inser_imprime_feuille = mysql_query("insert into bas_imprime_feuilles_presence set
        imprime_feuilles_presence = 'y',
        login = '".$_SESSION['login']."'
        ");
}


// initialisation
$visu = isset($_POST['visu']) ? $_POST['visu'] : (isset($_GET['visu']) ? $_GET['visu'] : '');
$numero_bas = isset($_POST['numero_bas']) ? $_POST['numero_bas'] : (isset($_GET['numero_bas']) ? $_GET['numero_bas'] : NULL);
if (!isset($_SESSION['order_by'])) {$_SESSION['order_by'] = "id_prop";}
$_SESSION['order_by'] = isset($_POST['order_by']) ? $_POST['order_by'] : (isset($_GET['order_by']) ? $_GET['order_by'] : $_SESSION['order_by']);
$order_by = $_SESSION['order_by'];

$themessage = "Etes-vous sûr de vouloir supprimer cette proposition ?";


//**************** EN-TETE *****************
$titre_page = "Gestion des ".$NomAtelier_pluriel;
require_once("../../lib/header.inc");
//**************** FIN EN-TETE *************

// choix du bas
if (!(isset($numero_bas))) {
    echo "<p class=bold>| <a href=\"../../accueil.php\">Retour à la page d'accueil</a> |";
//    echo "<a href='admin_stats.php'>Statistiques</a> |";
    echo "</p>";

    echo "<p class='grand'>Choisissez la séance : </p>";
    $req = mysql_query("select * from bas_bas order by nom");
    $nb_bas = mysql_num_rows($req);
    $i = 0 ;
    echo "<table border=\"1\">";
    while ($i < $nb_bas) {
        $num_periode = mysql_result($req,$i,'num_periode');
        $num_bas = mysql_result($req,$i,'id_bas');
        $date_bas = mysql_result($req,$i,'date_bas');
        $nom_bas = mysql_result($req,$i,'nom');
        $close_bas = mysql_result($req,$i,'close_bas');
        $aff_liste_par_classe = mysql_result($req,$i,'aff_liste_par_classe');
        $aff_affectations_eleves = mysql_result($req,$i,'aff_affectations_eleves');
        $modif_affectations_bas_cpe = mysql_result($req,$i,'modif_affectations_bas_cpe');
        $qui_inscrit = mysql_result($req,$i,'qui_inscrit');
        if ($close_bas == "y") $close_bas = "Liste des activités"; else $close_bas = "<font color='green'><b>Consulter/proposer des activités</b></font>";
        $description_bas = mysql_result($req,$i,'description_bas');
        $type_bas = mysql_result($req,$i,'type_bas');
        echo "<tr><td><b>".$nom_bas." </b>du ".$date_bas." - ".$description_bas."</td><td><a href='index.php?numero_bas=".$num_bas."'> ".$close_bas."</a></td>\n";
        if ($aff_liste_par_classe == 'y') {
            echo "<td><a href='index_listes.php?numero_bas=".$num_bas."'>Activités par classe</a></td>\n";
        } else {
            echo "<td>&nbsp;</td>\n";
        }
        if ($aff_affectations_eleves == 'y') {
            echo "<td><a href='bas_par_classes.php?numero_bas=".$num_bas."'  target='_blank'>Affectations élèves par classe</a></td>\n";
        } else {
            echo "<td>&nbsp;</td>\n";
        }
        if (($_SESSION['statut'] == "cpe") or (calcul_autorisation_gestion_ateliers($_SESSION['login'],"mod_plugins/gestion_ateliers/droit_special_index.txt"))) {
            if ($modif_affectations_bas_cpe == 'y')
                echo "<td><a href='admin_inscrip_rapide.php?numero_bas=".$num_bas."'><b>Affecter un élève&nbsp;-<br />Elèves non affectés</b></a></td>\n";
            else
                echo "<td>&nbsp;</td>";
                echo "<td><a href='admin_toutes_feuilles_presence.php?numero_bas=".$num_bas."'><b>Feuilles présence</b></a></td>\n";

//             if ($qui_inscrit == 's') {
//                 echo "<td><a href='admin_bas_salles.php?numero_bas=".$num_bas."'><b>Affecter les salles</b></a></td>\n";
//             } else {
//                 echo "<td>&nbsp;</td>";
//             }
             if ($type_bas == "s")
                 echo "<td><a href='admin_toutes_feuilles_presence2.php?numero_bas=".$num_bas."'><b>Feuilles d'inscription</b></a></td>\n";
             else
                 echo "<td>&nbsp;</td>";

         }
        echo "</tr>";
        $i++;
    }
    echo "</table>";
    echo "<br />";
    /*
    // Impression des feuilles de présence
    echo "<form action=\"index.php\" name=\"imprime_feuille\" method=\"post\">\n";
    $imprime_feuilles_presence = sql_query1("select imprime_feuilles_presence from bas_imprime_feuilles_presence where login = '".$_SESSION['login']."'");
    echo "<table width=\"100%\" border=\"1\" cellpadding=\"5\">\n<tr><td>\n";
    echo "<table width=\"100%\" border=\"0\" cellpadding=\"5\">\n<tr>\n";
    echo "<td><input type=\"checkbox\" name=\"imprime_feuilles_presence\" value=\"y\" ";
    if ((isset($imprime_feuilles_presence)) and ($imprime_feuilles_presence == 'y')) echo " checked";
    echo " /></td>\n";
    echo "<td>Je ne souhaite pas imprimer moi-même les feuilles de présence pour chacune de mes activités.<br />
    Je souhaite donc qu'elles soient mises à ma disposition dans mon casier avant chaque atelier.</td>\n";
    echo "<td><input type=\"submit\" name=\"ok\" /><input type=\"hidden\" name=\"action\" value=\"imprime_feuilles\" /></td>\n";
    echo "</tr>\n</table>\n";
    echo "</td></tr>\n</table>\n";
    echo "</form>\n";
    */

}


// Choix de la matière
if (isset($numero_bas) and !(isset($_GET['id_matiere']))) {
    $req = mysql_query("select id_matiere from bas_j_matieres_profs
    where ( id_professeur = '".$_SESSION['login']."' )"
    );
    $nb_mat = mysql_num_rows($req);
    if ($nb_mat == 0) {
        // Mode visualisation uniquement
        $_GET['id_matiere'] = '';
    } else if ($nb_mat > 1) {
        echo "<p class=bold>| <a href=\"../../accueil.php\">Retour à la page d'accueil</a> |<a href=\"./index.php\"> Choisir un autre ".$NomAtelier_singulier."</a> |</p>";
        $i = 0 ;
        echo "<p><b>Choisissez la matière : </b></p>";
        while ($i < $nb_mat) {
            $id_matiere = mysql_result($req,$i,'id_matiere');
            $nom_matiere = mysql_result(mysql_query("select nom_complet from bas_matieres where matiere = '".$id_matiere."'"),0,'nom_complet');
            echo "<a href='index.php?id_matiere=".$id_matiere."&amp;numero_bas=$numero_bas'>".$nom_matiere."</a><br />";
            $i++;
        }
    } else {
        $id_matiere = mysql_result($req,0,'id_matiere');
        $nom_matiere = mysql_result(mysql_query("select nom_complet from bas_matieres where matiere = '".$id_matiere."'"),0,'nom_complet');
        $_GET['id_matiere'] = $id_matiere;
    }
}

// La matière est définie ainsi que le numéro de bas
if (isset($numero_bas) and (isset($_GET['id_matiere']))) {
    // On verifie que c'est la bonne matiere
    $test_matiere = sql_query1("select id_matiere from bas_j_matieres_profs
    where ( id_professeur = '".$_SESSION['login']."' and id_matiere='".$_GET['id_matiere']."') ");
    if ($test_matiere == "-1") {
        $_GET['id_matiere'] = '';
        $visu = 'all';
    }


    echo "<p class=bold>| <a href=\"../../accueil.php\">Retour à la page d'accueil</a> |<a href=\"./index.php\"> Choisir un autre ".$NomAtelier_singulier."</a> |";

    $req = mysql_query("select id_matiere from bas_j_matieres_profs
    where ( id_professeur = '".$_SESSION['login']."' )"
    );
    $nb_mat = mysql_num_rows($req);
    if ($nb_mat > 1)
        echo "<a href=\"./index.php?numero_bas=$numero_bas\"> Choisir une autre matière</a> |";
    echo "</p>";

    // données sur le bas
    $date_bas = sql_query1("select date_bas from bas_bas where id_bas='".$numero_bas."'");
    $nom_bas = sql_query1("select nom from bas_bas where id_bas='".$numero_bas."'");
    $num_periode = sql_query1("select num_periode from bas_bas where id_bas='".$numero_bas."'");
    $close_bas = sql_query1("select close_bas from bas_bas where id_bas='".$numero_bas."'");
    $aff_affectations_eleves = sql_query1("select aff_affectations_eleves from bas_bas where id_bas='".$numero_bas."'");
    $inscription_bas = sql_query1("select inscription_bas from bas_bas where id_bas='".$numero_bas."'");
    $qui_inscrit = sql_query1("select qui_inscrit from bas_bas where id_bas='".$numero_bas."'");
    $date_limite = sql_query1("select date_limite from bas_bas where id_bas='".$numero_bas."'");
    if ($close_bas == "y") $close_bas_mess = "<font color='red'>(Consultation uniquement)</font>"; else $close_bas_mess = " - <font color='red'>A remplir jusqu'au ".$date_limite." (inclus)</font>";
    if ($close_bas == "y") {
        // Nombre total d'élève à inscrire
        $nb_total = sql_count(sql_query("select distinct login from bas_classes bc, j_eleves_classes jec
        where
        bc.id_classe = jec.id_classe and
        jec.periode = '".$num_periode."'
        "));

         // Calcul du nombre d'élèves inscrits
        $k = 1;
        $nb_choix1 = $nb_total;
        $nb_periode = sql_query1("select count(num_creneau) from bas_creneaux where id_bas='".$numero_bas."'");
        while ($k < $nb_periode+1) {
          if ($aff_affectations_eleves == 'y')
            $nb_temp = sql_count(sql_query("select distinct login from bas_classes bc, j_eleves_classes jec, bas_j_eleves_bas bjeb
            where
            bc.id_classe = jec.id_classe and
            jec.periode = '".$num_periode."' and
            jec.login = bjeb.id_eleve and
            bjeb.num_bas = '".$numero_bas."' and
            bjeb.num_sequence = '".$k."' and
            bjeb.num_choix = '0'
            "));
           else
            $nb_temp = sql_count(sql_query("select distinct login from bas_classes bc, j_eleves_classes jec, bas_j_eleves_bas bjeb
            where
            bc.id_classe = jec.id_classe and
            jec.periode = '".$num_periode."' and
            jec.login = bjeb.id_eleve and
            bjeb.num_bas = '".$numero_bas."' and
            bjeb.num_sequence = '".$k."' and
            bjeb.num_choix = '1'
            "));

            $k++;
            $nb_choix1 = min($nb_choix1,$nb_temp);
            $k++;
        }
    }
    $description_bas = sql_query1("select description_bas from bas_bas where id_bas='".$numero_bas."'");


    // Constitution du tableau $per
    $per =  tableau_periode($numero_bas);

    // Suppression d'un atelier
    if (isset($_GET['action']) and ($_GET['action'] == "del_bas")) {
        $bas_matiere = sql_query1("select id_matiere from bas_propositions where id_bas='".$_GET['id_bas']."'");
        if (($close_bas == 'n') and (($bas_matiere == $_GET['id_matiere']) or (calcul_autorisation_gestion_ateliers($_SESSION['login'],"mod_plugins/gestion_ateliers/droit_special_index.txt"))))
            $del = mysql_query("delete from bas_propositions where id_bas='".$_GET['id_bas']."'");
    }


    $nom_matiere = '';
    if ($_GET['id_matiere']!='') $nom_matiere = "Matière : ".mysql_result(mysql_query("select nom_complet from bas_matieres where matiere = '".$_GET['id_matiere']."'"),0,'nom_complet')." - ";
    echo "<p class='grand'>".$nom_matiere." ".$nom_bas." du ".$date_bas." - ".$description_bas." ".$close_bas_mess."</p>";

    if (($_GET['id_matiere']!='')  or (calcul_autorisation_gestion_ateliers($_SESSION['login'],"mod_plugins/gestion_ateliers/droit_special_index.txt"))) {
        echo "<p><b>";
        if (($close_bas == 'n') or (calcul_autorisation_gestion_ateliers($_SESSION['login'],"mod_plugins/gestion_ateliers/droit_special_index.txt"))) {
            if ($_SESSION['statut'] != 'cpe') {
                if (calcul_autorisation_gestion_ateliers($_SESSION['login'],"mod_plugins/gestion_ateliers/droit_special_index.txt")) {
                echo "|<a href=\"modify_bas.php?mode=unique&amp;id_matiere=&amp;numero_bas=$numero_bas\">Proposer une activité</a>";
                echo "|<a href=\"modify_bas.php?mode=multiple&amp;id_matiere=&amp;numero_bas=$numero_bas\">Proposer des activités à la chaîne</a>";

                } else {
                echo "|<a href=\"modify_bas.php?mode=unique&amp;id_matiere=".$_GET['id_matiere']."&amp;numero_bas=$numero_bas\">Proposer une activité</a>";
                echo "|<a href=\"modify_bas.php?mode=multiple&amp;id_matiere=".$_GET['id_matiere']."&amp;numero_bas=$numero_bas\">Proposer des activités à la chaîne</a>";
                }
            } else
//                echo "|<a href=\"modify_bas.php?action=type_public_designe&amp;mode=unique&amp;id_matiere=".$_GET['id_matiere']."&amp;numero_bas=$numero_bas\">Proposer une activité à public désigné</a>";
                echo "|<a href=\"modify_bas.php?mode=unique&amp;id_matiere=".$_GET['id_matiere']."&amp;numero_bas=$numero_bas\">Proposer une activité</a>";

        }
        if ($_SESSION['statut'] != 'cpe')
            if ($visu=='') {
                echo "|<a href=\"index.php?id_matiere=".$_GET['id_matiere']."&amp;numero_bas=$numero_bas&amp;visu=all\">Voir les propositions dans toutes les matières</a>";
            } else {
            echo "|<a href=\"index.php?id_matiere=".$_GET['id_matiere']."&amp;numero_bas=$numero_bas&amp;visu=\">Voir uniquement les propositions dans ma matière</a>";
            }
        else
            $visu="all";
        echo "|</b></p>";
    }
    echo "<p class=\"medium\">";


    // On va chercher les actvités déjà existantes, et on les affiche.

    if ($visu == '') {
        $calldata = mysql_query("SELECT * FROM bas_propositions
        WHERE (id_matiere='".$_GET['id_matiere']."' and num_bas= '".$numero_bas."') ORDER BY $order_by");
    } else {
        $calldata = mysql_query("SELECT * FROM bas_propositions
        WHERE (num_bas= '".$numero_bas."') ORDER BY $order_by");
    }
    $nombreligne = mysql_num_rows($calldata);
    if ($nombreligne == 0) {
        echo "<p><b>Actuellement, aucune proposition n'a été enregistrée.</b></p>";
        echo "</body></html>";
        die();
    } else {
        echo "<p><b>Actuellement, ".$nombreligne." propositions ont été enregistrées.</b>";
        if ($close_bas == 'y') {
            echo " - <a href=\"javascript:centrerpopup('stats_bas.php?numero_bas=$numero_bas',600,480,'scrollbars=yes,statusbar=no,resizable=yes')\"><b> Voir les Statistiques </b></a>";
            if ($aff_affectations_eleves == 'y')
                echo " - Actuellement <b>".$nb_choix1."</b> élèves affectés sur ".$nb_total.".";
            else
                echo " - Actuellement <b>".$nb_choix1."</b> élèves inscrits sur ".$nb_total.".";
        }
        echo "</p>";
    }

    $k=1;
    $ordre_public="";
    while ($k<NB_FILIERES+1) {
      $ordre_public .="public_".$k;
      if ($k<NB_FILIERES)
      $ordre_public .=",";
      $k++;
    }            
    echo "<table width = 100% cellpadding=3 border=1>";
    echo "<tr>
    <td><a href='index.php?order_by=id_prop&amp;id_matiere=".$_GET['id_matiere']."&amp;numero_bas=$numero_bas&amp;visu=$visu'>N°<br /><i>Matière</i></a></td>\n
    <td><a href='index.php?order_by=type,".$ordre_public.",responsable&amp;id_matiere=".$_GET['id_matiere']."&amp;numero_bas=$numero_bas&amp;visu=$visu'>Type</a></td>\n
    <td><a href='index.php?order_by=titre,".$ordre_public.",type,responsable&amp;id_matiere=".$_GET['id_matiere']."&amp;numero_bas=$numero_bas&amp;visu=$visu'>Intitulé de l'activité</a></td>";
    echo "<td><a href='index.php?order_by=precisions,".$ordre_public.",type,responsable&amp;id_matiere=".$_GET['id_matiere']."&amp;numero_bas=$numero_bas&amp;visu=$visu'>Brève description</a></td>";
    if ($close_bas == 'y') {
        echo "<td><a href='index.php?order_by=debut_final,".$ordre_public.",type,responsable&amp;id_matiere=".$_GET['id_matiere']."&amp;numero_bas=$numero_bas&amp;visu=$visu'>Heure</a></td>";
    }
    if (calcul_autorisation_gestion_ateliers($_SESSION['login'],"mod_plugins/gestion_ateliers/droit_special_index.txt")) {
        echo "<td><a href='index.php?order_by=".$ordre_public.",titre,responsable&amp;id_matiere=".$_GET['id_matiere']."&amp;numero_bas=$numero_bas&amp;visu=$visu'>Commentaires</a></td>";
    }
    echo "<td><a href='index.php?order_by=".$ordre_public.",type,responsable&amp;id_matiere=".$_GET['id_matiere']."&amp;numero_bas=$numero_bas&amp;visu=$visu'>Public</a></td>
    <td><a href='index.php?order_by=responsable,".$ordre_public.",type&amp;id_matiere=".$_GET['id_matiere']."&amp;numero_bas=$numero_bas&amp;visu=$visu'>Animateur(s)</a></td>
    <td><a href='index.php?order_by=nb_max,".$ordre_public.",responsable&amp;id_matiere=".$_GET['id_matiere']."&amp;numero_bas=$numero_bas&amp;visu=$visu'>Nb. max.<br />élèves</a></td>";
    if ($close_bas == 'y') {
        if ($aff_affectations_eleves == 'y')
            echo "<td>Nb. élèves</td>";
        else
            echo "<td>Nb. Choix N°1</td>";


    }
    if (count($per) != 1)
        echo "<td><a href='index.php?order_by=duree,".$ordre_public.",responsable&amp;id_matiere=".$_GET['id_matiere']."&amp;numero_bas=$numero_bas&amp;visu=$visu'>Durée<br />en séquences</a></td>";
    if ($close_bas != 'y')
        echo "<td>Horaire souhaité</td>";
    echo "<td><a href='index.php?order_by=salle,id_prop&amp;id_matiere=".$_GET['id_matiere']."&amp;numero_bas=$numero_bas&amp;visu=$visu'>Salle</a></td>";

    if ((($_GET['id_matiere']!='') or (calcul_autorisation_gestion_ateliers($_SESSION['login'],"mod_plugins/gestion_ateliers/droit_special_index.txt"))) and ($close_bas == 'n'))
        echo "<td>-</td>";

    echo "</tr>";
    $_SESSION['chemin_retour'] = $_SERVER['REQUEST_URI'];

    $i = 0;
    while ($i < $nombreligne){
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
        if ($close_bas == 'y') {
            $bas_debut_final = @mysql_result($calldata, $i, "debut_final");
        }

        if ($bas_precisions == '') $bas_precisions = "&nbsp;";
        $k=1;
        while ($k<NB_FILIERES+1) {
          $temp = "public_".$k;
          $$temp = mysql_result($calldata, $i, "public_".$k);
          $k++;
        }
        $bas_matiere = mysql_result($calldata, $i, "id_matiere");
        $nom_matiere_prop = sql_query1("select nom_complet from bas_matieres where matiere = '".$bas_matiere."'");
        
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
       
        $proprietaire = @mysql_result($calldata, $i, "proprietaire");
        $commentaire = @mysql_result($calldata, $i, "commentaire");
        $debut_sequence = @mysql_result($calldata, $i, "debut_sequence");
        $reg_debut_final = @mysql_result($calldata, $i, "debut_final");
        $bas_proprietaire = sql_query1("select prenom from utilisateurs where login = '".$proprietaire."'")." ".sql_query1("select nom from utilisateurs where login = '".$proprietaire."'");
        $bas_responsable = @mysql_result($calldata, $i, "responsable");
        if ($bas_responsable == '')
            $bas_responsable = "<font color=\"#FF0000\">*** A DEFINIR ***</font>";
        else {
            $nom_prof = sql_query1("select nom from utilisateurs where login='".$bas_responsable."'");
            $prenom_prof = sql_query1("select prenom from utilisateurs where login='".$bas_responsable."'");
            $email_prof = sql_query1("select email from utilisateurs where login='".$bas_responsable."'");
            if (($nom_prof != -1) and ($prenom_prof != -1)) $bas_responsable = $nom_prof." ".$prenom_prof;
            if (($email_prof != '-1') and (calcul_autorisation_gestion_ateliers($_SESSION['login'],"mod_plugins/gestion_ateliers/droit_special_index.txt")))
                $bas_responsable = "<a href='mailto:".$email_prof."'>".$bas_responsable."</a>";
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

        if ($close_bas == 'y') {
            // Calcul du nombre de choix 1
            $nb_inscrit_1 = sql_count(sql_query("select id_eleve from bas_j_eleves_bas where
            num_bas = '".$numero_bas."' and
            id_bas = '".$id_bas."' and
            num_choix='1' and
            num_sequence='".$bas_debut_final."'
            "));
            if (($nb_inscrit_1 > $bas_nb_max) and ($bas_nb_max!=0))
                if (($nb_inscrit_1 <= $bas_nb_max*5/4) and ($bas_nb_max!=0))
                    $nb_inscrit_1 = "<td bgcolor=\"#FF9D9D\"><b>".$nb_inscrit_1."</b></td>";
                else
                    $nb_inscrit_1 = "<td bgcolor=\"#FF0000\"><b>".$nb_inscrit_1."</b></td>";
            else if (($nb_inscrit_1 > 35) and ($bas_nb_max==0))
                    $nb_inscrit_1 = "<td bgcolor=\"#FF0000\"><b>".$nb_inscrit_1."</b></td>";
            else
                $nb_inscrit_1 = "<td>".$nb_inscrit_1."</td>";
            // Calcul du nombre d'inscrits
            $nb_inscrit_0 = sql_count(sql_query("select id_eleve from bas_j_eleves_bas where
            num_bas = '".$numero_bas."' and
            id_bas = '".$id_bas."' and
            num_choix='0' and
            num_sequence='".$bas_debut_final."'
            "));
            $nb_inscrit_0 = "<td><b>".$nb_inscrit_0."</b></td>";
        }
        if ($bas_statut == 'a') {
            $bas_salle = "<font color='red'><b>Annulé</b></font>";
            echo "<tr bgcolor=\"#C0C0C0\">";
        } else
            echo "<tr>";

        echo "<td><span class='small'>$bas_id_prop<br /><i>$nom_matiere_prop</i>";
        if ($aff_affectations_eleves == 'y') {
            echo "<br />[<a href='index_inscrits.php?numero_bas=$numero_bas&amp;id_bas=$id_bas' title='Afficher la liste des élèves inscrits' target='_blank'><b>Liste&nbsp;élèves</b></a>";
            if ($inscription_bas != "n") echo "<br /><font color='red'>non définitive</font>";
            echo "]\n";
        } else if ($aff_affectations_eleves != 'y') {
            echo "<br />[<a href='index_inscrits.php?numero_bas=$numero_bas&amp;id_bas=$id_bas' title='Afficher la liste des élèves ayant choisi cette activité' target='_blank'><b>Liste&nbsp;élèves</b></a>";
            if ($inscription_bas != "n") echo "<br /><font color='red'>non définitive</font>";
            echo "]\n";
        }
        if (($_SESSION['statut'] == "cpe") or (calcul_autorisation_gestion_ateliers($_SESSION['login'],"mod_plugins/gestion_ateliers/droit_special_index.txt"))) {
            if (($inscription_bas != "n")  and ($qui_inscrit == 's')) {
                echo "<br />[<a href='inscrire_eleves_par_atelier.php?numero_bas=$numero_bas&amp;id_bas=$id_bas' title='Inscrire des élèves'><b>Inscrire&nbsp;les&nbsp;élèves</b></a>";
                echo "]\n";
            }
        }
        echo "</span></td>\n";
        echo "<td>$bas_type</td>\n";
        if ((($close_bas == 'n') and ($bas_matiere == $_GET['id_matiere']) or (calcul_autorisation_gestion_ateliers($_SESSION['login'],"mod_plugins/gestion_ateliers/droit_special_index.txt"))))
            echo "<td><a href='modify_bas.php?id_bas=$id_bas&amp;id_matiere=".$_GET['id_matiere']."&amp;numero_bas=$numero_bas' title='Proposition effectuée par ".$bas_proprietaire."'><b>$bas_titre</b></a></td>\n";
        else
            echo "<td><b>$bas_titre</b></td>\n";
        echo "<td>$bas_precisions</td>\n";
        if ($close_bas == 'y') {
            if ($bas_statut == 'a')
                echo "<td><font color='red'><b>Annulé</b></font></td>";
            else if (isset($per[$bas_debut_final]))
                echo "<td>".$per[$bas_debut_final]."</td>\n";
            else
                echo "<td>-</td>\n";
        }


        if (calcul_autorisation_gestion_ateliers($_SESSION['login'],"mod_plugins/gestion_ateliers/droit_special_index.txt")) {
            if ($debut_sequence != 0) {
                if ($commentaire != '') $commentaire .= "<br />";
                $commentaire .= "A <b>".$per[$debut_sequence]."</b>";
            }
            if ($commentaire == '') $commentaire = "&nbsp;";
            echo "<td>".$commentaire;
            echo "</td>\n";
        }
        echo "<td>$public</td>\n";
        if ($bas_coresponsable != '') $responsables = $bas_responsable."<br />".$bas_coresponsable; else $responsables = $bas_responsable;
        echo "<td>$responsables</td>\n";
        echo "<td>$bas_nb_max_titre</td>\n";
        if ($close_bas == 'y') {
            if ($aff_affectations_eleves == 'y')
                // Affichage du nombre d'inscrits
                echo $nb_inscrit_0;
            else
                // Affichage du nombre de choix 1
                echo $nb_inscrit_1;
//            echo "<td><span class='small'>$nb_inscrit_2</span></td>\n";
//            echo "<td><span class='small'>$nb_inscrit_3</span></td>\n";
        }

        if (count($per) != 1)
            echo "<td>".$bas_duree." seq.</td>\n";
        if ($close_bas != 'y')
            echo "<td>".$per[$debut_sequence]."</td>";

        if (calcul_autorisation_gestion_ateliers($_SESSION['login'],"mod_plugins/gestion_ateliers/droit_special_index.txt")) {
            $materiel = sql_query1("select materiel from bas_salles where id_salle='".$bas_salle."'");
            if ($materiel != "-1") $bas_salle .= " (".$materiel.")";
        }

        echo "<td>$bas_salle</td>\n";
        if ((($_GET['id_matiere'] != '') or (calcul_autorisation_gestion_ateliers($_SESSION['login'],"mod_plugins/gestion_ateliers/droit_special_index.txt"))) and ($close_bas == 'n'))
            if  (($bas_matiere == $_GET['id_matiere']) or (calcul_autorisation_gestion_ateliers($_SESSION['login'],"mod_plugins/gestion_ateliers/droit_special_index.txt")))
                echo "<td><a href='index.php?id_bas=$id_bas&amp;action=del_bas&amp;id_matiere=".$_GET['id_matiere']."&amp;numero_bas=$numero_bas&amp;visu=$visu' onclick=\"return confirmlink(this,  '$themessage', 'Confirmation de suppression')\">
                <img src=\"./images/delete_s.png\" alt=\"supprimer\" border=\"0\" title=\"Supprimer la proposition\" />
                </a></td></tr>";
            else
                echo "<td>-</td></tr>\n";
        $i++;
    }
    echo "</table>";

}
?>
</body>
</html>