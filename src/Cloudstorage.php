<?php

use Google\Cloud\Storage\StorageClient;
use xfxstudios\general\GeneralClass;

class Cloudstorage
{

    public function __construct($bucket, $project, $json){
        $this->general = new GeneralClass();
        $this->storage = new StorageClient([
            //'keyFilePath' => APPPATH.'services/hitpagos-6c5d1793e2f3.json',
            'keyFilePath' => APPPATH.'services/'.$json,
            'projectId' => $project
        ]);
        $this->bucket = $this->storage->bucket($bucket);

        $this->ci =& get_instance();

        $this->ruta = APPPATH."/cargas/";
    }
    //carga archivos al bucket seleccionado
    //Recibe un array como parametro
    //Ejemplo: $this->my_storage->cargar(array('type'=>'public','name'=>'nombredearchivo'));//Para archivos con enlace publico
    //Ejemplo: $this->my_storage->cargar(array('name'=>'nombredearchivo'));//Para rchivos sin enlace publico
    public function cargar($X=null){

        if($X==null){
            return false;
            exit;
        }else if(!is_array($X)){
            return false;
            exit;
        }

        $folder = "";

        switch(strtolower($X['data']['file_ext'])){

            case '.jpg':
            case '.jpeg':
            case '.png':
                $folder = 'images/';
            break;

            case '.doc';
            case '.docx';
            case '.ppt';
            case '.pptx';
            case '.xls';
            case '.xlsx';
            case '.pdf';
                $folder = 'documents/';
            break;

            case '.mp4':
                $folder = 'videos/';
            break;

            default:
                $folder = 'uncategorized/';
            break;

        }


        $options = [
            'resumable' => true,
            'name' => $folder.$X['name'],
            'predefinedAcl' => 'publicRead'
        ];

            $object = $this->bucket->upload(
                fopen($this->ruta.$X['name'], 'r'),$options
            );

            unlink($this->ruta.$X['name']);
    }

    //Elimina un objeto u archivo
    //Recibe el nombre como parametro para ser eliminado del bucket y el tipo
    //ehemplo $this->my_storage->borrar(array('nombre','tipo'))
    public function borrar($X=null){

        switch($X[0]){
            case null:
                return false;
                exit;
            break;
        };

        switch($X[1]){
            case '.jpg':
            case '.jpeg':
            case '.png':
                $folder = 'images/';
            break;

            case '.doc';
            case '.docx';
            case '.ppt';
            case '.pptx';
            case '.xls';
            case '.xlsx';
            case '.pdf';
                $folder = 'documents/';
            break;

            case '.mp4':
                $folder = 'videos/';
            break;

            default:
                $folder = 'uncategorized/';
            break;
        }

        $imagen = $this->bucket->object($folder.$X[0]);
        $imagen->delete();
    
    }

    //Descarga un archivo/imagen del bucket
    //ejemplo: $this->my_storage->descarga(array('ruta'=>'','name'=>''));
    public function descarga($X){
        $object = $this->bucket->object($X['name']);
        $object->downloadToFile($X['ruta'].$X['name']);
    }

}

?>