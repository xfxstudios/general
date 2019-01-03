<?php
namespace xfxstudios\general;

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

class Myfire{
    private $database;
    private $reference;
    private $json;
    private $principal;
    private $service;
    private $manager;
    private $getNode;
    private $all = false;
    private $key = false;
    private $value = false;

    public function __construct($datos){
        $this->ci        = & get_instance();
        $this->database  = $datos[0];//Database
        $this->principal = $datos[1];//Nodo Principal
        $this->json      = $datos[2];//nombre de Json
        $this->services  = $this->setService();
        $this->manager   = $this->setManager();
    }//


    private function _reference($x){
        $this->reference = $this->services->getReference($this->principal.'/'.$x);
    }//

    private function setService(){
        $serviceAccount = ServiceAccount::fromJsonFile(APPPATH.'/firebase/'.$this->json);
        $firebase = (new Factory)
            ->withServiceAccount($serviceAccount)
            ->withDatabaseUri($this->database)
            ->create();
            return $firebase->getDatabase();
    }//

    private function setManager(){
        $serviceAccount = ServiceAccount::fromJsonFile(APPPATH.'/firebase/'.$this->json);
        $firebase = (new Factory)
                ->withServiceAccount($serviceAccount)
                ->create();
        return $firebase->getAuth();
    }//

    public function _All($x){
        $this->_reference($x);
        $this->all = true;
        return $this;
    }//
    
    public function _Key($x){
        $this->_reference($x);
        $this->key = true;
        return $this;
    }//
    
    public function _Value($x){
        $this->_reference($x);
        $this->value = true;
        return $this;
    }//


    //
    public function _get(){
        if($this->all==true){
            $snap = $this->reference->getSnapshot();
            $this->all = false;
            return $snap->getValue();
        }
        
        if($this->key==true){
            $snap = $this->reference->orderByKey()->getSnapshot();
            $this->key = false;
            return $snap->getValue();
        }
        
        if($this->value==true){
            $snap = $this->reference->orderByValue()->getSnapshot();
            $this->value = false;
            return $snap->getValue();
        }
    }//


}