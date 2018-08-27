<?php
namespace xfxstudios\general;
use xfxstudios\general\GeneralClass;

class Fixer{

    private $base = 'EUR';
    private $symbols;
    private $key = '76d3733f67a6760d048c0937876e1221';
    private $latest = FALSE;
    private $datehis;
    private $format = FALSE;

    
    public function __construct(){
        $this->general = new GeneralClass();
    }

    public function setLatest($x=null){
        $this->latest = ($x==null) ? FALSE : $x;
        return $this;
    }

    public function setBase($x=null){
        $this->base = ($x==null) ? 'USD' : $x;
        return $this;
    }
    
    public function setSymbols($x=null){
        $this->symbols = ($x==null) ? 'EUR' : $x;
        return $this;
    }
    
    public function setDate($x=null){
        $this->datehis = ($x==null) ? $this->general->date()->date : $x;
        return $this;
    }
    
    public function setJson($x=null){
        $this->format = ($x==null) ? 'json' : $x;
        return $this;
    }

    public function get(){
        if($this->latest===true){
            $ch = curl_init('http://data.fixer.io/api/latest?access_key='.$this->key.'&base='.$this->base);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $resp = curl_exec($ch);
            curl_close($ch);
            return json_decode($resp);
        }else{
            $ch = curl_init('http://data.fixer.io/api/'.$this->datehis.'?access_key='.$this->key.'&base='.$this->base);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $resp = curl_exec($ch);
            curl_close($ch);
            return json_decode($resp);
        }
    }
}

