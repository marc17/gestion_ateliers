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
$nom_script = "mod_plugins/gestion_ateliers/admin_stats.php";
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
if (!isset($_SESSION['orderby'])) {$_SESSION['orderby'] = "par_bas";}
$_SESSION['orderby'] = isset($_POST['orderby']) ? $_POST['orderby'] : (isset($_GET['orderby']) ? $_GET['orderby'] : $_SESSION['orderby']);
$orderby = $_SESSION['orderby'];

$numero_bas = isset($_POST['numero_bas']) ? $_POST['numero_bas'] : (isset($_GET['numero_bas']) ? $_GET['numero_bas'] : -1);
if ($orderby =="par_bas") $numero_bas = -1;


//**************** EN-TETE *****************
$titre_page = "Statistiques";
require_once("../../lib/header.inc.php");
//**************** FIN EN-TETE *************
    echo "<p class=bold>| <a href=\"../../accueil.php\">Retour à la page d'accueil</a> |<a href=\"./admin_index.php\"> Accueil Module Atelier</a> |\n";
if ($orderby !="par_bas") {
    echo "<form name=\"numbas\" method=\"post\" action=\"admin_stats.php\">\n<table><tr>";
    echo "<td>Etablir des statistiques sur : </td>";
    echo "<td><select name=\"numero_bas\" size=\"1\">\n";
    echo "<option value=-1";
    if ($numero_bas == -1) echo " selected";
    echo ">toutes les séances</option>\n";

    $req_bas = mysql_query("select * from bas_bas order by nom");
    $nb_bas = mysql_num_rows($req_bas);
    $i = 0 ;
    while ($i < $nb_bas) {
        $num_bas = mysql_result($req_bas,$i,'id_bas');
        $nom_bas = mysql_result($req_bas,$i,'nom'); 
        echo "<option value=".$num_bas."";
        if ($numero_bas == $num_bas) echo " selected";
        echo ">".$nom_bas."</option>\n";
        $i++;
    }
    echo "</select></td>";
    echo "<td><input type=\"submit\" name=\"Envoyer\" /></td>";
    echo "</tr></table></form>\n";
}

if ($numero_bas == -1)
    echo "<center><h1>Statistiques sur tous les ".$NomAtelier_pluriel."</h1></center>\n";
else
    echo "<center><h1>Statistiques ".$NomAtelier_preposition2.$NomAtelier_singulier." N° ".$numero_bas."</h1></center>\n";

echo "<center><table border=\"1\" cellpadding=\"5\"><tr>";
if ($orderby =="par_bas")
    echo "<td><b>&gt;&gt; Statistiques par ".$NomAtelier_singulier." &lt;&lt;</b></td>";
else
    echo "<td><a href='admin_stats.php?orderby=par_bas&amp;numero_bas=".$numero_bas."'>Statistiques par ".$NomAtelier_singulier."</a></td>";
if ($orderby =="m.nom_complet,u.nom,u.prenom")
    echo "<td><b>&gt;&gt; Statistiques par matières &lt;&lt;</b></td>";
else
    echo "<td><a href='admin_stats.php?orderby=m.nom_complet,u.nom,u.prenom&amp;numero_bas=".$numero_bas."'>Statistiques par matières</a></td>";
if ($orderby =="u.nom,u.prenom")
    echo "<td><b>&gt;&gt; Statistiques par professeurs &lt;&lt;</b></td>";
else
    echo "<td><a href='admin_stats.php?orderby=u.nom,u.prenom&amp;numero_bas=".$numero_bas."'>Statistiques par professeurs</a></td>";
echo "</tr></table></center><br />";

// Nombre total d'heures
$nb_heures_matieres = sql_query1("select sum(nb_heures) from bas_matieres");

// Nombre total d'heures d'atelier
$req = "SELECT SUM(duree) FROM bas_propositions ";
if ($numero_bas != -1) $req .=" where num_bas = '".$numero_bas."'";
$nb_heures_BAS = sql_query1($req);

