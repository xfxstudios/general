<?php
namespace xfxstudios\general;
/**
 * Modelo de codeigniter para uso con esla libreria
 * copiar a la carpeta models de codeigniter
 */
use xfxstudios\general\GeneralClass;

class GeneralModel
{
    public function __construct(){
        parent::__construct();
        $this->ci =& get_instance();
        $this->general = new GeneralClass();
    }

    public function setHistorial($X){
        return $X;
    }

}