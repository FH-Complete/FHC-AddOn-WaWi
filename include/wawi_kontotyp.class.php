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
 * Klasse WaWi Kontotyp
 */

require_once(dirname(__FILE__).'/../../../include/basis_db.class.php');
require_once(dirname(__FILE__).'/../../../include/sprache.class.php');

class wawi_kontotyp extends basis_db
{
	public $new;					//  boolean
	public $result = array();		//  Konto Objekt

	//Tabellenspalten

	public $kontotyp_kurzbz;		//  string
	public $bezeichnung;			//  string
	public $description;            //  string
	public $sort;					//  boolean

	public $sprache;


	/**
	 * Konstruktor
	 * @param $konto_id ID des Kontos das geladen werden soll (Default=null)
	 */
	public function __construct($kontotyp_kurzbz=null)
	{
		parent::__construct();


		if(!is_null($kontotyp_kurzbz))
			$this->load($kontotyp_kurzbz);
	}

	/**
	 * Laedt den Kontotyp mit der kontotyp_kurzbz
	 * @param  $kontotyp_kurzbz des zu ladenden Kontotyps
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function load($kontotyp_kurzbz)
	{

		$qry = "SELECT * FROM wawi.tbl_kontotyp WHERE kontotyp_kurzbz=".$this->db_add_param($kontotyp_kurzbz).';';

		if(!$this->db_query($qry))
		{
			$this->errormsg = 'Fehler bei einer Datenbankabfrage';
			return false;
		}
		if($row = $this->db_fetch_object())
		{
			$this->kontotyp_kurzbz	= $row->kontotyp_kurzbz;
			$this->bezeichnung = $row->bezeichnung;
			$this->description = $row->description;
			$this->sort = $row->sort;
		}
		else
		{
			$this->errormsg = 'Es ist kein Datensatz mit dieser kurzbz vorhanden';
			return false;
		}

		return true;
	}

	/**
	 * Laedt alle Konttypen
	 * @param $order Sortierreihenfolge
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function getAll($order='kontotyp_kurzbz ASC')
	{

		$qry = "SELECT * FROM wawi.tbl_kontotyp";

		if($order!='')
			$qry .= ' ORDER BY '.$order;

		if(!$this->db_query($qry))
		{
			$this->errormsg = 'Fehler bei einer Datenbankabfrage';
			return false;
		}

		while($row = $this->db_fetch_object())
		{
			$obj = new wawi_kontotyp();

			$obj->kontotyp_kurzbz = $row->kontotyp_kurzbz;
			$obj->bezeichnung = $row->bezeichnung;
			$obj->description = $row->description;
			$obj->sort = $row->sort;

			$this->result[] = $obj;
		}

		return true;
	}
}

?>
