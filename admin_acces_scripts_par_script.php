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
$tab_descriptifs=array();
foreach($tab_descriptifs_scripts as $rubrique => $tab_liste_scripts) {
	foreach($tab_liste_scripts as $script => $descriptif) {
		$tab_descriptifs[$script]=$descriptif;
	}
}

$acces=isset($_POST['acces'])?$_POST['acces']:NULL;
$script=isset($_GET['script'])?$_GET['script']:(isset($_POST['script'])?$_POST['script']:NULL);
// En accédant à cette page $script est toujours défini
$descriptif=$tab_descriptifs[$script];

// Tableau des statuts autorisés pour le script courant dans plugin.xml
$plugin_xml = simplexml_load_file('plugin.xml');
//$t_statut_autorises=array();
foreach($plugin_xml->administration->fichier->nomfichier as $fichier)
	{
	if ($fichier==$script) {
		$t_statut_autorises=explode("-",$fichier->attributes()->autorisation);
		// Précaution
		//foreach($t_statut_autorises as $id => $val) $t_statut_autorises[$id]=strtoupper($val);
		array_map("strtoupper",$t_statut_autorises);
		}
	}


// Fonction déterminant si un statut a accès au script courant dans dans plugin.xml
function statut_compatible($statut) {
	global $t_statut_autorises;
	switch ($statut) {
		case "_tous_" :
			return in_array("A",$t_statut_autorises) && in_array("P",$t_statut_autorises) && in_array("C",$t_statut_autorises);
			break;
		case "_administrateur_" :
			return in_array("A",$t_statut_autorises);
			break;
		case "_professeur_" :
			return in_array("P",$t_statut_autorises);
			break;
		case "_cpe_" :
			return in_array("C",$t_statut_autorises);
			break;
		default :
			return in_array(strtoupper(substr($statut,0,1)),$t_statut_autorises);
	}
}

// Supprimer un droit d'accès sauf dans le cas admin_acces_scripts*.php et _administrateur_
if (isset($_GET['supprimer']) && !((strpos($script,"admin_acces_scripts")==0) && $_GET['supprimer']=="_administrateur_")) {
	$delete_acces = mysql_query("DELETE FROM bas_gestion_acces_scripts WHERE (script='".$script."' and acces='".$_GET['supprimer']."')");
	if (!$delete_acces) { $msg = "Erreur lors de la suppression."; } else { $msg = "La suppression a bien été effectuée."; }
}


// Ajouter un droi d'accès
if (isset($_POST['ajouter'])) {
	if ($acces=="_tous_") {
		// On efface tous les accès
		mysql_query("DELETE FROM bas_gestion_acces_scripts WHERE (script='".$script."')");
		// On insère l'enregistrement
		$reg_data = mysql_query("INSERT INTO bas_gestion_acces_scripts SET acces='_tous_', script='".$script."'");
		if (!$reg_data) { $msg = "Erreur lors de l'ajout de l'accès ".$acces." !"; } else { $msg = "A présent, tous les utilisateurs de la liste ont accès au script !"; }

	} else {
		if (statut_compatible($tab_utilisateurs[$acces]['statut'])) {
			// On ajoute l'accès
			$R_ajout = mysql_query("INSERT INTO bas_gestion_acces_scripts SET acces='".$acces."', script='".$script."'");
			if (!$R_ajout) { $msg = "Erreur lors de l'ajout de l'utilisateur ".$acces." !"; } else { $msg = "L'utilisateur ou le statut a été ajouté !"; }
		} else $msg = "L'utilisateur ou le statut n'a pas été ajouté !";;
	}
}


// Tableau associatif des droits :
// clé : nom du script
// valeur : tableau des ayant droits sur le script
// C'est le contenu de la table bas_gestion_acces_scripts
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


//**************** EN-TETE *****************
$titre_page = "Gestion des ateliers - Configuration des autorisations d'accès aux scripts";
require_once("../../lib/header.inc.php");
//**************** FIN EN-TETE *****************

echo "<p class=\"bold\">|<a href='admin_acces_scripts.php'>Retour</a>|</p><br />\n";

echo "<p>Cette page permet de gérer les accès au script ".$descriptif." (".$script.").</p>";

