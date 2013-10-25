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
$nom_script = "mod_plugins/gestion_ateliers/admin_stats2.php";
if (!checkAccess_Plugin($nom_script)) {
    header("Location: ../../logout.php?auto=1");
    die();
}
// On vérifie que l'utilisateur a les droits spécifiques pour accéder à ce script
if (!calcul_autorisation_gestion_ateliers($_SESSION['login'],$nom_script)){
    header("Location: ../../logout.php?auto=1");
    die();
}

//**************** EN-TETE *****************
require_once("../../lib/header.inc.php");
//**************** FIN EN-TETE *************

echo "<table width=\"100%\" border=\"1\">\n";
echo "<tr>";
echo "<td><b>Numéro ".$NomAtelier_preposition2.$NomAtelier_singulier."</b></td>";
echo "<td><b>Identifiant de la proposition</b></td>";
echo "<td><b>Type</b></td>";
echo "<td><b>Intitulé</b></td>";
echo "<td><b>Précisions</b></td>";
echo "<td><b>Responsable</b></td>";
echo "<td><b>Co-responsable</b></td>";
echo "<td><b>Identifiant Matière</b></td>";
echo "<td><b>Début séquence (numéro créneau)</b></td>";
echo "<td><b>Durée (nb. séquences)</b></td>";

    $k=1;
    while ($k<NB_FILIERES+1) {
      $nom_fil[$k]=sql_query1("select nom_filiere from bas_filieres where id_filiere='".$k."'");
      echo "<td><span class='small'>".$nom_fil[$k]."</span></td>\n";
      $k++;
    }
echo "<td><b>Statut</b></td>";
echo "<td><b>Nb. de demandes en 1er choix</b></td>";
echo "<td><b>Nb. de demandes en 2ème choix</b></td>";
echo "<td><b>Nb. Max souhaité</b></td>";
echo "<td><b>Nb. d'élèves</b></td>";
echo "</tr>";
$calldata = mysql_query("SELECT * FROM bas_propositions  order by num_bas,id_bas,id_prop");
$nombreligne = mysql_num_rows($calldata);



$i = 0;
while($i < $nombreligne){
  $num_bas = mysql_result($calldata,$i,"num_bas");
  $id_bas = mysql_result($calldata,$i,"id_bas");
  $type = mysql_result($calldata,$i,"type");
  $titre = mysql_result($calldata,$i,"titre");
  $precisions = mysql_result($calldata,$i,"precisions");
  $responsable = mysql_result($calldata,$i,"responsable");
  $coresponsable = mysql_result($calldata,$i,"coresponsable");
  $id_matiere = mysql_result($calldata,$i,"id_matiere");
//  $proprietaire = mysql_result($calldata,$i,"proprietaire");
  $nb_max = mysql_result($calldata,$i,"nb_max");
//  $salle = mysql_result($calldata,$i,"salle");
  $duree = mysql_result($calldata,$i,"duree");
  $k=1;
  while ($k<NB_FILIERES+1) {
    $nom_fil[$k]=sql_query1("select nom_filiere from bas_filieres where id_filiere='".$k."'");
    $temp = "public_".$k;
    $$temp = mysql_result($calldata, $i, "public_".$k);
    if ($$temp == "") $$temp = "&nbsp;" ; else $$temp=$nom_fil[$k];
    $k++;
  }
  $id_prop = mysql_result($calldata,$i,"id_prop");
  $debut_sequence = mysql_result($calldata,$i,"debut_sequence");
  $salle_final = mysql_result($calldata,$i,"salle_final");
  $debut_final = mysql_result($calldata,$i,"debut_final");
  $statut = mysql_result($calldata,$i,"statut");

  $nb_eleves_final = sql_query1("select count(id_eleve) from bas_j_eleves_bas where id_bas='".$id_bas."' and num_bas='".$num_bas."' and num_choix='0'");
  $nb_eleves_final1 = sql_query1("select count(id_eleve) from bas_j_eleves_bas where id_bas='".$id_bas."' and num_bas='".$num_bas."' and num_choix='1'");
  $nb_eleves_final2 = sql_query1("select count(id_eleve) from bas_j_eleves_bas where id_bas='".$id_bas."' and num_bas='".$num_bas."' and num_choix='2'");

  echo "<tr>";
  echo "<td>$num_bas</td>";
//  echo "<td>$id_bas</td>";
  echo "<td>$id_prop</td>";
  echo "<td>$type</td>";
  echo "<td>$titre</td>";
  echo "<td>$precisions</td>";
  echo "<td>$responsable</td>";
  echo "<td>$coresponsable</td>";
  echo "<td>$id_matiere</td>";
//  echo "<td>$proprietaire</td>";
//  echo "<td>$salle</td>";
  echo "<td>$debut_final</td>";
  echo "<td>$duree</td>";
  $k=1;
  while ($k<NB_FILIERES+1) {
    $temp = "public_".$k;
    echo "<td><span class='small'>".$$temp."</span></td>\n";
    $k++;
  }
//  echo "<td>$salle_final</td>";
if ($statut=='a')
    $statut = "Annulé";
else
    $statut = "";
  echo "<td>$statut</td>";
  echo "<td>$nb_eleves_final1</td>";
  echo "<td>$nb_eleves_final2</td>";
  echo "<td>$nb_max</td>";
  echo "<td>$nb_eleves_final</td>";


  echo "</tr>\n";
  $i++;
} // while
echo "</table>";



?>


</body>
</html>