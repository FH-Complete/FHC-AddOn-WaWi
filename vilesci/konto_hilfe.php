<?php
/* Copyright (C) 2016 Technikum-Wien
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
 */
$basepath = $_SERVER['DOCUMENT_ROOT'];
require_once $basepath.'/config/wawi.config.inc.php';
require_once '../include/wawi_konto.class.php';
$konto = new wawi_konto(); 
if (!$konto->getAll(true, 'kontonr ASC'))
	exit(' | '.$konto->errormsg);
$result = $konto->result;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Info zu den Konten</title>
</head>
<body>
    <dl>
<?php
	foreach ($result as $r) {
		echo '<dt>'.$r->kurzbz.'</dt>';
		echo '<dd>'.$r->hilfe.'</dd>';
	}
?>      
	</dl>


<br><br><a href="javascript:window.close()">Fenster schließen</a>

</body>
</html>