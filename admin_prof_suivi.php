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
$nom_script = "mod_plugins/gestion_ateliers/admin_prof_suivi.php";
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


//**************** EN-TETE *****************
$titre_page = "Gestion des ".$NomAtelier_pluriel;
require_once("../../lib/header.inc");
//**************** FIN EN-TETE *************

echo "<p class=bold>| <a href=\"../../accueil.php\">Retour à la page d'accueil</a> |<a href=\"./admin_index.php\"> Menu de gestion des ".$NomAtelier_pluriel."</a> |";
    // données sur le bas
    $date_bas = sql_query1("select date_bas from bas_bas where id_bas='".$numero_bas."'");
    $nom_bas = sql_query1("select nom from bas_bas where id_bas='".$numero_bas."'");
    $num_periode = sql_query1("select num_periode from bas_bas where id_bas='".$numero_bas."'");
    $close_bas = sql_query1("select close_bas from bas_bas where id_bas='".$numero_bas."'");
    $date_limite = sql_query1("select date_limite from bas_bas where id_bas='".$numero_bas."'");
    if ($close_bas == "y") $close_bas_mess = "<font color='red'>(inscriptions impossibles)</font>"; else $close_bas_mess = " - <font color='red'>A remplir jusqu'au ".$date_limite." (inclus)</font>";
    echo "<p class='grand'>".$nom_bas." du ".$date_bas."</p>";
    $description_bas = sql_query1("select description_bas from bas_bas where id_bas='".$numero_bas."'");
    $per =  tableau_periode($numero_bas);


