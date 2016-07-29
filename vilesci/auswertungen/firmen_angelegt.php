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
 *          Andreas Oesterreicher <andreas.oesterreicher@technikum-wien.at> and
 *          Karl Burkhart <karl.burkhart@technikum-wien.at>.
 */
/**
 * Auswertung der Bestellungen und Rechnungen auf Kostenstellen
 */

require_once(dirname(__FILE__).'/../../../../config/wawi.config.inc.php');
//require_once('../auth.php');
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
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($user);

$kst_array = $rechte->getKostenstelle();

if(count($kst_array)==0)
	die('Sie benoetigen eine Kostenstellenberechtigung um diese Seite anzuzeigen');

$datum_obj = new datum();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
	<title>WaWi - Firmen zuletzt angelegt - Auswertung</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	
	<link rel="stylesheet" href="../../skin/jquery.css" type="text/css">
	<link rel="stylesheet" href="../../skin/tablesort.css" type="text/css">
	<link rel="stylesheet" href="../../skin/fhcomplete.css" type="text/css">
	<link rel="stylesheet" href="../../skin/wawi.css" type="text/css">
	
			
	<script type="text/javascript" src="../../../../include/js/jquery1.9.min.js"></script>	 
	
</head>
<body>
<h1>Bericht - Firmen angelegt innerhalb der letzten 7 Tage</h1>
<?php

	$db = new basis_db();
	
	$firma = new firma();
	if($firma->getLatestChanges())
	{
	
		echo '<br /><br />
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