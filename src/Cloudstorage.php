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
use xfxstudios\Exception\Storageexception;

class Cloudstorage
{
    private $ci;
    private $bucket;
    private $storage;
    private $ruta;
    private $project;
    private $json;
    private $name;
    private $path = FALSE;
    private $file;
    private $ext;

    public function __construct(){
        $this->ini = parse_ini_file(SYSDIR.'/services/d.ini');
        $this->project = $this->ini['project'];
        $this->json = $this->ini['cjson'];
        $this->storage = new StorageClient([
            'keyFilePath' => SYSDIR.'/'.$this->ini['services'].'/'.$this->json,
            'projectId' => $this->project
        ]);
        $this->bucket = $this->storage->bucket($this->ini['bucket']);

        $this->ci =& get_instance();
        
        $this->ruta = ($this->path) ? APPPATH."/".$this->path."/" : APPPATH."/".$this->ini['upload']."/";
    }

    public function nameFile($X=null){
        if($X==null){
            throw new Storageexception("No se ha enviado el Nombre del Archivo",1,array('errorCode'=>'214'));
            exit;
        }
        $this->name = $X;
        return $this;
    }

    public function file($X=null){
        if($X==null){
            throw new Storageexception("No se ha enviado la data del archivo cargado",1,array('errorCode'=>'212'));
            exit;
        }
        if(!is_array($X)){
            throw new Storageexception("La data del Archivo cargado no es un arreglo válido",1,array('errorCode'=>'210'));
            exit;
        }
        if(!file_exists($this->ruta.$this->name)){
            throw new Storageexception("El archivo que intenta descargar, no se encuentra en el Directorio Temporal",1,array('errorCode'=>'208'));
            exit;
        }
        $this->file = json_encode($X);
        $this->ext = strtolower($X['data']['file_ext']);
        return $this;
    }

    public function path($X=null){
        if($X==null){
            throw new Storageexception("No se ha indicado una Ruta válida",1,array('errorCode'=>'206'));
            exit;
        }
        $this->path = $X;
        return $this;
    }

    //carga archivos al bucket seleccionado
    public function _Cargar(){

        if(empty($this_>ext)){
            throw new Storageexception("No se ha detectado la Extensión del Archivo",1,array('errorCode'=>'204'));
            exit;
        }

        $folder = "";

        switch($this->ext){

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
            'resumable'     => true,
            'name'          => $folder.$this->name,
            'predefinedAcl' => 'publicRead'
        ];

            $object = $this->bucket->upload(
                fopen($this->ruta.$this->name, 'r'),$options
            );

            unlink($this->ruta.$this->name);
            if(file_exists($this->ruta.$this->name)){
                throw new Storageexception("Error al intentar eliminar el archivo del directorio",1,array('errorCode'=>'202'));
                exit;
            }
            return "200";
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