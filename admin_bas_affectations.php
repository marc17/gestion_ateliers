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
$nom_script = "mod_plugins/gestion_ateliers/admin_bas_affectations.php";
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
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : NULL);
if (!isset($_SESSION['order_by'])) {$_SESSION['order_by'] = "id_prop";}
$_SESSION['order_by'] = isset($_POST['order_by']) ? $_POST['order_by'] : (isset($_GET['order_by']) ? $_GET['order_by'] : $_SESSION['order_by']);
$order_by = $_SESSION['order_by'];
$themessage = "Etes-vous sûr de vouloir effecter cette action ?";

// Enregistrement des limites de blocage
if ((isset($numero_bas)) and (isset($action)) and ($action=="reg_nb_bloque")) {
    // On va chercher les activités
    $calldata = mysql_query("SELECT * FROM bas_propositions
    WHERE (num_bas= '".$numero_bas."')");
    $nombreligne = mysql_num_rows($calldata);
    $k = 0;
    while ($k < $nombreligne) {
        $id_bas = mysql_result($calldata,$k,"id_bas");
        $temp = "nb_bloque_".$id_bas;
        if (isset($_POST[$temp]))
            mysql_query("update bas_propositions set nb_bloque = '".$_POST[$temp]."' where id_bas='".$id_bas."'");
        $k++;
    }

    unset($action);
}

// Affectation des choix 1 prioritaires
if ((isset($numero_bas)) and (isset($action)) and ($action=="affect_priorite")) {
    $req = mysql_query("select * from bas_j_eleves_bas
    where num_bas='".$numero_bas."' and priorite='y' and num_choix!='0'");
    $nb = mysql_num_rows($req);
    $i = 0;
    while ($i < $nb) {
        $id_bast = mysql_result($req,$i,'id_bas');
        $id_eleve  = mysql_result($req,$i,'id_eleve');
        $num_sequence  = mysql_result($req,$i,'num_sequence');
        $debut_final = sql_query1("select debut_final from bas_propositions where id_bas='".$id_bast."'");
        $duree = sql_query1("select duree from bas_propositions where id_bas='".$id_bast."'");
        $d = 1;
        while ($d < ($duree+1)) {
            $temp = $debut_final+$d-1;
            // On efface les affectations avant de les réinsérer
            $req1 = mysql_query("delete from bas_j_eleves_bas
            where num_bas='".$numero_bas."' and num_sequence = '".$temp."' and num_choix='0' and id_eleve='".$id_eleve."'");
            // On insère
            $req2 = mysql_query("insert into bas_j_eleves_bas set
            num_bas='".$numero_bas."',
            num_choix='0',
            id_eleve='".$id_eleve."',
            num_sequence = '".$temp."',
            id_bas = '".$id_bast."',
            priorite = 'y'
            ");
            $d++;
        }
        $i++;
    }
    unset($action);
}

// Affectation des choix 1 non prioritaires jusqu'à compléter les effectifs à hauteur de nb_max
if ((isset($numero_bas)) and (isset($action)) and ($action=="affect_choix1")) {
    // Selection des élèves ayant fait un choix 1 et non prioritaires
    $req = mysql_query("select * from bas_j_eleves_bas
    where num_bas='".$numero_bas."' and num_choix='1' and priorite != 'y'");
    // Nombre  d'élèves ayant fait un choix 1 et non prioritaires
    $nb = mysql_num_rows($req);
    // 1ère passe : on prend en priorité les élèves qui n'ont pas de choix 2
    $nb_test = 0;
    while ($nb_test < 2) {
        $i = 0;
        while ($i < $nb) {
            $id_eleve  = mysql_result($req,$i,'id_eleve');
            $num_sequence  = mysql_result($req,$i,'num_sequence');
            // On regarde si cet élève est déjà affecté
            $test_c = sql_count(sql_query("select num_choix from bas_j_eleves_bas where
            num_bas='".$numero_bas."' and
            num_choix='0' and
            id_eleve='".$id_eleve."' and
            num_sequence = '".$num_sequence."'
            "));
        if ($test_c == 0)  {
        // L'élève n'est pas affecté
            // On regarde si cet élève a fait un choix 2
            $test_choix2 = sql_count(sql_query("select num_choix from bas_j_eleves_bas where
            num_bas='".$numero_bas."' and
            num_choix='2' and
            id_eleve='".$id_eleve."' and
            num_sequence = '".$num_sequence."'
            "));
            // 1ère passe ($nb_test = 0) : on prend les élèves qui n'ont pas de choix 2
            // 1ème passe ($nb_test = 1) : on prend les élèves qui ont un choix 2
            if ($test_choix2 == $nb_test) {
                $id_bast = mysql_result($req,$i,'id_bas');
                $debut_final = sql_query1("select debut_final from bas_propositions where id_bas='".$id_bast."'");
                $duree = sql_query1("select duree from bas_propositions where id_bas='".$id_bast."'");
                // Nombre max
                $nb_max = sql_query1("select nb_max from bas_propositions where id_bas = '".$id_bast."'")*5/4;
                //
                $nb_affect =  sql_count(sql_query("select id_eleve from bas_j_eleves_bas where
                num_bas = '".$numero_bas."' and  id_bas = '".$id_bast."' and num_choix='0' and num_sequence='".$debut_final."'"));
                if (($nb_affect < $nb_max) or ($nb_max == 0)) {
                    $d = 1;
                    while ($d < ($duree+1)) {
                        $temp = $debut_final+$d-1;
                        $req1 = mysql_query("delete from bas_j_eleves_bas
                        where
                        num_bas='".$numero_bas."' and
                        num_sequence = '".$temp."' and
                        num_choix='0' and
                        id_eleve='".$id_eleve."'");
                        $req2 = mysql_query("insert into bas_j_eleves_bas set
                        num_bas='".$numero_bas."',
                        num_choix='0',
                        id_eleve='".$id_eleve."',
                        num_sequence = '".$temp."',
                        id_bas = '".$id_bast."',
                        priorite = 'y'
                        ");
                        $d++;
                    }
                }
            }


            }

            $i++;
        }
        $nb_test++;
    }
    unset($action);
}

// Affectation des choix 2 jusqu'à compléter les effectifs à hauteur de nb_max
if ((isset($numero_bas)) and (isset($action)) and ($action=="affect_choix2")) {
    $req = mysql_query("select * from bas_j_eleves_bas
    where num_bas='".$numero_bas."' and num_choix='2'");
    $nb = mysql_num_rows($req);
    $i = 0;
    while ($i < $nb) {
        $id_eleve  = mysql_result($req,$i,'id_eleve');
        $num_sequence  = mysql_result($req,$i,'num_sequence');
        $id_bast = mysql_result($req,$i,'id_bas');
        $debut_final = sql_query1("select debut_final from bas_propositions where id_bas='".$id_bast."'");
        $duree = sql_query1("select duree from bas_propositions where id_bas='".$id_bast."'");
        // On regarde si cet élève est déjà affect
        $d = 1;
        while ($d < ($duree+1)) {
            $temp = $debut_final+$d-1;
            $test_choix[$d] = sql_count(sql_query("select num_choix from bas_j_eleves_bas where
            num_bas='".$numero_bas."' and
            num_choix='0' and
            id_eleve='".$id_eleve."' and
            num_sequence = '".$temp."'
            "));
            $d++;
        }
        $result_tests = 0;
        $d = 1;
        while ($d < $duree+1) {
            $result_tests = max($result_tests,$test_choix[$d]);
            $d++;
        }
        if ($result_tests == 0)  {
            // Nombre max
            $nb_max = sql_query1("select nb_max from bas_propositions where id_bas = '".$id_bast."'")*5/4;
                //
                $nb_affect =  sql_count(sql_query("select id_eleve from bas_j_eleves_bas where
                num_bas = '".$numero_bas."' and  id_bas = '".$id_bast."' and num_choix='0' and num_sequence='".$debut_final."'"));
                if (($nb_affect < $nb_max) or ($nb_max == 0)) {
                    $d = 1;
                    while ($d < ($duree+1)) {
                        $temp = $debut_final+$d-1;
                        $req1 = mysql_query("delete from bas_j_eleves_bas
                        where
                        num_bas='".$numero_bas."' and
                        num_sequence = '".$temp."' and
                        num_choix='0' and
                        id_eleve='".$id_eleve."'");
                        $req2 = mysql_query("insert into bas_j_eleves_bas set
                        num_bas='".$numero_bas."',
                        num_choix='0',
                        id_eleve='".$id_eleve."',
                        num_sequence = '".$temp."',
                        id_bas = '".$id_bast."',
                        priorite = 'y'
                        ");
                        $d++;
                    }
                }
            }
            $i++;
    }
    unset($action);
}



//**************** EN-TETE *****************
$titre_page = "Gestion des ateliers - Harmonisation des effectifs";
require_once("../../lib/header.inc.php");
//**************** FIN EN-TETE *************

// Si le numério du n'est pas défini, on arrete tout
if (!(isset($numero_bas))) die();

// Affichage du tableau général
if ((isset($numero_bas)) and ((!isset($action)) or ($action == 'modif_nb_blocage') )) {
    echo "<p class=bold>| <a href=\"../../accueil.php\">Retour à la page d'accueil</a> |<a href=\"./admin_index.php\"> Menu de gestion des ".$NomAtelier_pluriel."</a> |";
    // Données sur le bas
    $date_bas = sql_query1("select date_bas from bas_bas where id_bas='".$numero_bas."'");
    $nom_bas = sql_query1("select nom from bas_bas where id_bas='".$numero_bas."'");
    $close_bas = sql_query1("select close_bas from bas_bas where id_bas='".$numero_bas."'");
    $date_limite = sql_query1("select date_limite from bas_bas where id_bas='".$numero_bas."'");
    $description_bas = sql_query1("select description_bas from bas_bas where id_bas='".$numero_bas."'");
    $num_periode = sql_query1("select num_periode from bas_bas where id_bas='".$numero_bas."'");
    // Constitution du tableau $per
    $per =  tableau_periode($numero_bas);
    echo "<p class='grand'>".$nom_bas." du ".$date_bas." - ".$description_bas."</p>";

    // On va chercher les activités déjà existantes, et on les affiche.

    $calldata = mysql_query("SELECT * FROM bas_propositions
    WHERE (num_bas= '".$numero_bas."') ORDER BY $order_by");
    $nombreligne = mysql_num_rows($calldata);
    if ($nombreligne == 0) {
        echo "<p><b>Actuellement, aucune proposition n'a été enregistrée.</b></p>";
        echo "</body></html>";
        die();
    } else {
        // Nombre total d'élève à affecter
        $nb_total = sql_count(sql_query("select distinct login from bas_classes bc, j_eleves_classes jec
        where
        bc.id_classe = jec.id_classe and
        jec.periode = '".$num_periode."'
        "));

        // Calcul du nombre d'élèves ayant fait un choix 1
        // Calcul du nombre d'élèves ayant fait un choix 2
        // Calcul du nombre d'élèves définitivement affectés
        $c = 0;
        while ($c < 3) {
            $k = 1;
            while ($k < count($per)+1) {
                $ele_choi[$c][$k] = sql_count(sql_query("select distinct login from bas_classes bc, j_eleves_classes jec, bas_j_eleves_bas bjeb
                where
                bc.id_classe = jec.id_classe and
                jec.periode = '".$num_periode."' and
                jec.login = bjeb.id_eleve and
                bjeb.num_bas = '".$numero_bas."' and
                bjeb.num_sequence = '".$k."' and
                bjeb.num_choix = '".$c."' and
                bjeb.id_bas!='abs'
                "));
                $k++;
            }
            $c++;
        }

        echo "<p><b>".$nombreligne." propositions ont été enregistrées.</b></p>";
        echo "<p><b>1er choix :</b> ";
        $k = 1;
        while ($k < count($per)+1) {
            echo $per[$k]." : <b>".$ele_choi[1][$k]."/".$nb_total."</b> élèves inscrits - ";
            $k++;
        }

        echo "<p><b>2ème choix :</b> ";
        $k = 1;
        while ($k < count($per)+1) {
            echo $per[$k]." : <b>".$ele_choi[2][$k]."/".$nb_total."</b> élèves inscrits - ";
            $k++;
        }
        echo "</p>";

        echo "<p><b>Choix définitif :</b> ";
        $k = 1;
        while ($k < count($per)+1) {
            echo $per[$k]." : <b>".$ele_choi[0][$k]."/".$nb_total."</b> élèves inscrits - ";
            $k++;
        }
        echo "</p>";

        echo "<p><a href='admin_modif_choix1.php?numero_bas=$numero_bas'>Modif choix 1</a></p>";
        echo "<p><a href='admin_bas_affectations.php?action=affect_priorite&amp;numero_bas=$numero_bas' onclick=\"return confirmlink(this,  '$themessage', 'Confirmation')\">Affecter les choix 1 prioritaires</a>";
        echo " - <a href='admin_bas_affectations.php?action=affect_choix1&amp;numero_bas=$numero_bas' onclick=\"return confirmlink(this,  '$themessage', 'Confirmation')\">Affecter les choix 1</a>";
        echo " - <a href='admin_bas_affectations.php?action=affect_choix2&amp;numero_bas=$numero_bas' onclick=\"return confirmlink(this,  '$themessage', 'Confirmation')\">Affecter les choix 2</a>";
        echo " - <a href='admin_bas_affectations.php?action=modif_nb_blocage&amp;numero_bas=$numero_bas' >Modifier les limites de blocage</a>";
        echo "</p>";
    }
    if (isset($_GET['action']) and ($_GET['action'] == 'modif_nb_blocage'))
        echo "<form action=\"admin_bas_affectations.php\" name=\"limite_blocage\" method=\"post\">\n";
    // Affichage de l'entete du tableau
    echo "<table width = 100% cellpadding=1 border=1>";
    echo "<tr>";
    echo "<td><span class='small'><a href='admin_bas_affectations.php?order_by=id_prop&amp;numero_bas=$numero_bas'>N°<br /><i>Matière</i></a></span></td>\n";
    echo "<td><span class='small'><a href='admin_bas_affectations.php?order_by=titre,type,responsable&amp;numero_bas=$numero_bas'>Intitulé de l'activité</a></span></td>\n";
    echo "<td><span class='small'>Dédoubler</span></td>\n";
    echo "<td><span class='small'><a href='admin_bas_affectations.php?order_by=debut_final,titre,responsable&amp;numero_bas=$numero_bas'>Horaire</a></span></td>\n";
    echo "<td><span class='small'><a href='admin_bas_affectations.php?order_by=responsable,type&amp;numero_bas=$numero_bas'>Animateur</a></span></td>";
    echo "<td><span class='small'><a href='admin_bas_affectations.php?order_by=nb_max,responsable&amp;numero_bas=$numero_bas'>Nb. max.<br />élèves</a></span></td>";
    echo "<td><span class='small'>Nb. inscrits<br />Choix 1</span></td>";
    echo "<td><span class='small'>Nb. inscrits<br />Choix 2</span></td>";
    echo "<td><span class='small'>Nb. affectés<br />final</span></td>";
    echo "<td><span class='small'>Nb. blocage</span></td>";
    echo "<td><span class='small'><a href='admin_bas_affectations.php?order_by=duree,responsable&amp;numero_bas=$numero_bas'>Durée</a></span></td>";
    echo "<td><span class='small'><a href='admin_bas_affectations.php?order_by=salle,id_prop&amp;numero_bas=$numero_bas'>Salle</a></span></td>";
    echo "</tr>";
    // Affichage des lignes du tableau
    $i = 0;
    while ($i < $nombreligne){
        $bas_statut = @mysql_result($calldata, $i, "statut");
        $bas_id_prop = @mysql_result($calldata, $i, "id_prop");
        $bas_titre = @mysql_result($calldata, $i, "titre");
        $bas_type = @mysql_result($calldata, $i, "type");
        $bas_matiere = mysql_result($calldata, $i, "id_matiere");
        $nom_matiere_prop = sql_query1("select nom_complet from bas_matieres where matiere = '".$bas_matiere."'");
        $proprietaire = @mysql_result($calldata, $i, "proprietaire");
        $debut_final = @mysql_result($calldata, $i, "debut_final");
        $bas_proprietaire = sql_query1("select prenom from utilisateurs where login = '".$proprietaire."'")." ".sql_query1("select nom from utilisateurs where login = '".$proprietaire."'");
        $bas_responsable = @mysql_result($calldata, $i, "responsable");
        $req_bas = mysql_query("select debut_final from bas_propositions where (responsable = '". traitement_magic_quotes($bas_responsable)."' and num_bas = '".$numero_bas."') order by 'debut_final'");
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
        $email_prof = sql_query1("select email from utilisateurs where login='".$bas_responsable."'");
        if (($nom_prof != -1) and ($prenom_prof != -1)) $bas_responsable = $nom_prof." ".$prenom_prof;
        $bas_duree = @mysql_result($calldata, $i, "duree");
        $bas_salle = @mysql_result($calldata, $i, "salle");
        if ($bas_salle=='') $bas_salle= '-';
        $bas_nb_max = @mysql_result($calldata, $i, "nb_max");
        $id_bas = @mysql_result($calldata, $i, "id_bas");

        // Calcul du nombre d'inscrits
        $nb_inscrit_[1] = sql_count(sql_query("select id_eleve from bas_j_eleves_bas where
        num_bas = '".$numero_bas."' and  id_bas = '".$id_bas."' and num_choix='1' and num_sequence='".$debut_final."'"));
        $nb_inscrit_[2] = sql_count(sql_query("select id_eleve from bas_j_eleves_bas where
        num_bas = '".$numero_bas."' and  id_bas = '".$id_bas."' and num_choix='2' and num_sequence='".$debut_final."'"));
        $nb_inscrit_[0] = sql_count(sql_query("select id_eleve from bas_j_eleves_bas where
        num_bas = '".$numero_bas."' and  id_bas = '".$id_bas."' and num_choix='0' and num_sequence='".$debut_final."'"));

        // Max élèves inscrits
        $nb_bloque = sql_query1("select nb_bloque from bas_propositions where id_bas = '".$id_bas."'");
        if (($nb_bloque != -1) and ($nb_bloque <= $nb_inscrit_[0])) $flag_bloque = 'y'; else $flag_bloque = 'n';

        $m = 0;
        while ($m < 3) {
            if ((($nb_inscrit_[$m] > $bas_nb_max) and ($bas_nb_max!=0)) and ($m != 2))
                if (($nb_inscrit_[$m] <= $bas_nb_max*5/4) and ($bas_nb_max!=0))
                     $nb_inscrit_m[$m] = "<td bgcolor=\"#FF9D9D\"><span class='small'><b>".$nb_inscrit_[$m]."</b></span></td>";
                else
                     $nb_inscrit_m[$m] = "<td bgcolor=\"#FF0000\"><span class='small'><b>".$nb_inscrit_[$m]."</b></span></td>";
            else if ((($nb_inscrit_[$m] > 35) and ($bas_nb_max==0)) and ($m != 2))
                $nb_inscrit_m[$m] = "<td bgcolor=\"#FF0000\"><span class='small'><b>".$nb_inscrit_[$m]."</b></span></td>";
            else
                $nb_inscrit_m[$m] = "<td><span class='small'>".$nb_inscrit_[$m]."</span></td>";
            $m++;
        }
        if ($nb_inscrit_[0] == 1)
            $nb_inscrit_m[0] = "<td bgcolor=\"#C0FF00\"><span class='small'><b>".$nb_inscrit_[0]."</b></span></td>";
        if ($nb_inscrit_[0] == 0)
            $nb_inscrit_m[0] = "<td bgcolor=\"#00FFFF\"><span class='small'><b>".$nb_inscrit_[0]."</b></span></td>";


        if ($bas_statut == 'a') {
            $bas_salle = "<font color='red'><b>Annulé</b></font>";
            echo "<tr bgcolor=\"#C0C0C0\">";
        } else if ($flag_bloque == 'y') {
            $bas_salle = "<font color='red'><b>Bloqué</b></font>";
            echo "<tr bgcolor=\"#C0C0C0\">";
        } else
            echo "<tr>";
        echo "<td><span class='small'>$bas_id_prop<br /><i>$nom_matiere_prop</i></span></td>\n";
        echo "<td><span class='small'><a href='admin_bas_affectations.php?action=affects_par_bas&amp;id_bas=$id_bas&amp;numero_bas=$numero_bas' title='Proposition effectuée par ".$bas_proprietaire."' target = '_blank'><b>$bas_titre";
        if ($bas_type == 'R') echo " <font color='red'>(REMEDIATION)</font>";
        echo "</b></a></span></td>\n";
        echo "<td><span class='small'><a href='modify_bas.php?action=dedoublement&amp;id_bas=$id_bas&amp;numero_bas=$numero_bas&amp;retour=admin_bas_affectations'><b>Déd.</b></a></span></td>\n";
        if (isset($per[$debut_final]))
            echo "<td><span class='small'>".$per[$debut_final]."</span></td>\n";
        else
            echo "<td><span class='small'>-</span></td>\n";
        if ($email_prof != '-1')
            $bas_responsable = "<a href='mailto:".$email_prof."'>".$bas_responsable."</a>";
        $bas_responsable .= "<br />".$texte;
        echo "<td><span class='small'>$bas_responsable</span></td>\n";
        echo "<td><span class='small'>$bas_nb_max</span></td>\n";
        echo $nb_inscrit_m[1]."\n";
        echo $nb_inscrit_m[2]."\n";
        echo $nb_inscrit_m[0]."\n";
        if (isset($_GET['action']) and ($_GET['action'] == 'modif_nb_blocage')) {
            $aff_bloque = "<input type=\"text\" name=\"nb_bloque_".$id_bas."\" value=\"$nb_bloque\" size=\"3\" />";
        } else {
          // Affiche du nombre max avant blocage
          if ($nb_bloque != -1)
            $aff_bloque = "<font color=\"#FF0000\"><b>".$nb_bloque."</b></font>";
          else
            $aff_bloque = "-";
        }
        echo "<td><span class='small'>$aff_bloque</span></td>\n";
        echo "<td><span class='small'>$bas_duree h</span></td>\n";
        echo "<td ";
        if (isset($result_test_salle[$id_bas])) echo $result_test_salle[$id_bas];
        echo "><span class='small'>".$bas_salle."</span></td>\n";
        echo "</tr>\n";
        $i++;
    }
    echo "</table>";
    if (isset($_GET['action']) and ($_GET['action'] == 'modif_nb_blocage')) {
        echo "<input type=\"hidden\" name=\"action\" value=\"reg_nb_bloque\" />";
        echo "<input type=\"hidden\" name=\"numero_bas\" value=\"$numero_bas\" />";
        echo "<input type=\"submit\" name=\"Valider\" value=\"Valider\" />";
        echo "</form>\n";
    }

}

// Gestion des élèves par atelier
if ((isset($numero_bas)) and (isset($action)) and ($action=="affects_par_bas")) {
    // Informations sur la proposition atelier
    $call_bas_info = mysql_query("SELECT * FROM bas_propositions WHERE id_bas='$id_bas'");
    $titre = mysql_result($call_bas_info, "0", "titre");
    $req = mysql_query("select id_classe from bas_classes");
    $nb_classes = mysql_num_rows($req);
    $responsable = mysql_result($call_bas_info, "0", "responsable");
    $matiere = mysql_result($call_bas_info, "0", "id_matiere");
    $proprietaire = mysql_result($call_bas_info, "0", "proprietaire");
    $nb_max = mysql_result($call_bas_info, "0", "nb_max");
    $salle = mysql_result($call_bas_info, "0", "salle");
    $duree = mysql_result($call_bas_info, "0", "duree");
    $debut_final = mysql_result($call_bas_info, "0", "debut_final");

    // Tableau des effectifs des activités :
    $calldata_bas = mysql_query("SELECT * FROM bas_propositions WHERE (num_bas= '".$numero_bas."')");
    $nombreligne_ = mysql_num_rows($calldata_bas);
    $i=0;
    while ($i < $nombreligne_){
        $id_bas_ = @mysql_result($calldata_bas, $i, "id_bas");
        $nom_prop[$id_bas_] = @mysql_result($calldata_bas, $i, "id_prop");
        // Calcul du nombre d'inscrits
        $nb_inscrit_[$id_bas_] = sql_count(sql_query("select id_eleve from bas_j_eleves_bas where
        num_bas = '".$numero_bas."' and  id_bas = '".$id_bas_."' and num_choix='0' and num_sequence='".$debut_final."'"));
        // Max élèves inscrits
        $nb_max_[$id_bas_] = sql_query1("select nb_max from bas_propositions where id_bas = '".$id_bas_."'");
        if (($nb_inscrit_[$id_bas_] > $nb_max_[$id_bas_]) and ($nb_max_[$id_bas_] != 0))
            $avertissement[$id_bas_] = "yes";
        else
            $avertissement[$id_bas_] = "no";
        if ($nb_max_[$id_bas_] <= 0) $nb_max_[$id_bas_] = "-";

        $i++;
    }


    // Enregistrement des modifications
    if (isset($_POST['is_posted_statut'])) {
        $req = sql_query("update bas_propositions set statut='".$_POST['bas_statut']."', nb_bloque = '".$_POST['bas_nb_bloque']."' where id_bas='".$_POST['id_bas']."'");
        if ($_POST['bas_statut'] == "a") {
            $req = sql_query("delete from bas_j_eleves_bas
            where num_bas='".$numero_bas."' and id_bas = '".$_POST['id_bas']."' and num_choix='0'");

        }
    }


    // Enregistrement des modifications
    if (isset($_POST['is_posted'])) {
        $num_periode = sql_query1("select num_periode from bas_bas where id_bas='".$numero_bas."'");
        $eleves2 = mysql_query("select distinct jec.login from bas_classes bc, j_eleves_classes jec
        where
        bc.id_classe = jec.id_classe and
        jec.periode = '".$num_periode."'
        order by login
        ");
        $nb_eleves2 = mysql_num_rows($eleves2);
        $i = 0;
        while ($i < $nb_eleves2) {
            $login_eleve = mysql_result($eleves2,$i,'login');
            $temp = $login_eleve."_a";
            if (isset($_POST[$temp])) {
                if ($_POST[$temp] == 'desins') {
                    $d = 1;
                    while ($d < ($duree+1)) {
                        $temp2 = $debut_final+$d-1;
                        // Il faut désaffecter l'élève
                        $req = mysql_query("delete from bas_j_eleves_bas where
                        id_eleve = '".$login_eleve."' and
                        num_bas='".$numero_bas."' and
                        num_sequence = '".$temp2."' and
                        num_choix = '0'
                        ");
                        $d++;
                    }
                } else if ($_POST[$temp] != 'autre') {
                    $dureet = sql_query1("select duree from bas_propositions where id_bas='".$_POST[$temp]."'");
                    $d = 1;
                    while ($d < ($dureet+1)) {
                        $temp2 = $debut_final+$d-1;
                        $test_ele[$d] = sql_query1("select id_bas from bas_j_eleves_bas where
                        id_eleve = '".$login_eleve."' and
                        num_bas='".$numero_bas."' and
                        num_sequence = '".$temp2."' and
                        num_choix = '0'
                        ");
                        $d++;
                    }
                    // Il faut affecter l'élève à une activité
                    $d = 1;
                    while ($d < ($dureet+1)) {
                        $temp2 = $debut_final+$d-1;
                        if ($test_ele[$d] == -1) {  // L'élève n'était pas affecté, il faut le faire
                            $req = mysql_query("insert into bas_j_eleves_bas set
                            id_eleve = '".$login_eleve."',
                            num_bas='".$numero_bas."',
                            num_sequence = '".$temp2."',
                            num_choix = '0',
                            id_bas = '".$_POST[$temp]."',
                            priorite = ''
                            ");
                        } else {  // L'élève était déjà affecté à un bas, il faut mettre à jour l'enregistrement
                            $req = mysql_query("update bas_j_eleves_bas set
                            id_bas = '".$_POST[$temp]."'
                            where
                            id_eleve = '".$login_eleve."' and
                            num_bas='".$numero_bas."' and
                            num_sequence = '".$temp2."' and
                            num_choix = '0'
                            ");
                        }
                        $d++;
                    }
                }
            }
            $i++;
        }
    }

    // données sur le bas
    $description_bas = sql_query1("select description_bas from bas_bas where id_bas='".$numero_bas."'");
    $num_periode = sql_query1("select num_periode from bas_bas where id_bas='".$numero_bas."'");
    $nom_bas = sql_query1("select nom from bas_bas where id_bas='".$numero_bas."'");
    // Constitution du tableau $per
    $per =  tableau_periode($numero_bas);
    $per[0] = "Heure non défini";
    echo "<a href='admin_bas_affectations.php?numero_bas=$numero_bas'>Retour</a>";
    echo "<p class='grand'>".$nom_bas." - Proposition N° ".$id_bas." - ".$per[$debut_final]." - Durée : ".$duree." h - Salle : ".$salle;
    echo "<br />".$titre."</p>";
    echo "<p>Nombre max. d'élèves souhaités : ".$nb_max."</p>";
    echo "<p>Animateur : ".$responsable." (".$matiere.")</p>";

    $bas_statut = sql_query1("select statut from bas_propositions where id_bas='".$id_bas."'");
    $bas_nb_bloque = sql_query1("select nb_bloque from bas_propositions where id_bas='".$id_bas."'");
    if ($bas_statut == 'a')
        echo "<center><h1><font color='red'>L'activité est actuellement annulée.</font></h1></center>";
    // annulation d'une activité
    $nb_inscrit_0 = sql_count(sql_query("select id_eleve from bas_j_eleves_bas where
    num_bas = '".$numero_bas."' and  id_bas = '".$id_bas."' and num_choix='0' and num_sequence='".$debut_final."'"));
    $nb_inscrit_1 = sql_count(sql_query("select id_eleve from bas_j_eleves_bas where
    num_bas = '".$numero_bas."' and  id_bas = '".$id_bas."' and num_choix='1' and num_sequence='".$debut_final."'"));
//    if (($nb_inscrit_0==0) and ($nb_inscrit_1<=1)) {
        // On peut désactivé l'activité
        echo "<hr /><form action=\"admin_bas_affectations.php\" name=\"affectation\" method=\"post\">\n";
        echo "<table border=\"1\"><tr>";

        echo "<td><input type=\"radio\" name=\"bas_statut\" value=\"v\" ";
        if ($bas_statut == 'v') echo " checked";
        echo " />";
        echo "Activité ouverte</td></tr>";


        echo "<tr><td><input type=\"radio\" name=\"bas_statut\" value=\"a\" ";
        if ($bas_statut == 'a') echo " checked";
        echo " />";
        echo "Annuler l'activité (cette action a pour effet de désaffecter tous les élèves déjà affectés).</td></tr>";

        echo "<tr><td><input type=\"radio\" name=\"bas_statut\" value=\"m\" ";
        if ($bas_statut == 'm') echo " checked";
        echo " />";
        echo "Bloquer les inscriptions et les affectations pour cette activité</td></tr>";

        echo "<tr><td>Bloquer les inscriptions à partir de <input type=\"text\" name=\"bas_nb_bloque\" size=\"20\" value=\"".$bas_nb_bloque."\" />";
        echo " élèves</td></tr>";

        echo "<tr><td><input type=\"hidden\" name=\"id_bas\" value=\"$id_bas\" />";
        echo "<input type=\"hidden\" name=\"numero_bas\" value=\"$numero_bas\" />";
        echo "<input type=\"hidden\" name=\"is_posted_statut\" value=\"yes\" />";
        echo "<input type=\"hidden\" name=\"action\" value=\"affects_par_bas\" />";
        echo "<input type=\"submit\" name=\"ok\" value=\"Envoyer\" /></td></tr></table>";
        echo "</form><hr />\n";
  //  }
    // Liste des élèves ayant le choix 0, 1 ou 2d pour cet atelier
    $eleves = mysql_query("select distinct jec.login from bas_classes bc, j_eleves_classes jec, bas_j_eleves_bas bjeb
    where
    bc.id_classe = jec.id_classe and
    jec.periode = '".$num_periode."' and
    jec.login = bjeb.id_eleve and
    bjeb.num_bas = '".$numero_bas."' and
    bjeb.id_bas = '".$id_bas."' and
    bjeb.num_sequence = '".$debut_final."'
    order by num_choix, jec.id_classe, jec.login, priorite
    ");
    $nb_eleves = mysql_num_rows($eleves);
    $i = 0;
    echo "<form action=\"admin_bas_affectations.php\" name=\"affectation\" method=\"post\">\n";
    echo "<table border=\"1\"><tr>";
    echo "<td>Nom prénom</td>\n";
    echo "<td>Classe</td>\n";
    echo "<td>Filière</td>\n";
    echo "<td>Priorité</td>\n";
    echo "<td>N° du choix</td>\n";
    echo "<td>Affecter à une activité</td>\n";
    echo "<td>Choix N° 1</td>\n";
    echo "<td>Choix N° 2</td>\n";
    $nb_seq = 1;
    while ($nb_seq < 4) {
        if ($nb_seq != $debut_final) {
            echo "<td>Choix final<br />Séquence N° ".$nb_seq."</td>\n";
        }
        $nb_seq++;
    }
    echo "</tr>\n";
    while ($i < $nb_eleves) {
        $login_eleve = mysql_result($eleves,$i,'login');
        // Nom prénom, classe de l'élève
        $nom_eleve = sql_query1("select nom from eleves where login = '".$login_eleve."'");
        $prenom_eleve = sql_query1("select prenom from eleves where login = '".$login_eleve."'");
        $classe = mysql_query("select id, classe from classes c, j_eleves_classes j
        where j.login = '".$login_eleve."' and
        j.id_classe = c.id and
        j.periode = '".$num_periode."'
        ");
        $classe_eleve = mysql_result($classe,0,'classe');
        $id_filiere=sql_query1("select id_filiere from bas_j_eleves_filieres where id_eleve='".$login_eleve."'");
        if ($id_filiere!=-1)
         $nom_filiere=sql_query1("select nom_filiere from bas_filieres where id_filiere='".$id_filiere."'");
        else
         $nom_filiere='-';
        // Recherche des numéros de choix sur le bas
        $req_num_choix = mysql_query("select num_choix from bas_j_eleves_bas where
        id_eleve = '".$login_eleve."' and
        num_bas='".$numero_bas."' and
        num_sequence = '".$debut_final."' and
        id_bas = '".$id_bas."' and
        num_choix != '0' order by num_choix
        ");
        $nb_num_choix = mysql_num_rows($req_num_choix);
        $num_choix = '';
        $nb = 0;
        while ($nb < $nb_num_choix) {
            $num_choix .= mysql_result($req_num_choix,$nb,'num_choix')." ";
            $nb++;
        }
        if ($num_choix == "") {
            $num_choix = "-";
            $fond = "#C18883";
        } else if ($num_choix == "1 ") {
            $fond = '#C6F399';
        } else if ($num_choix == "2 ") {
            $fond = '#7CD91F';
        } else {
            $fond = "#C18883";
        }
        // Recherche de la priorité
        $priorite = sql_query1("select priorite from bas_j_eleves_bas where
        id_eleve = '".$login_eleve."' and
        num_bas='".$numero_bas."' and
        num_sequence = '".$debut_final."' and
        id_bas = '".$id_bas."' and
        num_choix = '1'
        ");
        if ($priorite == "-1") $priorite = "-";

        echo "<tr><td bgcolor=\"".$fond."\"><a href = 'admin_inscrip_rapide.php?numero_bas=$numero_bas&amp;login_eleve=$login_eleve&amp;cible=unique&amp;action=inscription' target='_blank'>".$nom_eleve." ".$prenom_eleve."</a></td>
        <td bgcolor=\"".$fond."\">".$classe_eleve."</td>
        <td bgcolor=\"".$fond."\">".$nom_filiere."</td>
        <td bgcolor=\"".$fond."\">".$priorite."</td>
        <td bgcolor=\"".$fond."\">".$num_choix."</td>";

        // On cherche l'activités à laquelle l'élève est affecté (choix 0) sur le créneau de ce bas
        $id_bas_eleve = sql_query1("select id_bas from bas_j_eleves_bas where
        id_eleve = '".$login_eleve."' and
        num_bas='".$numero_bas."' and
        num_sequence = '".$debut_final."' and
        num_choix = '0'
        ");

        // Choix d'un autre bas
        echo "<td bgcolor=\"".$fond."\">";
        if ($id_filiere==-1) {
        echo "<font color='red'><a href = 'admin_inscrip_rapide.php?numero_bas=$numero_bas&amp;login_eleve=$login_eleve&amp;cible=unique&amp;action=inscription_filiere' target='_blank'>Filière non affectée</a></font>";
        } else {
        echo "<select name=\"".$login_eleve."_a\" size=\"1\">\n";
        $calldata = mysql_query("SELECT * FROM bas_propositions
            WHERE (
            num_bas= '".$numero_bas."' and 
            public_".$id_filiere." != ''and
            debut_final = '".$debut_final."'
            ) ORDER BY id_prop");
        $nombreligne = mysql_num_rows($calldata);
        $m = 0;
        echo "<option value='desins'>(non affecté)</option>\n";
        $flag = 0;
        while ($m < $nombreligne) {
            $bas_id_bas = @mysql_result($calldata, $m, "id_bas");
            $bas_id_prop = @mysql_result($calldata, $m, "id_prop");
            $bas_duree = @mysql_result($calldata, $m, "duree");
            echo "<option value=".$bas_id_bas." ";
            if ($id_bas_eleve == $bas_id_bas) {
                echo "selected";
                $flag = "1";
            }
            echo ">".$bas_id_prop;
            if ($bas_duree != $duree) echo " (durée : ".$bas_duree." h) ";
            echo "</option>\n";
            $m++;
        }
        if (($flag == "0") and ($id_bas_eleve != "-1"))
            echo "<option value='autre' selected>(bas n° ".$id_bas_eleve." 2/2)</option>\n";

        echo "</select>";
        }
        echo "</td>\n";
        // On cherche le choix N° 1  de l'élève
        $id_bas_eleve_choix2 = sql_query1("select id_prop from bas_propositions p, bas_j_eleves_bas j where
        j.id_eleve = '".$login_eleve."' and
        j.num_bas='".$numero_bas."' and
        j.num_sequence = '".$debut_final."' and
        j.num_choix = '1' and
        j.id_bas = p.id_bas
        ");
        echo "<td>".$id_bas_eleve_choix2."</td>\n";

        // On cherche le choix N° 2  de l'élève
        $id_bas_eleve_choix2 = sql_query1("select id_bas from bas_j_eleves_bas j where
        j.id_eleve = '".$login_eleve."' and
        j.num_bas='".$numero_bas."' and
        j.num_sequence = '".$debut_final."' and
        j.num_choix = '2'
        ");
        if (isset($nom_prop[$id_bas_eleve_choix2])) {
          echo "<td>".$nom_prop[$id_bas_eleve_choix2]."<font size=-1> ";
          if (isset($avertissement[$id_bas_eleve_choix2]) and  ($avertissement[$id_bas_eleve_choix2] == "yes")) echo "<font color = 'red'>";
          if (isset($nb_inscrit_[$id_bas_eleve_choix2]) and isset($nb_max_[$id_bas_eleve_choix2]))
              echo "(Eff. : ".$nb_inscrit_[$id_bas_eleve_choix2]." - Max : ".$nb_max_[$id_bas_eleve_choix2].")";
          else
              echo "ND";
          if (isset($avertissement[$id_bas_eleve_choix2]) and ($avertissement[$id_bas_eleve_choix2] == "yes")) echo "</font>";
          echo "</td>\n";
        } else
          echo "<td>ND</td>";
        // On cherche les choix N°0 de l'élève sur les autres créneaux
        $nb_seq = 1;
        while ($nb_seq < 4) {
            if ($nb_seq != $debut_final) {
               $id_bas_eleve_choix_autre = sql_query1("select id_bas from bas_j_eleves_bas j where
               j.id_eleve = '".$login_eleve."' and
               j.num_bas='".$numero_bas."' and
               j.num_sequence = '".$nb_seq."' and
               j.num_choix = '0'
               ");
              if (isset($nom_prop[$id_bas_eleve_choix_autre]))
                  echo "<td><b>".$nom_prop[$id_bas_eleve_choix_autre]." </b></td>\n";
              else
                  echo "<td><b>-</td>\n";
            }
            $nb_seq++;

        }

        // Fin de la ligne
        echo "</tr>";
        $i++;


    }
    echo "</table>";
    echo "<input type=\"hidden\" name=\"id_bas\" value=\"$id_bas\" />";
    echo "<input type=\"hidden\" name=\"numero_bas\" value=\"$numero_bas\" />";
    echo "<input type=\"hidden\" name=\"is_posted\" value=\"yes\" />";
    echo "<input type=\"hidden\" name=\"action\" value=\"affects_par_bas\" />";
    echo "<div id=\"fixe\"><input type=\"submit\" name=\"ok\" value=\"Envoyer\" /></div>";
    echo "</form>\n";
}

?>
</body>
</html>