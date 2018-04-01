<?php

namespace xfxstudios\general;

use GeoIp2\Database\Reader;

class GeneralClass
{
    /**
     * Create a new Skeleton Instance
     */
    public function __construct()
    {
		// Instancia de Codeigniter para cargar la libreria mgeneral
		$this->ci =& get_instance();
		$this->ci->load->model('mgeneral');
	}
	
	//Funcion para escribir en historial de aplicaciones
	//Recibe 2 parametros, todo lo demás se configura en el mgeneral de cada app
	public function historial($X){
		$data = array(
			"asunto"	=>	$X[0],
			"info"		=>	$X[1]
		);
		$this->ci->mgeneral->setHistorial($data);
	}

    //detecta el idioma del usuario
	public function idioma(){
	
		if(isset($_SESSION['lang'])){
			$idi = $_SESSION['lang'];
		}
		if(!isset($_SESSION['lang']) && isset($_COOKIE['lang'])){
			$idi = $_COOKIE['lang'];
		}else{
			$idi = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
		}

		return ($idi=='es') ? 'es' : 'en';
	}//


	//Genera una clave de usuario
	function claveusuario($X){
		$salt = substr(base64_encode(openssl_random_pseudo_bytes('30')), 0, 22);
		$salt = strtr($salt, array('+' => '.'));
		$hash = crypt($X, '$2y$10$' . $salt);
		$claveB = $hash;
		return $claveB;
	}//END

	//Retorna Informacion completa de una IP
	public function city($X=null){
		$ip = ($X==null) ? $this->IPreal() : $X;
		$reader = new Reader($_SERVER['DOCUMENT_ROOT'].'/application/libraries/GeoLite2-City.mmdb');
		
		try{
			$data = $reader->city($ip);
			
			$out = (object) array(
				'isoCode'   => $data->country->isoCode,
				'nombre'    => $data->country->name,
				'estado'    => $data->mostSpecificSubdivision->name,
				'isoEstado' => $data->mostSpecificSubdivision->isoCode,
				'ciudad'    => $data->city->name,
				'postal'    => $data->postal->code,
				'latitud'   => $data->location->latitude,
				'longitud'  => $data->location->longitude,
				'timezone'  => $data->location->timeZone,
			);
			return $out;
		}catch(Exception $e){
			return $e->getMessage();
		}
    }

	//Retorna Informacion del Pais
    public function country($X=null){
		$ip = ($X==null) ? $this->IPreal() : $X;
		$reader = new Reader($_SERVER['DOCUMENT_ROOT'].'/application/libraries/GeoLite2-Country.mmdb');
		
		try{
			$data = $reader->country($ip);
			$data = json_encode($data);
			$data = json_decode($data,true);

			$out = (object) array(
				'continente'    => $data['continent']['names']['es'],
				'continente_id' => $data['continent']['geoname_id'],
				'pais_id'       => $data['country']['geoname_id'],
				'iso_code'      => $data['country']['iso_code'],
				'pais'          => $data['country']['names']['es']
			);
			return $out;
		}catch(Exception $e){
			return $e->getMessage();
		}
	}
	

	//Suma o resta dias a una fecha
	function calfechas($X){
		$timezone = $this->city($this->IPreal())->timezone;
		date_default_timezone_set($timezone);

		$fecha = $this->date()->date;

		//Si no esta declarada toma la fecha actual
		if($X[0]!=""){
			$f = $X[0];
		}else{
			$f = $fecha;
		}
		//Si no esta reclarada toma el calculo como resta
		if($X[1]=="s"){
			$s="+";
		}else{
			$s="-";
		};


		$nfech = strtotime($s.$X[2].' days', strtotime($f));
		$nfech = date("Y-m-d", $nfech);
		
		return $nfech;
	}
	

