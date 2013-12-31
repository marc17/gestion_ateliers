<?php
include("config.inc.php");

// Tableau des filières
$req_filieres = @mysql_query("select * from bas_filieres order by id_filiere");
$nb_filieres = @mysql_num_rows($req_filieres);
if ($nb_filieres>0) {
$f = 0 ;
while ($f < $nb_filieres) {
  $id_filiere = mysql_result($req_filieres,$f,'id_filiere');
  $nom_filiere = mysql_result($req_filieres,$f,'nom_filiere');
  $niveau_filiere = mysql_result($req_filieres,$f,'niveau_filiere');
  $tab_filière[$niveau_filiere]["id"][]= $id_filiere;
  $tab_filière[$niveau_filiere]["nom"][]= $nom_filiere;
  $f++;
}
}

/*
  Vérifier si le statut de l'utilisateur permet d'accéder au script $nom_fichier d'un plugin
  Comme la fonction suivante est utilisée dans d'autres plugins su même auteur, on vérifie son existence
  pour éviter "Fatal error: Cannot redeclare checkaccess_plugin() ..."
*/
if (!function_exists('checkAccess_Plugin')) {
function checkAccess_Plugin($nom_fichier='') {
 global $gepiPath;
 if ($nom_fichier=='') {
   $url = parse_url($_SERVER['REQUEST_URI']);
   $nom_fichier = mb_substr($url['path'], (mb_strlen($gepiPath) + 1));
 }
 $test = sql_query1("select user_statut from plugins_autorisations where user_statut='".$_SESSION['statut']."' and fichier='".$nom_fichier."' and auth='V'");
  if ($test!=-1)
   return true;
  else
    return false;
}
}
/*
$_login : l'identifiant de l'utilisateur
$_lien_item : le nom du script vers lequel pointe le menu. Du type mod_plugins/nom_du_plugin/nom_du_fichier.php
*/
function calcul_autorisation_gestion_ateliers($_login,$_lien_item){
	// Si l'appel se fait depuis les scripts générant la page d'accueil il faut supprimer le chemin
	$_lien_item=basename($_lien_item);

// Cas particulier de index_suivi.php
  if ($_lien_item=="index_suivi.php") {
	// Pour avoir accès à index_suivi.php il faut être prof et avoir des élèves ou avoir des droits
	// sur droits sur droit_special_index_suivi.txt

    // On teste si le l'utilisateur est prof ayant des élèves
    $test_prof_suivi = sql_count(sql_query("SELECT professeur FROM j_eleves_professeurs  WHERE professeur = '".$_login."'"));
    if (($test_prof_suivi == "0") and !(calcul_autorisation_gestion_ateliers($_login,"droit_special_index_suivi.txt")))
	// Si le prof n'a pas d'élève et s'il n'a pas de droits sur droit_special_index_suivi.txt alors il n'a pas accès à index_suivi.php
        return FALSE;
  }
  
// Cas général
  $test1 = sql_query1("SELECT count(script) FROM bas_gestion_acces_scripts WHERE (acces = '_tous_' and script = '".$_lien_item."')");
  if ($test1 == 1) {
	// Cas où tous les utlisateurs ont accès à ce script, on teste alors si le statut de l'utilisateur
	// est parmi les statuts donnant accès  à ce script définis dans plugin.xml
    $_statut = sql_query1("select statut from utilisateurs where login='".$_login."'");
    $test2 = sql_query1("SELECT count(user_statut) FROM plugins_autorisations WHERE (user_statut  = '".$_statut."' and  fichier = 'mod_plugins/gestion_ateliers/".$_lien_item."')");
    if ($test2 == 1)
		// dans plugin.xml le statut de l'utilisateur lui donne accès au script
      return TRUE;
    else
		// dans plugin.xml le statut de l'utilisateur ne lui donne pas accès au script
      return FALSE;
  } else {


	// Cas où tous les utlisateurs n'ont pas accès à ce script
    $call_prof_resp = mysql_query("SELECT * FROM bas_gestion_acces_scripts WHERE (acces = '" . $_login . "' and script = '".$_lien_item."')");
    $nb_result = mysql_num_rows($call_prof_resp);
    if ($nb_result != 0)
		// le login de l'utilisateur est accocié au script dans bas_gestion_acces_scripts
		// il a donc accès au script
      return TRUE;
    else {

      $_statut = sql_query1("select statut from utilisateurs where login='".$_login."'");
      $test = sql_query1("select count(script) from bas_gestion_acces_scripts  where script='".$_lien_item."' and acces='_".$_statut."_'");
      if ($test == 1)
		// le statut de l'utilisateur est accocié au script dans bas_gestion_acces_scripts
		// il a accès donc au script
        return TRUE;
      else
		// le statut de l'utilisateur n'est pas accocié au script dans bas_gestion_acces_scripts
		// il n'a donc pas accès au script
        return FALSE;
    }
  }
}


