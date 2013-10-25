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
$nom_script = "mod_plugins/gestion_ateliers/index_inscrits.php";
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
$id_bas = isset($_POST['id_bas']) ? $_POST['id_bas'] : (isset($_GET['id_bas']) ? $_GET['id_bas'] : NULL);

//**************** EN-TETE *****************
require_once("../../lib/header.inc");
//**************** FIN EN-TETE *************

// Si le numéro n'est pas défini, on arrete tout
if (!(isset($numero_bas))) die();

// Gestion des élèves par BAS
if (isset($numero_bas) and (isset($id_bas))) {
    // Informations sur la proposition BAS
    $call_bas_info = mysql_query("SELECT * FROM bas_propositions WHERE id_bas='$id_bas'");
    $titre = mysql_result($call_bas_info, "0", "titre");
    $id_prop = mysql_result($call_bas_info, "0", "id_prop");

    $responsable = mysql_result($call_bas_info, "0", "responsable");
    $civilite = sql_query1("select civilite from utilisateurs where login = '".$responsable."'");
    $nom_prof = sql_query1("select nom from utilisateurs where login = '".$responsable."'");

    $matiere = mysql_result($call_bas_info, "0", "id_matiere");
    $nom_matiere = sql_query1("select nom_complet from matieres where matiere = '".$matiere."'");

    $salle = mysql_result($call_bas_info, "0", "salle");
    $duree = mysql_result($call_bas_info, "0", "duree");
    $debut_final = mysql_result($call_bas_info, "0", "debut_final");


    // données sur le bas
    $description_bas = sql_query1("select description_bas from bas_bas where id_bas='".$numero_bas."'");
    $nom_bas = sql_query1("select nom from bas_bas where id_bas='".$numero_bas."'");
    $aff_affectations_eleves = sql_query1("select aff_affectations_eleves from bas_bas where id_bas='".$numero_bas."'");
    $inscription_bas = sql_query1("select inscription_bas from bas_bas where id_bas='".$numero_bas."'");
    $num_periode = sql_query1("select num_periode from bas_bas where id_bas='".$numero_bas."'");
    // Constitution du tableau $per
    $per =  tableau_periode($numero_bas);

    if (!isset($per[$debut_final])) $per[$debut_final] = "<font color='green'>Heure à définir</font>";
    if ($salle == '') $salle = "<font color='green'> à définir</font>";


        // Affichage de la liste des élèves ayant choisi cette activité (choix N° 1)
    if ($aff_affectations_eleves != "y") {
        $eleves = mysql_query("select distinct id_eleve from bas_j_eleves_bas
        where
        num_bas = '".$numero_bas."' and
        id_bas = '".$id_bas."' and
        (num_choix = '1' or num_choix = '0')
        order by id_eleve
        ");

    // Affichage de la liste des élèves affectés
    } else {
        $eleves = mysql_query("select distinct id_eleve from bas_j_eleves_bas
        where
        num_bas = '".$numero_bas."' and
        id_bas = '".$id_bas."' and
        num_choix = '0'
        order by id_eleve
        ");
    }
    $nb_eleves = mysql_num_rows($eleves);


    echo "<p class='grand'>".$nom_bas." - ".$id_prop." - ".$per[$debut_final];
    if (count($per) > 1) echo " - Durée : ".$duree." h";
    echo " - Salle : ".$salle;
    echo "<br />".$titre;
    if ($nom_prof == -1)
        if ($responsable == '')
            echo "<br /><font color='red'>*** Animateur A DEFINIR ***</font>";
        else
            echo "<br />Animateur : ".$responsable;
    else
        echo "<br />Animateur : ".$civilite." ".$nom_prof." (".$nom_matiere.")";
    echo " -  ".$nb_eleves." élèves inscrits</p>";

    // Affichage de la liste des élèves ayant choisi cette activité (choix N° 1)
    if ($aff_affectations_eleves != "y") {
        echo "<center><p class='grand'><font color='red'>Liste des élèves ayant choisi l'activité (choix N° 1) ou temporairement affectés.</font></p></center>";
        echo "<center><p class='grand'><font color='red'>La liste des élèves définitivement affectés n'est pas encore disponible.</font></p></center>";

    // Affichage de la liste des élèves affectés
    } else {
        if ($inscription_bas != "n") echo "<center><p class='grand'><font color='red'>*** Liste temporaire des élèves affectés. ***</font></p></center>";
    }

    $i = 0;
    echo "<table border=\"1\" width=\"80%\" cellpadding=\"2\"><tr>";
    echo "<td width=\"30%\"><span class='style_bas'><b>Nom prénom</b></span></td>\n";
    echo "<td width=\"20%\"><span class='style_bas'><b>Classe</b></span></td>\n";
    if ($aff_affectations_eleves == "y")
        echo "<td width=\"50%\"><span class='style_bas'><b>Commentaire (présent/absent/retard, ...)</b></span></td>\n";
    else {
        echo "<td width=\"50%\"><span class='style_bas'><b>Choix N° 2</b></span></td>\n";
        echo "<td width=\"50%\"><span class='style_bas'><b>Professeur de suivi</b></span></td>\n";
    }
    echo "</tr>";
    while ($i < $nb_eleves) {
        $login_eleve = mysql_result($eleves,$i,'id_eleve');
        // Nom prénom, classe de l'élève
        $nom_eleve = sql_query1("select nom from eleves where login = '".$login_eleve."'");
        $prenom_eleve = sql_query1("select prenom from eleves where login = '".$login_eleve."'");
        $classe = mysql_query("select id, classe from classes c, j_eleves_classes j
        where j.login = '".$login_eleve."' and
        j.id_classe = c.id and
        j.periode = '".$num_periode."'
        ");
        $classe_eleve = mysql_result($classe,0,'classe');
        $id_classe = mysql_result($classe,0,'id');
        echo "<tr><td><span class='style_bas'>".$nom_eleve." ".$prenom_eleve."</span></td>
        <td><span class='style_bas'>".$classe_eleve."</span></td>";
        if ($aff_affectations_eleves == "y")
            echo "<td><span class='style_bas'>&nbsp;</span></td>";
        else {
            $bas2 = sql_query1("select id_bas from bas_j_eleves_bas
            where id_eleve = '".$login_eleve."' and
            num_bas = '".$numero_bas."' and
            num_choix = '2' and
            num_sequence = '".$debut_final."'
            ");

            $id_prop2 = sql_query1("select id_prop from bas_propositions where id_bas='".$bas2."'");
            if ($id_prop2 != "-1") {
                echo "<td><span class='style_bas'>Choix N° 2 : ".$id_prop2."</span></td>\n";
            } else {
                echo "<td><span class='style_bas'>Pas de choix N° 2</span></td>\n";
            }
            // Professeur de suivi :
            $login_prof_suivi = sql_query1("select professeur from j_eleves_professeurs where login='".$login_eleve."' and id_classe='".$id_classe."'");
            if ($login_prof_suivi == -1)
                echo "<td><span class='style_bas'>-</span></td>\n";
            else {
                $nom_prof = sql_query1("select nom from utilisateurs where login='".$login_prof_suivi."'");
                $prenom_prof = sql_query1("select prenom from utilisateurs where login='".$login_prof_suivi."'");
                $email_prof = sql_query1("select email from utilisateurs where login='".$login_prof_suivi."'");
                if ($email_prof == -1)
                    echo "<td><span class='style_bas'>".$prenom_prof."&nbsp;".$nom_prof."</span></td>\n";
                else
                    echo "<td><span class='style_bas'><a href='mailto:".$email_prof."'>".$prenom_prof."&nbsp;".$nom_prof."</a></span></td>\n";
            }

        }
        echo "</tr>";
        $i++;
    }
    echo "</table>";
    if ($aff_affectations_eleves == "y") {
        echo "<p>Prière de rapporter ce document signé, au service Vie Scolaire dès que possible.</p>";
        echo "<center><p>".$civilite." ".$nom_prof." (Signature)</p></center>";
    }
}

?>
</body>
</html>