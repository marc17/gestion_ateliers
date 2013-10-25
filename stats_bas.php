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
$nom_script = "mod_plugins/gestion_ateliers/stats_bas.php";
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
// Constitution du tableau $per
$per =  tableau_periode($_GET['numero_bas']);
$nb_per = count($per);


$nom_bas = sql_query1("select nom from bas_bas where id_bas='".$_GET['numero_bas']."'");
echo "<H1 class='gepi'>GEPI - ".$nom_bas." - Statistiques</H1>";

$calldata = mysql_query("SELECT * FROM bas_propositions WHERE (num_bas= '".$_GET['numero_bas']."' and type != 'R' and type != 'D' and statut ='v')");
$nombreligne1 = mysql_num_rows($calldata);
echo "<p>Nombre total de propositions : <b>".$nombreligne1."</b></p>";

$calldata = mysql_query("SELECT * FROM bas_propositions WHERE (num_bas= '".$_GET['numero_bas']."' and debut_final='1' and type != 'R' and type != 'D' and statut ='v')");
$nombreligne2 = mysql_num_rows($calldata);
echo "<p>Nombre total de propositions en première heure <b>: ".$nombreligne2."</b></p>";

if ($nb_per>1) {
  $calldata = mysql_query("SELECT * FROM bas_propositions WHERE (num_bas= '".$_GET['numero_bas']."' and (debut_final = '2' or (debut_final = '1' and (duree = '2' or duree = '3'))) and type != 'R' and type != 'D' and statut ='v')");
  $nombreligne3 = mysql_num_rows($calldata);
  echo "<p>Nombre total de propositions en deuxième heure <b>: ".$nombreligne3."</b></p>";
}

if ($nb_per>2) {
  $calldata = mysql_query("SELECT * FROM bas_propositions WHERE (num_bas= '".$_GET['numero_bas']."' and (debut_final = '3' or (debut_final = '1' and duree = '3') or (debut_final = '2' and duree = '2')) and type != 'R' and type != 'D' and statut ='v')");
  $nombreligne4 = mysql_num_rows($calldata);
  echo "<p>Nombre total de propositions en troisième heure <b>: ".$nombreligne4."</b></p>";
}
$reste = sql_query1("SELECT count(id_bas) FROM bas_propositions WHERE (num_bas= '".$_GET['numero_bas']."' and (debut_final != '1' and debut_final != '2'  and debut_final != '3') and type != 'R' and type != 'D' and statut ='v')");
if ($reste != 0) {
    echo "<p><font color='red'>Nombre total de propositions non encore affectées <b>: ".$reste."</b></font></p>\n";
}

echo "<table border=\"1\" cellpadding=\"3\" cellspacing=\"1\">\n";
echo "<tr><td><b>Public</b></td><td><b>Première heure</b></td>";
if ($nb_per>1)
  echo "<td><b>Deuxième heure</b></td>";
if ($nb_per>2)
  echo "<td><b>Troisième heure</b></td>";
echo "</tr>\n";


// Par niveau - première heure
$n=1;
while ($n<NB_NIVEAUX_FILIERES+1) {
  $flag=0;
  $sql = "select id_bas from bas_propositions where ((";
  foreach($tab_filière[$n]["id"] as $key => $_id){
    if ($flag==1) $sql .= " or ";
    $sql .= "public_".$_id." !='' ";
    $flag=1;
  }
  $sql .=") and
  num_bas = '".$_GET['numero_bas']."' and
  debut_final = '1' and
  type != 'R'
  and type != 'D'
  and statut = 'v'
  )";
// echo $sql."<br />";
  $req= mysql_query($sql);
  $nb_h1_niveau[$n] = mysql_num_rows($req);
  $n++;        
}

// Par niveau - 2ème heure
if ($nb_per>1) {
  $n=1;
  while ($n<NB_NIVEAUX_FILIERES+1) {
    $flag=0;
    $sql = "select id_bas from bas_propositions where ((";
    foreach($tab_filière[$n]["id"] as $key => $_id){
      if ($flag==1) $sql .= " or ";
      $sql .= "public_".$_id." !='' ";
      $flag=1;
    }
    $sql .=") and
    num_bas = '".$_GET['numero_bas']."' and
    (debut_final = '2' or (debut_final = '1' and (duree = '2' or duree = '3'))) and
    type != 'R'
    and type != 'D'
    and statut = 'v'
    )";
    // echo $sql."<br />";
    $req= mysql_query($sql);
    $nb_h2_niveau[$n] = mysql_num_rows($req);
    $n++;        
  }
}

