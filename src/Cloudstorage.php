<?php
namespace xfxstudios\general;
/**
 * Autor: Carlos Quintero 
 * Corporación HITCEL
 * Requiere para su uso 2 Carpetas en la raiz de application en Codeigniter
 * Carpetas: cargas y services
 * Colocar el Archivo de cuentas de servicios json en la carpeta services
 * Ejemplo de carga:
 * ------------------------------------------------
 * use xfxstudios\general\Cloudstorage;
 * 
 * $this->storage = new Cloudstorage(
 *      'Aquí Bucket Google Gloud Storage',
 *      'Aquí Proyecto',
 *      'Aquí nombre de archivo json de servicio'
 * );
 * -------------------------------------------------
 * 
 * requiere de la libreria de Cloud Storage
 * Ejecutar en la raiz de application
 * composer require google/cloud-storage
 * SOLO SI NO SE TIENE INSTALADA
*/

use Google\Cloud\Storage\StorageClient;

class Cloudstorage
{
    private $ci;
    private $bucket;
    private $storage;
    private $ruta;
    private $project;
    private $json;

    public function __construct(){
        $this->ini = parse_ini_file(APPPATH.'services/d.ini');
        $this->project = $this->ini['project'];
        $this->json = $this->ini['cjson'];
        $this->storage = new StorageClient([
            'keyFilePath' => APPPATH.$this->ini['services'].'/'.$this->json,
            'projectId' => $this->project
        ]);
        $this->bucket = $this->storage->bucket($this->ini['bucket']);

        $this->ci =& get_instance();

        $this->ruta = APPPATH."/".$this->ini['upload']."/";
    }
    //carga archivos al bucket seleccionado
    //Recibe un array como parametro
    //Ejemplo: $this->my_storage->cargar(array('type'=>'public','name'=>'nombredearchivo'));//Para archivos con enlace publico
    //Ejemplo: $this->my_storage->cargar(array('name'=>'nombredearchivo'));//Para rchivos sin enlace publico
    public function _Cargar($X=null){

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
    public function _Borrar($X=null){

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
    public function _Descarga($X){
        $object = $this->bucket->object($X['name']);
        $object->downloadToFile($X['ruta'].$X['name']);
    }

}

?>