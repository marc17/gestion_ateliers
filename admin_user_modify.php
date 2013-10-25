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
$nom_script = "mod_plugins/gestion_ateliers/admin_user_modify.php";
if (!checkAccess_Plugin($nom_script)) {
    header("Location: ../../logout.php?auto=1");
    die();
}
// On vérifie que l'utilisateur a les droits spécifiques pour accéder à ce script
if (!calcul_autorisation_gestion_ateliers($_SESSION['login'],$nom_script)){
    header("Location: ../../logout.php?auto=1");
    die();
}

// Initialisation des variables
$user_login = isset($_POST["user_login"]) ? $_POST["user_login"] : (isset($_GET["user_login"]) ? $_GET["user_login"] : NULL);

if (isset($_POST['valid']) and ($_POST['valid'] == "yes")) {
// ACF
    $req_acf  = mysql_query("select * from j_aid_utilisateurs where indice_aid = '".getSettingValue("active_acf_num_aid")."' and id_utilisateur='".$user_login."'");
    $nb_acf = mysql_num_rows($req_acf);
    $k = 0;
    while ($k < $nb_acf) {
      $id_aid= mysql_result($req_acf,$k,'id_aid');
      $temp = "reg_acf_".$id_aid;
      if (isset($_POST[$temp])) {
         $req=mysql_query("update j_aid_utilisateurs set ordre='".$_POST[$temp]."' where indice_aid = '".getSettingValue("active_acf_num_aid")."' and id_utilisateur='".$user_login."' and id_aid='".$id_aid."'");
      }
      $k++;
    }

if ($is_LP2I) {
// absences justifiées le jeudi après-midi
    $nb_bas = sql_query("select count(id_bas) from bas_bas where type_bas='n' order by id_bas");
    $k = 0;
    $del = mysql_query("delete from bas_j_professeurs_absences where id_professeur='".$user_login."'");
    while ($k < $nb_bas) {
        $temp = "n_".$k;
        if (isset($_POST[$temp]))
            $req = mysql_query("insert into bas_j_professeurs_absences set id_bas='".$k."', id_professeur='".$user_login."'");
        $k++;
    }
}
    $k = 0;
    while ($k < $_POST['max_mat']) {
        $temp = "matiere_".$k;
        $reg_matiere[$k] = $_POST[$temp];
        $k++;
    }
        $test = sql_query1("select count(login) from bas_utilisateurs where login = '".$user_login."'");
        if ($test > 0)
            $reg_data = mysql_query("update bas_utilisateurs
            SET nb_jury='".$_POST['reg_nb_jury']."',service='".$_POST['reg_service']."',service_pb='".$_POST['reg_service_pb']."',sous_service='".$_POST['reg_sous_service']."'
            where login='".$user_login."'
           ");
        else
            $reg_data = mysql_query("insert into bas_utilisateurs
            SET nb_jury='".$_POST['reg_nb_jury']."',service='".$_POST['reg_service']."',service_pb='".$_POST['reg_service_pb']."',sous_service='".$_POST['reg_sous_service']."', login='".$user_login."'");

        $list_mat_old = sql_query("select id_matiere from bas_j_matieres_profs where id_professeur = '".$user_login."'");
        $nb_list_mat_old = mysql_num_rows($list_mat_old);
        $k = 0;
        $ok = "yes";
        while ($k < $nb_list_mat_old) {
            $mat_old = mysql_result($list_mat_old,$k,'id_matiere');
            if (!in_array($mat_old, $reg_matiere)) {
                $test = sql_query1("SELECT count(id_bas) FROM bas_propositions
                    WHERE (
                    id_matiere = '".$mat_old."' and
                    (responsable = '".$user_login."' or
                    coresponsable = '".$user_login."')
                    )");
                if ($test > 0) {
                    $ok = $mat_old;
                }
            }
            $k++;
        }

        if ($ok != 'yes') {
            $msg = "Impossible de supprimer la matière ".$ok.". Cette matiere est actuellement utilisée par ce professeur dans certaines propositions d'activités !";
        } else {
            $del = mysql_query("DELETE FROM bas_j_matieres_profs WHERE id_professeur = '".$user_login."'");
            $m = 0;
            while ($m < $_POST['max_mat']) {
                $num=$m+1;
                if ($reg_matiere[$m] != '') {
                    $test = mysql_query("SELECT * FROM bas_j_matieres_profs WHERE (id_professeur = '".$user_login."' and id_matiere = '$reg_matiere[$m]')");
                    $resultat = mysql_num_rows($test);
                    if ($resultat == 0) {
                        $reg = mysql_query("INSERT INTO bas_j_matieres_profs SET id_professeur = '".$user_login."', id_matiere = '$reg_matiere[$m]'");
                    }
                    $reg_matiere[$m] = '';
                }
                $m++;
            }
            $msg="Les modifications ont bien été enregistrées !";
        }


}
// On appelle les informations de l'utilisateur pour les afficher :
if (isset($user_login) and ($user_login!='')) {
    $call_user_info = mysql_query("SELECT * FROM utilisateurs WHERE login='".$user_login."'");
    $nb_jury = sql_query1("select nb_jury from bas_utilisateurs WHERE login='".$user_login."'");
    if ($nb_jury == -1) $nb_jury = "";
    $service = sql_query1("select service from bas_utilisateurs WHERE login='".$user_login."'");
    if ($service == -1) $service = 0;
    $service_pb = sql_query1("select service_pb from bas_utilisateurs WHERE login='".$user_login."'");
    if ($service_pb == -1) $service_pb = 0  ;

    $sous_service = sql_query1("select sous_service from bas_utilisateurs WHERE login='".$user_login."'");
    if ($sous_service == -1) $sous_service = "";
    $user_nom = @mysql_result($call_user_info, "0", "nom");
    $user_prenom = @mysql_result($call_user_info, "0", "prenom");
    $user_email = @mysql_result($call_user_info, "0", "email");
    $call_matieres = mysql_query("SELECT * FROM bas_j_matieres_profs j WHERE j.id_professeur = '".$user_login."' ORDER BY id_matiere");
    $nb_mat = mysql_num_rows($call_matieres);
    $k = 0;
    while ($k < $nb_mat) {
        $user_matiere[$k] = mysql_result($call_matieres, $k, "id_matiere");
        $k++;
    }
}

//**************** EN-TETE *****************
$titre_page = "Gestion des utilisateurs  \"Ateliers\" | Modifier un utilisateur";
require_once("../../lib/header.inc");
//**************** FIN EN-TETE *****************
?>
<p class=bold>
|<a href="admin_user_index.php">Retour</a>|
</p>

<form enctype="multipart/form-data" action="admin_user_modify.php" method="post">

<!--span class = "norme"-->
<div class = "norme">
<b>Identifiant :</b>
<?php
if (isset($user_login) and ($user_login!='')) {
    echo "<b>".$user_login."</b>";
    echo "<input type=hidden name=reg_login value=\"".$user_login."\" />";
    echo "<table>";
    echo "<tr><td>Nom : </td><td><b>".$user_nom."</b></td></tr>";
    echo "<tr><td>Prénom : </td><td><b>".$user_prenom."</b></td></tr>";
    echo "<tr><td>Email : </td><td><b>".$user_email."</b></td></tr>";
    if ($is_LP2I) {
      echo "<tr><td>Nb. de séquences 55 min. d'enseignement effectif<br />dans le second degré : </td><td><input type=text name=reg_service size=20 value=\"";
      if (isset($service)) echo $service;
      echo "\" /></td></tr>";
      echo "<tr><td>Nb. de séquences 55 min. d'enseignement effectif<br />dans le Post-BAc : </td><td><input type=text name=reg_service_pb size=20 value=\"";
      if (isset($service_pb)) echo $service_pb;
      echo "\" /></td></tr>";
      echo "<tr><td>Nb. de séquences 55 min. de sous-service effectif : </td><td><input type=text name=reg_sous_service size=20 value=\"";
      if (isset($sous_service)) echo $sous_service;
      echo "\" /></td></tr>";
      echo "<tr><td>Participation à des jurys ACF : </td><td><input type=text name=reg_nb_jury size=20 value=\"";
      if (isset($nb_jury)) echo $nb_jury;
      echo "\" /></td></tr>";
      echo "<tr><td>ACF en responsabilité : </td><td>";
      $req_acf  = mysql_query("select * from j_aid_utilisateurs where indice_aid = '".getSettingValue("active_acf_num_aid")."' and id_utilisateur='".$user_login."'");
      $nb_acf = mysql_num_rows($req_acf);
      $k = 0;
      while ($k < $nb_acf) {
        $id_aid= mysql_result($req_acf,$k,'id_aid');
        $ordre_aid= mysql_result($req_acf,$k,'ordre');
        $nom_aid=sql_query1("select nom from aid where id='".$id_aid."'");
        echo $nom_aid. " : <b>responsable</b> <input type=\"radio\" name=\"reg_acf_".$id_aid."\" size=20 value=\"1\" ";
        if ($ordre_aid == '1') echo " checked";
        echo " />";
        echo "<b>co-responsable</b> <input type=\"radio\" name=\"reg_acf_".$id_aid."\" size=20 value=\"2\" ";
        if ($ordre_aid == '2') echo " checked";
        echo " /><br />";
        $k++;
      }
      echo "</td></tr>";
    }
    echo "</table>";
}

echo "<br />";
$k = 0;
while ($k < $nb_mat+1) {
    $num_mat = $k+1;
    echo "Matière N°$num_mat (si professeur): ";
    $temp = "matiere_".$k;
    echo "<select size=1 name='$temp'>\n";
    $calldata = mysql_query("SELECT * FROM bas_matieres ORDER BY matiere");
    $nombreligne = mysql_num_rows($calldata);
    echo "<option value='' "; if (!(isset($user_matiere[$k]))) {echo " SELECTED";} echo ">(vide)</option>";
    $i = 0;
    while ($i < $nombreligne){
        $matiere_list = mysql_result($calldata, $i, "matiere");
        $matiere_complet_list = mysql_result($calldata, $i, "nom_complet");
        echo "<option value=$matiere_list "; if (isset($user_matiere[$k]) and ($matiere_list == $user_matiere[$k])) {echo " SELECTED";} echo ">$matiere_list | ".htmlentities($matiere_complet_list,ENT_COMPAT | ENT_HTML401,'UTF-8')."</option>\n";
        $i++;
    }
    echo "</select><br />\n";
    $k++;
}
$nb_mat++;
echo "<input type=hidden name=max_mat value=$nb_mat />\n";

if ($is_LP2I) {
  echo "<br /><table border=\"1\" cellpadding=\"5\">\n";
  echo "<tr><td><b>".ucfirst($NomAtelier_singulier)."</b></td><td><b>Cocher la case lorsque le professeur est absent de façon justifiée</b></td></tr>\n";
  $query = sql_query("select * from bas_bas where type_bas = 'n' order by nom");
  $nb_query = mysql_num_rows($query);
  $k = 0;
  while ($k < $nb_query) {
      $id_bas = mysql_result($query,$k,'id_bas');
      $date_bas = mysql_result($query,$k,'date_bas');
      $nom_bas =  mysql_result($query,$k,'nom');
      echo "<tr><td>".$nom_bas." du ".$date_bas." : </td>";
      echo "<td><input type=\"checkbox\" name=\"n_".$id_bas."\" value=\"y\" ";
      $test = sql_query1("select count(id_professeur) from bas_j_professeurs_absences where id_professeur='".$user_login."' and id_bas='".$id_bas."'");
      if ($test >=1 ) echo " checked ";
      echo "/></td></tr>";
      $k++;
  } 
  echo "</table>\n";
}
?>
<input type=hidden name=valid value="yes" />
<?php if (isset($user_login)) echo "<input type=hidden name=user_login value=\"".$user_login."\" />\n"; ?>
<center><input type=submit value=Enregistrer /></center>
<!--/span-->
</div>
</form>

</body>
</html>