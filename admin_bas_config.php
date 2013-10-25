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
$nom_script = "mod_plugins/gestion_ateliers/admin_bas_config.php";
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

$msg = "";
if (isset($_POST['is_posted'])) {
    $req = mysql_query("select * from bas_bas order by nom");
    $nb_bas = mysql_num_rows($req);
    $i = 0 ;
    while ($i < $nb_bas) {
        $num_bas = mysql_result($req,$i,'id_bas');
        $temp = "date_bas_".$num_bas;
        $reg_date_bas = $_POST[$temp];
        $temp = "num_periode_".$num_bas;
        $reg_num_periode = $_POST[$temp];
        $temp = "date_limite_".$num_bas;
        $reg_date_limite = $_POST[$temp];
        $temp = "type_bas_".$num_bas;
        $reg_type_bas = $_POST[$temp];
        $temp = "bas_passee_".$num_bas;
        if (isset($_POST[$temp])) $reg_bas_passee = $_POST[$temp]; else $reg_bas_passee = 'n';
        $temp = "aut_insc_eleve_".$num_bas;
        if (isset($_POST[$temp])) $reg_aut_insc_eleve = $_POST[$temp]; else $reg_aut_insc_eleve = 'n';
        $temp = "close_bas_".$num_bas;
        if (isset($_POST[$temp])) $reg_close_bas = $_POST[$temp]; else $reg_close_bas = 'n';

        $temp = "active_blocage_".$num_bas;
        if (isset($_POST[$temp])) $reg_active_blocage = $_POST[$temp]; else $reg_active_blocage = 'n';

        $temp = "inscription_bas_".$num_bas;
        if (isset($_POST[$temp])) $reg_inscription_bas = $_POST[$temp];

        $temp = "qui_inscrit_".$num_bas;
        if (isset($_POST[$temp])) $reg_qui_inscrit = $_POST[$temp];

        $temp = "aff_liste_par_classe_".$num_bas;
        if (isset($_POST[$temp]))  $reg_aff_liste_par_classe = $_POST[$temp]; else  $reg_aff_liste_par_classe = 'n';

        $temp = "aff_affectations_eleves_".$num_bas;
        if (isset($_POST[$temp]))  $reg_aff_affectations_eleves = $_POST[$temp]; else  $reg_aff_affectations_eleves = 'n';

        $temp = "modif_affectations_bas_cpe_".$num_bas;
        if (isset($_POST[$temp])) $reg_modif_affectations_bas_cpe = $_POST[$temp]; else $reg_modif_affectations_bas_cpe = 'n';

        $upd = mysql_query("update bas_bas set
        date_bas = '".$reg_date_bas."',
        date_limite = '".$reg_date_limite."',
        type_bas = '".$reg_type_bas."',
        num_periode = '".$reg_num_periode."',
        close_bas  = '".$reg_close_bas."',
        bas_passee  = '".$reg_bas_passee."',
        aut_insc_eleve  = '".$reg_aut_insc_eleve."',
        active_blocage  = '".$reg_active_blocage."',
        inscription_bas = '".$reg_inscription_bas."',
        qui_inscrit = '".$reg_qui_inscrit."',
        aff_liste_par_classe = '".$reg_aff_liste_par_classe."',
        modif_affectations_bas_cpe = '".$reg_modif_affectations_bas_cpe."',
        aff_affectations_eleves = '".$reg_aff_affectations_eleves."'
        where id_bas = '".$num_bas."'");
        $i++;
    }
    $msg .= "Les modifications ont été enregistrées.<br />";


    $i = 0 ;
    while ($i < $nb_bas) {
        $num_bas = mysql_result($req,$i,'id_bas');
        $temp = "supprime_".$num_bas;
        if (isset($_POST[$temp])) {
            $test = sql_query1("select count(id_bas) from bas_propositions where num_bas = '".$num_bas."'");
            if ($test > 0) {
                $msg .= "Impossible de supprimer l'atelier dont l'identifiant est ".$num_bas." car des propositions d'activité existent pour cet atelier.<br />";
            } else {
                $del = mysql_query("delete from bas_j_professeurs_absences where id_bas = '".$num_bas."'");
                $del = mysql_query("delete from bas_bas  where id_bas = '".$num_bas."'");
                if (!($del)) {
                    $msg .= "L'atelier dont l'identifiant est ".$num_bas." n'a pas pu être supprimé pour une raison inconnue.<br />";
                } else {
                    $msg .= "L'atelier dont l'identifiant est ".$num_bas." a été supprimé.<br />";
                }
            }

        }

        $i++;
    }
}





if (isset($_POST['is_posted2'])) {
    $test = sql_query1("select count(id_bas) from bas_bas where id_bas = '".$_POST['num_bas']."'");
    if ($test == 0) {
        $ins = mysql_query("INSERT INTO bas_bas set
        nom = '".$_POST['nom']."',
        id_bas = '".$_POST['num_bas']."',
        date_bas = 'XX/XX/XXXX',
        date_limite = 'XX/XX/XXXX',
        description_bas = '".$_POST['description']."',
        num_periode = '1',
        close_bas  = 'y',
        bas_passee = 'n',
        aut_insc_eleve = 'n',
        inscription_bas = 'n',
        type_bas = 'n',
        aff_liste_par_classe = 'n',
        modif_affectations_bas_cpe = 'n',
        aff_affectations_eleves = 'n',
        qui_inscrit = 'p'
        ");
        if ($ins) $msg .= "Les modifications ont été enregistrées."; else $msg .= "Problème lors de l'enregistrement.";
        $numero_bas = $_POST['num_bas'];
    } else {
        if ($_POST["nouveau"]=="n") {
        $upd = mysql_query("UPDATE bas_bas set
        nom = '".$_POST['nom']."',
        description_bas = '".$_POST['description']."'
        where id_bas = '".$_POST['num_bas']."'");
        if ($upd) $msg .= "Les modifications ont été enregistrées."; else $msg .= "Problème lors de l'enregistrement.";
        } else {
            $msg .= "Un atelier portant le même numéro existe déjà !";
        }
        $numero_bas = $_POST['num_bas'];
    }

    // Enregistrement des périodes
        $sql = mysql_query("DELETE FROM bas_creneaux WHERE id_bas='".$numero_bas."'");
        $nb = count($_POST['reg_per']);
        $i = 0;
        $n = 1;
        while ($i < $nb) {
            if (trim($_POST['reg_per'][$i]) != "") {
               $sql = mysql_query("insert into bas_creneaux set
               id_bas='".$numero_bas."',
               num_creneau = '".$n."',
               intitule_creneau='".$_POST['reg_per'][$i]."'");
               $n++;
            }
            $i++;
        }
}



//**************** EN-TETE *****************
$titre_page = "Configuration des ".$NomAtelier_pluriel;
require_once("../../lib/header.inc.php");
//**************** FIN EN-TETE *************

echo "<p class=bold>| <a href=\"../../accueil.php\">Retour à la page d'accueil</a>
| <a href=\"./admin_bases.php\">Retour Gestion des bases</a>
| <a href=\"./admin_bas_config.php?action=modif\">Ajouter un atelier</a> |</p>";

if (isset($_GET['action']) and ($_GET['action'] == 'modif')) {
    if (isset($_GET['num_bas'])) {
        $num_bas = $_GET['num_bas'];
        $nom = sql_query1("select nom from bas_bas where id_bas='".$num_bas."'");
        $description = sql_query1("select description_bas from bas_bas where id_bas='".$num_bas."'");
        $sql_creneaux = "select num_creneau, intitule_creneau from bas_creneaux where id_bas='".$num_bas."' order by num_creneau";
        $res_creneaux = mysql_query($sql_creneaux);
        $num_per = mysql_num_rows($res_creneaux);
        $i = 0;
        while ($i < $num_per) {
          $per[$i+1] = mysql_result($res_creneaux,$i,"intitule_creneau");
          $i++;
        }
    } else {
        $per = $per_defaut;
    }

    echo "<form action=\"admin_bas_config.php\" name=\"bas\" method=\"post\">\n";
    echo "Numéro de l'atelier : ";
    if (isset($num_bas)) {
        echo $num_bas."<input type=\"hidden\" name=\"num_bas\" value=\"".$num_bas."\" size=\"20\" />
        <input type=\"hidden\" name=\"nouveau\" value=\"n\" size=\"20\" />";
    } else {
        echo "<input type=\"text\" name=\"num_bas\" value=\"\" size=\"20\" />
        <input type=\"hidden\" name=\"nouveau\" value=\"y\" size=\"20\" />";
    }
    echo "<br />Intitulé de l'atelier : <input type=\"text\" name=\"nom\" ";
    if (isset($nom)) echo " value=\"".$nom."\" ";
    echo " size=\"20\" />";
    echo "<br />Description de l'atelier : <input type=\"text\" name=\"description\" ";
    if (isset($description)) echo " value=\"".$description."\" ";
    echo " size=\"50\" />";
    echo "<H2>Définition des créneaux</H2>";
    $nb_per = count($per);
    // Nombre de select à afficher
    $nb_affiche = max(4,($nb_per+1));
    $i = 0;
    while ($i < $nb_affiche) {
        echo "Créneau N° ".($i+1)." : <input type=\"text\" name=\"reg_per[]\" ";
        if (isset($per[$i+1])) echo " value=\"".$per[$i+1]."\" ";
        echo " size=\"20\" /><br />";
        $i++;
    }


    echo "<input type=\"hidden\" name=\"is_posted2\" value=\"yes\" />";
    echo "<center><input type=\"submit\" value=\"Envoyer\" /></center>";
    echo "</form>\n";

} else {
$req = mysql_query("select * from bas_bas order by nom");
$nb_bas = mysql_num_rows($req);
$i = 0 ;
echo "<form action=\"admin_bas_config.php\" name=\"bas\" method=\"post\">\n";

echo "<table cellpadding=\"3\" border=\"1\">";
echo "<tr><td><span class='style_bas'>&nbsp;</span></td>
<td><span class='style_bas'><b>Date ".$NomAtelier_preposition2.$NomAtelier_singulier."</b></span></td>
<td><span class='style_bas'><b>Date limite<br />collecte des propositions</b></span></td>
<td><span class='style_bas'><b>Période de référence</b></span></td>";
if ($is_LP2I) echo "<td><span class='style_bas'><b>Type</b></span></td>";
echo "<td><span class='style_bas'><b>Collecte<br />des propositions<br />Période close</b></span></td>
<td><span class='style_bas'><b>Inscription/Affectation<br />des élèves par les professeurs</b></span></td>
<td><span class='style_bas'><b>Affichage<br />liste activités</b></span></td>
<td><span class='style_bas'><b>Affichage<br />affectation<br />des élèves</b></span></td>
<td><span class='style_bas'><b>Modification<br />affectation<br />des élèves<br />Par les CPE</b></span></td>
<td><span class='style_bas'><b>Inscription</b></span></td>
<td><span class='style_bas'><b>Activer blocage inscription en cas de surreffectif</b></span></td>
<td><span class='style_bas'><b>Supprimer</b></span></td>
</tr>";
while ($i < $nb_bas) {
    $num_periode = mysql_result($req,$i,'num_periode');
    $nom_bas = mysql_result($req,$i,'nom');
    $num_bas = mysql_result($req,$i,'id_bas');
    $date_bas = mysql_result($req,$i,'date_bas');
    $date_limite = mysql_result($req,$i,'date_limite');
    $description_bas = mysql_result($req,$i,'description_bas');
    $type_bas = mysql_result($req,$i,'type_bas');
    $close_bas = mysql_result($req,$i,'close_bas');
    $bas_passee = mysql_result($req,$i,'bas_passee');
    $aut_insc_eleve = mysql_result($req,$i,'aut_insc_eleve');
    $active_blocage = mysql_result($req,$i,'active_blocage');
    $inscription_bas = mysql_result($req,$i,'inscription_bas');
    $qui_inscrit = mysql_result($req,$i,'qui_inscrit');
    $aff_liste_par_classe = mysql_result($req,$i,'aff_liste_par_classe');
    $aff_affectations_eleves = mysql_result($req,$i,'aff_affectations_eleves');
    $modif_affectations_bas_cpe = mysql_result($req,$i,'modif_affectations_bas_cpe');
    if ($type_bas=='') $type_bas='n';
    echo "<tr><td><b><a href=\"admin_bas_config.php?action=modif&amp;num_bas=$num_bas\">".$nom_bas."</a></b>
    <br />".$description_bas;
    if ($is_LP2I) {
      echo "<br /><input type=\"checkbox\" name=\"bas_passee_".$num_bas."\" value=\"y\" ";
      if ($bas_passee == 'y') echo " checked";
      echo " /> Atelier décompté";
    }
    echo "<br /><input type=\"checkbox\" name=\"aut_insc_eleve_".$num_bas."\" value=\"y\" ";
    if ($aut_insc_eleve == 'y') echo " checked";
    echo " /> Inscription possible par les élèves";
    echo "</td>\n";
    
    
    
    echo "<td><input type=\"text\" name=\"date_bas_".$num_bas."\" value=\"".$date_bas."\" size=\"10\" /></td>\n";
    echo "<td><input type=\"text\" name=\"date_limite_".$num_bas."\" value=\"".$date_limite."\" size=\"10\" /></td>\n";
    echo "<td><select name=\"num_periode_".$num_bas."\" size=\"1\">\n";
    $k = 1;
    while ($k < 4) {
        echo "<option ";
        if ($num_periode == $k) echo " selected ";
        echo ">".$k."</option>\n";
        $k++;
    }
    echo "</select>";
    if ($is_LP2I) {
      echo "<td><table border=\"1\"><tr>";
      echo "<td><input type=\"radio\" name=\"type_bas_".$num_bas."\" value=\"n\" ";
      if ($type_bas == 'n') echo " checked";
      echo " />normal (BAS)</td>";
      echo "<td><input type=\"radio\" name=\"type_bas_".$num_bas."\" value=\"s\" ";
      if ($type_bas == 's') echo " checked";
      echo " />Autre</td>";
      echo "</tr></table>";
      echo "</td>\n";
    }
    echo "<td><center><input type=\"checkbox\" name=\"close_bas_".$num_bas."\" value=\"y\" ";
    if ($close_bas == 'y') echo " checked";
    echo " /></center></td>\n";



    echo "<td>";
    echo "<input type=\"radio\" name=\"inscription_bas_".$num_bas."\" value=\"a\" ";
    if ($inscription_bas == 'a') echo " checked";
    echo " />Affectations<br />";

    echo "<input type=\"radio\" name=\"inscription_bas_".$num_bas."\" value=\"y\" ";
    if ($inscription_bas == 'y') echo " checked";
    echo " />Inscriptions<br />";

    echo "<input type=\"radio\" name=\"inscription_bas_".$num_bas."\" value=\"r\" ";
    if ($inscription_bas == 'r') echo " checked";
    echo " />Inscriptions reméd. uniquement<br />";

    echo "<input type=\"radio\" name=\"inscription_bas_".$num_bas."\" value=\"n\" ";
    if ($inscription_bas == 'n') echo " checked";
    echo " />Inscriptions fermées";

    echo "</td>\n";
    echo "<td><center><input type=\"checkbox\" name=\"aff_liste_par_classe_".$num_bas."\" value=\"y\" ";
    if ($aff_liste_par_classe == 'y') echo " checked";
    echo " /></center></td>\n";
    echo "<td><center><input type=\"checkbox\" name=\"aff_affectations_eleves_".$num_bas."\" value=\"y\" ";
    if ($aff_affectations_eleves == 'y') echo " checked";
    echo " /></center></td>\n";
    echo "<td><center><input type=\"checkbox\" name=\"modif_affectations_bas_cpe_".$num_bas."\" value=\"y\" ";
    if ($modif_affectations_bas_cpe == 'y') echo " checked";
    echo " /></center></td>\n";
    // Qui inscrit
    echo "<td><input type=\"radio\" name=\"qui_inscrit_".$num_bas."\" value=\"p\" ";
    if ($qui_inscrit == 'p') echo " checked";
    echo " />Les professeurs<br />";
    echo "<input type=\"radio\" name=\"qui_inscrit_".$num_bas."\" value=\"s\" ";
    if ($qui_inscrit == 's') echo " checked";
    echo " />La scolarité<br /></td>";
    // Activer blocage inscription en cas de surreffectif
    echo "<td><center><input type=\"checkbox\" name=\"active_blocage_".$num_bas."\" value=\"y\" ";
    if ($active_blocage == 'y') echo " checked";
    echo " /></center></td>\n";
    echo "<td><center><input type=\"checkbox\" name=\"supprime_".$num_bas."\" value=\"y\" /></center></td>\n";

    echo "</tr>";
    $i++;
}
echo "</table>";
echo "<input type=\"hidden\" name=\"is_posted\" value=\"yes\" />";
echo "<center><input type=\"submit\" value=\"Envoyer\" /></center>";
echo "</form>\n";
}
?>
</div>
</body>
</html>