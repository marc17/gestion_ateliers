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
$nom_script = "mod_plugins/gestion_ateliers/admin_toutes_feuilles_presence.php";
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
require_once("../../lib/header.inc.php");
//**************** FIN EN-TETE *************

// Si le numério n'est pas défini, on arrete tout
if (!(isset($numero_bas))) die();
$calldata = mysql_query("SELECT distinct bp.* FROM bas_propositions bp, bas_j_eleves_bas bjeb
WHERE (
bp.num_bas= '".$numero_bas."' and
bp.id_bas = bjeb.id_bas and
bjeb.num_bas = '".$numero_bas."' and
bjeb.num_choix = '0' and
bp.statut = 'v'
) ORDER BY bp.responsable");

$nombreligne = mysql_num_rows($calldata);
if ($nombreligne == 0) {
    echo "<h2>Aucun élève n'a été affecté...</h2></body></html>";
    die();
}
$m = 0;
while ($m < $nombreligne){
    $id_bas = @mysql_result($calldata, $m, "id_bas");
    $eleves = mysql_query("select distinct id_eleve from bas_j_eleves_bas
    where
    num_bas = '".$numero_bas."' and
    id_bas = '".$id_bas."' and
    num_choix = '0'
    order by id_eleve
    ");
    $nb_eleves = mysql_num_rows($eleves);

    // Informations sur la proposition
    $call_bas_info = mysql_query("SELECT * FROM bas_propositions WHERE id_bas='$id_bas'");
    $responsable = mysql_result($call_bas_info, "0", "responsable");
    $test_imprime_feuille = sql_query1("select imprime_feuilles_presence from bas_imprime_feuilles_presence
    where login = '".$responsable."'");
    // Modif temporaire
    $test_imprime_feuille = 'y';

    if ($test_imprime_feuille == 'y') {

    $titre = mysql_result($call_bas_info, "0", "titre");
    $id_prop = mysql_result($call_bas_info, "0", "id_prop");


    $civilite = sql_query1("select civilite from utilisateurs where login = '".$responsable."'");
    $nom_prof = sql_query1("select nom from utilisateurs where login = '".$responsable."'");

    $matiere = mysql_result($call_bas_info, "0", "id_matiere");
    $nom_matiere = sql_query1("select nom_complet from matieres where matiere = '".$matiere."'");

    $salle = mysql_result($call_bas_info, "0", "salle");
    $duree = mysql_result($call_bas_info, "0", "duree");
    $debut_final = mysql_result($call_bas_info, "0", "debut_final");


    // données sur le bas
    $description_bas = sql_query1("select description_bas from bas_bas where id_bas='".$numero_bas."'");
    $num_periode = sql_query1("select num_periode from bas_bas where id_bas='".$numero_bas."'");
    $nom_bas = sql_query1("select nom from bas_bas where id_bas='".$numero_bas."'");
    // Constitution du tableau $per
    $per =  tableau_periode($numero_bas);

    $type_bas = sql_query1("select type_bas from bas_bas where id_bas='".$numero_bas."'");
    if ($type_bas == "s") {
        echo "<p class='grand'>".$nom_bas." - ".$id_prop." - ";
        echo "Horaire : ";
        $k = $debut_final;
        while ($k < $debut_final+$duree) {
            echo "[".$per[$k]."]";
            if ($k < $debut_final+$duree-1) echo " - ";
            $k++;
        }
        echo " - Salle : ".$salle;
    } else {
        echo "<p class='grand'>".$nom_bas." - ".$id_prop." - ".$per[$debut_final]." - Durée : ".$duree." seq. - Salle : ".$salle;
    }
    echo "<br />".$titre." <br />Animateur ou responsable : ";
    if ($nom_prof != "-1")
        echo $civilite." ".$nom_prof;
    else
        echo $responsable;
    if ($nom_matiere != -1) echo " (".$nom_matiere.")";
    echo " -  ".$nb_eleves." élèves inscrits</p>";
    $i = 0;

    echo "<table border=\"1\" width=\"80%\" cellpadding=\"2\"><tr>";
    echo "<td width=\"30%\"><span class='style_bas'><b>Nom prénom</b></span></td><td width=\"20%\"><span class='style_bas'><b>Classe</b></span></td><td width=\"50%\"><span class='style_bas'><b>Commentaire (présent/absent/retard, ...)</b></span></td></tr>";
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
        $classe_eleve = @mysql_result($classe,0,'classe');
        $id_classe = @mysql_result($classe,0,'id');
        echo "<tr><td><span class='style_bas'>".$nom_eleve." ".$prenom_eleve."</span></td>
        <td><span class='style_bas'>".$classe_eleve."&nbsp;</span></td>
        <td><span class='style_bas'>&nbsp;</span></td>";


        echo "</tr>";
        $i++;
    }
    echo "</table>";
    echo "<p>Prière de rapporter ce document signé, au service Vie Scolaire dès que possible.</p>";
    echo "<center><p>";
    if ($nom_prof != "-1")
        echo $civilite." ".$nom_prof;
    else
        echo $responsable;

    echo " (Signature)</p></center>";
    echo "<p class='saut'></p>";
    }
    $m++;
}

?>
</body>
</html>