	//Retorna la fecha en formato corto
	//Agregadas nuevas formas de fecha
	public function date($time=null){
		setlocale(LC_TIME,$this->idioma());
		
		if($time == null){
			$timezone = $this->city($this->IPreal())->timezone;
		}else{
			$timezone = $time;
		}
		date_default_timezone_set($timezone);
		$ret = (object) array(
			'datetime'  => date("Y-m-d H:i:s"),
			'date'      => date("Y-m-d"),
			'time'      => date("H:i:s"),
			'microtime' => date("H:i a"),
			'large'     => strftime("%A, %e %B %Y - %H:%M hrs"),
			'extra'     => strftime('%A %e de %B del %Y'),
			'iso'		=> date("c"),
			'seconds'	=> date("U"),
			'format'	=> date("r")
		);
		return $ret;
	}//END


	//Corta el texto agregando 3 puntos al final
	public function cortarTexto($texto, $numMaxCaract){
		if (strlen($texto) <  $numMaxCaract){
			$textoCortado = $texto;
		}else{
			$textoCortado = substr($texto, 0, $numMaxCaract);
			$ultimoEspacio = strripos($textoCortado, " ");
	 
			if ($ultimoEspacio !== false){
				$textoCortadoTmp = substr($textoCortado, 0, $ultimoEspacio);
				if (substr($textoCortado, $ultimoEspacio)){
					$textoCortadoTmp .= '...';
				}
				$textoCortado = $textoCortadoTmp;
			}elseif (substr($texto, $numMaxCaract)){
				$textoCortado .= '...';
			}
		}
	 	return $textoCortado;
	}//END


	//Retorna un codigo alfanumerico aleatorio de 10 digitos exactos
	public function claveUnica(){
	        //Cadena de Letras
	        $cadena = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
	        //Creamos un array con la cadena
	        $lets = str_split($cadena);
	        //Generamos un numero a partir de la fecha y hora del momento
	        $num = strtotime(date("Y-m-d H:i:s"));
	        //Inicializo la variable de prefijo
	        $pref = "";
	        //Indico la cantidad de caracteres a utilizar en el prefijo
	        $l=10;
	        //Genero el Prefijo
	        for($i=0; $i<$l; $i++){
	            $pref .= $lets[rand($l,(count($lets)-1))];
	        };
	        
	        //Genero la clave
	        $clave = $pref.$num;
	        //Retorno la Clave Generada
	        return $clave;
	}//END


	//Genera un codigo aleatorio basado en una longitud indicada
	public function getCodigo($cant){
		$chars = "abcdefghijklmnopqrstuvwxyzABCDRFGHIJKLMNOPQRSTUVWXYZ1234567890";
		$pass = array();
		$alpha = strlen($chars)-1;
		for ($i=0; $i < $cant; $i++) {
			$n = rand(0,$alpha);
			$pass[]=$chars[$n];
		}
		return implode($pass);
	}//end


	//Limpia un numero de simbolos no deseados
	public function limpiaNumero($X){
		$ca = array('|' , ',' , '.' , '-' , '_' , '/' , '(' , ')' , '[' , ']' , ';' , ' ' , '{', '}');
		$X = str_replace($ca,"", $X);
		return $X;
	}//END

	//Formatea la fecha con hora
	public function fechalan($X){
		$lan = $this->idioma();
		return $fecha = ($lan == "es") ? date("d-m-Y H:i:s", strtotime($X)) : date("Y-m-d H:i:s", strtotime($X));
	}//END

	//Formatea la fecha sin hora de acuerdo al idioma
	public function fechalanST($X){
		$lan = $this->idioma();
		return $fecha = ($lan == "es") ? date("d-m-Y", strtotime($X)) : date("Y-m-d", strtotime($X));
	}//END

	//Genera un token de Seguridad
	public function token(){
			$token = md5(uniqid(microtime(), true));
			$token_time = time();
			$datos = array(
				'token'=>$token,
				'token_time'=>$token_time
			);
		return $datos;
	}//END


