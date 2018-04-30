<?php
namespace xfxstudios\general;

class Firemanager
{
    public $ur;
    private $error;

    public function __construct($ur){
        $this->url = $ur;
    }

    public function _error($x=null){
        if($x===null){
            return $this->error;
        }else{
            switch ($x) {
                case 'auth/invalid-phone-number':
                    return "Formáto de movil Inválido, Formato correcto: +58xxxxxxx";
                break;
                
                case 'auth/phone-number-already-exists':
                    return "El número de Móvil ya existe, por favor, verifique";
                break;
                
                case 'auth/invalid-email':
                    return "Formato de Email Inválido, por favor, verifique";
                break;
                
                case 'auth/email-already-exists':
                    return "El Email ya existem por favor, verifique";
                break;
                
                case 'auth/user-not-found':
                    return "Usuario no registrado en el Sistema, por favor, Verifique";
                break;
                
                default:
                    return "Error inesperado de Firebase";
                break;
            }
        }
    }

    //Crea un nuevo Usuario
    /*
    $send = array('a@a.com','+584144402465','14186541','Carlos Molleja');
    var_dump($this->my_fireuser->addUser($send));*/
    public function addUser($X=null){
        if(count($X) < 4 || $X===null){
            $this->error = "Faltan elementos para crear el usuario";
            return false;
            exit;
        }
        foreach($X as $item){
            if($item=="" || $item===null){
                $this->error = "Faltan elementos para crear el usuario";
                return false;
                exit;
            }
        }

        $service_url = $this->url."addUser/".$X[0]."/".$X[1]."/".$X[2]."/".str_replace(" ","%20",$X[3]);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL            => $service_url,
            CURLOPT_POST           => true,
            CURLOPT_HEADER         => true,
            CURLOPT_CONNECTTIMEOUT => 120,
            CURLOPT_TIMEOUT        => 120
        ));
        $resp = curl_exec($curl);
        if(!curl_exec($curl)){
            return 'Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl);
        }
        curl_close($curl);
        $data = explode("vegur",$resp);
        return json_decode($data[1]);
    }//


    /**
     * Retorna la información de un usuario de firebase
     * Recibe el Email como Parametro
     * Retorna un Objeto
     */
    //var_dump($this->my_fireuser->getUser('a@a.com'));
    public function getUser($X=null){
            if($X===null){
                $this->error = "Debe indicar el Email de usuario para obtener sus datos";
                return false;
            }
            $service_url = $this->url."getUser/".$X;

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_URL            => $service_url,
                CURLOPT_CUSTOMREQUEST   =>  'GET',
                CURLOPT_HEADER         => true,
                CURLOPT_CONNECTTIMEOUT => 120,
                CURLOPT_TIMEOUT        => 120
            ));
            $resp = curl_exec($curl);
            if(!curl_exec($curl)){
                return 'Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl);
            }
            curl_close($curl);
            $data = explode("vegur",$resp);
            return json_decode($data[1]);
    }//

    /**
     * Actualiza el Nombre y Movil de un usuario
     * Recibe el uid, movil, nombre
     * Toodos los parametros son obligatorios
     * Formato de movil +58XXXXXXXXX (es decir debe incluir el codigo país)  
     * Retorna un Objeto
     */
    /*$datos = array('uep6JxJM0OYBZ9Xh2J8HcFcfMLl1','+584165140351',str_replace(" ","%20",'Sorely Rodriguez'));
    var_dump($this->my_fireuser->setNamePhone($datos));*/
    public function setNamePhone($X=null){
            if(count($X) < 3 || $X===null){
                $this->error = "Faltan elemento para procesar su solicitud, por favor, Verifique";
                return false;
            }
            foreach($X as $item){
                if($item=="" || $item===null){
                    $this->error = "Faltan elementos para crear el usuario";
                    return false;
                    exit;
                }
            }
            $service_url = $this->url."updateUserUid/".$X[0]."/".$X[1]."/".str_replace(" ","%20",$X[2]);

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_URL            => $service_url,
                CURLOPT_CUSTOMREQUEST   =>  'PUT',
                CURLOPT_HEADER         => true,
                CURLOPT_CONNECTTIMEOUT => 120,
                CURLOPT_TIMEOUT        => 120
            ));
            $resp = curl_exec($curl);
            if(!curl_exec($curl)){
                return 'Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl);
            }
            curl_close($curl);
            $data = explode("vegur",$resp);
            return json_decode($data[1]);
           // return $resp;
    }//


    /**
     * Actualzia el Email del Usuario de Firebase
     * NOTA: Esta funcion debe utilizarse actualizando de igual manera el usuario en la base de datos local
     * en caso contrario falla el login de dicho usuario
     * Recibe UID y el Email como Parametro
     * Retorna un Objeto
     */
    /*$datos = array('uep6JxJM0OYBZ9Xh2J8HcFcfMLl1','ab@aa.com');
    var_dump($this->my_fireuser->setEmail($datos));*/
    public function setEmail($X=null){
            if(count($X) < 2 || $X===null){
                $this->error = "Faltan elemento para procesar su solicitud, por favor, Verifique";
                return false;
            }
            foreach($X as $item){
                if($item=="" || $item===null){
                    $this->error = "Faltan elementos para crear el usuario";
                    return false;
                    exit;
                }
            }
            $service_url = $this->url."updateUserEmail/".$X[0]."/".$X[1];

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_URL            => $service_url,
                CURLOPT_CUSTOMREQUEST   =>  'PUT',
                CURLOPT_HEADER         => true,
                CURLOPT_CONNECTTIMEOUT => 120,
                CURLOPT_TIMEOUT        => 120
            ));
            $resp = curl_exec($curl);
            if(!curl_exec($curl)){
                return 'Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl);
            }
            curl_close($curl);
            $data = explode("vegur",$resp);
            return json_decode($data[1]);
            //return $resp;
    }//


    /**
     * Valida una cuenta de Email en Firebase
     * Recibe UID
     * Retorna un Objeto
     * var_dump($this->my_fireuser->validateEmail('uep6JxJM0OYBZ9Xh2J8HcFcfMLl1'));
    */
    public function validateEmail($X=null){
            if($X===null){
                $this->error = "Faltan el UID del usuario para procesar su solicitud, por favor, Verifique";
                return false;
            }
            $service_url = $this->url."validateUserEmail/".$X['uid'];

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_URL            => $service_url,
                CURLOPT_CUSTOMREQUEST   =>  'PUT',
                CURLOPT_HEADER         => true,
                CURLOPT_CONNECTTIMEOUT => 120,
                CURLOPT_TIMEOUT        => 120
            ));
            $resp = curl_exec($curl);
            if(!curl_exec($curl)){
                return 'Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl);
            }
            curl_close($curl);
            $data = explode("vegur",$resp);
            return json_decode($data[1]);
            //return $resp;
    }//


    /**
     * Actualiza la Clave del Usuario en Firebase
     * NOTA: Debe realizarse la actualizacióin tambien en modo local del Usuario
     * en caso contrario tendra problemas para el login al sistema
     * Recibe UID y Clave nueva del usuario
     * Retorna un Objeto
     */
    /*$datos = array('uep6JxJM0OYBZ9Xh2J8HcFcfMLl1','16401770');
    var_dump($this->my_fireuser->updatePass($datos));*/
    public function updatePass($X=null){
            if(count($X)<2 || $X===null){
                $this->error = "Faltan elementos para procesar su solicitud, por favor, Verifique";
                return false;
            };
            foreach($X as $item){
                if($item=="" || $item===null){
                    $this->error = "Faltan elementos para crear el usuario";
                    return false;
                    exit;
                }
            }
            $service_url = $this->url."updateUserPassword/".$X[0]."/".urlencode($X[1]);

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_URL            => $service_url,
                CURLOPT_CUSTOMREQUEST   =>  'PUT',
                CURLOPT_HEADER         => true,
                CURLOPT_CONNECTTIMEOUT => 120,
                CURLOPT_TIMEOUT        => 120
            ));
            $resp = curl_exec($curl);
            if(!curl_exec($curl)){
                return 'Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl);
            }
            curl_close($curl);
            $data = explode("vegur",$resp);
            return json_decode($data[1]);
            //return $resp;
    }//


    /**
     * Suspende un usuario en Firebase
     * NOTA: Una ves suspendido el sistema no permite el ingreso a gmanager
     * Recibe UID
     * Retorna un Objeto
     */
    /*var_dump($this->my_fireuser->suspendUser('uep6JxJM0OYBZ9Xh2J8HcFcfMLl1'));*/
    public function suspendUser($X=null){
            if($X===null){
                $this->error = "Faltan el UID del usuario procesar su solicitud, por favor, Verifique";
                return false;
            };
            $service_url = $this->url."suspendUser/".$X;

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_URL            => $service_url,
                CURLOPT_CUSTOMREQUEST   =>  'PUT',
                CURLOPT_HEADER         => true,
                CURLOPT_CONNECTTIMEOUT => 120,
                CURLOPT_TIMEOUT        => 120
            ));
            $resp = curl_exec($curl);
            if(!curl_exec($curl)){
                return 'Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl);
            }
            curl_close($curl);
            $data = explode("vegur",$resp);
            return json_decode($data[1]);
            //return $resp;
    }//


    /**
     * Reactiva un usuario suspendido previamente
     * Recibe UID
     * Retorna un Objeto
     */
    /*var_dump($this->my_fireuser->activateUser('uep6JxJM0OYBZ9Xh2J8HcFcfMLl1'));*/
    public function activateUser($X=null){
            if($X===null){
                $this->error = "Faltan el UID del usuario procesar su solicitud, por favor, Verifique";
                return false;
            };
            $service_url = $this->url."activateUser/".$X;

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_URL            => $service_url,
                CURLOPT_CUSTOMREQUEST   =>  'PUT',
                CURLOPT_HEADER         => true,
                CURLOPT_CONNECTTIMEOUT => 120,
                CURLOPT_TIMEOUT        => 120
            ));
            $resp = curl_exec($curl);
            if(!curl_exec($curl)){
                return 'Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl);
            }
            curl_close($curl);
            $data = explode("vegur",$resp);
            return json_decode($data[1]);
            //return $resp;
    }//


    /**
     * Elimina un Usuario de Firebase
     * NOTA: Si elimina un usuario de firebase, este no poodra ingresar nuevamente al sistema
     * Recibe UID
     * Retorna un Objeto
     */
    /*var_dump($this->my_fireuser->deleteUser('uep6JxJM0OYBZ9Xh2J8HcFcfMLl1'));*/
    public function deleteUser($X=null){
            if($X===null){
                $this->error = "Faltan el UID del usuario procesar su solicitud, por favor, Verifique";
                return false;
            };
            $service_url = $this->url."deleteUser/".$X;

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_URL            => $service_url,
                CURLOPT_CUSTOMREQUEST   =>  'DELETE',
                CURLOPT_HEADER         => true,
                CURLOPT_CONNECTTIMEOUT => 120,
                CURLOPT_TIMEOUT        => 120
            ));
            $resp = curl_exec($curl);
            if(!curl_exec($curl)){
                return 'Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl);
            }
            curl_close($curl);
            $data = explode("vegur",$resp);
            return json_decode($data[1]);
            //return $resp;
    }//

}