// Par niveau - 3ème heure
if ($nb_per>2) {
  $n=1;
  while ($n<NB_NIVEAUX_FILIERES+1) {
    $flag=0;
    $sql = "select id_bas from bas_propositions where ((";
    foreach($tab_filière[$n]["id"] as $key => $_id){
      if ($flag==1) $sql .= " or ";
      $sql .= "public_".$_id." !='' ";
      $flag=1;
    }
    $sql .=") and
    num_bas = '".$_GET['numero_bas']."' and
    (debut_final = '3' or (debut_final = '1' and duree = '3') or (debut_final = '2' and duree = '2')) and
    type != 'R'
    and type != 'D'
    and statut = 'v'
    )";
    // echo $sql."<br />";
    $req= mysql_query($sql);
    $nb_h3_niveau[$n] = mysql_num_rows($req);
    $n++;        
  }
}

// On écrit le tableau
$n=1;
while ($n<NB_NIVEAUX_FILIERES+1) {
  echo "<tr><td>".$intitule_filiere[$n]."</td><td><b>".$nb_h1_niveau[$n]."</b></td>";
  if ($nb_per>1)
    echo "<td><b>".$nb_h2_niveau[$n]."</b></td>";
  if ($nb_per>2)
    echo "<td><b>".$nb_h3_niveau[$n]."</b></td>";

  echo "</tr>\n";
  $n++;        
}

// Par filière - 1ère heure 
$n=1;
while ($n<NB_NIVEAUX_FILIERES+1) {
  foreach($tab_filière[$n]["id"] as $key => $_id){
    $sql = "select id_bas from bas_propositions where (";
    $sql .= "public_".$_id." !='' ";
    $sql .=" and
  num_bas = '".$_GET['numero_bas']."' and
  debut_final = '1' and
  type != 'R'
  and type != 'D'
  and statut = 'v'
  )";
//    echo $sql."<br /><br />";
    $req= mysql_query($sql);
    $nb_h1_filiere[$_id] = mysql_num_rows($req);
  }

  $n++;        
}

// Par filière - 2ème heure 
if ($nb_per>1) {
  $n=1;
  while ($n<NB_NIVEAUX_FILIERES+1) {
    foreach($tab_filière[$n]["id"] as $key => $_id){
      $sql = "select id_bas from bas_propositions where (";
      $sql .= "public_".$_id." !='' ";
      $sql .=" and
      num_bas = '".$_GET['numero_bas']."' and
      (debut_final = '2' or (debut_final = '1' and (duree = '2' or duree = '3'))) and
      type != 'R'
      and type != 'D'
      and statut = 'v'
      )";
    //    echo $sql."<br /><br />";
      $req= mysql_query($sql);
      $nb_h2_filiere[$_id] = mysql_num_rows($req);
    }
    $n++;        
  }
}

// Par filière - 3ème heure 
if ($nb_per>2) {
  $n=1;
  while ($n<NB_NIVEAUX_FILIERES+1) {
    foreach($tab_filière[$n]["id"] as $key => $_id){
      $sql = "select id_bas from bas_propositions where (";
      $sql .= "public_".$_id." !='' ";
      $sql .=" and
      num_bas = '".$_GET['numero_bas']."' and
      (debut_final = '3' or (debut_final = '1' and duree = '3') or (debut_final = '2' and duree = '2')) and
      type != 'R'
      and type != 'D'
      and statut = 'v'
      )";
    //    echo $sql."<br /><br />";
      $req= mysql_query($sql);
      $nb_h3_filiere[$_id] = mysql_num_rows($req);
    }
    $n++;        
  }
}

// Tableau
$n=1;
while ($n<NB_NIVEAUX_FILIERES+1) {
  foreach($tab_filière[$n]["id"] as $key => $_id){
    $nom_fil=sql_query1("select nom_filiere from bas_filieres where id_filiere='".$_id."'");
    echo "<tr><td>".$nom_fil."</td><td>".$nb_h1_filiere[$_id]."</td>";
    if ($nb_per>1)
      echo "<td>".$nb_h2_filiere[$_id]."</td>";
    if ($nb_per>2)
      echo "<td>".$nb_h3_filiere[$_id]."</td>";

    echo "</tr>\n";
  }
  $n++;
}

echo "</table>";

?>


</body>
</html>