	//Retorna la IP
	public function IPreal() {
	    if (!empty($_SERVER['HTTP_CLIENT_IP']))
	        return $_SERVER['HTTP_CLIENT_IP'];

	    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
	        return $_SERVER['HTTP_X_FORWARDED_FOR'];

		//return $_SERVER['REMOTE_ADDR'];
		return '190.205.215.57';
	}//fin funcion IPreal


	//Calcula la Diferencia en Dias entre fechas
	public function diferencia($X){
		$datetime1 = new DateTime($X[0]);
		$datetime2 = new DateTime($X[1]);
		$sale = $datetime1->diff($datetime2);

		return $sale->format('%a');
	}//END



	//Calcula la Edad
	public function edad($X){
		$actual = date("Y-m-d H:i:s");
		$nace = $X;

		$a = $this->diferencia(array($actual, $nace));

		return round(($a / 365),0);
	}//END



	//Formatea un Numero de acuerdo al idioma
	public function numFormat($X){
		switch ($this->idioma()) {
			case 'es':
				$X = number_format($X,2,',','.');
			break;

			case 'en':
				$X = number_format($X,2,'.',',');
			break;
			
			default:
				$X = number_format($X,2,',','.');
			break;
		}
		return $X;
	}//END


	
	//Realiza un explode a los componentes get de una URL
	public function url($X){
		if(isset($X) && $X != ""){
			$host  = explode("?",$X);
			if(isset($host[1])){
				$hostb = explode("&",$host[1]);
			}
			$data  = array();
			if(isset($hostb)){
				foreach ($hostb as $key => $value) {
					$v = explode("=",$value);
					$data[$v[0]] = $v[1];
				}
			}
			return $data;
		}
	}//end



	//Retorna el Dispositivo usado (Ordenador/Tablet/Smartphone)
	public function dispositivo(){

			$tablet_browser = 0;
			$mobile_browser = 0;
			$body_class     = 'desktop';

			if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
			    $tablet_browser++;
			    $body_class = "tablet";
			}

