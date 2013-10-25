<?php
// Initialisations files
$niveau_arbo = 2;require_once("../../lib/initialisations.inc.php");

/*
// Resume session
$resultat_session = $session_gepi->security_check();
if ($resultat_session == '0') {
   header("Location: ../../logout.php?auto=1");
   die();
};
*/

//**************** EN-TETE *****************
require_once("../../lib/header.inc.php");
//**************** FIN EN-TETE *************

$calldata = mysql_query("SELECT * FROM bas_propositions  order by num_bas,id_bas,id_prop");
$nombreligne = mysql_num_rows($calldata);
echo "<table width=\"100%\" border=\"1\">\n";
echo "<tr>";
echo "<td><b>Numéro de l'atelier</b></td>";
echo "<td><b>Identifiant de la proposition</b></td>";
echo "<td><b>Type</b></td>";
echo "<td><b>Intitulé</b></td>";
echo "<td><b>Précisions</b></td>";
echo "<td><b>Responsable</b></td>";
echo "<td><b>Co-responsable</b></td>";
echo "<td><b>Identifiant Matière</b></td>";
echo "<td><b>Début séquence (numéro créneau)</b></td>";
echo "<td><b>Durée (nb. séquences)</b></td>";

echo "<td><b>2D1</b></td>";
echo "<td><b>2D2</b></td>";
echo "<td><b>2D3</b></td>";
echo "<td><b>2D4</b></td>";
echo "<td><b>2-C</b></td>";
echo "<td><b>1-L</b></td>";
echo "<td><b>1-S1</b></td>";
echo "<td><b>1-S2</b></td>";
echo "<td><b>1-ES3</b></td>";
echo "<td><b>1-STI</b></td>";
echo "<td><b>TL</b></td>";
echo "<td><b>TS1</b></td>";
echo "<td><b>TS2</b></td>";
echo "<td><b>TES3</b></td>";
echo "<td><b>TSTI</b></td>";
echo "<td><b>Statut</b></td>";
echo "<td><b>Nb. de demandes en 1er choix</b></td>";
echo "<td><b>Nb. de demandes en 2ème choix</b></td>";
echo "<td><b>Nb. Max souhaité</b></td>";
echo "<td><b>Nb. d'élèves</b></td>";


echo "</tr>";
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
//  $commentaire = mysql_result($calldata,$i,"commentaire");
  $public_1 = mysql_result($calldata,$i,"public_1");
  $public_2 = mysql_result($calldata,$i,"public_2");
  $public_3 = mysql_result($calldata,$i,"public_3");
  $public_21 = mysql_result($calldata,$i,"public_21");
  $public_22 = mysql_result($calldata,$i,"public_22");
  $public_6 = mysql_result($calldata,$i,"public_6");
  $public_7 = mysql_result($calldata,$i,"public_7");
  $public_8 = mysql_result($calldata,$i,"public_8");
  $public_9 = mysql_result($calldata,$i,"public_9");
  $public_14 = mysql_result($calldata,$i,"public_14");
  $public_15 = mysql_result($calldata,$i,"public_15");
  $public_16 = mysql_result($calldata,$i,"public_16");
  $public_33 = mysql_result($calldata,$i,"public_33");
  $public_34 = mysql_result($calldata,$i,"public_34");
  $public_23 = mysql_result($calldata,$i,"public_23");
  $public_35 = mysql_result($calldata,$i,"public_35");
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

//  echo "<td>$commentaire</td>";
  echo "<td>$public_6</td>";
  echo "<td>$public_7</td>";
  echo "<td>$public_8</td>";
  echo "<td>$public_9</td>";
  echo "<td>$public_35</td>";
  echo "<td>$public_1</td>";
  echo "<td>$public_2</td>";
  echo "<td>$public_3</td>";
  echo "<td>$public_21</td>";
  echo "<td>$public_22</td>";
  echo "<td>$public_14</td>";
  echo "<td>$public_15</td>";
  echo "<td>$public_16</td>";
  echo "<td>$public_34</td>";
  echo "<td>$public_33</td>";
  echo "<td>$public_23</td>";
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