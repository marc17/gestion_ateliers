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
$nom_script = "mod_plugins/gestion_ateliers/admin_bas.php";
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

// Enregistrement des horaires
if (isset($_GET['is_posted'])  and ($_SESSION['action_ges']=='horaire')) {
    // Test de cohérence prof-horaire
    function test_prof_horaire($login_prof,$debut_final,$duree,$numero_bas,$id_bas) {
    if ($debut_final >= 1) {
        // On teste si l'animateur est déjà prix au meme horaire
        $test = mysql_num_rows(mysql_query("select salle from bas_propositions where
        (debut_final = '".$debut_final."' and (responsable = '". traitement_magic_quotes($login_prof)."' or coresponsable = '". traitement_magic_quotes($login_prof)."') and num_bas='".$numero_bas."' and id_bas!='".$id_bas."' and statut='v')"));
        if ($test >= 1) {
            return 1;
            die();
        }
        // On teste si l'animateur est déjà prix sur un horaire différent mais à cheval sur cet horaire
        $test2 = mysql_query("select duree, debut_final from bas_propositions where
        ((responsable = '".traitement_magic_quotes($login_prof)."' or coresponsable = '".traitement_magic_quotes($login_prof)."') and num_bas='".$numero_bas."' and debut_final != '0' and id_bas!='".$id_bas."' and statut='v')");
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

    $i = 0;
    while ($i < $_GET['nombreligne']) {
    /*
        $nb_max = sql_query1("select nb_max from bas_propositions where id_bas='".$ident_bas."'");
        if ($nb_max != 0) {
            $temp = $nb_max*25/100;
            $temp = max(5,$temp);
            $nb_bloque = $nb_max + $temp;
        } else $nb_bloque=40;
        $req = mysql_query("update bas_propositions set nb_bloque='".$nb_bloque."' where id_bas='".$ident_bas."'");
      */
        $temp = "id_bas_".$i;
        $ident_bas = $_GET[$temp];
        $temp = "reg_debut_sequence_".$i;
        $debut_final = $_GET[$temp];
        $responsable = sql_query1("select responsable from bas_propositions where id_bas='".$ident_bas."'");
        $bas_statut = sql_query1("select statut from bas_propositions where id_bas='".$ident_bas."'");
        $duree = sql_query1("select duree from bas_propositions where id_bas='".$ident_bas."'");
        if ((test_prof_horaire($responsable,$debut_final,$duree,$numero_bas,$ident_bas) == 0) or ($bas_statut=='a')) {
            $req = mysql_query("update bas_propositions set debut_final='".$debut_final."' where id_bas='".$ident_bas."'");
            $result_test[$ident_bas] = '';
        } else {
            $result_test[$ident_bas] = "<br /><b><font color=\"#FF0000\">Erreur (".$debut_final.")</b></font>";
            $msg = "Attention : Au moins une erreur subsiste sur la page.";
        }
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
$titre_page = "Gestion des Ateliers";
require_once("../../lib/header.inc");
//**************** FIN EN-TETE *************

// choix de la séance
if (!(isset($numero_bas))) die();


// La matière est définie ainsi que le numéro de la séance
if (isset($numero_bas)) {

    echo "<p class=bold>| <a href=\"../../accueil.php\">Retour à la page d'accueil</a> |<a href=\"./admin_index.php\"> Choisir une autre séance</a> |";

    // données sur le bas
    $date_bas = sql_query1("select date_bas from bas_bas where id_bas='".$numero_bas."'");
    $close_bas = sql_query1("select close_bas from bas_bas where id_bas='".$numero_bas."'");
    $date_limite = sql_query1("select date_limite from bas_bas where id_bas='".$numero_bas."'");
    if ($close_bas == "y") $close_bas_mess = "<font color='red'>(inscriptions impossibles)</font>"; else $close_bas_mess = " - <font color='red'>A remplir jusqu'au ".$date_limite." (inclus)</font>";
    $description_bas = sql_query1("select description_bas from bas_bas where id_bas='".$numero_bas."'");
    // Constitution du tableau $per
    $per =  tableau_periode($numero_bas);


    echo "<p class='grand'>".ucfirst($NomAtelier_singulier)." N° ".$numero_bas." du ".$date_bas." - ".$description_bas." ".$close_bas_mess."</p>";

    echo "<p><b>";
    echo "|<a href=\"modify_bas.php?action=add_bas&amp;mode=unique&amp;id_matiere=&amp;numero_bas=$numero_bas&amp;retour=admin_bas\">Ajouter une proposition ".$NomAtelier_preposition.$NomAtelier_singulier."</a>";
    echo "|</b></p>";

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
        echo "<p><a href='admin_bas.php?numero_bas=$numero_bas&amp;action_ges=horaire'><b>Répartition des activités sur les créneaux horaires</b></a>";
        echo " - <a href=\"javascript:centrerpopup('stats_bas.php?numero_bas=$numero_bas',600,480,'scrollbars=yes,statusbar=no,resizable=yes')\">Stat.</a>";
        echo " - <a href='admin_bas.php?numero_bas=$numero_bas&amp;action_ges=verif_salle&amp;is_posted=yes'><b>Vérification compatibilité Salle<->Créneaux</b></a>";
        echo "</p>";
    }
    // Attribution des horaires
    if (isset($_SESSION['action_ges']) and ($_SESSION['action_ges']=='horaire')) {
        echo "<form action=\"admin_bas.php\" name=\"horaire\" method=\"get\">\n";
        echo "<input type=\"hidden\" name=\"numero_bas\" value=\"$numero_bas\" />";
        echo "<input type=\"hidden\" name=\"is_posted\" value=\"yes\" />";
        echo "<input type=\"hidden\" name=\"nombreligne\" value=\"$nombreligne\" />";

    }
    $k=1;
    $ordre_public="";
    while ($k<NB_FILIERES+1) {
      $ordre_public .="public_".$k;
      if ($k<NB_FILIERES)
      $ordre_public .=",";
      $k++;
    }


    echo "<table width = 100% cellpadding=1 border=1>";
    echo "<tr>";
    echo "<td><span class='small'><a href='admin_bas.php?order_by=id_prop&amp;numero_bas=$numero_bas'>N°<br /><i>Matière</i></a></span></td>\n";
    echo "<td><span class='small'><a href='admin_bas.php?order_by=titre,".$ordre_public.",type,responsable&amp;numero_bas=$numero_bas'>Intitulé de l'activité</a></span></td>\n";
    echo "<td><span class='small'><a href='admin_bas.php?order_by=type,".$ordre_public.",titre,responsable&amp;numero_bas=$numero_bas'>Type</a></span></td>\n";
    echo "<td><span class='small'><a href='admin_bas.php?order_by=debut_final,".$ordre_public.",titre,responsable&amp;numero_bas=$numero_bas'>Horaire</a></span></td>\n";

    $k=1;
    while ($k<NB_FILIERES+1) {
      $temp="ordre_public".$k;
      $$temp = urlencode("public_".$k." DESC,id_prop,responsable");
      $nom_fil[$k]=sql_query1("select nom_filiere from bas_filieres where id_filiere='".$k."'");
      echo "<td><span class='small'><a href='admin_bas.php?order_by=".$$temp."&amp;numero_bas=$numero_bas'>".$nom_fil[$k]."</a></span></td>\n";
      $k++;
    }
    echo "<td><span class='small'><a href='admin_bas.php?order_by=responsable,".$ordre_public.",type&amp;numero_bas=$numero_bas'>Animateur</a></span></td>
    <td><span class='small'><a href='admin_bas.php?order_by=nb_max,".$ordre_public.",responsable&amp;numero_bas=$numero_bas'>Nb. max.<br />élèves</a></span></td>
    <td><span class='small'><a href='admin_bas.php?order_by=duree,".$ordre_public.",responsable&amp;numero_bas=$numero_bas'>Durée</a></span></td>
    <td><span class='small'><a href='admin_bas.php?order_by=salle,id_prop&amp;numero_bas=$numero_bas'>Salle</a></span></td>";

    echo "</tr>";
    $_SESSION['chemin_retour'] = $_SERVER['REQUEST_URI'];

    $i = 0;
    while ($i < $nombreligne){
        $bas_statut = mysql_result($calldata, $i, "statut");
        $bas_id_prop = @mysql_result($calldata, $i, "id_prop");
        $bas_titre = @mysql_result($calldata, $i, "titre");
        $bas_type = @mysql_result($calldata, $i, "type");
        if ($bas_type == "S") {
            $bas_type_im = "<img src=\"./images/s.gif\" alt=\"Soutien\" border=\"0\" title=\"Soutien\" />";
        } else if ($bas_type == "A") {
            $bas_type_im = "<img src=\"./images/a.gif\" alt=\"Approfondissement\" border=\"0\" title=\"Approfondissement\" />";
        } else if ($bas_type == "R") {
            $bas_type_im = "<img src=\"./images/r.gif\" alt=\"Remédiation\" border=\"0\" title=\"Remediation\" />";
        } else if ($bas_type == "D") {
            $bas_type_im = "<img src=\"./images/d.gif\" alt=\"Remédiation\" border=\"0\" title=\"Public désigné\" />";
        } else  {
            $bas_type_im = "-";
        }
        

        $k=1;
        while ($k<NB_FILIERES+1) {
          $temp = "public_".$k;
          $$temp = mysql_result($calldata, $i, "public_".$k);
          if ($$temp == "") $$temp = "&nbsp;" ; else $$temp=$nom_fil[$k];
          $k++;
        }
        
        $bas_matiere = mysql_result($calldata, $i, "id_matiere");
        $nom_matiere_prop = sql_query1("select nom_complet from bas_matieres where matiere = '".$bas_matiere."'");
        $proprietaire = @mysql_result($calldata, $i, "proprietaire");
        $debut_sequence = @mysql_result($calldata, $i, "debut_sequence");
        $reg_debut_final = @mysql_result($calldata, $i, "debut_final");
        $bas_proprietaire = sql_query1("select prenom from utilisateurs where login = '".$proprietaire."'")." ".sql_query1("select nom from utilisateurs where login = '".$proprietaire."'");
        $bas_responsable = @mysql_result($calldata, $i, "responsable");
        $bas_coresponsable = @mysql_result($calldata, $i, "coresponsable");
        $req_bas = mysql_query("select distinct debut_final from bas_propositions where
        ((responsable = '".traitement_magic_quotes($bas_responsable)."' or coresponsable = '".traitement_magic_quotes($bas_responsable)."') and num_bas = '".$numero_bas."') order by 'debut_final'");
        $nb_prop = mysql_num_rows($req_bas);
        $n = 0;
        $texte = '(';
        while ($n < $nb_prop) {
            $prop = mysql_result($req_bas,$n,'debut_final');
            $texte .= $prop;
            if ($n == $nb_prop-1) $texte .=")"; else $texte .= " - ";
            $n++;
        }
        $nom_prof = sql_query1("select nom from utilisateurs where login='".$bas_responsable."'");
        $prenom_prof = sql_query1("select prenom from utilisateurs where login='".$bas_responsable."'");
        if (($nom_prof != -1) and ($prenom_prof != -1))
            $bas_responsable = $nom_prof." ".$prenom_prof;
        else
            $bas_responsable = "<font color='red'>$bas_responsable</font>";

        $bas_responsable .= "<br />".$texte;

        if ($bas_coresponsable != '') {
            $req_bas = mysql_query("select distinct debut_final from bas_propositions where
            ((responsable = '".traitement_magic_quotes($bas_coresponsable)."' or coresponsable = '".traitement_magic_quotes($bas_coresponsable)."') and num_bas = '".$numero_bas."') order by 'debut_final'");
            $nb_prop = mysql_num_rows($req_bas);
            $n = 0;
            $texte_b = '(';
            while ($n < $nb_prop) {
                $prop = mysql_result($req_bas,$n,'debut_final');
                $texte_b .= $prop;
                if ($n == $nb_prop-1) $texte_b .=")"; else $texte_b .= " - ";
                $n++;
            }
            $nom_prof_b = sql_query1("select nom from utilisateurs where login='".$bas_coresponsable."'");
            $prenom_prof_b = sql_query1("select prenom from utilisateurs where login='".$bas_coresponsable."'");
            if (($nom_prof_b != -1) and ($prenom_prof_b != -1))
                $bas_coresponsable = $nom_prof_b." ".$prenom_prof_b;
            else
                $bas_coresponsable = "<font color='red'><b>$bas_coresponsable</b></font>";


            $bas_responsable .= "<br />".$bas_coresponsable."<br />".$texte_b;
        }


        $bas_duree = @mysql_result($calldata, $i, "duree");
        $bas_salle = @mysql_result($calldata, $i, "salle");
        if ($bas_salle=='') $bas_salle= '-';
        $bas_nb_max = @mysql_result($calldata, $i, "nb_max");
        $id_bas = @mysql_result($calldata, $i, "id_bas");
        // Attribution des horaires
        if (isset($_SESSION['action_ges']) and ($_SESSION['action_ges']=='horaire')) {
            if ((isset($reg_debut_final)) and ($reg_debut_final>=1)) {
              $fond_cellule = "bgcolor=\"#B0FFB0\"";
            } else  $fond_cellule = '';
        } else if ($bas_statut == 'a')
            $fond_cellule = "bgcolor=\"#C0C0C0\"";
        else
            $fond_cellule = "";
        // Fin portion de code attribution des horaires


        echo "<tr ".$fond_cellule.">";
        echo "<td><span class='small'>$bas_id_prop<br /><i>$nom_matiere_prop</i></span></td>\n";
        echo "<td><span class='small'><a href='modify_bas.php?action=modif_bas&amp;id_bas=$id_bas&amp;id_matiere=&amp;numero_bas=$numero_bas&amp;retour=admin_bas' title='Proposition effectuée par ".$bas_proprietaire."'><b>$bas_titre";
        if ($bas_type == 'R') echo " <font color='red'>(REMEDIATION)</font>";
        if ($bas_type == 'D') echo " <font color='green'>(PUBLIC DESIGNE)</font>";
        echo "</b></a></span></td>\n";
        echo "<td>$bas_type_im</td>\n";        

        $horaire = "";
        if ($bas_statut == 'a')
            $horaire = "<font color='red'><b>ANNULE</b></font><br />";


            if ($debut_sequence != 0) {
                $horaire .= "Souhait&nbsp;:&nbsp;<b>".$per[$debut_sequence]."</b>";
            }
            if ($horaire != '') $horaire .= "<br />";
            // Attribution des horaires
            if (isset($_SESSION['action_ges']) and ($_SESSION['action_ges']=='horaire')) {
                $horaire .= "<select name=\"reg_debut_sequence_$i\" size=\"1\">\n";
                $horaire .=  "<option value=\"0\">(choix)</option>\n";
                $k = 1;
                while ($k < count($per)+1) {
                    $horaire .=  "<option value=\"".$k."\" ";
                    if ((isset($reg_debut_final)) and ($reg_debut_final==$k)) { $horaire .=  "selected";}
                    $horaire .=  ">".$per[$k]."</option>\n";
                   $k++;
                }
                $horaire .=  "</select>\n";
                $horaire .=  "<input type=\"hidden\" name=\"id_bas_$i\" value=\"$id_bas\" />";
                if (isset($result_test[$id_bas])) $horaire .=  $result_test[$id_bas];
            } else {
                if (isset($per[$reg_debut_final])) {
                   if (($debut_sequence != 0) and ($debut_sequence != $reg_debut_final))
                        $horaire .=  "<font color='red'><b>&gt;&gt;".$per[$reg_debut_final]."&lt;&lt;&lt;</b></font>";
                   else
                       $horaire .=  "&gt;&gt;&gt;".$per[$reg_debut_final]."&lt;&lt;&lt;";
                 }
            }

             // Fin portion de code attribution des horaires

        echo "<td><span class='small'>".$horaire."</span></td>\n";
        $k=1;
        while ($k<NB_FILIERES+1) {
          $temp = "public_".$k;
          echo "<td><span class='small'>".$$temp."</span></td>\n";
          $k++;
        }
        echo "<td><span class='small'>$bas_responsable</span></td>\n";
        echo "<td><span class='small'>$bas_nb_max</span></td>\n";
        echo "<td><span class='small'>$bas_duree seq.</span></td>\n";
        echo "<td ";
        if (isset($result_test_salle[$id_bas])) echo $result_test_salle[$id_bas];
        echo "><span class='small'>".$bas_salle."</span></td>\n";
        echo "</tr>\n";
        $i++;
    }
    echo "</table>";
    // Attribution des horaires
    if (isset($_SESSION['action_ges']) and ($_SESSION['action_ges']=='horaire')) {
        echo "<center><div id=\"fixe\">";
        echo "<input type=\"submit\" name=\"ok\" value=\"Enregistrer\" /></div></center></form>";
    }

}
?>
</body>
</html>