			if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
			    $mobile_browser++;
			    $body_class = "mobile";
			}

			if ((strpos(strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml') > 0) or ((isset($_SERVER['HTTP_X_WAP_PROFILE']) or isset($_SERVER['HTTP_PROFILE'])))) {
			    $mobile_browser++;
			    $body_class = "mobile";
			}

			$mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
			$mobile_agents = array(
			    'w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',
			    'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',
			    'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',
			    'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',
			    'newt','noki','palm','pana','pant','phil','play','port','prox',
			    'qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar',
			    'sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',
			    'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',
			    'wapr','webc','winw','winw','xda ','xda-');

			if (in_array($mobile_ua,$mobile_agents)) {
			    $mobile_browser++;
			}

			if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']),'opera mini') > 0) {
			    $mobile_browser++;
			    //Check for tablets on opera mini alternative headers
			    $stock_ua = strtolower(isset($_SERVER['HTTP_X_OPERAMINI_PHONE_UA'])?$_SERVER['HTTP_X_OPERAMINI_PHONE_UA']:(isset($_SERVER['HTTP_DEVICE_STOCK_UA'])?$_SERVER['HTTP_DEVICE_STOCK_UA']:''));
			    if (preg_match('/(tablet|ipad|playbook)|(android(?!.*mobile))/i', $stock_ua)) {
			      $tablet_browser++;
			    }
			}
			if ($tablet_browser > 0) {
			// Si es tablet has lo que necesites
			   //print 'es tablet';
			   return 'Tablet';
			}
			else if ($mobile_browser > 0) {
			// Si es dispositivo mobil has lo que necesites
			   //print 'es un mobil';
			   return 'Smartphone';
			}
			else {
			// Si es ordenador de escritorio has lo que necesites
			   //print 'es un ordenador de escritorio'
			   return 'Ordenador';
			}
	}//END


	//Retorna la URL actual
	public function getUrl() {
		$url  = @( $_SERVER["HTTPS"] != 'on' ) ? 'http://'.$_SERVER["SERVER_NAME"] :  'https://'.$_SERVER["SERVER_NAME"];
		$url .= ( $_SERVER["SERVER_PORT"] !== 80 ) ? ":".$_SERVER["SERVER_PORT"] : "";
		$url .= $_SERVER["REQUEST_URI"];
		return $url;
	  }//end

	  
	//Retorna la imagen gravatar si el usuario la posee
	public function gravatar($mail=null){
		$gravatar_link = 'http://www.gravatar.com/avatar/' . md5($mail) . '?s=32';
		echo '<img src="' . $gravatar_link . '" />';
	}

	//Genera un Archivo CSV a partir de un array
	function generateCsv($data, $delimiter = ',', $enclosure = '"') {
		$handle = fopen('php://temp', 'r+');
		foreach ($data as $line) {
				fputcsv($handle, $line, $delimiter, $enclosure);
		}
		rewind($handle);
		while (!feof($handle)) {
				$contents .= fread($handle, 8192);
		}
		fclose($handle);
		return $contents;
	 }
	 
	 /**
	 * Rerifica Cuenta de Email, Dominio de una Cuenta dada
	 * Retorna datos en Formato Object
	 */

	public function validEmail($email=null){
		//Iniciamos arreglo de respuesta
		$return = (object) array();

		//Verificamos el formato de Email
		if(valid_email($email)){
			$return->formato = true;
		}else{
			$return->formato = false;
		}

		//Verificamos el Dominio
		$dom = explode("@",$email);
		$r2=checkdnsrr($dom[1], "MX");
		if($r2){
			$return->dominio=true;
		}else{
			$return->dominio=false;
		}

		//Rerificamos que el servidor no este caido
		ini_set("user_agent","Mozilla custom agent");
		$a = @get_headers('http://'.$dom[1],1);
		if (is_array($a) && $a !== FALSE) {
			$return->serverStatus = true;
		} else {
			$return->serverStatus = false;
		}

		return $return;
	}


	/**
	 * Retorna un array de fechas de acuerdo al rango solicitado, excluyendo fines de semana
	*/
	public function diasFin($X){
		$timezone = $this->city($this->IPreal())->timezone;
		date_default_timezone_set($timezone);

		$fecha1 = strtotime($X[0]); 
		$fecha2 = strtotime($X[1]); 
		$ret = array();
		for($fecha1;$fecha1<=$fecha2;$fecha1=strtotime('+1 day ' . date('Y-m-d',$fecha1))){ 

			if((strcmp(date('w',$fecha1),'0'))!==0 && (strcmp(date('w',$fecha1),'6'))!==0){
				array_push($ret, date('Y-m-d l',$fecha1)); 
			}

		}
		return $ret;
	}//


	/**
	 * Retorna un array con los dias habiles en un rango dado
	 * recibe un arra con la data a procesar
	 * array('fecha1','fecha2',array feriados)
	 */
	function diashabiles($X){
		$timezone = $this->city($this->IPreal())->timezone;
		date_default_timezone_set($timezone);

		$inicio = new DateTime($X[0]);//Inicio
		$final = new DateTime($X[1]);//Fin

		// Meter fecha final en la operación.
		$final->modify('+1 day');
		
		$intervalo = $final->diff($inicio);
		
		//Días totales
		$dias = $intervalo->days;
		
		// Creamos un perido para que imprima los días (P1D es igual a 1 dia)
		$periodo = new DatePeriod($inicio, new DateInterval('P1D'), $final);
		
		//Array con días de fiesta
		$holidays = $X[2];//Array con días de fiesta
	
		foreach($periodo as $d) {
			$pos = $d->format('D');
		
			if ($pos == 'Sat' || $pos == 'Sun') {
				$dias--;
			}
		
			elseif (in_array($d->format('Y-m-d'), $holidays)) {
				$dias--;
			}
		}
		return $dias;
	}//



}
