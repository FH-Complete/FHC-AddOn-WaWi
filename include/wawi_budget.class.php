<?php
/* Copyright (C) 2010 Technikum-Wien
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
 */


/**
 * Klasse WaWi Budget
 */

require_once(dirname(__FILE__).'/../../../include/basis_db.class.php');
require_once(dirname(__FILE__).'/../../../include/sprache.class.php');

class wawi_budget extends basis_db
{
	public $new;					//  boolean
	public $result = array();		//  Konto Objekt
	public $user; 					//  string

	//Tabellenspalten
	public $kostenstelle_id; 				// integer
	public $geschaeftsjahr_kurzbz;	// string
	public $bezeichnung;						// string
	public $budgetposition_id;      // integer
	public $budgetposten;						// string
	public $konto_id;								// integer
	public $betrag;									// float
	public $kommentar;							// string
	public $projekt_id;  						// integer

	/**
	 * Konstruktor
	 * @param $budgetposition_id ID des Budgetpostens der geladen werden soll (Default=null)
	 */
	public function __construct($budgetposition_id=null)
	{
		parent::__construct();

		if(!is_null($budgetposition_id))
			$this->load($budgetposition_id);
	}

	/**
	 * Laedt das Konto mit der ID $konto_id
	 * @param  $konto_id ID des zu ladenden Kontos
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function load($budgetposition_id)
	{
		if(!is_numeric($budgetposition_id) || $budgetposition_id == '')
		{
			$this->errormsg = 'budgetposition_id muss eine Zahl sein';
			return false;
		}

		$qry = "SELECT * FROM extension.vw_budget_approved WHERE budgetposition_id=".$this->db_add_param($budgetposition_id).';';

		if(!$this->db_query($qry))
		{
			$this->errormsg = 'Fehler bei einer Datenbankabfrage';
			return false;
		}
		if($row = $this->db_fetch_object())
		{
			$this->kostenstelle_id = $row->kostenstelle_id;
			$this->geschaeftsjahr_kurzbz = $row->geschaeftsjahr_kurzbz;
			$this->bezeichnung = $row->bezeichnung;
			$this->budgetposition_id = $row->budgetposition_id;
			$this->budgetposten = $row->budgetposten;
			$this->konto_id = $row->konto_id;
			$this->betrag = $row->betrag;
			$this->kommentar = $row->kommentar;
			$this->projekt_id = $row->projekt_id;
		}
		else
		{
			$this->errormsg = 'Es ist kein Datensatz mit dieser ID vorhanden';
			return false;
		}

		return true;
	}

	/**
	 * Laedt alle Budgetposten
	 * @param $gj Geschäftsjahr
	 * @param $order Sortierreihenfolge
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function getAll($gj=null, $order='bezeichnung ASC')
	{
		$sprache = new sprache();
		$beschreibung = $sprache->getSprachQuery('beschreibung');

		$qry = "SELECT * FROM extension.vw_budget_approved ";

		if(!is_null($gj))
		{
			$qry.=' WHERE geschaeftsjahr_kurzbz='.$this->db_add_param($gj);
		}

		if($order!='')
			$qry .= ' ORDER BY '.$order;

		if(!$this->db_query($qry))
		{
			$this->errormsg = 'Fehler bei einer Datenbankabfrage';
			return false;
		}

		while($row = $this->db_fetch_object())
		{
			$obj = new wawi_budget();

			$obj->kostenstelle_id = $row->kostenstelle_id;
			$obj->geschaeftsjahr_kurzbz = $row->geschaeftsjahr_kurzbz;
			$obj->bezeichnung = $row->bezeichnung;
			$obj->budgetposition_id = $row->budgetposition;
			$obj->budgetposten = $row->budgetposten;
			$obj->konto_id = $row->konto_id;
			$obj->betrag = $row->betrag;
			$obj->kommentar = $row->kommentar;
			$obj->projekt_id = $row->projekt_id;

			$this->result[] = $obj;
		}

		return true;
	}

	/**
	 * Prueft die Variablen auf Gueltigkeit
	 * @return true wenn ok, false im Fehlerfall
	 */
	protected function validate()
	{
		return true;
	}

	/**
	 * Liefert alle Budgetposten die den Suchstring $filter entsprechen
	 * @param $filter String nach dem gefiltert wird
	 * @param $order Sortierkriterium
	 * @return array mit Konten oder false wenn ein Fehler auftritt
	 */
	public function getBudget($gj, $filter, $order='budgetposten')
	{
		$sql_query = "SELECT * FROM extension.vw_budget_approved";

		if($filter!='')
		{
			$sql_query.=" WHERE lower(budgetposten) LIKE  lower('%".$this->db_escape($filter)."%') ".
			            " AND geschaeftsjahr_kurzbz='.$this->db_add_param($gj) ";
		}

		$sql_query .= " ORDER BY $order ;";

		if($this->db_query($sql_query))
		{
			while($row = $this->db_fetch_object())
			{
				$obj = new wawi_budget();

				$obj->kostenstelle_id = $row->kostenstelle_id;
				$obj->geschaeftsjahr_kurzbz = $row->geschaeftsjahr_kurzbz;
				$obj->bezeichnung = $row->bezeichnung;
				$obj->budgetposition_id = $row->budgetposition;
				$obj->budgetposten = $row->budgetposten;
				$obj->konto_id = $row->konto_id;
				$obj->betrag = $row->betrag;
				$obj->kommentar = $row->kommentar;
				$obj->projekt_id = $row->projekt_id;

				$this->result[] = $obj;

			}
		}
		else
		{
			$this->errormsg = 'Fehler beim Laden der Daten';
			return false;
		}
		return true;
	}


	/**
	 *
	 * gibt alle Budgetposten die der übergebenen Kostenstelle zugeordnet sind zurück
	 * @param $kostenstelle_id
	 */
	public function getBudgetFromKostenstelle($gj, $kostenstelle_id)
	{
		if(is_numeric($kostenstelle_id))
		{

			$qry = 'SELECT *
					FROM
						extension.vw_budget_approved
					WHERE
						geschaeftsjahr_kurzbz='.$this->db_add_param($gj).'
						AND kostenstelle_id ='.$this->db_add_param($kostenstelle_id, FHC_INTEGER).'
					ORDER by budgetposten ASC;';

			if(!$this->db_query($qry))
			{
				$this->errormsg = "Fehler bei der Abfrage aufgetreten.";
				return false;

			}
			while($row = $this->db_fetch_object())
			{
				$obj = new wawi_konto();

				$obj->kostenstelle_id = $row->kostenstelle_id;
				$obj->geschaeftsjahr_kurzbz = $row->geschaeftsjahr_kurzbz;
				$obj->bezeichnung = $row->bezeichnung;
				$obj->budgetposition_id = $row->budgetposition_id;
				$obj->budgetposten = $row->budgetposten;
				$obj->konto_id = $row->konto_id;
				$obj->betrag = $row->betrag;
				$obj->kommentar = $row->kommentar;
				$obj->projekt_id = $row->projekt_id;

				$this->result[] = $obj;
			}
			return true;
		}
		else
		return false;
	}

}
?>