function affiche_props_bas_par_eleve($_login,$complet='y') {
    $entete='o';
    $test = sql_query1("select count(id_eleve) from bas_j_eleves_bas where id_eleve ='".$_login."'");
    if ($test <= 0) {
        return "";
        die();
    }
    $eleve_nom = sql_query1("select nom from eleves where login = '".$_login."'");
    $eleve_prenom = sql_query1("select prenom from eleves where login = '".$_login."'");

    $call_bas = sql_query("select id_bas, nom, date_bas from bas_bas where aff_affectations_eleves='y' order by nom");
    $nb_bas = mysql_num_rows($call_bas);
    $k = 0;
    // Première boucle sur les bas
    // $k : numéro de l'atelier
    while ($k < $nb_bas) {
        $numero_bas = mysql_result($call_bas,$k,'id_bas');
        $nom_bas = mysql_result($call_bas,$k,'nom');
        $date_bas = mysql_result($call_bas,$k,'date_bas');
        $j = 1;
        $affiche_ligne = "non";
        $html_partiel = "";
        // Deuxième boucle sur le numéro de séquence
        while ($j < 4) {
            // $j : numéro de la séquence
            $call_prop = sql_query("select e.priorite, e.num_choix, e.id_bas
            from bas_j_eleves_bas e
            Where
            e.id_eleve = '".$_login."' and
            e.num_sequence = '".$j."' and
            e.num_bas = '".$numero_bas."'
            order by e.num_choix
            ");
            $nb_prop_par_seq = mysql_num_rows($call_prop);
            $i = 0;
            // Pour une séquen,ce donnée, boucle sur les choix 0, 1 et 2
            $mess = "";
            while ($i < $nb_prop_par_seq) {
                $affiche_ligne = "oui";
                // $i : numéro du choix
                $priorite = mysql_result($call_prop,$i,'e.priorite');
                $num_choix = mysql_result($call_prop,$i,'e.num_choix');
                $id_bas = mysql_result($call_prop,$i,'e.id_bas');
                if ($num_choix == 0)
                    if ($complet=='y')
                        $mess .= "<b>Affectation finale : </b>";
                else if (($num_choix == 1) and ($complet=='y'))
                    $mess .= "<b>Choix N° 1 : </b>";
                else if (($num_choix == 2) and ($complet=='y'))
                    $mess .= "<b>Choix N° 2 : </b>";

                if ($id_bas == 'abs') {
                  if ($num_choix == 0)
                    $mess .= "Absent";
  
                } else if ($id_bas == '-1') {
                    $mess .= "-";
                } else {
                    if (($num_choix==0) or ($complet=='y')) {
                    // On appelle les infos concernant la proposition
                    $call_infos_prop = sql_query("select p.type, p.titre, p.precisions, p.responsable, p.coresponsable, p.id_matiere, p.num_bas, p.salle, p.duree, p.id_prop, p.statut
                    from bas_propositions p
                    Where
                    p.id_bas = '".$id_bas."' and
                    p.num_bas = '".$numero_bas."'
                    ");
                    $type = @mysql_result($call_infos_prop,0,'p.type');
                    $titre = @mysql_result($call_infos_prop,0,'p.titre');
                    $precisions = @mysql_result($call_infos_prop,0,'p.precisions');
                    $responsable = @mysql_result($call_infos_prop,0,'p.responsable');
                    $coresponsable = @mysql_result($call_infos_prop,0,'p.coresponsable');
                    $id_matiere = @mysql_result($call_infos_prop,0,'p.id_matiere');
                    $num_bas = @mysql_result($call_infos_prop,0,'p.num_bas');
                    $salle = @mysql_result($call_infos_prop,0,'p.salle');
                    $duree  = @mysql_result($call_infos_prop,0,'p.duree ');
                    $id_prop = @mysql_result($call_infos_prop,0,'p.id_prop');
                    $statut = @mysql_result($call_infos_prop,0,'p.statut');

                    // Responsanel et coresponsable
                    $text_responsable = "";
                    if ($responsable != "") {
                        $civilite = sql_query1("select civilite from utilisateurs where login = '".$responsable."'");
                        $nom_prof = sql_query1("select nom from utilisateurs where login = '".$responsable."'");
                        if ($nom_prof != -1)
                            $text_responsable = "<i>".$civilite." ".$nom_prof;
                        else
                            $text_responsable = "<i>".$responsable;
                    }
                    if ($coresponsable != "") {
                        $civilite = sql_query1("select civilite from utilisateurs where login = '".$coresponsable."'");
                        $nom_prof = sql_query1("select nom from utilisateurs where login = '".$coresponsable."'");
                        if ($nom_prof != -1)
                            $text_responsable .= ", ".$civilite." ".$nom_prof;
                        else
                            $text_responsable .= ", ".$coresponsable;
                    }
                    $text_responsable .= "</i>";
                    // Matière
                    $nom_matiere = sql_query1("select nom_complet from bas_matieres where matiere = '".$id_matiere."'");
                    if ($nom_matiere == -1) $nom_matiere = "";
                    // Type
                    $text_type = "";
                    if ($type == "S")
                        $text_type = "Soutien";
                    else if ($type == "A")
                        $text_type = "Approfondissement";
                    else if ($type == "R")
                        $text_type = "Remédiation";
                    else if ($type == "D")
                        $text_type = "Public désigné";

                    }
                    if ($num_choix == 0) {
                        if ($complet=='y') {
                            $mess .= $id_prop. " (".$nom_matiere." - ".$text_responsable;
                            if ($statut == 'a') $mess .= " - <font color='red'>Annulé</font> ";
                            $mess .= ")";
                        } else
                            $mess .= "<b>".$nom_matiere."</b> - ".$text_responsable;

                        $mess .= "<br /><b>Titre : </b>".$titre
                        ."<br /><b>Type : </b>".$text_type;
                    } else if ($complet=='y') {
                        $mess .= $id_prop. " (".$nom_matiere." ".$text_type;
                        if ($statut == 'a') $mess .= " - <font color='red'>Annulé</font> ";
                        $mess .= ")";
                    }




                }
                if ($complet=='y')
                $mess .= "<br /><br />";
                $i++;
            }
            $html_partiel .= "<td><span class='small'>".$mess."&nbsp;</span></td>";
            $j++;
        }
        if ($affiche_ligne == "oui")  {
            if ($entete=='o') {
              $html = "<h3>Tableau récapitulatif des choix ".$NomAtelier_preposition.$NomAtelier_pluriel." de ".$eleve_prenom." ".$eleve_nom."</h3>";
              $html .= "<table border=\"1\" cellpadding=\"3\" width=\"100%\">\n";
              $html .= "<tr><td><span class='small'><b>.ucfirst($NomAtelier_singulier).</b></span></td><td><span class='small'><b>1ère séquence</b></span></td><td><span class='small'><b>2ème séquence</b></span></td><td><span class='small'><b>3ème séquence</b></span></td></tr>\n";
            }
            $entete='n';

        $html .= "<tr><td><span class='small'>".$nom_bas." (".$date_bas.")</span></td>".$html_partiel."</tr>\n";
        }
        $k++;
    }
    $html .= "</table>\n";
    return $html;
}

function tableau_periode($numero_bas) {
    $sql = "select num_creneau, intitule_creneau from bas_creneaux where id_bas='".$numero_bas."' order by num_creneau";
    $res = mysql_query($sql);
    $num_per = mysql_num_rows($res);
    $i = 0;
    while ($i < $num_per) {
        $per[$i+1] = str_replace(" ", "&nbsp;", mysql_result($res,$i,"intitule_creneau"));
        $i++;
    }
    return $per;
}
?>