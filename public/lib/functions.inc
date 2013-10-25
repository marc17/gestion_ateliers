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

function make_bas_select_html( $link, $current)
{
  $out_html = "<form name=\"num_bas\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\"><b><i>Choix de l'atelier :</i></b><br />
  <select name=\"numero_bas\" onChange=\"numero_go()\">";
  $out_html .= "<option value=\"".$link."?numero_bas=-1\">(Choisissez un atelier)";
  $sql = "select id_bas, date_bas, nom from bas_bas where aff_liste_par_classe='y' order by nom";

  $res = sql_query($sql);
  if ($res) for ($i = 0; ($row = sql_row($res, $i)); $i++)
  {
    $selected = ($row[0] == $current) ? "selected" : "";
    $link2 = "$link?numero_bas=$row[0]";
    $out_html .= "<option $selected value=\"$link2\">".htmlspecialchars($row[2])." du ". htmlspecialchars($row[1]);
  }
  $out_html .= "</select>
  <script type=\"text/javascript\">
  <!--
  function numero_go()
  {
  box = document.forms[\"num_bas\"].numero_bas;
  destination = box.options[box.selectedIndex].value;
  if (destination) location.href = destination;
  }
  // -->
  </SCRIPT>
  <noscript>
  <input type=\"submit\" value=\"OK\" />
  </noscript>
  </form>";
  return $out_html;
}

function make_filieres_select_html2( $link, $numero_bas, $current)

{
  $out_html = "<form name=\"filiere\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\"><b><i>Filière :</i></b><br />
  <select name=\"filiere\" onChange=\"filiere_go()\">";
  $out_html .= "<option value=\"".$link."?numero_bas=$numero_bas&amp;id_filiere=-1\">(Choisissez une filière)";
  $sql = "select DISTINCT id_filiere, nom_filiere from bas_filieres order by niveau_filiere";

  $res = sql_query($sql);
  if ($res) for ($i = 0; ($row = sql_row($res, $i)); $i++)
  {
    $selected = ($row[0] == $current) ? "selected" : "";
    $link2 = "$link?numero_bas=$numero_bas&amp;id_filiere=$row[0]";
    $out_html .= "<option $selected value=\"$link2\">" . htmlspecialchars($row[1]);
  }
  $out_html .= "</select>
  <script type=\"text/javascript\">
  <!--
  function filiere_go()
  {
  box = document.forms[\"filiere\"].filiere;
  destination = box.options[box.selectedIndex].value;
  if (destination) location.href = destination;
  }
  // -->
  </SCRIPT>
  <noscript>
  <input type=\"submit\" value=\"OK\" />
  </noscript>
  </form>";
  return $out_html;
}

function make_matiere_select_htm( $link, $id_classe, $current)
{
  $out_html = "<form name=\"matiere\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\"><b><i>Matière :</i></b><br />
  <select name=\"matiere\" onChange=\"matiere_go()\">";
  $out_html .= "<option value=\"".$link."?id_classe=$id_classe&amp;id_matiere=-1\">(Choisissez une matière)";

  $sql = "select DISTINCT m.matiere, m.nom_complet from j_classes_matieres_professeurs j, matieres m, ct_entry ct where (j.id_classe=$id_classe and j.id_matiere=m.matiere and ct.id_classe=$id_classe and m.matiere=ct.id_matiere) order by m.nom_complet";
  $res = sql_query($sql);
  if ($res) for ($i = 0; ($row = sql_row($res, $i)); $i++)
  {
   $test_prof = "SELECT nom, prenom FROM j_classes_matieres_professeurs j, utilisateurs u WHERE (j.id_matiere='".$row[0]."' and j.id_classe='$id_classe' and j.id_professeur=u.login) ORDER BY nom, prenom";
   $res_prof = sql_query($test_prof);
   $chaine = "(";
   for ($k=0;$prof=sql_row($res_prof,$k);$k++) {
     if ($k != 0) $chaine .= ", ";
     $chaine .= htmlspecialchars($prof[0])." ".mb_substr(htmlspecialchars($prof[1]),0,1);
   }
   $chaine .= ")";


   $selected = ($row[0] == $current) ? "selected" : "";
   $link2 = "$link?id_classe=$id_classe&amp;id_matiere=$row[0]";
   $out_html .= "<option $selected value=\"$link2\">" . htmlspecialchars($row[1])." ".$chaine;
   }
  $out_html .= "</select>
  <script type=\"text/javascript\">
  <!--
  function matiere_go()
  {
    box = document.forms[\"matiere\"].matiere;
    destination = box.options[box.selectedIndex].value;
    if (destination) location.href = destination;
  }
  // -->
  </SCRIPT>

  <noscript>
  <input type=\"submit\" value=\"OK\" />
  </noscript>
  </form>";

  return $out_html;
}

?>