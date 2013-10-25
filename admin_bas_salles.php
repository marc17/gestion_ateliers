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
$nom_script = "mod_plugins/gestion_ateliers/admin_bas_salles.php";
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
if (!isset($_SESSION['order_by'])) {$_SESSION['order_by'] = "id_prop";}
$_SESSION['order_by'] = isset($_POST['order_by']) ? $_POST['order_by'] : (isset($_GET['order_by']) ? $_GET['order_by'] : $_SESSION['order_by']);
$order_by = $_SESSION['order_by'];
if (!isset($_SESSION['action_ges'])) {$_SESSION['action_ges'] = "";}
$_SESSION['action_ges'] = isset($_POST['action_ges']) ? $_POST['action_ges'] : (isset($_GET['action_ges']) ? $_GET['action_ges'] : $_SESSION['action_ges']);

if (isset($_POST['is_posted2']))  {
    $calldata = mysql_query("SELECT salle, debut_final, duree, id_bas FROM bas_propositions WHERE (num_bas= '".$numero_bas."')");
    $nombreligne = mysql_num_rows($calldata);
    $i = 0;
    while ($i < $nombreligne) {
        $ident_bas = mysql_result($calldata,$i,'id_bas');
        $temp = "reg_salle_".$ident_bas;
        $req = mysql_query("update bas_propositions set salle = '".$_POST[$temp]."' where id_bas='".$ident_bas."'");
        $i++;
    }

}