$req = mysql_query("select distinct u.login, u.nom, u.prenom, u.email from utilisateurs u, j_eleves_professeurs j
where u.login=j.professeur order by u.nom, u.prenom");
$nb_prof = mysql_num_rows($req);
echo "<table border=\"0\">";
$i = 0;
while ($i < $nb_prof) {
    $login_prof = mysql_result($req,$i,'login');
    $nom_prof = mysql_result($req,$i,'nom');
    $prenom_prof = mysql_result($req,$i,'prenom');
    $email_prof = mysql_result($req,$i,'email');
    if ($email_prof != '')
        echo "<tr><td colspan=\"11\"><span class=\"style_bas\"><a href='mailto:".$email_prof."'>".$prenom_prof." ".$nom_prof."</a>";
    else
        echo "<tr><td colspan=\"11\"><span class=\"style_bas\">".$prenom_prof." ".$nom_prof;
    echo "- <a href='index_suivi.php?numero_bas=".$numero_bas."&amp;action=edit_feuille&amp;login_prof=".$login_prof."' title='ce lien ouvre une nouvelle fenêtre dans votre navigateur' target='_blank'>Feuille vierge</a>
    - <a href='index_suivi.php?numero_bas=".$numero_bas."&amp;action=inscription&amp;login_prof=".$login_prof."' title='ce lien ouvre une nouvelle fenêtre dans votre navigateur' target='_blank'>Inscrire les élèves</a>
    </span></td></tr>";

    $req_eleves = mysql_query("select j.login,  e.nom, e.prenom, jec.id_classe
    from eleves e, j_eleves_professeurs j, j_eleves_classes jec
    where
    j.professeur='".$login_prof."' and
    e.login = j.login and
    jec.login = j.login and
    jec.periode = '".$num_periode."'
    ");
    $nb_eleves = mysql_num_rows($req_eleves);
    echo "<tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td><span class=\"style_bas\">Nom Prénom</span></td>
    <td><span class=\"style_bas\">Classe</span></td>";
    $k = 1;
    while ($k < count($per)+1) {
        echo "<td><span class=\"style_bas\">H".$k." - C0</span></td><td><span class=\"style_bas\">H".$k." - C1</span></td><td><span class=\"style_bas\">H".$k." - C2</span></td>";
        $k++;
    }

    $j = 0;
    while ($j < $nb_eleves) {
        $login_eleve = mysql_result($req_eleves,$j,'login');
        $nom_eleve = mysql_result($req_eleves,$j,'nom');
        $prenom_eleve = mysql_result($req_eleves,$j,'prenom');
        $id_classe = mysql_result($req_eleves,$j,'id_classe');
        $nom_classe = sql_query1("select classe from classes where id='".$id_classe."'");
        // Recherche des choix bas
        $k=1;
        while ($k < count($per)+1) {
        $c='0';
        $priorite_temp = "";
        $choix_temp=array();
        while ($c < '3') {
            $req_bas = mysql_query("select * from bas_j_eleves_bas where
            id_eleve = '".$login_eleve."' and
            num_bas='".$numero_bas."' and
            num_sequence = '".$k."' and
            num_choix = '".$c."'
            ");
            $nb_req_bas = mysql_num_rows($req_bas);
            if ($nb_req_bas == 0) {
                if (($c == 2) and (isset($choix_temp[0])) and (($choix_temp[0] != $choix_temp[1]) and ($choix_temp[0] != $choix_temp[2]))) {
                    $choix[$c][$k] = "<td bgcolor=\"#8000FF\"><span class=\"style_bas\">PB ?</span></td>";
                } else {
                    $choix[$c][$k] = "<td bgcolor=\"#FF8581\"><span class=\"style_bas\">-</span></td>";
                }
            } else if ($nb_req_bas >1) {
                $choix[$c][$k] = "<td bgcolor=\"#C000FF\"><span class=\"style_bas\"><b>Erreur</b></span></td>";
            } else {
                $priorite = mysql_result($req_bas,0,'priorite');
                $priorite_aff = "";
                if (($priorite != '') and ($c == 1)) {
                    $priorite_temp = $priorite;
                    $priorite_aff = "(<b>P</b>)";
                }

                $id_bas = mysql_result($req_bas,0,'id_bas');
                $nom_bas = sql_query1("select titre from bas_propositions where id_bas = '".$id_bas."'");
                if ($id_bas != 'abs') {
                    $id_prop = sql_query1("select id_prop from bas_propositions where id_bas = '".$id_bas."'");
                    $choix_temp[$c] = $id_prop;
                    // Vérification
                    if (
                    ($c == 2) and
                    (isset($choix_temp[0])) and
                    (($choix_temp[0] != $choix_temp[1]) and ( ($choix_temp[0] != $choix_temp[2])  or ($priorite_temp != '')  ))
                    )
                    {
                        $choix[$c][$k] = "<td bgcolor=\"#8000FF\"><span class=\"style_bas\">PB ? ".$id_prop." ".$priorite_aff."</span></td>";
                    } else {
                        $choix[$c][$k] = "<td bgcolor=\"#7DFF7D\"><span class=\"style_bas\">".$id_prop." ".$priorite_aff."</span></td>";
                    }
                } else {
                    $choix[$c][$k] = "<td bgcolor=\"#FF8000\"><span class=\"style_bas\">Absent</span></td>";
                }
            }
            $c++;
        }
        $k++;
        }
        echo "<tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td><span class=\"style_bas\"><a href = 'admin_inscrip_rapide.php?numero_bas=$numero_bas&amp;login_eleve=$login_eleve&amp;cible=unique&amp;action=inscription' target='_blank'>".$nom_eleve." ".$prenom_eleve."</a></span></td>
        <td><span class=\"style_bas\">(<a href='index_listes.php?id_classe=".$id_classe."&amp;numero_bas=".$numero_bas."&amp;en_tete=no' target='_blank' title='Voir toutes les propositions pour cette classe'>".$nom_classe."</a>)</span></td>";
        $k=1;
        while ($k < count($per)+1) {
            $c='0';
            while ($c < '3') {
                echo $choix[$c][$k];
                $c++;
            }
            $k++;
        }

        $j++;
    }
    echo "</tr>";
    $i++;
}
echo "</table>";

?>
</body>
</html>