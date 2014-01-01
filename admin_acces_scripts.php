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
// On commence par les statuts, l'espace devant le nom sert a ne pas afficher d'initiale dans la liste déroulante
$tab_utilisateurs["_tous_"]=array("login" => "_tous_","nom" => " Tous les administrateurs, professeurs et CPE","prenom" => "","statut" => "_tous_");
$tab_utilisateurs["_administrateur_"]=array("login" => "_administrateur_","nom" => " Tous les administrateurs","prenom" => "","statut" => "_administrateur_");
$tab_utilisateurs["_professeur_"]=array("login" => "_professeur_","nom" => " Tous les professeurs","prenom" => "","statut" => "_professeur_");
$tab_utilisateurs["_cpe_"]=array("login" => "_cpe_","nom" => " Tous les CPE","prenom" => "","statut" => "_cpe_");
// On continue avec la table 'utilisateurs'
$r_sql="SELECT login,nom,prenom,statut FROM `utilisateurs` WHERE `statut` IN ('administrateur','professeur','cpe') ORDER BY login";
$R_utilisateurs=mysql_query($r_sql);
while ($un_utilisateur=mysql_fetch_assoc($R_utilisateurs)) {
	$tab_utilisateurs[$un_utilisateur['login']]=$un_utilisateur;
}


// Tableau des descriptifs des scripts
include("tab_scripts.php");

// Tableau associatif des droits :
// clé : nom du script
// valeur : tableau des ayant droits sur le script
// C'est le contenu de la table bas_gestion_acces_scripts
$tab_droits_acces_scripts=array();
$r_sql="SELECT * FROM `bas_gestion_acces_scripts`";
$R_droits_acces_scripts=mysql_query($r_sql);
while ($droits_acces_scripts=mysql_fetch_assoc($R_droits_acces_scripts)) {
	if (array_key_exists($droits_acces_scripts['script'],$tab_droits_acces_scripts)) {
		$tab_droits_acces_scripts[$droits_acces_scripts['script']][]=$droits_acces_scripts['acces'];
		} else {$tab_droits_acces_scripts[$droits_acces_scripts['script']]=array($droits_acces_scripts['acces']);
		}
	}

// Vérification de la cohérence de la table bas_gestion_acces_scripts
// On commence par le cas le plus simple : _tous_
foreach($tab_droits_acces_scripts as $script => $t_acces) {
	if (in_array("_tous_",$t_acces)) {
		foreach($t_acces as $acces) {
			if ($acces<>"_tous_") {
				// On supprime les entrées inutiles dans bas_gestion_acces_scripts
				$r_sql="delete from `bas_gestion_acces_scripts` where `script`='".$script."' and `acces`='".$acces."'";
				$R_suppression=mysql_query($r_sql);
				// On met à jour le tableau tab_droits_acces_scripts
				unset($tab_droits_acces_scripts[$script][array_search($acces,$t_acces)]);
			}
		}
	}
}
// Plus compliqué : _administrateur_ _professeur_ ou _cpe_
$t_statuts=array("_administrateur_","_professeur_","_cpe_");
foreach($t_statuts as $statut) {
	foreach($tab_droits_acces_scripts as $script => $t_acces) {
		if (in_array($statut,$t_acces)) {
			foreach($t_acces as $acces) {
				if ("_".$tab_utilisateurs[$acces]['statut']."_"==$statut) {
					// On supprime les entrées inutiles dans bas_gestion_acces_scripts
					$r_sql="delete from `bas_gestion_acces_scripts` where `script`='".$script."' and `acces`='".$acces."'";
					$R_suppression=mysql_query($r_sql);
					// On met à jour le tableau tab_droits_acces_scripts
					unset($tab_droits_acces_scripts[$script][array_search($acces,$t_acces)]);
				}
			}
		}
	}
}

// On supprime sauf dans le cas admin_acces_scripts*.php et _administrateur_
if (isset($_GET['supprimer']) && isset($_GET['script']) && !((strpos($_GET['script'],"admin_acces_scripts")===0) && $_GET['supprimer']=="_administrateur_")) {
	$delete_acces = mysql_query("DELETE FROM `bas_gestion_acces_scripts` WHERE (`script`='".$_GET['script']."' and `acces`='".$_GET['supprimer']."')");
	if (!$delete_acces) { $msg = "Erreur lors de la suppression."; } else { $msg = "La suppression a bien été effectuée."; }
}


//**************** EN-TETE *****************
$titre_page = "Gestion des ateliers - Configuration des autorisations d'accès aux scripts";
require_once("../../lib/header.inc.php");
//**************** FIN EN-TETE *****************

echo "<p class=\"bold\">| <a href='../../accueil.php'>Retour</a> | <a href='admin_acces_scripts_par_acces.php'>Définir les droits d'un statut ou d'un utilisateur |</a></p>\n";
echo "<br />";
echo "<p>Cette page permet de gérer les accès aux différents script du plugin.
<br />Il est ainsi possible de déléguer l'administration des ateliers à un utilisateur particulier en lui autorisant
l'accès aux scripts correspondants</p>";

echo "<hr />";
foreach($tab_descriptifs_scripts as $rubrique => $tab_liste_scripts) {
	echo "<h2>$rubrique</h2>";
	foreach($tab_liste_scripts as $script => $descriptif) {
		echo "<h4>$descriptif ($script)</h4>";
		echo "&nbsp;&nbsp;Liste des ayant droits sur ce script :<br />";
		if (array_key_exists($script,$tab_droits_acces_scripts)) {
			foreach($tab_droits_acces_scripts[$script] as $un_acces) {
				echo "&nbsp;&nbsp;&nbsp;".$tab_utilisateurs[$un_acces]['nom'];
				// Supprimer un droit d'accès sauf dans le cas admin_acces_scripts*.php et _administrateur_
				if (!((strpos($script,"admin_acces_scripts")===0) && $tab_utilisateurs[$un_acces]['statut']=="_administrateur_"))
					echo " <a href='admin_acces_scripts.php?script=".$script."&supprimer=".$un_acces.add_token_in_url()."'>(Suppimer)</a>";
				echo "<br />";
			}
		}
	echo "&nbsp;&nbsp;<a href='admin_acces_scripts_par_script.php?script=".$script.add_token_in_url()."'>Modifier cette liste</a><br />\n";
	}
	echo "\n";
}
include "./footer.inc.php";
?>