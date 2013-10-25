<?php
    // Code afffichant la liste des activités pour une filière donnée ($id_filiere)
    // et un numéro donné ($numero_bas)
    // données sur le bas
    // $affiche_titre = 0 : on n'affichae pas le titre (1 par défaut)
    if (!isset($affiche_titre)) $affiche_titre = 1;

    $date_bas = sql_query1("select date_bas from bas_bas where id_bas='".$numero_bas."'");
    $nom_bas = sql_query1("select nom from bas_bas where id_bas='".$numero_bas."'");
    $close_bas = sql_query1("select close_bas from bas_bas where id_bas='".$numero_bas."'");
    $date_limite = sql_query1("select date_limite from bas_bas where id_bas='".$numero_bas."'");
    $description_bas = sql_query1("select description_bas from bas_bas where id_bas='".$numero_bas."'");
    $inscription_bas = sql_query1("select inscription_bas from bas_bas where id_bas='".$numero_bas."'");
    $aff_affectations_eleves = sql_query1("select aff_affectations_eleves from bas_bas where id_bas='".$numero_bas."'");
    // Constitution du tableau $per
    $per =  tableau_periode($numero_bas);

    $req = mysql_query("select id_filiere, nom_filiere from bas_filieres");
    $nb_filieres = mysql_num_rows($req);

    if ($affiche_titre == 1) {
        if ($id_filiere != "-1") {     
            $nom_filiere = sql_query1("select nom_filiere from bas_filieres where id_filiere='".$id_filiere."'");
        } else {
            $j = 0;
            $premier = 'yes';
            $nom_filiere = "";
            while ($j < $nb_filieres) {
                $id_filiere2 = mysql_result($req,$j,'id_filiere');
                $pub = "reg_public_".$id_filiere2;
                if (isset($_POST[$pub])) {
                    if ($premier == 'yes') $premier = 'no'; else $nom_filiere .= " - ";
                    $nom_filiere .= mysql_result($req,$j,'nom_filiere');
                }
                $j++;
            }
        }
        echo "<p class='grand'>Filière : ".$nom_filiere." - ".$nom_bas." du ".$date_bas." - ".$description_bas."</p>\n";
    }

    // On va chercher les actvités déjà existants, et on les affiche.

    $h = 1;
    while ($h < count($per)+1) {
        if (count($per) == 1)
            echo "<p><b>Activités : ".$per[$h]." :</b></p>\n";
        else
            echo "<p><b>Activités commencant à ".$per[$h]." :</b></p>\n";
        if ($id_filiere != '-1') {
            $calldata = mysql_query("SELECT * FROM bas_propositions
            WHERE (num_bas= '".$numero_bas."' and public_".$id_filiere." != '' and debut_final = '".$h."' and type != 'R' and type != 'D') ORDER BY id_prop");
        } else {
            $sqlprop = "SELECT * FROM bas_propositions
            WHERE (num_bas= '".$numero_bas."' and (";
            $j = 0;
            $premier = 'yes';
            while ($j < $nb_filieres) {
                $id_filiere2 = mysql_result($req,$j,'id_filiere');
                $pub = "reg_public_".$id_filiere2;
                if (isset($_POST[$pub])) {
                    if ($premier == 'yes') {
                        $premier = 'no';
                    } else {
                        $sqlprop .= " OR ";
                    }
                    $sqlprop .= " public_".$id_filiere2." != ''";
                    }
                $j++;
            }
            $sqlprop .= ") and debut_final = '".$h."' and type != 'R' and type != 'D') ORDER BY id_prop";
            $calldata = mysql_query($sqlprop);
        }
        $nombreligne = mysql_num_rows($calldata);
        echo "<table style='width:100%' cellpadding='1' border='1'>";
        echo "<tr>\n";
        echo "<td><span class=\"style_bas_titre\">N°&nbsp;identifiant<br /><i>Matière</i></span></td>\n
        <td><span class=\"style_bas_titre\">Type</span></td>\n
        <td><span class=\"style_bas_titre\">Intitulé de l'activité<br /><i>Brève description</i></span></td>";
        echo "<td><span class=\"style_bas_titre\">Public</span></td>";
        echo "<td><span class=\"style_bas_titre\">Animateur</span></td>";
        echo "<td><span class=\"style_bas_titre\">Nb. max.<br />élèves</span></td>";
        if ($aff_affectations_eleves == 'y')
            echo "<td><span class=\"style_bas_titre\">Elèves<br />inscrits</span></td>";
        if (count($per) != 1)
            echo "<td><span class=\"style_bas_titre\">Durée</span></td>";

        echo "<td><span class=\"style_bas_titre\">Salle</span></td>";
        echo "</tr>\n";

        $i = 0;
        while ($i < $nombreligne) {
            $bas_statut = @mysql_result($calldata, $i, "statut");
            $bas_id_prop = @mysql_result($calldata, $i, "id_prop");
            $bas_type = @mysql_result($calldata, $i, "type");
            if ($bas_type == "S") {
                $bas_type = "Soutien";
            } else if ($bas_type == "A") {
                $bas_type = "Approf.";
            } else if ($bas_type == "R") {
                $bas_type = "Reméd.";
            } else if ($bas_type == "P") {
                $bas_type = "Pub. désigné";
            } else  {
                $bas_type = "-";
            }
            $bas_titre = @mysql_result($calldata, $i, "titre");
            $bas_precisions = @mysql_result($calldata, $i, "precisions");
            $bas_matiere = mysql_result($calldata, $i, "id_matiere");
            $nom_matiere_prop = sql_query1("select nom_complet from bas_matieres where matiere = '".$bas_matiere."'");
            $j = 0 ;
            while ($j < $nb_filieres) {
                $id_filiere2 = mysql_result($req,$j,'id_filiere');
                $pub = "public_".$id_filiere2;
                $$pub = mysql_result($calldata, $i, "public_".$id_filiere2);
                $j++;
            }
            
            
        $public = '';
        $n=1;
        while ($n<NB_NIVEAUX_FILIERES+1) {
          $flag=1;
          $pub="";
          foreach($tab_filière[$n]["id"] as $key => $_id){
              $temp = "public_".$_id;
              if ($$temp=='') $flag=0;
              if ($$temp!='') $pub .= $tab_filière[$n]["nom"][$key]."<br />"; 
          }
          if ($flag==1) {
              $public .= $intitule_filiere[$n]."<br />";
          } else {
              $public .=$pub;
          }
          $n++;        
        }


        $bas_responsable = @mysql_result($calldata, $i, "responsable");
        $nom_prof = sql_query1("select nom from utilisateurs where login='".$bas_responsable."'");
        if ($nom_prof != -1) {
            $civilite = sql_query1("select civilite from utilisateurs where login = '".$bas_responsable."'");
            $bas_responsable = $civilite." ".$nom_prof;
        }

        $bas_duree = @mysql_result($calldata, $i, "duree");
        $bas_salle = @mysql_result($calldata, $i, "salle");
        if ($bas_salle=='') $bas_salle= '-'; else $bas_salle = sql_query1("select nom_court_salle from bas_salles where id_salle='".$bas_salle."'");
        $bas_nb_max = @mysql_result($calldata, $i, "nb_max");
        if ($bas_nb_max == 0) $bas_nbmax = "-"; else $bas_nbmax = $bas_nb_max;
        $id_bas = @mysql_result($calldata, $i, "id_bas");
        // Max élèves inscrits
        $nb_bloque = sql_query1("select nb_bloque from bas_propositions where id_bas = '".$id_bas."'");
        // cas des affectation : Calcul du nombre d'affectés
        $nb_inscrit = sql_query1("select count(id_eleve) from bas_j_eleves_bas where
        num_bas = '".$numero_bas."' and
        id_bas = '".$id_bas."' and
        num_choix='0' and
        num_sequence='".$h."'
        ");
        // Cas des inscriptions : Calcul du nombre d'inscrits
        $nb_affect = sql_query1("select count(id_eleve) from bas_j_eleves_bas where
        num_bas = '".$numero_bas."' and
        id_bas = '".$id_bas."' and
        num_choix='1' and
        num_sequence='".$h."'
        ");
        if ($inscription_bas == "a")
            // Cas des affectation
            if (($nb_bloque != -1) and ($nb_bloque <= $nb_inscrit)) $flag_bloque = 'y'; else $flag_bloque = 'n';
        else
            // Cas des affectation
            if (($nb_bloque != -1) and ($nb_bloque <= $nb_affect)) $flag_bloque = 'y'; else $flag_bloque = 'n';

        if ($bas_statut == 'a') {
            echo "<tr style=\"background:#C0C0C0\">";
        } else if ($flag_bloque == 'y') {
            echo "<tr style=\"background:#ffC0C0\" >";
        } else
            echo "<tr>\n";
        echo "<td><span class=\"style_bas\"><b>$bas_id_prop</b><br /><i>$nom_matiere_prop</i></span></td>\n";
        echo "<td><span class=\"style_bas\">$bas_type</span></td>\n";
        if ($bas_precisions != '') $bas_precisions = "<br />".$bas_precisions;
        $bas_titre_precisions = $bas_titre;
        if ($bas_precisions != "") $bas_titre_precisions .= "<i>".$bas_precisions."</i>";
        if ($bas_statut == 'a')
            $bas_titre_precisions .= "<br /></span><div style='text-align:center'><span class=\"style_bas\" style='color:red'><b>*** ACTVITE ANNULEE ! ***</b></span></div><span class=\"style_bas\">&nbsp;";
        if ($flag_bloque == 'y')
            if ($inscription_bas == "a")
                $bas_titre_precisions .= "<br /></span><div style='text-align:center'><span class=\"style_bas\" style='color:red'><b>*** Affectation impossible pour cause de sureffectif ! ***</b></span></div><span class=\"style_bas\">&nbsp;";
            else
                if ($active_blocage == "y")
                    $bas_titre_precisions .= "<br /></span><div style='text-align:center'><span class=\"style_bas\" style='color:red'><b>*** Inscriptions bloquées pour cause de sureffectif ! ***</b></span></div><span class=\"style_bas\">&nbsp;";
                else
                    $bas_titre_precisions .= "<br /></span><div style='text-align:center'><span class=\"style_bas\" style='color:red'><b>*** Avertissement : le nombre d'inscrits est actuellement supérieur à l'effectif souhaité. ***</b></span></div><span class=\"style_bas\">&nbsp;";

        echo "<td><span class=\"style_bas\">$bas_titre_precisions</span></td>\n";
        echo "<td><span class=\"style_bas\">$public</span></td>\n";
        echo "<td><span class=\"style_bas\">$bas_responsable</span></td>\n";
        echo "<td><span class=\"style_bas\">$bas_nbmax</span></td>\n";
        if ($aff_affectations_eleves == 'y') {
            if (($nb_inscrit > $bas_nb_max) and ($bas_nb_max!=0))
                if (($nb_inscrit <= $bas_nb_max*5/4) and ($bas_nb_max!=0))
                    $nb_inscrit = "<td style=\"background:#FF9D9D\" ><b>".$nb_inscrit."</b></td>\n";
                else
                    $nb_inscrit = "<td style=\"background:#FF0000\"><b>".$nb_inscrit."</b></td>\n";
            else if (($nb_inscrit > 35) and ($bas_nb_max==0))
                    $nb_inscrit = "<td style=\"background:#FF0000\" ><b>".$nb_inscrit."</b></td>\n";
            else
                $nb_inscrit = "<td><b>".$nb_inscrit."</b></td>\n";
            echo $nb_inscrit;
        }
        if (count($per) != 1)
            if ($bas_duree > 1)
                echo "<td><span class=\"style_bas\"><b>".$bas_duree." h</b></span></td>\n";
            else
                echo "<td><span class=\"style_bas\">".$bas_duree." h</span></td>\n";
        echo "<td><span class=\"style_bas\">$bas_salle</span></td>\n";
        echo "</tr>\n";
        $i++;
    }
    echo "</table>";

    // Les actvités de Remédiation
    if ($id_filiere != '-1') {
        $calldata_r = mysql_query("SELECT * FROM bas_propositions
        WHERE (num_bas= '".$numero_bas."' and public_".$id_filiere." != '' and debut_final = '".$h."' and (type = 'R' or type = 'D')  and statut='v') ORDER BY id_prop");
    } else {
            $sqlprop = "SELECT * FROM bas_propositions
            WHERE (num_bas= '".$numero_bas."' and (";
            $j = 0;
            $premier = 'yes';
            while ($j < $nb_filieres) {
                $id_filiere2 = mysql_result($req,$j,'id_filiere');
                $pub = "reg_public_".$id_filiere2;
                if (isset($_POST[$pub])) {
                    if ($premier == 'yes') {
                        $premier = 'no';
                    } else {
                        $sqlprop .= " OR ";
                    }
                    $sqlprop .= " public_".$id_filiere2." != ''";
                    }
                $j++;
            }
            $sqlprop .= ") and debut_final = '".$h."' and (type = 'R' or type = 'D')  and statut='v') ORDER BY id_prop";
            $calldata_r = mysql_query($sqlprop);
    }
    $nombreligne_r = mysql_num_rows($calldata_r);
    if ($nombreligne_r != '0') {
        if (count($per) == 1)
            echo "<p><b>Activités de remédiation ou à public désigné  - ".$per[$h]." (*** Public désigné ***) :</b></p>\n";
        else
            echo "<p><b>Activités de remédiation ou à public désigné commencant à ".$per[$h]." (*** Public désigné ***) :</b></p>\n";
        echo "<table style='width:100%' cellpadding='1' border='1'>";
        echo "<tr>
        <td><span class=\"style_bas_titre\">N°&nbsp;identifiant<br /><i>Matière</i></span></td>\n
        <td><span class=\"style_bas_titre\">Type</span></td>\n
        <td><span class=\"style_bas_titre\">Intitulé de l'activité<br /><i>Brève description</i></span></td>";
        echo "<td><span class=\"style_bas_titre\">Public</span></td>
        <td><span class=\"style_bas_titre\">Animateur</span></td>
        <td><span class=\"style_bas_titre\">Nb. max.<br />élèves</span></td>";
        if (count($per) != 1)
            echo "<td><span class=\"style_bas_titre\">Durée</span></td>";
        echo "<td><span class=\"style_bas_titre\">Salle</span></td>";
        echo "</tr>\n";
        $i = 0;
        while ($i < $nombreligne_r) {
            $bas_statut = @mysql_result($calldata, $i, "statut");
            $bas_id_prop = @mysql_result($calldata_r, $i, "id_prop");
            $bas_type = @mysql_result($calldata_r, $i, "type");
            if ($bas_type == "R") {
                $bas_type = "Remédiation";
            } else if ($bas_type == "D") {
                $bas_type = "Pub. Désigné";
            } else  {
                $bas_type = "-";
            }
            $bas_titre = @mysql_result($calldata_r, $i, "titre");
            $bas_precisions = @mysql_result($calldata_r, $i, "precisions");
            $bas_matiere = mysql_result($calldata_r, $i, "id_matiere");
            $nom_matiere_prop = sql_query1("select nom_complet from bas_matieres where matiere = '".$bas_matiere."'");
            $j = 0 ;
            while ($j < $nb_filieres) {
                $id_filiere2 = mysql_result($req,$j,'id_filiere');
                $pub = "public_".$id_filiere2;
                $$pub = mysql_result($calldata_r, $i, "public_".$id_filiere2);
                $j++;
           }

            $public = '';
            $n=1;
            while ($n<NB_NIVEAUX_FILIERES+1) {
              $flag=1;
              $pub="";
              foreach($tab_filière[$n]["id"] as $key => $_id){
                  $temp = "public_".$_id;
                  if ($$temp=='') $flag=0;
                  if ($$temp!='') $pub .= $tab_filière[$n]["nom"][$key]."<br />"; 
              }
              if ($flag==1) {
                  $public .= $intitule_filiere[$n]."<br />";
              } else {
                  $public .=$pub;
              }
              $n++;        
            }


            $bas_responsable = @mysql_result($calldata_r, $i, "responsable");
            $nom_prof = sql_query1("select nom from utilisateurs where login='".$bas_responsable."'");
            if ($nom_prof != -1) {
                $civilite = sql_query1("select civilite from utilisateurs where login = '".$bas_responsable."'");
                $bas_responsable = $civilite." ".$nom_prof;
            }
            $bas_duree = @mysql_result($calldata_r, $i, "duree");
            $bas_salle = @mysql_result($calldata_r, $i, "salle");
            if ($bas_salle=='') $bas_salle= '-'; else $bas_salle = sql_query1("select nom_court_salle from bas_salles where id_salle='".$bas_salle."'");
            $bas_nb_max = @mysql_result($calldata_r, $i, "nb_max");
            if ($bas_nb_max == 0) $bas_nbmax = "-"; else $bas_nbmax = $bas_nb_max;
            $id_bas = @mysql_result($calldata_r, $i, "id_bas");
            echo "<tr>\n";
            echo "<td><span class=\"style_bas\"><b>$bas_id_prop</b><br /><i>$nom_matiere_prop</i></span></td>\n";
            echo "<td><span class=\"style_bas\">$bas_type</span></td>\n";
            if ($bas_precisions != '') $bas_precisions = "<br />".$bas_precisions;
            $bas_titre_precisions = $bas_titre;
            if ($bas_precisions != "") $bas_titre_precisions .= "<i>".$bas_precisions."</i>";
            echo "<td><span class=\"style_bas\">$bas_titre_precisions</span></td>\n";
            echo "<td><span class=\"style_bas\">$public</span></td>\n";
            echo "<td><span class=\"style_bas\">$bas_responsable</span></td>\n";
            echo "<td><span class=\"style_bas\">$bas_nbmax</span></td>\n";
            if (count($per) != 1)
                if ($bas_duree > 1)
                    echo "<td><span class=\"style_bas\"><b>".$bas_duree." h</b></span></td>\n";
                else
                    echo "<td><span class=\"style_bas\">".$bas_duree." h</span></td>\n";
            echo "<td><span class=\"style_bas\">$bas_salle</span></td>\n";
            echo "</tr>\n";
            $i++;
        }
        echo "</table>";
    }

    $h++;
    }


?>