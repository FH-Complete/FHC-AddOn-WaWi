<?php
/* Copyright (C) 2010 FH Technikum Wien
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307, USA.
 *
 * Authors: Christian Paminger <christian.paminger@technikum-wien.at>,
 *          Andreas Oesterreicher <andreas.oesterreicher@technikum-wien.at>,
 *          Karl Burkhart <burkhart@technikum-wien.at> and
 *          Andreas Moik <moik@technikum-wien.at>.
 */
/**
 * Auswertung der Bestellungen und Rechnungen auf Kostenstellen
 */

require_once(dirname(__FILE__).'/../../config.inc.php');
require_once('../auth.php');
require_once(dirname(__FILE__).'/../../include/wawi_benutzerberechtigung.class.php');
require_once(dirname(__FILE__).'/../../../../include/functions.inc.php');
require_once(dirname(__FILE__).'/../../include/wawi_rechnung.class.php');
require_once(dirname(__FILE__).'/../../include/wawi_bestellung.class.php');
require_once(dirname(__FILE__).'/../../include/wawi_kostenstelle.class.php');
require_once(dirname(__FILE__).'/../../../../include/studiensemester.class.php');
require_once(dirname(__FILE__).'/../../../../include/tags.class.php');
require_once(dirname(__FILE__).'/../../../../include/geschaeftsjahr.class.php');
require_once(dirname(__FILE__).'/../../../../include/datum.class.php');
require_once(dirname(__FILE__).'/../../../../include/firma.class.php');

$user = get_uid();
$rechte = new wawi_benutzerberechtigung();
$rechte->getBerechtigungen($user);

$kst_array = $rechte->getKostenstelle();

if(numberOfElements($kst_array)==0)
	die('Sie benoetigen eine Kostenstellenberechtigung um diese Seite anzuzeigen');

$datum_obj = new datum();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
	<title>WaWi - Firmen zuletzt angelegt - Auswertung</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

	<link rel="stylesheet" href="../../../../skin/jquery.css" type="text/css">
	<link rel="stylesheet" href="../../../../skin/tablesort.css" type="text/css">
	<link rel="stylesheet" href="../../../../skin/fhcomplete.css" type="text/css">
	<link rel="stylesheet" href="../../skin/wawi.css" type="text/css">

	<script type="text/javascript" src="../../../../vendor/jquery/jquery1/jquery-1.12.4.min.js"></script>
	<script type="text/javascript" src="../../../../vendor/christianbach/tablesorter/jquery.tablesorter.min.js"></script>

</head>
<body>

<?php

	$db = new basis_db();

	$firma = new firma();
	$kw = @$_POST['kw'] != null ? $_POST['kw'] : 0;
	$jahr = @$_POST['jahr'] != null ? $_POST['jahr'] : 0;
	if ($kw == 0 || $jahr == 0)
	{
		echo "<h1>Bericht - Firmen angelegt innerhalb der letzten 7 Tage</h1>";
	} else {
		echo "<h1>Bericht - Firmen angelegt innerhalb KW $kw/$jahr</h1>";
	}
	echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">";
	echo "KW <input type=\"text\" name=\"kw\" value=\"".($kw>0?$kw:'')."\" maxlength=\"3\" style=\"width:50px\"/>";
	echo " Jahr <input type=\"text\" name=\"jahr\" value=\"".($jahr>0?$jahr:'')."\" maxlength=\"4\" style=\"width:50px\"/>";
	echo "<input type=\"submit\" value=\"Anzeigen\" name=\"anzeigen\">";
	echo "</form>";

	if ($kw>0 && $jahr>0)
	{
		$changes = $firma->getChangesByKW($kw, $jahr);
	} else {
		$changes = $firma->getLatestChanges();
	}
	//echo "kw $kw jahr $jahr<br/>";

	if($changes)
	{

		echo '
				<script type="text/javascript">
				$(document).ready(function()
				{
					$("#myTable").tablesorter(
					{
						sortList: [[2,0]],
						widgets: ["zebra"]
					});
				});
				</script>

				<table id="myTable" class="tablesorter">
				<thead>
				<tr>
					<th>&nbsp;</th>
					<th>ID</th>
					<th>Name</th>
					<th>Nation</th>
					<th>Adresse</th>
				</tr>
				</thead>
				<tbody>';


		foreach($firma->result as $row)
		{
			echo '<tr>';
			echo '<td><a href="../firma.php?method=update&amp;id='.$row->firma_id.'" title="Bearbeiten"> <img src="../../skin/images/edit_wawi.gif"> </a></td>';
			echo '<td>',$row->firma_id,'</td>';
			echo '<td>',$row->name,'</td>';
			echo '<td>',$row->nation,'</td>';
			echo '<td>',$row->strasse,' ',$row->plz,' ',$row->ort,'</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
	}
	else
	{
		echo "Keine Änderungen vorhanden.";
	}
?>
<br /><br /><br /><br /><br /><br /><br /><br />
</body>
</html>
