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

$acces=isset($_POST['acces'])?$_POST['acces']:(isset($_GET['acces'])?$_GET['acces']:"");

if (isset($_POST['modification'])) {
	// C'est un peu lourdingue mais $_POST['scripts_temoin'] donne la liste 
	// des cases à cocher présentes et ça passe sur petite table comme bas_gestion_acces_scripts
	foreach($_POST['scripts_temoin'] as $script) {
		mysql_query("delete from `bas_gestion_acces_scripts` where `script`='".$script."' and `acces`='".$acces."';");
	}
	if (isset($_POST['scripts'])) {
		foreach($_POST['scripts'] as $script) {
			mysql_query("insert into `bas_gestion_acces_scripts` values('".$script."','".$acces."');");
		}
	}
}

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

// Tableau des statuts autorisés dans plugin.xml
$plugin_xml = simplexml_load_file('plugin.xml');
//$t_statut_autorises=array();
foreach($plugin_xml->administration->fichier->nomfichier as $fichier)
	{
	$t_statut_autorises[(string) $fichier]=explode("-",$fichier->attributes()->autorisation);
	// Précaution
	array_map("strtoupper",$t_statut_autorises[(string) $fichier]);
	}

// Fonction déterminant si $acces à accès à $script dans dans plugin.xml
function statut_compatible($acces,$script) {
	global $t_statut_autorises,$tab_utilisateurs;
	switch ($tab_utilisateurs[$acces]['statut']) {
		case "_tous_" :
			return in_array("A",$t_statut_autorises[$script]) && in_array("P",$t_statut_autorises[$script]) && in_array("C",$t_statut_autorises[$script]);
			break;
		case "_administrateur_" :
			return in_array("A",$t_statut_autorises[$script]);
			break;
		case "_professeur_" :
			return in_array("P",$t_statut_autorises[$script]);
			break;
		case "_cpe_" :
			return in_array("C",$t_statut_autorises[$script]);
			break;
		default :
			return in_array(strtoupper(substr($tab_utilisateurs[$acces]['statut'],0,1)),$t_statut_autorises[$script]);
	}
}

//**************** EN-TETE *****************
$titre_page = "Gestion des ateliers - Configuration des autorisations d'accès aux scripts";
require_once("../../lib/header.inc.php");
//**************** FIN EN-TETE *****************

if ($acces=="") {
?>
<p class="bold">| <a href='admin_acces_scripts.php'>Retour</a> |</p>
<br />
<p>Cette page permet de gérer les accès d'un statut ou d'un utilisateur.</p>
<h2>Sélectionner un statut ou un utilisateur</h2>
<form style="margin-left: 40px;" method="post" action="admin_acces_scripts_par_acces.php" name="choix_utilisateur">
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
	foreach($tab_utilisateurs as $un_utilisateur) {
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
?>
		</optgroup>
	</select>
	<input name="selection" value="Sélectionner cet utilisateur ou ce statut" type="submit">
</form>

<?php
} else {
?>
<p class="bold">| <a href='admin_acces_scripts_par_acces.php'>Retour</a> |</p>
<br />
<p>Cette page permet de gérer les accès de : <?php echo $tab_utilisateurs[$acces]['nom']; ?>.</p>
<form style="margin-left: 40px;" method="post" action="admin_acces_scripts_par_acces.php" name="choix_acces">
<?php
	foreach($tab_descriptifs_scripts as $rubrique => $tab_liste_scripts) {
		echo "<h2>$rubrique</h2>";
		$compteur=0;
		foreach($tab_liste_scripts as $script => $descriptif) {
			if (statut_compatible($acces,$script)) {
				// inutile d'ajouter un droit à un utilisateur si son son statut lui donne déjà accès au script
				if (isset($tab_droits_acces_scripts[$script]) && (!in_array("_".$tab_utilisateurs[$acces]['statut']."_",$tab_droits_acces_scripts[$script]) && !in_array("_tous_",$tab_droits_acces_scripts[$script]))) {
					echo "<input type=\"checkbox\" name=\"scripts[]\" value=\"".$script."\"";
					if (array_key_exists($script,$tab_droits_acces_scripts)) {
						if (in_array($acces,$tab_droits_acces_scripts[$script])) echo " checked=\"checked\"";
							else echo "";
					} else {
						echo "";
						}
					// On ne peut pas modifier les accès administrateur sur les pages admin_acces_scripts*.php
					if ((strpos($script,"admin_acces_scripts")===0) && $tab_utilisateurs[$acces]['statut']=="_administrateur_") echo " disabled=\"disabled\"";
					echo ">\n";
					echo "$descriptif ($script)<br />\n";
					echo "<input type=\"hidden\" name=\"scripts_temoin[]\" value=\"".$script."\" >";
					$compteur++;
				}
			}
		}
	if ($compteur==0) echo "Pour les scripts de cette rubrique, soit le statut ou l'utilisateur y a déjà accès, soit il ne peut y avoir accès.<br />\n";
	}
?>
	<input type="hidden" name="acces" value="<?php echo $acces; ?>">
	<br />
	<p style="text-align:center;"><input type="submit" name="modification" value=" Valider les modifications "></p>
</form>
<?php
}
include "./footer.inc.php";
?>