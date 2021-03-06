<?php
//error_reporting(E_ALL);
define("NB_NIVEAUX_FILIERES",3);  // Nombre de niveaux de filères  (seconde, première, terminale)
define("NB_FILIERES",8);          // Nombre total de filières (seconde, première L, première S, ..., terminale L, terminale S, ...)
/*
ATTENTIION
1) Par défaut, le nombre maximal de filières est égal à 20
Si vous voulez augmenter ce nombre, il suffit d'ajouter des colonnes public_21, public_22, etc... dans la table bas_propositions

2) pour l'instant, il n'y a pas d'interface permettant de définir les filères. Il faut donc modifier "à la main" la table "bas_filieres".
Les filières 
*/

$intitule_filiere[1]="Toutes secondes";  // Intitulé du premier niveau de filière
$intitule_filiere[2]="Toutes premières";  // Intitulé du deuxième niveau de filière
$intitule_filiere[3]="Toutes terminales";   // Intitulé du troisième niveau de filières
define("NB_MAX_COL",4);           // Affichage -> nombre de colonnes

// Définition des créneaux par défaut.
$per_defaut[1] = "13h50";
$per_defaut[2] = "14h40";
$per_defaut[3] = "15h30";

// Intitulés des "ateliers"
$NomAtelier_singulier="atelier";
$NomAtelier_pluriel="ateliers";
$NomAtelier_preposition="des ";
$NomAtelier_preposition2="de l'";
/*
Remarque à propos des intitulés des "ateliers" : Il faut aussi changer "en dur", le cas échéant, dans le fichier plugin.xml, les intitulé dans les lignes 373 à 378
*/

// Les deux paramètres suivants sont propres à la version "spéciale" utilisée au LP2I -> Ne pas toucher !
$is_LP2I=FALSE;  // Laisser cette variable à False
define("NB_SEQ_BAS_PAR_AM",2);    // Nombre de séquence par atelier (1, 2 ou 3)) -> utilisé pour le décompte des heures dues
?>