// Statistiques par matière ou par professeurs
if (($orderby == "m.nom_complet,u.nom,u.prenom") or ($orderby == "u.nom,u.prenom")) {
/*
$req = "SELECT distinct u.login, u.nom, u.prenom, b.id_matiere, m.nom_complet
FROM utilisateurs u
left join bas_j_matieres_profs b ON b.id_professeur = u.login
left join bas_matieres m ON m.matiere = b.id_matiere
where
u.etat = 'actif' and (u.statut='professeur' or u.statut = 'cpe')
 ORDER BY $orderby";
*/
$req = "SELECT distinct u.login, u.nom, u.prenom, b.id_matiere, m.nom_complet
FROM utilisateurs u, bas_j_matieres_profs b, bas_matieres m
where (
b.id_professeur = u.login and
m.matiere = b.id_matiere and
u.etat = 'actif'
) ORDER BY $orderby";


$calldata = mysql_query($req);

echo "<table border=\"1\" cellpadding=\"5\" width=\"100%\">";
echo "<tr><td><b><a href='admin_stats.php?orderby=u.nom,u.prenom&amp;numero_bas=".$numero_bas."'>Nom Prénom</a></b></td>";
echo "<td><b>Nb. d'heures ".$NomAtelier_preposition.$NomAtelier_pluriel." proposées par prof.<br />par matière</b></td>";
if  ($orderby == "m.nom_complet,u.nom,u.prenom") {
    echo "<td><b>Nb. Total d'heures ".$NomAtelier_preposition.$NomAtelier_pluriel." par matière</b></td>";
}
if  ($orderby == "u.nom,u.prenom") {
    echo "<td><b>Nb. Total d'heures ".$NomAtelier_preposition.$NomAtelier_singulier." proposées par prof.</b></td>";
}
echo "</tr>";
$nombreligne = mysql_num_rows($calldata);
$i = 0;
while ($i < $nombreligne){
    $user_nom = mysql_result($calldata, $i, "nom");
    $user_prenom = mysql_result($calldata, $i, "prenom");
    if ($i > 0) {
        $user_matiere_prec = $user_matiere;
        $user_login_prec = $user_login;
    } else {
        $user_matiere_prec = "";
        $user_login_prec = "";
    }
    if ($i < $nombreligne-1) {
        $user_login_suiv = mysql_result($calldata, $i+1, "login");
    } else {
        $user_login_suiv = "";
    }

    $user_login = mysql_result($calldata, $i, "login");
    $user_matiere = mysql_result($calldata, $i, "id_matiere");
    $user_nom_matiere = mysql_result($calldata, $i, "nom_complet");

    // Nombre total d'heures proposées par prof et par matière
    $req = "SELECT SUM(duree)
    FROM bas_propositions
    where (
    (responsable = '".$user_login."' or
    coresponsable = '".$user_login."') and";
    if ($numero_bas != -1) $req .=" num_bas = '".$numero_bas."' and ";
    $req .= " id_matiere = '".$user_matiere."'
    )";
    $nb_prop_par_prof_par_mat = sql_query1($req);
    if ($nb_prop_par_prof_par_mat == -1) $nb_prop_par_prof_par_mat = 0;

    // Nombre total d'heures annulées par prof et par matière
    $req = "SELECT SUM(duree)
    FROM bas_propositions
    where (
    (responsable = '".$user_login."' or
    coresponsable = '".$user_login."') and";
    if ($numero_bas != -1) $req .=" num_bas = '".$numero_bas."' and ";
    $req .= " id_matiere = '".$user_matiere."' and
    statut = 'a'
    )";
    $nb_prop_par_prof_par_mat_a = sql_query1($req);
    if ($nb_prop_par_prof_par_mat_a == -1) $nb_prop_par_prof_par_mat_a = 0;

    // Nombre total d'heures proposées toutes matières confondues
    $req = "SELECT SUM(duree)
    FROM bas_propositions
    where (
    (responsable = '".$user_login."' or coresponsable = '".$user_login."')";
    if ($numero_bas != -1) $req .=" and num_bas = '".$numero_bas."'";
    $req .= ")";
    $nb_prop_par_prof = sql_query1($req);
    if ($nb_prop_par_prof == -1) $nb_prop_par_prof = 0;

    // Construction de la variable $temp
    if (!isset($flag)) $flag = 1;
    if  ($orderby == "u.nom,u.prenom") {
        // Il s'agit d'un nouveau prof, on réinitialise
        if ($flag == 1) {
            $temp = '';
            $total_mat = 0;
        }
        // On construit la variable à afficher plus bas
        // Nom de la matière et nombre d'heures dans cette matière
        if ($flag != 1) $temp .= "<br />";
        $temp .= "<img src=\"./images/ok.png\" alt=\"ok\" width=\"10\" height=\"10\"  border=\"0\" /> ";
        $temp .= $user_nom_matiere." : <b>".$nb_prop_par_prof_par_mat." h </b>";
        // Eventuellement, nombre d'heures annulées dans la matière
        if ($nb_prop_par_prof_par_mat_a != 0) $temp .= " dont ".$nb_prop_par_prof_par_mat_a." h annulée(s)";
        // Cumul des heures "matières"
        $total_mat += $nb_prop_par_prof_par_mat;
        if ($user_login_suiv !=$user_login) {
            // Si, dans la prochaine boucle, on passe à un nouveau prof :

            // S'il existe des heures effectuées dans d'autres matières que celles affectées à l'utilisateur,
            // c'est le moment de les afficher
            if  ($nb_prop_par_prof > $total_mat) {
                $temp .= "<br /><img src=\"./images/ok.png\" alt=\"ok\" width=\"10\" height=\"10\"  border=\"0\" /> ";
                $temp .= "Autre(s) discipline(s) : <b>".($nb_prop_par_prof-$nb_prop_par_prof_par_mat)." h </b>";
            }
            // On indique qu'il faudra réinitialiser dans la prochaine boucle
            $flag = 1;
        } else
            $flag = 0;
    } else {
        // On construit la variable à afficher plus bas
        // Nom de la matière et nombre d'heures dans cette matière
        $temp = "<img src=\"./images/ok.png\" alt=\"ok\" width=\"10\" height=\"10\"  border=\"0\" /> ";
        $temp .= $user_nom_matiere." : <b>".$nb_prop_par_prof_par_mat." h </b>";
        // Eventuellement, nombre d'heures annulées dans la matière
        if ($nb_prop_par_prof_par_mat_a != 0) $temp .= " dont ".$nb_prop_par_prof_par_mat_a." h annulée(s)";
        // S'il existe des heures effectuées dans d'autres matières que celles affectées à l'utilisateur,
        // c'est le moment de les afficher
        if  ($nb_prop_par_prof > $nb_prop_par_prof_par_mat) {
            $temp .= "<br /><img src=\"./images/ok.png\" alt=\"ok\"  width=\"10\" height=\"10\"  border=\"0\" /> ";
            $temp .= "<i>Autre(s) discipline(s) : <b>".($nb_prop_par_prof-$nb_prop_par_prof_par_mat)."</b> h </i>";
        }
    }

    // Groupement par matière
    if  ($orderby == "m.nom_complet,u.nom,u.prenom") {
         if ($user_matiere_prec !=$user_matiere) {
             // Calcul du nombre de ligne pour le rowspan
             $nb_mat = sql_query1("SELECT COUNT(id_matiere) as mat
             FROM bas_j_matieres_profs, bas_matieres
             where id_matiere='".$user_matiere."' and
             bas_matieres.matiere =bas_j_matieres_profs.id_matiere
             GROUP BY id_matiere
             ORDER BY nom_complet");

             // Nombre d'heures de la matières
             $nb_heures = sql_query1("select nb_heures from bas_matieres where matiere = '".$user_matiere."'");


             // Nombre total d'heures dans la matières
             $req = "SELECT SUM(duree)
             FROM bas_propositions
             where (
             id_matiere = '".$user_matiere."'";
             if ($numero_bas != -1) $req .=" and num_bas = '".$numero_bas."'";
             $req .= ")";
             $nb_prop_par_mat = sql_query1($req);
             if ($nb_prop_par_mat == -1) $nb_prop_par_mat = 0;

             // Nombre total d'heures annulées dans la matières
             $req = "SELECT SUM(duree)
             FROM bas_propositions
             where (
             id_matiere = '".$user_matiere."' and statut = 'a'";
             if ($numero_bas != -1) $req .=" and num_bas = '".$numero_bas."'";
             $req .= ")";
             $nb_prop_par_mat_a = sql_query1($req);
             if ($nb_prop_par_mat_a == -1) $nb_prop_par_mat_a = 0;

             // Répartition par type
             $sql = "SELECT sum(duree) as nb_type, type FROM bas_propositions
             where (
             id_matiere = '".$user_matiere."'";
             if ($numero_bas != -1) $sql .=" and num_bas = '".$numero_bas."'";
             $sql .= ") GROUP BY type ORDER BY type DESC";
             $req = mysql_query($sql);
             $nbtype = mysql_num_rows($req);
             $k = 0;
             $aff_type = "";
             while ($k < $nbtype) {
                 $temp2 = mysql_result($req,$k,'type');
                 if ($temp2 == "S") $titre_type = "Soutien";
                 else if ($temp2 == "A") $titre_type = "Approf.";
                 else if ($temp2 == "D") $titre_type = "Pub. dés.";
                 else if ($temp2 == "R") $titre_type = "Reméd.";
                 if ($k > 0) $aff_type .= "<br />";
                 $aff_type .=  "<img src=\"./images/ok.png\" alt=\"ok\" width=\"10\" height=\"10\"  border=\"0\" /> ";
                 $aff_type .= $titre_type." : ".mysql_result($req,$k,'nb_type')." (".round(mysql_result($req,$k,'nb_type')*100/$nb_prop_par_mat,0)." %)";
                 $k++;
             }

         }
    }
    // Groupement par utilisateur
    if  ($orderby == "u.nom,u.prenom") {
         if ($user_login_suiv !=$user_login) {
             // Calcul du coefficient équivalent temp plein
             $service = sql_query1("select service from bas_utilisateurs where login='".$user_login."'");
             $h_second = sql_query1("select heures_secondaire from bas_utilisateurs where login='".$user_login."'");
             if (($service != -1) and ($h_second != -1)) {
                 $coef = $h_second * 100 / $service;
             } else
                 $coef = -1;

             // Heures équivalent temps complet
             if (($coef != 0) and ($coef != -1))
                 $nb_prop_par_prof_etc = round ($nb_prop_par_prof * 100 / $coef,0);
             else
                 $nb_prop_par_prof_etc = "-1";  
             // Nombre total d'heures annulées
             $req = "SELECT SUM(duree)
             FROM bas_propositions
             where (
             (responsable = '".$user_login."' or coresponsable = '".$user_login."') and statut = 'a'";
             if ($numero_bas != -1) $req .=" and num_bas = '".$numero_bas."'";
             $req .= ")";
             $nb_prop_par_prof_a = sql_query1($req);
             if ($nb_prop_par_prof_a == -1) $nb_prop_par_prof_a = 0;
         }
    }
    // Affichage
    // Dans le cas des stats par utilisateurs, on affiche uniquement les lignes dans le cas ou l'utilisateur change dans la prochaine boucle
    // Dans le cas des stats par matière, on affiche toutes les lignes
    if  ((($orderby == "u.nom,u.prenom") and ($user_login_suiv !=$user_login)) or ($orderby != "u.nom,u.prenom"))  {
        // Nom et prenom
        echo "<tr><td>".$user_nom." ".$user_prenom."</td>\n";
        //Nb. d'heures proposées par prof. dans la matière enseignée
        echo "<td >".$temp."</td>";
        // Groupement par matière
        if  ($orderby == "m.nom_complet,u.nom,u.prenom") {
            if ($user_matiere_prec !=$user_matiere) {
                echo "<td rowspan=\"".$nb_mat."\">";
                echo "<table><tr><td>";
                  echo "<b>".$user_nom_matiere."</b>";
                  if ($nb_heures != 0) {
                      $temp = round(($nb_heures*100/$nb_heures_matieres),0);
                      if ($temp == 0) $temp = round(($nb_heures*100/$nb_heures_matieres),1);
                      echo " (<b>".$temp." %</b> du volume horaire global)";
                  }
                  echo " :";
                  echo "<br /><img src=\"./images/ok.png\" alt=\"ok\" width=\"10\" height=\"10\"  border=\"0\" /> <b>".$nb_prop_par_mat." h </b> ".$NomAtelier_preposition.$NomAtelier_pluriel;
                  if ($nb_prop_par_mat != 0) {
                      $temp = round(($nb_prop_par_mat*100/$nb_heures_BAS),0);
                      if ($temp == 0) $temp = round(($nb_prop_par_mat*100/$nb_heures_BAS),1);
                      echo " soit <b>".$temp." %</b> du volume global des ".$NomAtelier_pluriel;
                  }
                  if ($nb_prop_par_mat_a != 0)
                      echo "<br /> dont ".$nb_prop_par_mat_a." h annulée(s)";
                echo "</td><td>&nbsp;&nbsp;&nbsp;</td><td>";
                   echo $aff_type;
                echo "</td></tr></table>";
                echo "</td>\n";
             }
        }
        // Groupement par utilisateur
        if  ($orderby == "u.nom,u.prenom") {
            echo "<td><b>".$user_nom." ".$user_prenom."</b> : ";
            echo "<br /><img src=\"./images/ok.png\" alt=\"ok\" width=\"10\" height=\"10\"  border=\"0\" /> ";
            echo "Total heures proposées : <b>".$nb_prop_par_prof." h </b>";
            if ($nb_prop_par_prof_a != 0)
                echo " dont ".$nb_prop_par_prof_a." annulée(s)";
            if ($coef != -1) {
                echo "<br /><img src=\"./images/ok.png\" alt=\"ok\" width=\"10\" height=\"10\"  border=\"0\" /> ";
                echo "Poucentage du service dans l'ens. sec. : <b>".(round ($coef,0))." %.</b>";
            }
//            echo "<br /><img src=\"./images/ok.png\" alt=\"ok\" width=\"10\" height=\"10\"  border=\"0\" /> ";
//            if ($nb_prop_par_prof_etc != "-1")
//                echo "<font size=\"+1\">Equivalent temps complet : <b>".$nb_prop_par_prof_etc." h.</b></font>";
//            else
//                echo "Equiv. temps complet : <b>non significatif</b>";
            echo "</td>";
    }
    echo "</tr>";
    }
    $i++;
}
echo "</table>";

}

// Statistiques par bas
if ($orderby == "par_bas") {

$req = mysql_query("select * from bas_bas");
$nb_bas = mysql_num_rows($req);
$i = 0 ;
echo "<table cellpadding=\"3\" border=\"1\">";
echo "<tr><td><span class='style_bas'>&nbsp;</span></td>
<td><center><span class='style_bas'><b>Date ".$NomAtelier_preposition2.$NomAtelier_singulier."</b></span></center></td>
<td><center><span class='style_bas'><b>Nombre<br />propositions</b></span></center></td>
<td><center><span class='style_bas'><b>Répartition<br />des propositions<br />par type</b></span></center></td>
<td><center><span class='style_bas'><b>Répartition<br />des propositions<br />par durée</b></span></center></td>
<td><center><span class='style_bas'><b>Répartition<br />des propositions<br />par début d'activité</b></span></center></td>
<td><center><span class='style_bas'><b>Répartition<br />des propositions<br />par public</b></span></center></td>
</tr>";
while ($i < $nb_bas) {
    $num_bas = mysql_result($req,$i,'id_bas');
    $date_bas = mysql_result($req,$i,'date_bas');
    $description_bas = mysql_result($req,$i,'description_bas');

    // tout type
    $type_all = sql_query1("SELECT count(type) FROM bas_propositions
    where num_bas='".$num_bas."'");

    $type_all_heures = sql_query1("SELECT sum(duree) FROM bas_propositions
    where num_bas='".$num_bas."'");

    $type_all_a = sql_query1("SELECT count(type) FROM bas_propositions
    where num_bas='".$num_bas."' and statut = 'a'");

    if ($type_all != 0) {
        // type S
        $type_S = sql_query1("SELECT sum(duree) FROM bas_propositions
        where num_bas='".$num_bas."' and type = 'S' GROUP BY type");
        if ($type_S != -1)
            $type_S = "Soutien : <b>".$type_S." h</b> soit ".round($type_S *100 /$type_all_heures,0)." %";
        else
           $type_S = "Soutien : 0";
        // type A
        $type_A = sql_query1("SELECT sum(duree) FROM bas_propositions
        where num_bas='".$num_bas."' and type = 'A' GROUP BY type");
        if ($type_A != -1)
            $type_A = "Approf. : <b>".$type_A." h</b> soit ".round($type_A *100 /$type_all_heures,0)." %";
        else
            $type_A = "Approf. : 0";

        // type R
        $type_R = sql_query1("SELECT sum(duree) FROM bas_propositions
        where num_bas='".$num_bas."' and type = 'R' GROUP BY type");
        if ($type_R != -1)
            $type_R = "Reméd. : <b>".$type_R." h</b> soit ".round($type_R *100 /$type_all_heures,0)." %";
        else
            $type_R = "Reméd. : 0" ;

        // type D
        $type_D = sql_query1("SELECT sum(duree) FROM bas_propositions
        where num_bas='".$num_bas."' and type = 'D' GROUP BY type");
        if ($type_D != -1)
            $type_D = "Pub. dés. <b>: ".$type_D." h</b> soit ".round($type_D *100 /$type_all_heures,0)." %";
        else
            $type_D = "Pub. dés. : 0";

        $aff_type = $type_S."<br />".$type_R."<br />".$type_A."<br />".$type_D;
    } else {
        $aff_type = "";
    }
    // Durée
    $k = 1;
    $aff_duree = "";
    while ($k < 4) {
        $duree_bas = sql_query1("SELECT count(duree) FROM bas_propositions where num_bas='".$num_bas."' and duree='".$k."'");
        if ($k > 1) $aff_duree .= "<br />";
        $aff_duree .= "Durée ".$k." h : ".$duree_bas." prop.";
        $k++;
    }
    // Heure de début
    $k = 1;
    $aff_debut = "";
    while ($k < 4) {
        $debut_bas = sql_query1("SELECT count(debut_final) FROM bas_propositions where num_bas='".$num_bas."' and debut_final='".$k."'");
        if ($k > 1) $aff_debut .= "<br />";
        $aff_debut .= "Créneau ".$k." : ".$debut_bas." prop.";
        $k++;
    }
    // Public
    $reqpub1="(";
    $flag=0;
    foreach($tab_filière[1]["id"] as $key2 => $_id2){
        if ($flag=="1") $reqpub1 .=" OR ";
        $reqpub1.="public_".$_id2."='y'";
        $flag=1;
    }
    $reqpub1.=")";
    $reqpub2="(";
    $flag=0;
    foreach($tab_filière[2]["id"] as $key2 => $_id2){
        if ($flag=="1") $reqpub2 .=" OR ";
        $reqpub2.="public_".$_id2."='y'";
        $flag=1;
    }
    $reqpub2.=")";
    $reqpub3="(";
    $flag=0;
    foreach($tab_filière[3]["id"] as $key2 => $_id2){
        if ($flag=="1") $reqpub3 .=" OR ";
        $reqpub3.="public_".$_id2."='y'";
        $flag=1;
    }
    $reqpub3.=")";


    $public_sec_bas = sql_query1("SELECT count(id_bas) FROM bas_propositions
    where num_bas='".$num_bas."' and ".$reqpub1);
    $public_prems_bas = sql_query1("SELECT count(id_bas) FROM bas_propositions
    where num_bas='".$num_bas."' and ".$reqpub2);
    $public_terms_bas = sql_query1("SELECT count(id_bas) FROM bas_propositions
    where num_bas='".$num_bas."' and ".$reqpub3);



    // Affichage
    echo "<tr><td><b>".ucfirst($NomAtelier_singulier)." N° ".$num_bas." </b></td>\n";
    echo "<td><center>".$date_bas."<br />".$description_bas;
    echo "</center></td>\n";
    if ($type_all > 0) {
        echo "<td><center>Total : <b>".$type_all."</b> propositions";
        if ($type_all_a == 1) echo "<br />(1 annulée)";
        if ($type_all_a > 1) echo "<br />(".$type_all_a." annulées)";
        echo "<br /><b>".$type_all_heures."</b> heures";
        echo "</center></td>\n";
        echo "<td>".$aff_type."</td>\n";
        echo "<td>".$aff_duree."</td>\n";
        echo "<td>".$aff_debut."</td>\n";
        echo "<td>";
        echo "Secondes : ".$public_sec_bas;
        echo "<br />Prem : ".$public_prems_bas;
        echo "<br />Term : ".$public_terms_bas;
        echo "</td>\n";

    } else {
        echo "<td>&nbsp;</td>\n";
        echo "<td>&nbsp;</td>\n";
        echo "<td>&nbsp;</td>\n";
        echo "<td>&nbsp;</td>\n";
        echo "<td>&nbsp;</td>\n";
    }

    echo "</tr>";
    $i++;
}
echo "</table>";
}
?>
</body>
</html>