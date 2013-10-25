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
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html lang="fr">
<head>
<meta HTTP-EQUIV="Content-Type" content="text/html; charset=iso-8859-1" />
<META HTTP-EQUIV="Pragma" CONTENT="no-cache" />
<META HTTP-EQUIV="Cache-Control" CONTENT="no-cache" />
<META HTTP-EQUIV="Expires" CONTENT="0" />
<title><?php echo getSettingValue("gepiSchoolName"); ?> : consultation des propositions</title>
<link rel="stylesheet" type="text/css" href="<?php echo($gepiPath); ?>/style.css" />
<LINK REL="SHORTCUT ICON" href="../favicon.ico" /></head>
<body>
<?php if (isset($titre_page)) {?>
  <!-- Header start -->
  <div class="header">
  <!-- Page title, access rights -->
    <div class="headerLeft">
    <?php
    echo("<h3>" . $titre_page . "</h3>");
    ?>
    </div>

    <!-- User name, status, main matter, home, logout, account management -->
    <div class="headerRight" style="font:normal small-caps normal 12pt Tahoma;line-height: 200%;">
    <?php echo getSettingValue("gepiSchoolName"); ?> : Consultation des propositions 

    </div>

    <!-- Contact -->
    <div class="headerBottom">
    <?php
    echo "<a href=\"http://gepi.mutualibre.org\">Visiter le site de GEPI</a> - ";
    echo "<a href=\"mailto:" . getSettingValue("gepiAdminAdress") . "\">Contacter l'administrateur</a>";
    ?>
    </div>
    <hr class="header"/>
  </div>
 <?php } ?>

