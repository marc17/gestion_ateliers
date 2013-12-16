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

$nom_plugin = "gestion_ateliers";
//On vérifie si le module est activé
$test_plugin = sql_query1("select ouvert from plugins where nom='".$nom_plugin."'");
if ($test_plugin!='y') {
    die("Le module n'est pas activé.");
}

// On vérifie que le statut de l'utilisateur permet d'accéder à ce script
$nom_script = "mod_plugins/gestion_ateliers/admin_acces_scripts.php";
if (!checkAccess_Plugin($nom_script)) {
    header("Location: ../../logout.php?auto=1");
    die();
}
// On vérifie que l'utilisateur a les droits spécifiques pour accéder à ce script
if (!calcul_autorisation_gestion_ateliers($_SESSION['login'],$nom_script)){
    header("Location: ../../logout.php?auto=1");
    die();
}

// Tableau associatif des utilisateurs et statuts
$tab_utilisateurs=array();
$r_sql="SELECT login,nom,prenom,statut FROM `utilisateurs` WHERE `statut` IN ('administrateur','professeur','cpe') ORDER BY login";
$R_utilisateurs=mysql_query($r_sql);
while ($un_utilisateur=mysql_fetch_assoc($R_utilisateurs)) {
	$tab_utilisateurs[$un_utilisateur['login']]=$un_utilisateur['prenom']." ".$un_utilisateur['nom']." (".$un_utilisateur['statut'].")";
}
$tab_utilisateurs["_tous_"]="Tous les administrateurs, professeurs et CPE";
$tab_utilisateurs["_administrateur_"]="Tous les administrateurs";
$tab_utilisateurs["_professeur_"]="Tous les professeurs";
$tab_utilisateurs["_cpe_"]="Tous les CPE";


// Tableau des descriptifs des scripts
include("tab_scripts.php");
$tab_descriptifs=array();
foreach($tab_descriptifs_scripts as $rubrique => $tab_liste_scripts) {
	foreach($tab_liste_scripts as $script => $descriptif) {
		$tab_descriptifs[$script]=$descriptif;
	}
}


// Tableau associatif des droits :
// clé : nom du script
// valeur : tableau des ayant droits sur le script
$tab_droits_acces_scripts=array();
$tab_droits_acces_scripts_1=array();
$r_sql="SELECT * FROM `bas_gestion_acces_scripts`";
$R_droits_acces_scripts=mysql_query($r_sql);
while ($droits_acces_scripts=mysql_fetch_assoc($R_droits_acces_scripts)) {
	if (array_key_exists($droits_acces_scripts['script'],$tab_droits_acces_scripts)) {
		$tab_droits_acces_scripts[$droits_acces_scripts['script']][]=$droits_acces_scripts['acces'];
		} else {$tab_droits_acces_scripts[$droits_acces_scripts['script']]=array($droits_acces_scripts['acces']);
		}
	}


$script=$_GET['script'];
$descriptif=$tab_descriptifs[$_GET['script']];

if (isset($_GET['supprimer'])) {
	$delete_acces = mysql_query("DELETE FROM bas_gestion_acces_scripts WHERE (script='".$_GET['script']."' and acces='".$_GET['supprimer']."')");
	if (!$delete_acces) { $msg = "Erreur lors de la suppression."; } else { $msg = "La suppression a bien été effectuée."; }
}

//**************** EN-TETE *****************
$titre_page = "Gestion des ateliers - Configuration des autorisations d'accès aux scripts";
require_once("../../lib/header.inc.php");
//**************** FIN EN-TETE *****************

echo "<p class=\"bold\">|<a href='test.php'>Retour</a>|</p>\n";

echo "<p>Cette page permet de gérer les accès au script ".$descriptif."(".$script.").
</p>";

echo "<hr />";

		echo "<h4>$descriptif ($script)</h4>";
		echo "&nbsp;&nbsp;Liste des ayant droits sur ce script :<br />";
		if (array_key_exists($script,$tab_droits_acces_scripts)) {
			foreach($tab_droits_acces_scripts[$_GET['script']] as $un_acces) {
				echo "&nbsp;&nbsp;&nbsp;".$tab_utilisateurs[$un_acces];
				echo " <a href='admin_acces_scripts_par_script.php?script=".$_GET['script']."&supprimer=".$un_acces.add_token_in_url()."'>(Suppimer)</a><br />";
			}

	echo "<hr />\n";
}
?>

<?php
include "./footer.inc.php";
?>