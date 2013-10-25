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
$nom_script = "mod_plugins/gestion_ateliers/admin_tab_profs_matieres.php";
if (!checkAccess_Plugin($nom_script)) {
    header("Location: ../../logout.php?auto=1");
    die();
}
// On vérifie que l'utilisateur a les droits spécifiques pour accéder à ce script
if (!calcul_autorisation_gestion_ateliers($_SESSION['login'],$nom_script)){
    header("Location: ../../logout.php?auto=1");
    die();
}

// Initialisation du message signalant les enregistrements ou les problèmes.
$msg="";

if(isset($_POST['user_login'])){
    $user_login=$_POST['user_login'];
    $tab_matiere=$_POST['tab_matiere'];

    for($i=0;$i<count($user_login);$i++){
        if (isset($_POST['c_'.$i.'_'])) {
        $check_matiere=$_POST['c_'.$i.'_'];
        for($j=0;$j<count($tab_matiere);$j++){
            if (isset($check_matiere[$j]) and ($check_matiere[$j]=="oui")) {
                $sql="SELECT * FROM bas_j_matieres_profs WHERE id_professeur='$user_login[$i]' AND id_matiere='$tab_matiere[$j]'";
                $result_test=mysql_query($sql);
                if(mysql_num_rows($result_test)==0){
                    $sql="INSERT INTO bas_j_matieres_profs set id_professeur = '$user_login[$i]', id_matiere='$tab_matiere[$j]'";
                    $result_insert=mysql_query($sql);
                }
            }
            else{
                $sql="SELECT * FROM bas_j_matieres_profs WHERE id_professeur='$user_login[$i]' AND id_matiere='$tab_matiere[$j]'";
                $result_test=mysql_query($sql);
                if(mysql_num_rows($result_test)!=0){
                    // On a décoché la matière pour ce professeur!

                    // On vérifie que le professeur n'est pas associé à un groupe pour cette matière...
                    $test = sql_query1("SELECT count(id_bas) FROM bas_propositions
                    WHERE (
                    id_matiere = '".$tab_matiere[$j]."' and
                    (responsable = '".$user_login[$i]."' or
                    coresponsable = '".$user_login[$i]."')
                    )");
                    if ($test==0) {
                        // ... puis on supprime l'entrée de la table 'bas_j_matieres_profs'
                        $sql="DELETE FROM bas_j_matieres_profs WHERE id_professeur='$user_login[$i]' AND id_matiere='$tab_matiere[$j]'";
                        $result_suppr=mysql_query($sql);
                    }
                }
            }
        }
    }
    }
    if($msg==""){
        $msg="Enregistrement réussi.";
    }
}



//**************** EN-TETE *****************
$titre_page = "Gestion des utilisateurs | Affectation des matières aux professeurs";
require_once("../../lib/header.inc");
//**************** FIN EN-TETE *****************
?>
<p class=bold>
|<a href="admin_user_index.php">Retour</a>|
</p>
<form enctype="multipart/form-data" action="admin_tab_profs_matieres.php" method="post">

<!--span class = "norme"-->
<div class = "norme">

<?php
    // Fonction destinée à afficher verticalement, lettre par lettre, une chaine:
    function aff_vertical($texte){
        $chaine="";
        for($i=0;$i<mb_strlen($texte);$i++){
            //echo mb_substr($texte,$i,1)."<br />";
            $chaine=$chaine.mb_substr($texte,$i,1)."<br />";
        }
        //echo "\n";
        $chaine=$chaine."\n";
        return $chaine;
    }

    // Tableau de la liste des matières:
    $tab_matiere=array();
    $sql="SELECT matiere FROM bas_matieres ORDER BY matiere";
    $result_matieres=mysql_query($sql);
    while($ligne=mysql_fetch_object($result_matieres)){
        $tab_matiere[]=$ligne->matiere;
    }

    $calldata = mysql_query("SELECT login FROM utilisateurs  where (statut = 'professeur' or statut='cpe') ORDER BY login");
    $nombreligne = mysql_num_rows($calldata);

    echo "<script type='text/javascript' language='javascript'>
    function colore(idcellule,idcheckbox){
        if(document.getElementById(idcheckbox).checked){
            document.getElementById(idcellule).style.background='green';
        }
        else{
            document.getElementById(idcellule).style.background='grey';
        }
    }

    function survol_colore(ligne){
        for(i=0;i<".count($tab_matiere).";i++){
            idcellule='td_'+ligne+'_'+i;
            eval('document.getElementById(\''+idcellule+'\').style.background=\'lightblue\'');
        }
    }

    function retablit_couleurs(ligne){
        for(i=0;i<".count($tab_matiere).";i++){
            idcellule='td_'+ligne+'_'+i;
            idcheckbox='c_'+ligne+'_'+i;
            if(document.getElementById(idcheckbox).checked){
                //eval('document.getElementById(\''+idcellule+'\').style.background=\'lightblue\'');
                document.getElementById(idcellule).style.background='green';
            }
            else{
                if(i%2==0){
                    document.getElementById(idcellule).style.background='silver';
                }
                else{
                    document.getElementById(idcellule).style.background='white';
                }
            }
        }
    }

    function masquage(colonne){
        if(document.getElementById('c_col_'+colonne).checked){
            document.getElementById('td_col_'+colonne).style.background='red';
            for(j=0;j<colonne;j++){
                document.getElementById('c_col_'+j).checked=false;
                document.getElementById('d_col_'+j).style.display='none';
                for(i=0;i<$nombreligne;i++){
                    if(i%10==0){
                        document.getElementById('d_titre_'+i+'_'+j).style.display='none';
                    }
                    document.getElementById('d_'+i+'_'+j).style.display='none';
                }
            }
        }
        else{
            document.getElementById('td_col_'+colonne).style.background='white';
            for(j=0;j<colonne;j++){
                document.getElementById('c_col_'+j).checked=false;
                document.getElementById('d_col_'+j).style.display='block';
                for(i=0;i<$nombreligne;i++){
                    if(i%10==0){
                        document.getElementById('d_titre_'+i+'_'+j).style.display='block';
                    }
                    document.getElementById('d_'+i+'_'+j).style.display='block';
                }
            }
        }
    }
</script>\n";


    $cell_style[0]="background: silver";
    $cell_style[1]="background: white";

    for($i=0;$i<count($tab_matiere);$i++){
        echo "<input type='hidden' name='tab_matiere[$i]' value='$tab_matiere[$i]' />\n";
    }

    echo "<table border='1'>\n";
    echo "<tr style='text-align:center; background: white;'>\n";
    echo "<td>Masquage</td>\n";
    for($i=0;$i<count($tab_matiere);$i++){
        echo "<td id='td_col_".$i."'><div id='d_col_".$i."'><input type='checkbox' name='c_col_".$i."' id='c_col_".$i."' value='coche' onchange='masquage($i)' /></div></td>\n";
    }
    echo "</tr>\n";

    $cpt=0;
    while ($cpt < $nombreligne){

        if($cpt/10-round($cpt/10)==0){
            echo "<tr valign='top'>\n";
            echo "<th>Professeur</th>\n";
            for($i=0;$i<count($tab_matiere);$i++){
                echo "<th style='".$cell_style[$i%2]."'><div id='d_titre_".$cpt."_".$i."'>".aff_vertical($tab_matiere[$i])."</div></th>\n";
            }
            echo "</tr>\n";
        }
        $user_login = mysql_result($calldata, $cpt, "login");
        $user_nom = sql_query1("select nom from utilisateurs where login='".$user_login."'");
        $user_prenom = sql_query1("select prenom from utilisateurs where login='".$user_login."'");

        echo "<tr>\n";
        echo "<td>\n";
        echo "<input type='hidden' name='user_login[]' value='$user_login' />\n";
        echo "$user_nom $user_prenom";
        echo "</td>\n";

        for($j=0;$j<count($tab_matiere);$j++){
            $sql="SELECT * FROM bas_j_matieres_profs WHERE id_professeur='$user_login' AND id_matiere='".$tab_matiere[$j]."'";
            $result_matiere_prof=mysql_query($sql);
            if(mysql_num_rows($result_matiere_prof)!=0){
                $checked_ou_pas=" checked";
                $couleur=" background: lime;";
            }
            else{
                $checked_ou_pas="";
                //$couleur="";
                $couleur=$cell_style[$j%2];
            }

            echo "<td id='td_".$cpt."_".$j."' style='text-align:center;$couleur' onMouseOver='survol_colore($cpt);' onMouseOut='retablit_couleurs($cpt);'>\n";
            echo "<div id='d_".$cpt."_".$j."'>\n";
            echo "<input type='checkbox' id='c_".$cpt."_".$j."' name='c_".$cpt."_[".$j."]' value='oui' onchange='colore(\"td_".$cpt."_".$j."\",\"c_".$cpt."_".$j."\")' $checked_ou_pas />\n";
            echo "</div>\n";
            echo "</td>\n";
        }

        echo "</tr>\n";
        $cpt++;
    }
    echo "</table>\n";
?>
<input type='hidden' name='valid' value="yes" />
<center><input type='submit' value='Enregistrer' /></center>
</div>
</form>
</body>
</html>