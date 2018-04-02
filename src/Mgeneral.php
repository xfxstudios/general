<?php
/**
 * Modelo de codeigniter para uso con esla libreria
 * copiar a la carpeta models de codeigniter
 */
use xfxstudios\general\GeneralClass;

class Mgeneral extends CI_Model
{
    public function __construct(){
        parent::__construct();
        $this->general = new GeneralClass();
    }

    public function setHistorial($X){

    }

}