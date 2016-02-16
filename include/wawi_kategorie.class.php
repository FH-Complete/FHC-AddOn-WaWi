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
 * Authors: Christian Paminger <christian.paminger@technikum-wien.at>,
 *          Andreas Oesterreicher <andreas.oesterreicher@technikum-wien.at> and
 *          Karl Burkhart <burkhart@technikum-wien.at>.
 */
/**
 * Klasse WaWi Konto
 */
$basepath = $_SERVER['DOCUMENT_ROOT'];
require_once($basepath.'/include/basis_db.class.php');
require_once($basepath.'/include/sprache.class.php');

class wawi_bestellung_kategorie extends basis_db
{
	public $new;					//  boolean
	public $result = array();		//  Konto Objekt
	public $user; 					//  string

	//Tabellenspalten
	public $bkategorie_id;				//  integer
	public $beschreibung ;                          //  string 
	public $kurzbz;					//  string
	public $aktiv;					//  boolean
	public $insertamum;        		//  timestamp
	public $insertvon;				//  string
	public $updateamum;         	//  timestamp
	public $updatevon;				//  string	
	
	/**
	 * Konstruktor
	 * @param $konto_id ID des Kontos das geladen werden soll (Default=null)
	 */
	public function __construct($bkategorie_id=null)
	{
		parent::__construct();
		
		if(!is_null($bkategorie_id))
			$this->load($bkategorie_id);
	}

	/**
	 * Laedt das Kategorie mit der ID $$bkategorie_id
	 * @param  $bkategorie_id ID der zu ladenden Kategorie
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function load($bkategorie_id)
	{
		if(!is_numeric($bkategorie_id) || $bkategorie_id == '')
		{
			$this->errormsg = 'bkategorie_id muss eine Zahl sein';
			return false;
		}
                $qry = "SELECT * FROM wawi.tbl_bestellung_kategorie WHERE bkategorie_id =".$this->db_add_param($bkategorie_id, FHC_INTEGER).';';
		 
		if($this->db_query($qry))
		{
                    if($row = $this->db_fetch_object())
                    {
                            $this->bkategorie_id	= $row->bkategorie_id;
                            $this->beschreibung = $row->beschreibung;
                            $this->kommentar = $row->kommentar;
                            $this->aktiv = $this->db_parse_bool($row->aktiv);
                            $this->insertamum = $row->insertamum;
                            $this->insertvon = $row->insertvon;
                            $this->updateamum = $row->updateamum;
                            $this->updatevon = $row->updatevon;
                    }
                    else
                    {
                            $this->errormsg = 'Es ist kein Datensatz mit dieser ID vorhanden';
                            return false;
                    }
                }

		return true;
	}

	/**
	 * Laedt alle Kategorien
	 * @param $aktiv wenn true werden nur die aktiven Datensaetze geladen, sonst alle
	 * @param $order Sortierreihenfolge
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function getAll($aktiv=null, $order='bkategorie_id ASC')
	{
		
		$qry = "SELECT * FROM wawi.tbl_bestellung_kategorie";

		if(!is_null($aktiv))
		{
			$qry.=' WHERE aktiv='.$this->db_add_param($aktiv, FHC_BOOLEAN);
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
			$obj = new wawi_bestellung_kategorie();
			
			$obj->bkategorie_id	= $row->bkategorie_id;
			$obj->beschreibung = $row->beschreibung;
			$obj->kommentar = $row->kommentar;
			$obj->aktiv = $this->db_parse_bool($row->aktiv);
			$obj->insertamum = $row->insertamum;
			$obj->insertvon = $row->insertvon;
			$obj->updateamum = $row->updateamum;
			$obj->updatevon = $row->updatevon;
			
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
				
		if(mb_strlen($this->beschreibung)>256)
		{
			$this->errormsg = 'Beschreibung darf nicht laenger als 256 Zeichen sein.';
			return false;
		}

		if(is_bool($this->aktiv)!= true)
		{
			$this->errormsg = 'Aktiv ist nicht gesetzt.';
			return false;
		}
				
		$this->errormsg = '';
		return true;
	}
	
	
}
?>
