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
$niveau_arbo = "3";
// Initialisations files (Attention au chemin des fichiers en fonction de l'arborescence)
include("../../../lib/initialisationsPropel.inc.php");
include("../../../lib/initialisations.inc.php");
include("../functions_gestion_ateliers.php");
require_once("lib/functions.inc");
// On vérifie si l'accès est restreint ou non
require_once("lib/auth.php");
unset($numero_bas);
$numero_bas = isset($_POST["numero_bas"]) ? $_POST["numero_bas"] : (isset($_GET["numero_bas"]) ? $_GET["numero_bas"] : -1);
unset($id_filiere);
$id_filiere = isset($_POST["id_filiere"]) ? $_POST["id_filiere"] : (isset($_GET["id_filiere"]) ? $_GET["id_filiere"] : -1);


//**************** EN-TETE *****************
$titre_page = "Accès aux propositions ".$NomAtelier_preposition.$NomAtelier_pluriel;
require_once("lib/header.inc");
//**************** FIN EN-TETE *************
//On vérifie si le module est activé
$test_plugin = sql_query1("select ouvert from plugins where nom='gestion_ateliers'");
if ($test_plugin!='y') {
    die("Le module n'est pas activé.");
}

echo "<table cellspacing=\"5\" cellpadding=\"5\"><tr>";
echo "<td valign='top'>";
echo make_bas_select_html('index.php', $numero_bas);
echo "</td><td>";
if ($numero_bas!=-1) echo make_filieres_select_html2('index.php', $numero_bas, $id_filiere);
echo "</td></tr></table><hr />";

if ($id_filiere == -1)  {
    echo "<center><h3 class='gepi'>".getSettingValue("gepiSchoolName"). " - année scolaire " . getSettingValue("gepiYear")."</H3>";
    echo "<h3 class='gepi'><font color='red'>Choisissez un ".$NomAtelier_singulier." et une filiere.</font></h3>";
    $nom_fic_logo = getSettingValue("logo_etab");
    $nom_fic_logo_c = "../images/".$nom_fic_logo;
    if (($nom_fic_logo != '') and (file_exists($nom_fic_logo_c))) {
        echo "<IMG SRC=\"".$nom_fic_logo_c."\" BORDER=0 ALT=\"\"><br />";
    }
    echo "</center></body></html>";
    die();
}

// La filiere est définie ainsi que le numéro de bas
if (isset($numero_bas) and (isset($id_filiere)))
    include "../code_liste_bas_par_classe.php";



?>
</body>
</html>