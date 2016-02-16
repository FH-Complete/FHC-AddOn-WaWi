<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


/**
 * Zuordnung ersetzt zusammen mit der Kategorie das ursprüngliche Konto
 */
class wawi_zuordnung {
    const LEHRE = "lehre";
    const FUE = "fue";
    const VERWALTUNG = "verwaltung";

    private static $enum = array(self::LEHRE => "Lehre", self::FUE => "F&E", 
        self::VERWALTUNG => "Verwaltung");


    public static function getAll() 
    {
        return self::$enum;
    }
    
    public static function getLabel($key)
    {
        return self::$enum[$key];
    }
}