// Test de compatibilité salle<->Créneaux
if (isset($_GET['is_posted'])  and ($_SESSION['action_ges']=='verif_salle')) {
    // Test de cohérence d'occupation des salles
    function test_occupation_salle($salle,$debut_final,$duree,$numero_bas,$id_bas) {
    if (($debut_final >= 1) and ($salle != '')) {
        $test = mysql_num_rows(mysql_query("select salle from bas_propositions where
        (salle = '".$salle."' and debut_final = '".$debut_final."' and num_bas='".$numero_bas."' and id_bas!='".$id_bas."' and statut='v')"));
        if ($test >= 1) {
            return 1;
            die();
        }
        $test2 = mysql_query("select duree, debut_final from bas_propositions where
        (salle = '".$salle."' and num_bas='".$numero_bas."' and debut_final != '0' and id_bas!='".$id_bas."' and statut='v')");
        $nb_test = mysql_num_rows($test2);
        $i = 0;
        while ($i < $nb_test) {
            $duree_2 = mysql_result($test2,$i,'duree');
            $debut_final_2 = mysql_result($test2,$i,'debut_final');
            $result = 0;
            if (($duree_2 >= 2) and ($debut_final == $debut_final_2+1)) $result = 1;
            if (($duree_2 == 3) and ($debut_final == $debut_final_2+2)) $result = 1;
            if (($duree >= 2) and ($debut_final == $debut_final_2-1)) $result = 1;
            if (($duree == 3) and ($debut_final == $debut_final_2-2)) $result = 1;
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

    $calldata = mysql_query("SELECT salle, debut_final, duree, id_bas FROM bas_propositions WHERE (num_bas= '".$_GET['numero_bas']."' and statut='v')");
    $nombreligne = mysql_num_rows($calldata);

    $i = 0;
    while ($i < $nombreligne) {
        $salle = mysql_result($calldata,$i,'salle');
        $debut_final = mysql_result($calldata,$i,'debut_final');
        $duree = mysql_result($calldata,$i,'duree');
        $ident_bas = mysql_result($calldata,$i,'id_bas');

        if (test_occupation_salle($salle,$debut_final,$duree,$numero_bas,$ident_bas) == 0) {
            $result_test_salle[$ident_bas] = '';
        } else {
            $result_test_salle[$ident_bas] = "bgcolor=\"#FF0000\"";
            $msg = "Attention : Au moins une erreur subsiste sur la page.";
        }
        $i++;
    }
}


//**************** EN-TETE *****************
$titre_page = "Gestion des ".$NomAtelier_pluriel;
require_once("../../lib/header.inc");
//**************** FIN EN-TETE *************

// choix du bas
if (!(isset($numero_bas))) die();


// La matière est définie ainsi que le numéro de bas
if (isset($numero_bas)) {

    echo "<p class=bold>| <a href=\"../../accueil.php\">Retour à la page d'accueil</a> |<a href=\"admin_index.php\"> Menu de gestion des ".$NomAtelier_pluriel."</a> |";

    // données sur le bas
    $date_bas = sql_query1("select date_bas from bas_bas where id_bas='".$numero_bas."'");
    $nom_bas = sql_query1("select nom from bas_bas where id_bas='".$numero_bas."'");
    $close_bas = sql_query1("select close_bas from bas_bas where id_bas='".$numero_bas."'");
    $date_limite = sql_query1("select date_limite from bas_bas where id_bas='".$numero_bas."'");
    if ($close_bas == "y") $close_bas_mess = "<font color='red'>(inscriptions impossibles)</font>"; else $close_bas_mess = " - <font color='red'>A remplir jusqu'au ".$date_limite." (inclus)</font>";
    $description_bas = sql_query1("select description_bas from bas_bas where id_bas='".$numero_bas."'");
    // Constitution du tableau $per
    $per[0] = "Non&nbsp;défini";
    $per =  tableau_periode($numero_bas);



    echo "<p class='grand'>".$nom_bas." du ".$date_bas." - ".$description_bas." ".$close_bas_mess."</p>";

    // On va chercher les activités déjà existantes, et on les affiche.

    $calldata = mysql_query("SELECT * FROM bas_propositions
    WHERE (num_bas= '".$numero_bas."') ORDER BY $order_by");
    $nombreligne = mysql_num_rows($calldata);
    if ($nombreligne == 0) {
        echo "<p><b>Actuellement, aucune proposition n'a été enregistrée.</b></p>";
        echo "</body></html>";
        die();
    } else {
        echo "<p><b>Actuellement, ".$nombreligne." propositions ont été enregistrées.</b></p>";
        echo "<p>";
        echo " - <a href='admin_bas_salles.php?numero_bas=$numero_bas&amp;action_ges=verif_salle&amp;is_posted=yes'><b>Vérification compatibilité Salle<->Créneaux</b></a>";
        echo "</p>";
    }

    echo "<form action=\"admin_bas_salles.php\" name=\"salles\" method=\"post\">\n";
    echo "<table width = 100% cellpadding=1 border=1>";
    echo "<tr>";
    echo "<td><span class='small'><a href='admin_bas_salles.php?order_by=id_prop&amp;numero_bas=$numero_bas'>N°<br /><i>Matière</i></a></span></td>\n";
    echo "<td><span class='small'><a href='admin_bas_salles.php?order_by=titre,type,responsable&amp;numero_bas=$numero_bas'>Intitulé de l'activité</a></span></td>\n";
        echo "<td><span class='small'><a href='admin_bas_salles.php?order_by=debut_final,titre,responsable&amp;numero_bas=$numero_bas'>Horaire</a></span></td>\n";
    echo "<td><span class='small'><a href='admin_bas_salles.php?order_by=responsable,type&amp;numero_bas=$numero_bas'>Animateur</a></span></td>
    <td><span class='small'>Nb. élèves</span></td>
    <td><span class='small'><a href='admin_bas_salles.php?order_by=duree,responsable&amp;numero_bas=$numero_bas'>Durée</a></span></td>
    <td><span class='small'><a href='admin_bas_salles.php?order_by=salle,id_prop&amp;numero_bas=$numero_bas'>Salle</a></span></td>";

    echo "</tr>";
    $req_salles = mysql_query("select * from bas_salles order by 'nom_salle'");
    $nb_salles = mysql_num_rows($req_salles);
    $m = 0;
    while ($m < $nb_salles) {
        $temp_salle = mysql_result($req_salles,$m,'id_salle');
        $call_total_duree = mysql_query("SELECT sum(duree) total FROM bas_propositions
        WHERE (
        num_bas='".$numero_bas."' and
        salle = '".$temp_salle."'
        )");
        $total_duree =  mysql_result($call_total_duree, 0, "total");
        if ($total_duree == "") $total_duree = 0;
        if ($total_duree < 3)  {
            $statut_salle[$m] = "libre";
            $duree_occupation[$m][1] = sql_query1("SELECT duree FROM bas_propositions
            WHERE (
            num_bas='".$numero_bas."' and
            salle = '".$temp_salle."' and
            debut_final = '1'
            )");
            $duree_occupation[$m][2] = sql_query1("SELECT duree FROM bas_propositions
            WHERE (
            num_bas='".$numero_bas."' and
            salle = '".$temp_salle."' and
            debut_final= '2'
            )");
            $duree_occupation[$m][3] = sql_query1("SELECT duree FROM bas_propositions
            WHERE (
            num_bas='".$numero_bas."' and
            salle = '".$temp_salle."' and
            debut_final= '3'
            )");
        } else {
            $statut_salle[$m] = "complet";
            $duree_occupation[$m][1] = "";
            $duree_occupation[$m][2] = "";
            $duree_occupation[$m][3] = "";

        }
        $duree_occupation[$m][0] = "";
        $duree_occupation[$m][-1] = "";
        $duree_occupation[$m][-2] = "";

        $id_salle[$m] = $temp_salle;
        $nom_salle[$m] = mysql_result($req_salles,$m,'nom_salle');
        $nb_places[$m] = mysql_result($req_salles,$m,'nb_places');
        $materiel[$m] = mysql_result($req_salles,$m,'materiel');
        $m++;
    }



    $i = 0;
    while ($i < $nombreligne){
        $bas_statut = @mysql_result($calldata, $i, "statut");
        $bas_id_prop = @mysql_result($calldata, $i, "id_prop");
        $bas_titre = @mysql_result($calldata, $i, "titre");
        $bas_matiere = mysql_result($calldata, $i, "id_matiere");
        $nom_matiere_prop = sql_query1("select nom_complet from bas_matieres where matiere = '".$bas_matiere."'");
        $proprietaire = @mysql_result($calldata, $i, "proprietaire");
        $debut_sequence = @mysql_result($calldata, $i, "debut_sequence");
        $debut_final = @mysql_result($calldata, $i, "debut_final");
        $bas_proprietaire = sql_query1("select prenom from utilisateurs where login = '".$proprietaire."'")." ".sql_query1("select nom from utilisateurs where login = '".$proprietaire."'");
        $bas_responsable = @mysql_result($calldata, $i, "responsable");
        $req_bas = mysql_query("select debut_final from bas_propositions where (responsable = '".traitement_magic_quotes($bas_responsable)."' and num_bas = '".$numero_bas."') order by 'debut_final'");
        $nb_prop = mysql_num_rows($req_bas);
        $n = 0;
        $texte = '(';
        while ($n < $nb_prop) {
            $prop = mysql_result($req_bas,$n,'debut_final');
            $texte .= $prop;
            if ($n == $nb_prop-1) $texte .=")"; else $texte .= " - ";
            $n++;
        }

        $nom_prof = sql_query1("select nom from utilisateurs where login='".traitement_magic_quotes($bas_responsable)."'");
        $prenom_prof = sql_query1("select prenom from utilisateurs where login='".traitement_magic_quotes($bas_responsable)."'");
        if (($nom_prof != -1) and ($prenom_prof != -1)) $bas_responsable = $nom_prof." ".$prenom_prof;

        $bas_responsable .= "<br />".$texte;

        $bas_duree = @mysql_result($calldata, $i, "duree");
        $bas_salle = @mysql_result($calldata, $i, "salle");
        if ($bas_salle=='') $bas_salle= '-';
        $id_bas = @mysql_result($calldata, $i, "id_bas");
        $nb_inscrit_0 = sql_count(sql_query("select id_eleve from bas_j_eleves_bas where
        num_bas = '".$numero_bas."' and  id_bas = '".$id_bas."' and num_choix='0' and num_sequence='".$debut_final."'"));

        if ($bas_statut == 'a') {
            echo "<tr bgcolor=\"#C0C0C0\">";
            $bas_salle = "<font color='red'><b>Annulé</b></font>";
        } else if ((isset($bas_salle)) and ($bas_salle != '-'))
            echo "<tr bgcolor=\"#D5FF57\">";
        else
            echo "<tr>";
        echo "<td><span class='small'>$bas_id_prop<br /><i>$nom_matiere_prop</i></span></td>\n";
        echo "<td><span class='small'><a href='modify_bas.php?action=modif_bas&amp;id_bas=$id_bas&amp;id_matiere=&amp;numero_bas=$numero_bas&amp;retour=admin_bas_salles' title='Proposition effectuée par ".$bas_proprietaire."'><b>$bas_titre</b></a></span></td>\n";
        if ($bas_statut == 'a')
            echo "<td><font color='red'><b>Annulé</b></font></td>\n";
        else
            echo "<td><span class='small'>".$per[$debut_final]."</span></td>\n";
        echo "<td><span class='small'>$bas_responsable</span></td>\n";
        echo "<td><span class='small'>$nb_inscrit_0</span></td>\n";
        echo "<td><span class='small'>$bas_duree h</span></td>\n";
        echo "<td ";
        if (isset($result_test_salle[$id_bas])) echo $result_test_salle[$id_bas];
        echo "><span class='small'>";
        echo "<select name=\"reg_salle_".$id_bas."\" size=\"1\">\n";
        echo "<option value=\"\">(sans objet)</option>\n";
        $k = 0;     
        while ($k < $nb_salles) {
            if ((isset($bas_salle)) and ($bas_salle==$id_salle[$k])) {
                echo "<option value=\"".$id_salle[$k]."\" selected >".$nom_salle[$k]." (".$nb_places[$k].")";
                if ($materiel[$k] != "-1") echo " ".$materiel[$k];
                if ($statut_salle[$k] != "libre") echo " * COMPLET *";
                echo "</option>\n";
            } else if ($statut_salle[$k] == "libre") {
                if (($duree_occupation[$k][$debut_final] == "-1") and
                (($duree_occupation[$k][$debut_final-1] != "2")) and
                (($duree_occupation[$k][$debut_final-2] != "3"))
                ) {


//                if (isset($duree_occupation[$k][$debut_final]) and ($duree_occupation[$k][$debut_final] == "-1") and
//                (isset($duree_occupation[$k][$debut_final-1]) and ($duree_occupation[$k][$debut_final-1] != "2")) and
//                (isset($duree_occupation[$k][$debut_final-2]) and ($duree_occupation[$k][$debut_final-2] != "3"))
//                ) {
                   echo "<option value=\"".$id_salle[$k]."\">".$nom_salle[$k]." (".$nb_places[$k].")";
                   if ($materiel[$k] != "-1") echo " ".$materiel[$k];
                   echo "</option>\n";
                }
            }
            $k++;
        }
        echo "</select>";
        echo "</span></td>\n";
        echo "</tr>\n";
        $i++;
    }
    echo "</table>";
    echo "<input type=\"hidden\" name=\"numero_bas\" value=\"$numero_bas\" />";
    echo "<input type=\"hidden\" name=\"is_posted2\" value=\"yes\" />";
    echo "<div id=\"fixe\">";
    echo "<input type=\"submit\" name=\"ok\" value=\"Envoyer\" /></div>";
    echo "</form>";

}
?>
</body>
</html>