echo "<hr />";

echo "Dans plugin.xml les statuts autorisés sont : ";
foreach($t_statut_autorises as $s) echo $s." ";
echo "<br />";

echo "<p style='margin-left: 40px;'>Liste des ayant droits sur le script $descriptif ($script)<br />";
if (array_key_exists($script,$tab_droits_acces_scripts)) {
	foreach($tab_droits_acces_scripts[$script] as $un_acces) {
		echo "&nbsp;&nbsp;&nbsp;".$tab_utilisateurs[$un_acces]['prenom']." ".$tab_utilisateurs[$un_acces]['nom']." (".$tab_utilisateurs[$un_acces]['statut'].")";
		// Supprimer un droit d'accès sauf dans le cas admin_acces_scripts*.php et _administrateur_
		if (!((strpos($script,"admin_acces_scripts")===0) && $tab_utilisateurs[$un_acces]['statut']=="_administrateur_"))
			echo " <a href='admin_acces_scripts_par_script.php?script=".$script."&supprimer=".$un_acces.add_token_in_url()."'>(Suppimer)</a>";
		echo "<br />";
	}
echo "</p><hr />\n";
}

// Tous les statuts aurisés dans plugin.xml sont-ils autorisés dans bas_gestion_acces_scripts ?
$tous_autorises=false;
$t_s=array("A"=>"administrateur","P"=>"professeur","C"=>"cpe");
if (isset($tab_droits_acces_scripts[$script])) {
	$tous_autorises=true;
	foreach($t_statut_autorises as $s) $tous_autorises=$tous_autorises && (in_array("_".$t_s[$s]."_",$tab_droits_acces_scripts[$script]));
	$tous_autorises=$tous_autorises || in_array("_tous_",$tab_droits_acces_scripts[$script]);
}

// Si tous les utilisateurs n'ont pas accès au script on peut en rajouter
	if (!$tous_autorises) {
	?>
	<h2>Ajouter un statut ou un utilisateur ayant accès à ce script</h2>
	<form style="margin-left: 40px;" method="post" action="admin_acces_scripts_par_script.php" name="choix_utilisateur">
		<?php if (function_exists("add_token_field")) echo add_token_field(); ?>
		Sélectionner :&nbsp;
		<select name="acces">
			<optgroup>
				<option></option>
			</optgroup>
			<optgroup label="Statut">
			</optgroup>
		<?php
		$initiale_courante=0;
		foreach($tab_utilisateurs as $un_utilisateur)
			if ((isset($tab_droits_acces_scripts[$script])?(!in_array($un_utilisateur['login'],$tab_droits_acces_scripts[$script]) && !in_array("_".$un_utilisateur['statut']."_",$tab_droits_acces_scripts[$script])):true) && statut_compatible($un_utilisateur['statut'])) {
				//
				if (!isset($tab_droits_acces_scripts[$script]) || (isset($tab_droits_acces_scripts[$script]) && (!in_array($un_utilisateur['login'],$tab_droits_acces_scripts[$script]) && !in_array("_tous_",$tab_droits_acces_scripts[$script])))) {
					$nom=strtoupper($un_utilisateur['nom'])." ".$un_utilisateur['prenom'];
					$initiale=ord(strtoupper($un_utilisateur['nom']));
					if ($initiale!=$initiale_courante)
						{
						$initiale_courante=$initiale;
						echo "\t</optgroup><optgroup label=\"".chr($initiale)."\">";
						}
					?>
					<option value="<?php echo $un_utilisateur['login']; ?>"><?php echo $un_utilisateur['nom']." ".$un_utilisateur['prenom']." (".$un_utilisateur['statut'].")"; ?></option>
					<?php
					}
				}
		?>
			</optgroup>
		</select>
		<input type="hidden" name="script" value="<?php echo $script; ?>">
		<input name="ajouter" value="Ajouter cet utilisateur ou ce statut" type="submit">
	</form>
<?php
}
else echo "<p>Pour cohérence avec ce qui est défini dans plugin.xml aucun satut ou utilisateur ne peut ête ajouté à cette liste.</p>";
include "./footer.inc.php";
?>