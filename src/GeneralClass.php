<?php
namespace xfxstudios\general;

use GeoIp2\Database\Reader;
use xfxstudios\general\GeneralModel;

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
		$this->genmodel = new GeneralModel();
	}
	
	//Funcion para escribir en historial de aplicaciones
	//Recibe 2 parametros, todo lo demás se configura en el mgeneral de cada app
	public function historial($X){
		$data = array(
			"asunto"     => $X[0],
			"info"       => $X[1]
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

	//Crear variable a partir de cadena de texto simple
	public function _variable($X){
		echo eval('return $'. $X);
	}

	//Retorna los Datos de un usuario desde el CNE con su Cédula de Identidad
	public function limpiarCampo($valor) {
		$rempl = array('\n', '\t');
		$r = trim(str_replace($rempl, ' ', $valor));
		return str_replace("\r", "", str_replace("\n", "", str_replace("\t", "", $r)));
	}//
	public function _verificador($X) {
	
		$n = explode("|",$X);
		if(strlen($n[1])==7){
			$c = "0".$n[1];//Cédulas de 7 Digitos
		}else if(strlen($n[1])==6){
			$c = "00".$n[1];//Cédulas de 6 digitos
		}else{
			$c = $n[1];//Cedula normal
		}
	
			$digitos = str_split($n[0].$c);
			$digitos[8] *= 2;
			$digitos[7] *= 3;
			$digitos[6] *= 4;
			$digitos[5] *= 5;
			$digitos[4] *= 6;
			$digitos[3] *= 7;
			$digitos[2] *= 2;
			$digitos[1] *= 3;
	
			switch ($digitos[0]) {
				case 'V':
					$digitoEspecial = 1;
					break;
				case 'E':
					$digitoEspecial = 2;
					break;
				case 'C':
				case 'J':
					$digitoEspecial = 3;
					break;
				case 'P':
					$digitoEspecial = 4;
					break;
				case 'G':
					$digitoEspecial = 5;
					break;
			}
			$suma = (array_sum($digitos)) + ($digitoEspecial*4);
			$residuo = $suma % 11;
			$resta = 11 - $residuo;
			$digitoVerificador = ($resta >= 10) ? 0 : $resta;
			
		return $digitoVerificador;
	}//
	public function _getUrl($url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, FALSE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        if (curl_exec($curl) === false) {
            return false;
        } else {
            $return = curl_exec($curl);
        }
        curl_close($curl);

        return $return;
	}
	public function getCNE($X){
		//Obtenemos el HTML del CNE
			try{
				$context = stream_context_create(array(
					'http' => array(
						'timeout' => 10   // Timeout in seconds
					)
				));
				//$html = $this->_getUrl('http://www.cne.gov.ve/web/registro_electoral/ce.php?nacionalidad='.$X[0].'&cedula='.$X[1]);
				$html = file_get_contents('http://www.cne.gov.ve/web/registro_electoral/ce.php?nacionalidad='.$X[0].'&cedula='.$X[1] , 0 , $context);
				if(!$html){
					throw new Exception("Error ".error_get_last());	
				}
			}catch(Exception $e){
				return $e;
			}
		//Eliminamos las etiquetas HTML
			$html = strip_tags($html);
		//Datos a buscar en el texto generado
			$rempl = array('Cédula:', 'Nombre:', 'Estado:', 'Municipio:', 'Parroquia:', 'Centro:', 'Dirección:', 'SERVICIO ELECTORAL', 'Mesa:');
		//Reemplazamos dichos datos por caracter de control
			$r = trim(str_replace($rempl, '|', $this->limpiarCampo($html)));
		//Gebneramos el array desde el caracter de control
			$recurso = explode("|", $r);
		//Verificamos que el resultado sea válido
			if(strlen($recurso[1])>20){
				//Si no es v´´alido la cédula no existe
				$datos = (object) array(
					"cod"	=>	"201",
					"msg"	=>	"La cédula no se encuentra Registrada o Se envión un dato errado, por favor, verifique"
				);
			}else{
				//Si es válido preparamos el objeto de salida
			$n = explode("-",$recurso[1]);//separamos la cédula
			$nn = explode(" ",$recurso[2]);//Separamos el nombre
			$est = explode(" ",$recurso[3]);
			unset($est[0]);
			$estFinal = (count($est)>1) ? implode(" ",$est) : implode("",$est);
			
			$cid = explode(" ",$recurso[4]);
			unset($cid[0]);
			$cidFinal = (count($cid)>1) ? implode(" ",$cid) : implode("",$cid);
			
			$parr = explode(" ",$recurso[5]);
			unset($parr[0]);
			$parrFinal = (count($parr)>1) ? implode(" ",$parr) : implode("",$parr);


			//$titulos = array('Primer Nombre','Segundo Nombre','Primer Apellido','Segundo Apellido');
		
				$datos = (object) array(
					"cod"            => "200",
					"nacionalidad"   => $n[0],
					"cedula"         => $n[1],
					"RIF"            => (strlen($n[1]) == 7 ) ? $n[0].'-0'.$n[1].'-'.$this->_verificador($n[0].'|'.$n[1]) : $n[0].'-'.$n[1].'-'.$this->_verificador($n[0].'|'.$n[1]),
					"cedCompleta"    => $recurso[1],
					"nombreCompleto" => $recurso[2],
					"estado"         => $estFinal,
					"municipio"      => $cidFinal,
					"parroquia"      => $parrFinal,
					"escuela"        => $recurso[6],
				);
				//Verificamos la cantidad de nombres y apellidos y los recorremos
				/*for($i=0; $i<count($nn);$i++){
					$datos[$titulos[$i]] = $nn[$i];
				}*/
			}
		//Retornamos la respuesta
			return $datos;
	}//

	//Genera una clave de usuario
	function claveusuario($X){
		$salt = substr(base64_encode(openssl_random_pseudo_bytes('30')), 0, 22);
		$salt = strtr($salt, array('+' => '.'));
		$hash = crypt($X, '$2y$10$' . $salt);
		$claveB = $hash;
		return $claveB;
	}//END



	//Limpia una cadena de simbolos injnecesarios
	public function eliminar_simbolos($string){
 
        $string = trim($string);
     
        $string = str_replace(
            array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'),
            array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A'),
            $string
        );
     
        $string = str_replace(
            array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'),
            array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E'),
            $string
        );
     
        $string = str_replace(
            array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'),
            array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I'),
            $string
        );
     
        $string = str_replace(
            array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'),
            array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O'),
            $string
        );
     
        $string = str_replace(
            array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'),
            array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U'),
            $string
        );
     
        $string = str_replace(
            array('ñ', 'Ñ', 'ç', 'Ç'),
            array('n', 'N', 'c', 'C',),
            $string
        );
     
        $string = str_replace(
            array("\\", "¨", "º", "-", "~",
                 "#", "@", "|", "!", "\"",
                 "·", "$", "%", "&", "/",
                 "(", ")", "?", "'", "¡",
                 "¿", "[", "^", "<code>", "]",
                 "+", "}", "{", "¨", "´",
                 ">", "< ", ";", ",", ":",
                 ".", " "),
            ' ',
            $string
        );
        return $string;
    } 

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


	//Retorna y Maneja la lista de monedas del sistema
	//Lista de monedas mas comunes
	public function getMonedas($moneda = null){
        
        $data = array(
            "euro"             => (object) array("nombre"=>"Euro","iso"=>"EUR","isocode"=>"978","simboloDerecha"=>"","simboloIzquierda"=>"E","decimales"=>"2","separadordecimal"=>",","separadormiles"=>".","plural"=>"euros"),
            "pesoargentino"    => (object) array("nombre"=>"Peso Argentino","iso"=>"ARS","isocode"=>"032","simboloDerecha"=>"","simboloIzquierda"=>"$ ","decimales"=>"2","separadordecimal"=>",","separadormiles"=>".","plural"=>"pesos"),
            "dolaraustraliano" => (object) array("nombre"=>"Dólar Australiano","iso"=>"AUD","isocode"=>"036","simboloDerecha"=>"","simboloIzquierda"=>"$","decimales"=>"2","separadordecimal"=>".","separadormiles"=>",","plural"=>"dolares"),
            "boliviano"        => (object) array("nombre"=>"Boliviano","iso"=>"BOB","isocode"=>"068","simboloDerecha"=>"","simboloIzquierda"=>"Bs","decimales"=>"2","separadordecimal"=>",","separadormiles"=>".","plural"=>"bolivianos"),
            "dolaramericano"   => (object) array("nombre"=>"Dólar Americano","iso"=>"USD","isocode"=>"840","simboloDerecha"=>"","simboloIzquierda"=>"us$","decimales"=>"2","separadordecimal"=>".","separadormiles"=>",","plural"=>"dolares"),
            "realbrasileño"    => (object) array("nombre"=>"Real Brasileño","iso"=>"BRL","isocode"=>"986","simboloDerecha"=>"","simboloIzquierda"=>"R$","decimales"=>"2","separadordecimal"=>",","separadormiles"=>".","plural"=>"reales"),
            "rupiaindia"       => (object) array("nombre"=>"Rupia","iso"=>"INR","isocode"=>"356","simboloDerecha"=>"","simboloIzquierda"=>"Rs","decimales"=>"2","separadordecimal"=>",","separadormiles"=>".","plural"=>"rupias"),
            "dolarcanadiense"  => (object) array("nombre"=>"Dólar Canadiense","iso"=>"CAD","isocode"=>"124","simboloDerecha"=>"","simboloIzquierda"=>"$","decimales"=>"2","separadordecimal"=>",","separadormiles"=>".","plural"=>"dolares"),
            "pesochileno"      => (object) array("nombre"=>"Peso Chileno","iso"=>"CLP","isocode"=>"152","simboloDerecha"=>"","simboloIzquierda"=>"$","decimales"=>"2","separadordecimal"=>",","separadormiles"=>".","plural"=>"pesos"),
            "yuan"             => (object) array("nombre"=>"Yuan","iso"=>"CNY","isocode"=>"156","simboloDerecha"=>"","simboloIzquierda"=>"¥","decimales"=>"2","separadordecimal"=>",","separadormiles"=>".","plural"=>"yuanes"),
            "pesocolombiano"   => (object) array("nombre"=>"Peso Colombiano","iso"=>"COP","isocode"=>"170","simboloDerecha"=>"","simboloIzquierda"=>"$ ","decimales"=>"2","separadordecimal"=>"","separadormiles"=>"","plural"=>"pesos"),
            "pesomexicano"     => (object) array("nombre"=>"Peso Mexicano","iso"=>"MXN","isocode"=>"4217","simboloDerecha"=>"","simboloIzquierda"=>"$ ","decimales"=>"2","separadordecimal"=>"","separadormiles"=>"","plural"=>"pesos"),
            "pesocubano"       => (object) array("nombre"=>"Peso Cubano","iso"=>"CUP","isocode"=>"192","simboloDerecha"=>"","simboloIzquierda"=>"$","decimales"=>"2","separadordecimal"=>",","separadormiles"=>".","plural"=>"pesos"),
            "libraesterlina"   => (object) array("nombre"=>"Libra Esterlina","iso"=>"GBP","isocode"=>"826","simboloDerecha"=>"","simboloIzquierda"=>"£","decimales"=>"2","separadordecimal"=>".","separadormiles"=>",","plural"=>"libras"),
            "yen"              => (object) array("nombre"=>"Yen","iso"=>"JPY","isocode"=>"392","simboloDerecha"=>"","simboloIzquierda"=>"¥","decimales"=>"2","separadordecimal"=>",","separadormiles"=>".","plural"=>"yenes"),
            "sol"              => (object) array("nombre"=>"Sol Peruano","iso"=>"PEN","isocode"=>"604","simboloDerecha"=>"","simboloIzquierda"=>"S/","decimales"=>"2","separadordecimal"=>",","separadormiles"=>".","plural"=>"soles"),
            "bolívar"          => (object) array("nombre"=>"Bólivar","iso"=>"VEF","isocode"=>"937","simboloDerecha"=>"Bs.","simboloIzquierda"=>"","decimales"=>"2","separadordecimal"=>",","separadormiles"=>".","plural"=>"bolivares"),
        );

        return ($moneda == null) ? $data : $data[$moneda];
	}
	

	//Retorna una cantidad en letras
	//------    CONVERTIR NUMEROS A LETRAS         ---------------
	//------    Máxima cifra soportada: 18 dígitos con 2 decimales
	//------    999,999,999,999,999,999.99
	// NOVECIENTOS NOVENTA Y NUEVE MIL NOVECIENTOS NOVENTA Y NUEVE BILLONES
	// NOVECIENTOS NOVENTA Y NUEVE MIL NOVECIENTOS NOVENTA Y NUEVE MILLONES
	// NOVECIENTOS NOVENTA Y NUEVE MIL NOVECIENTOS NOVENTA Y NUEVE 99/100 M.N.
	public function numletras($xcifra){
		$xarray = array(0 => "Cero",
			1 => "UN", "DOS", "TRES", "CUATRO", "CINCO", "SEIS", "SIETE", "OCHO", "NUEVE",
			"DIEZ", "ONCE", "DOCE", "TRECE", "CATORCE", "QUINCE", "DIECISEIS", "DIECISIETE", "DIECIOCHO", "DIECINUEVE",
			"VEINTI", 30 => "TREINTA", 40 => "CUARENTA", 50 => "CINCUENTA", 60 => "SESENTA", 70 => "SETENTA", 80 => "OCHENTA", 90 => "NOVENTA",
			100 => "CIENTO", 200 => "DOSCIENTOS", 300 => "TRESCIENTOS", 400 => "CUATROCIENTOS", 500 => "QUINIENTOS", 600 => "SEISCIENTOS", 700 => "SETECIENTOS", 800 => "OCHOCIENTOS", 900 => "NOVECIENTOS"
		);
	//
		$xcifra = trim($xcifra);
		$xlength = strlen($xcifra);
		$xpos_punto = strpos($xcifra, ".");
		$xaux_int = $xcifra;
		$xdecimales = "00";
		if (!($xpos_punto === false)) {
			if ($xpos_punto == 0) {
				$xcifra = "0" . $xcifra;
				$xpos_punto = strpos($xcifra, ".");
			}
			$xaux_int = substr($xcifra, 0, $xpos_punto); // obtengo el entero de la cifra a covertir
			$xdecimales = substr($xcifra . "00", $xpos_punto + 1, 2); // obtengo los valores decimales
		}

		$XAUX = str_pad($xaux_int, 18, " ", STR_PAD_LEFT); // ajusto la longitud de la cifra, para que sea divisible por centenas de miles (grupos de 6)
		$xcadena = "";
		for ($xz = 0; $xz < 3; $xz++) {
			$xaux = substr($XAUX, $xz * 6, 6);
			$xi = 0;
			$xlimite = 6; // inicializo el contador de centenas xi y establezco el límite a 6 dígitos en la parte entera
			$xexit = true; // bandera para controlar el ciclo del While
			while ($xexit) {
				if ($xi == $xlimite) { // si ya llegó al límite máximo de enteros
					break; // termina el ciclo
				}

				$x3digitos = ($xlimite - $xi) * -1; // comienzo con los tres primeros digitos de la cifra, comenzando por la izquierda
				$xaux = substr($xaux, $x3digitos, abs($x3digitos)); // obtengo la centena (los tres dígitos)
				for ($xy = 1; $xy < 4; $xy++) { // ciclo para revisar centenas, decenas y unidades, en ese orden
					switch ($xy) {
						case 1: // checa las centenas
							if (substr($xaux, 0, 3) < 100) { // si el grupo de tres dígitos es menor a una centena ( < 99) no hace nada y pasa a revisar las decenas
								
							} else {
								$key = (int) substr($xaux, 0, 3);
								if (TRUE === array_key_exists($key, $xarray)){  // busco si la centena es número redondo (100, 200, 300, 400, etc..)
									$xseek = $xarray[$key];
									$xsub = $this->subfijo($xaux); // devuelve el subfijo correspondiente (Millón, Millones, Mil o nada)
									if (substr($xaux, 0, 3) == 100)
										$xcadena = " " . $xcadena . " CIEN " . $xsub;
									else
										$xcadena = " " . $xcadena . " " . $xseek . " " . $xsub;
									$xy = 3; // la centena fue redonda, entonces termino el ciclo del for y ya no reviso decenas ni unidades
								}
								else { // entra aquí si la centena no fue numero redondo (101, 253, 120, 980, etc.)
									$key = (int) substr($xaux, 0, 1) * 100;
									$xseek = $xarray[$key]; // toma el primer caracter de la centena y lo multiplica por cien y lo busca en el arreglo (para que busque 100,200,300, etc)
									$xcadena = " " . $xcadena . " " . $xseek;
								} // ENDIF ($xseek)
							} // ENDIF (substr($xaux, 0, 3) < 100)
							break;
						case 2: // checa las decenas (con la misma lógica que las centenas)
							if (substr($xaux, 1, 2) < 10) {
								
							} else {
								$key = (int) substr($xaux, 1, 2);
								if (TRUE === array_key_exists($key, $xarray)) {
									$xseek = $xarray[$key];
									$xsub = $this->subfijo($xaux);
									if (substr($xaux, 1, 2) == 20)
										$xcadena = " " . $xcadena . " VEINTE " . $xsub;
									else
										$xcadena = " " . $xcadena . " " . $xseek . " " . $xsub;
									$xy = 3;
								}
								else {
									$key = (int) substr($xaux, 1, 1) * 10;
									$xseek = $xarray[$key];
									if (20 == substr($xaux, 1, 1) * 10)
										$xcadena = " " . $xcadena . " " . $xseek;
									else
										$xcadena = " " . $xcadena . " " . $xseek . " Y ";
								} // ENDIF ($xseek)
							} // ENDIF (substr($xaux, 1, 2) < 10)
							break;
						case 3: // checa las unidades
							if (substr($xaux, 2, 1) < 1) { // si la unidad es cero, ya no hace nada
								
							} else {
								$key = (int) substr($xaux, 2, 1);
								$xseek = $xarray[$key]; // obtengo directamente el valor de la unidad (del uno al nueve)
								$xsub = $this->subfijo($xaux);
								$xcadena = " " . $xcadena . " " . $xseek . " " . $xsub;
							} // ENDIF (substr($xaux, 2, 1) < 1)
							break;
					} // END SWITCH
				} // END FOR
				$xi = $xi + 3;
			} // ENDDO

			if (substr(trim($xcadena), -5, 5) == "ILLON") // si la cadena obtenida termina en MILLON o BILLON, entonces le agrega al final la conjuncion DE
				$xcadena.= " DE";

			if (substr(trim($xcadena), -7, 7) == "ILLONES") // si la cadena obtenida en MILLONES o BILLONES, entoncea le agrega al final la conjuncion DE
				$xcadena.= " DE";

			// ----------- esta línea la puedes cambiar de acuerdo a tus necesidades o a tu país -------
			if (trim($xaux) != "") {
				switch ($xz) {
					case 0:
						if (trim(substr($XAUX, $xz * 6, 6)) == "1")
							$xcadena.= "UN BILLON ";
						else
							$xcadena.= " BILLONES ";
						break;
					case 1:
						if (trim(substr($XAUX, $xz * 6, 6)) == "1")
							$xcadena.= "UN MILLON ";
						else
							$xcadena.= " MILLONES ";
						break;
					case 2:
						if ($xcifra < 1) {
							$xcadena = "CERO $xdecimales/100 M.N.";
						}
						if ($xcifra >= 1 && $xcifra < 2) {
							$xcadena = "UN $xdecimales/100 M.N. ";
						}
						if ($xcifra >= 2) {
							$xcadena.= " $xdecimales/100 M.N. "; //
						}
						break;
				} // endswitch ($xz)
			} // ENDIF (trim($xaux) != "")
			// ------------------      en este caso, para México se usa esta leyenda     ----------------
			$xcadena = str_replace("VEINTI ", "VEINTI", $xcadena); // quito el espacio para el VEINTI, para que quede: VEINTICUATRO, VEINTIUN, VEINTIDOS, etc
			$xcadena = str_replace("  ", " ", $xcadena); // quito espacios dobles
			$xcadena = str_replace("UN UN", "UN", $xcadena); // quito la duplicidad
			$xcadena = str_replace("  ", " ", $xcadena); // quito espacios dobles
			$xcadena = str_replace("BILLON DE MILLONES", "BILLON DE", $xcadena); // corrigo la leyenda
			$xcadena = str_replace("BILLONES DE MILLONES", "BILLONES DE", $xcadena); // corrigo la leyenda
			$xcadena = str_replace("DE UN", "UN", $xcadena); // corrigo la leyenda
		} // ENDFOR ($xz)
		return trim($xcadena);
	}

	// END FUNCTION

	public function subfijo($xx)
	{ // esta función regresa un subfijo para la cifra
		$xx = trim($xx);
		$xstrlen = strlen($xx);
		if ($xstrlen == 1 || $xstrlen == 2 || $xstrlen == 3)
			$xsub = "";
		//
		if ($xstrlen == 4 || $xstrlen == 5 || $xstrlen == 6)
			$xsub = "MIL";
		//
		return $xsub;
	}


	/**
	 * Retorna la lista de Paises del mundo en formato objeto
	 */
	public function paises(){
		$data =  array(
				 array('id'=>1, 'nombre'=>'Australia'),
				 array('id'=>2, 'nombre'=>'Austria'),
				 array('id'=>3, 'nombre'=>'Azerbaiyán'),
				 array('id'=>4, 'nombre'=>'Anguilla'),
				 array('id'=>5, 'nombre'=>'Argentina'),
				 array('id'=>6, 'nombre'=>'Armenia'),
				 array('id'=>7, 'nombre'=>'Bielorrusia'),
				 array('id'=>8, 'nombre'=>'Belice'),
				 array('id'=>9, 'nombre'=>'Bélgica'),
				 array('id'=>10, 'nombre'=>'Bermudas'),
				 array('id'=>11, 'nombre'=>'Bulgaria'),
				 array('id'=>12, 'nombre'=>'Brasil'),
				 array('id'=>13, 'nombre'=>'Reino Unido'),
				 array('id'=>14, 'nombre'=>'Hungría'),
				 array('id'=>15, 'nombre'=>'Vietnam'),
				 array('id'=>16, 'nombre'=>'Haiti'),
				 array('id'=>17, 'nombre'=>'Guadalupe'),
				 array('id'=>18, 'nombre'=>'Alemania'),
				 array('id'=>19, 'nombre'=>'Países Bajos, Holanda'),
				 array('id'=>20, 'nombre'=>'Grecia'),
				 array('id'=>21, 'nombre'=>'Georgia'),
				 array('id'=>22, 'nombre'=>'Dinamarca'),
				 array('id'=>23, 'nombre'=>'Egipto'),
				 array('id'=>24, 'nombre'=>'Israel'),
				 array('id'=>25, 'nombre'=>'India'),
				 array('id'=>26, 'nombre'=>'Irán'),
				 array('id'=>27, 'nombre'=>'Irlanda'),
				 array('id'=>28, 'nombre'=>'España'),
				 array('id'=>29, 'nombre'=>'Italia'),
				 array('id'=>30, 'nombre'=>'Kazajstán'),
				 array('id'=>31, 'nombre'=>'Camerún'),
				 array('id'=>32, 'nombre'=>'Canadá'),
				 array('id'=>33, 'nombre'=>'Chipre'),
				 array('id'=>34, 'nombre'=>'Kirguistán'),
				 array('id'=>35, 'nombre'=>'China'),
				 array('id'=>36, 'nombre'=>'Costa Rica'),
				 array('id'=>37, 'nombre'=>'Kuwait'),
				 array('id'=>38, 'nombre'=>'Letonia'),
				 array('id'=>39, 'nombre'=>'Libia'),
				 array('id'=>40, 'nombre'=>'Lituania'),
				 array('id'=>41, 'nombre'=>'Luxemburgo'),
				 array('id'=>42, 'nombre'=>'México'),
				 array('id'=>43, 'nombre'=>'Moldavia'),
				 array('id'=>44, 'nombre'=>'Mónaco'),
				 array('id'=>45, 'nombre'=>'Nueva Zelanda'),
				 array('id'=>46, 'nombre'=>'Noruega'),
				 array('id'=>47, 'nombre'=>'Polonia'),
				 array('id'=>48, 'nombre'=>'Portugal'),
				 array('id'=>49, 'nombre'=>'Reunión'),
				 array('id'=>50, 'nombre'=>'Rusia'),
				 array('id'=>51, 'nombre'=>'El Salvador'),
				 array('id'=>52, 'nombre'=>'Eslovaquia'),
				 array('id'=>53, 'nombre'=>'Eslovenia'),
				 array('id'=>54, 'nombre'=>'Surinam'),
				 array('id'=>55, 'nombre'=>'Estados Unidos'),
				 array('id'=>56, 'nombre'=>'Tadjikistan'),
				 array('id'=>57, 'nombre'=>'Turkmenistan'),
				 array('id'=>58, 'nombre'=>'Islas Turcas y Caicos'),
				 array('id'=>59, 'nombre'=>'Turquía'),
				 array('id'=>60, 'nombre'=>'Uganda'),
				 array('id'=>61, 'nombre'=>'Uzbekistán'),
				 array('id'=>62, 'nombre'=>'Ucrania'),
				 array('id'=>63, 'nombre'=>'Finlandia'),
				 array('id'=>64, 'nombre'=>'Francia'),
				 array('id'=>65, 'nombre'=>'República Checa'),
				 array('id'=>66, 'nombre'=>'Suiza'),
				 array('id'=>67, 'nombre'=>'Suecia'),
				 array('id'=>68, 'nombre'=>'Estonia'),
				 array('id'=>69, 'nombre'=>'Corea del Sur'),
				 array('id'=>70, 'nombre'=>'Japón'),
				 array('id'=>71, 'nombre'=>'Croacia'),
				 array('id'=>72, 'nombre'=>'Rumanía'),
				 array('id'=>73, 'nombre'=>'Hong Kong'),
				 array('id'=>74, 'nombre'=>'Indonesia'),
				 array('id'=>75, 'nombre'=>'Jordania'),
				 array('id'=>76, 'nombre'=>'Malasia'),
				 array('id'=>77, 'nombre'=>'Singapur'),
				 array('id'=>78, 'nombre'=>'Taiwan'),
				 array('id'=>79, 'nombre'=>'Bosnia y Herzegovina'),
				 array('id'=>80, 'nombre'=>'Bahamas'),
				 array('id'=>81, 'nombre'=>'Chile'),
				 array('id'=>82, 'nombre'=>'Colombia'),
				 array('id'=>83, 'nombre'=>'Islandia'),
				 array('id'=>84, 'nombre'=>'Corea del Norte'),
				 array('id'=>85, 'nombre'=>'Macedonia'),
				 array('id'=>86, 'nombre'=>'Malta'),
				 array('id'=>87, 'nombre'=>'Pakistán'),
				 array('id'=>88, 'nombre'=>'Papúa-Nueva Guinea'),
				 array('id'=>89, 'nombre'=>'Perú'),
				 array('id'=>90, 'nombre'=>'Filipinas'),
				 array('id'=>91, 'nombre'=>'Arabia Saudita'),
				 array('id'=>92, 'nombre'=>'Tailandia'),
				 array('id'=>93, 'nombre'=>'Emiratos árabes Unidos'),
				 array('id'=>94, 'nombre'=>'Groenlandia'),
				 array('id'=>95, 'nombre'=>'Venezuela'),
				 array('id'=>96, 'nombre'=>'Zimbabwe'),
				 array('id'=>97, 'nombre'=>'Kenia'),
				 array('id'=>98, 'nombre'=>'Algeria'),
				 array('id'=>99, 'nombre'=>'Líbano'),
				 array('id'=>100, 'nombre'=>'Botsuana'),
				 array('id'=>101, 'nombre'=>'Tanzania'),
				 array('id'=>102, 'nombre'=>'Namibia'),
				 array('id'=>103, 'nombre'=>'Ecuador'),
				 array('id'=>104, 'nombre'=>'Marruecos'),
				 array('id'=>105, 'nombre'=>'Ghana'),
				 array('id'=>106, 'nombre'=>'Siria'),
				 array('id'=>107, 'nombre'=>'Nepal'),
				 array('id'=>108, 'nombre'=>'Mauritania'),
				 array('id'=>109, 'nombre'=>'Seychelles'),
				 array('id'=>110, 'nombre'=>'Paraguay'),
				 array('id'=>111, 'nombre'=>'Uruguay'),
				 array('id'=>112, 'nombre'=>'Congo Brazzaville'),
				 array('id'=>113, 'nombre'=>'Cuba'),
				 array('id'=>114, 'nombre'=>'Albania'),
				 array('id'=>115, 'nombre'=>'Nigeria'),
				 array('id'=>116, 'nombre'=>'Zambia'),
				 array('id'=>117, 'nombre'=>'Mozambique'),
				 array('id'=>119, 'nombre'=>'Angola'),
				 array('id'=>120, 'nombre'=>'Sri Lanka'),
				 array('id'=>121, 'nombre'=>'Etiopía'),
				 array('id'=>122, 'nombre'=>'Túnez'),
				 array('id'=>123, 'nombre'=>'Bolivia'),
				 array('id'=>124, 'nombre'=>'Panamá'),
				 array('id'=>125, 'nombre'=>'Malawi'),
				 array('id'=>126, 'nombre'=>'Liechtenstein'),
				 array('id'=>127, 'nombre'=>'Bahrein'),
				 array('id'=>128, 'nombre'=>'Barbados'),
				 array('id'=>130, 'nombre'=>'Chad'),
				 array('id'=>131, 'nombre'=>'Man, Isla de'),
				 array('id'=>132, 'nombre'=>'Jamaica'),
				 array('id'=>133, 'nombre'=>'Malí'),
				 array('id'=>134, 'nombre'=>'Madagascar'),
				 array('id'=>135, 'nombre'=>'Senegal'),
				 array('id'=>136, 'nombre'=>'Togo'),
				 array('id'=>137, 'nombre'=>'Honduras'),
				 array('id'=>138, 'nombre'=>'República Dominicana'),
				 array('id'=>139, 'nombre'=>'Mongolia'),
				 array('id'=>140, 'nombre'=>'Irak'),
				 array('id'=>141, 'nombre'=>'Sudáfrica'),
				 array('id'=>142, 'nombre'=>'Aruba'),
				 array('id'=>143, 'nombre'=>'Gibraltar'),
				 array('id'=>144, 'nombre'=>'Afganistán'),
				 array('id'=>145, 'nombre'=>'Andorra'),
				 array('id'=>147, 'nombre'=>'Antigua y Barbuda'),
				 array('id'=>149, 'nombre'=>'Bangladesh'),
				 array('id'=>151, 'nombre'=>'Benín'),
				 array('id'=>152, 'nombre'=>'Bután'),
				 array('id'=>154, 'nombre'=>'Islas Virgenes Británicas'),
				 array('id'=>155, 'nombre'=>'Brunéi'),
				 array('id'=>156, 'nombre'=>'Burkina Faso'),
				 array('id'=>157, 'nombre'=>'Burundi'),
				 array('id'=>158, 'nombre'=>'Camboya'),
				 array('id'=>159, 'nombre'=>'Cabo Verde'),
				 array('id'=>164, 'nombre'=>'Comores'),
				 array('id'=>165, 'nombre'=>'Congo Kinshasa'),
				 array('id'=>166, 'nombre'=>'Cook, Islas'),
				 array('id'=>168, 'nombre'=>'Costa de Marfil'),
				 array('id'=>169, 'nombre'=>'Djibouti, Yibuti'),
				 array('id'=>171, 'nombre'=>'Timor Oriental'),
				 array('id'=>172, 'nombre'=>'Guinea Ecuatorial'),
				 array('id'=>173, 'nombre'=>'Eritrea'),
				 array('id'=>175, 'nombre'=>'Feroe, Islas'),
				 array('id'=>176, 'nombre'=>'Fiyi'),
				 array('id'=>178, 'nombre'=>'Polinesia Francesa'),
				 array('id'=>180, 'nombre'=>'Gabón'),
				 array('id'=>181, 'nombre'=>'Gambia'),
				 array('id'=>184, 'nombre'=>'Granada'),
				 array('id'=>185, 'nombre'=>'Guatemala'),
				 array('id'=>186, 'nombre'=>'Guernsey'),
				 array('id'=>187, 'nombre'=>'Guinea'),
				 array('id'=>188, 'nombre'=>'Guinea-Bissau'),
				 array('id'=>189, 'nombre'=>'Guyana'),
				 array('id'=>193, 'nombre'=>'Jersey'),
				 array('id'=>195, 'nombre'=>'Kiribati'),
				 array('id'=>196, 'nombre'=>'Laos'),
				 array('id'=>197, 'nombre'=>'Lesotho'),
				 array('id'=>198, 'nombre'=>'Liberia'),
				 array('id'=>200, 'nombre'=>'Maldivas'),
				 array('id'=>201, 'nombre'=>'Martinica'),
				 array('id'=>202, 'nombre'=>'Mauricio'),
				 array('id'=>205, 'nombre'=>'Myanmar'),
				 array('id'=>206, 'nombre'=>'Nauru'),
				 array('id'=>207, 'nombre'=>'Antillas Holandesas'),
				 array('id'=>208, 'nombre'=>'Nueva Caledonia'),
				 array('id'=>209, 'nombre'=>'Nicaragua'),
				 array('id'=>210, 'nombre'=>'Níger'),
				 array('id'=>212, 'nombre'=>'Norfolk Island'),
				 array('id'=>213, 'nombre'=>'Omán'),
				 array('id'=>215, 'nombre'=>'Isla Pitcairn'),
				 array('id'=>216, 'nombre'=>'Qatar'),
				 array('id'=>217, 'nombre'=>'Ruanda'),
				 array('id'=>218, 'nombre'=>'Santa Elena'),
				 array('id'=>219, 'nombre'=>'San Cristobal y Nevis'),
				 array('id'=>220, 'nombre'=>'Santa Lucía'),
				 array('id'=>221, 'nombre'=>'San Pedro y Miquelón'),
				 array('id'=>222, 'nombre'=>'San Vincente y Granadinas'),
				 array('id'=>223, 'nombre'=>'Samoa'),
				 array('id'=>224, 'nombre'=>'San Marino'),
				 array('id'=>225, 'nombre'=>'San Tomé y Príncipe'),
				 array('id'=>226, 'nombre'=>'Serbia y Montenegro'),
				 array('id'=>227, 'nombre'=>'Sierra Leona'),
				 array('id'=>228, 'nombre'=>'Islas Salomón'),
				 array('id'=>229, 'nombre'=>'Somalia'),
				 array('id'=>232, 'nombre'=>'Sudán'),
				 array('id'=>234, 'nombre'=>'Swazilandia'),
				 array('id'=>235, 'nombre'=>'Tokelau'),
				 array('id'=>236, 'nombre'=>'Tonga'),
				 array('id'=>237, 'nombre'=>'Trinidad y Tobago'),
				 array('id'=>239, 'nombre'=>'Tuvalu'),
				 array('id'=>240, 'nombre'=>'Vanuatu'),
				 array('id'=>241, 'nombre'=>'Wallis y Futuna'),
				 array('id'=>242, 'nombre'=>'Sáhara Occidental'),
				 array('id'=>243, 'nombre'=>'Yemen'),
				 array('id'=>246, 'nombre'=>'Puerto Rico')
		);
		return $data;
	}//



	public function estados($X){
		$data = array(
			"3"	=> array(
				array(1, 'Azerbaijan'),
				array(2, 'Nargorni Karabakh'),
				array(3, 'Nakhichevanskaya Region')
			),
			"4"	=>	array(
				array(4, 'Anguilla')
			),
			"7" =>	array(
				array(5, 'Brestskaya obl.'),
				array(6, 'Vitebskaya obl.'),
				array(7, 'Gomelskaya obl.'),
				array(8, 'Grodnenskaya obl.'),
				array(9, 'Minskaya obl.'),
				array(10, 'Mogilevskaya obl.'),
			),
			"8"	=>	array(
				array(11, 'Belize'),
			),
			"10" => array(
				array(12, 'Hamilton'),
			),
			"15"	=>	array(
				array(13, 'Dong Bang Song Cuu Long'),
				array(14, 'Dong Bang Song Hong'),
				array(15, 'Dong Nam Bo'),
				array(16, 'Duyen Hai Mien Trung'),
				array(17, 'Khu Bon Cu'),
				array(18, 'Mien Nui Va Trung Du'),
				array(19, 'Thai Nguyen'),
			),
			"16"	=>	array(
				array(20, 'Artibonite'),
				array(21, 'Grand&#039;Anse'),
				array(22, 'North West'),
				array(23, 'West'),
				array(24, 'South'),
				array(25, 'South East'),
			),
			"17"	=>	array(
				array(26, 'Grande-Terre'),
				array(27, 'Basse-Terre'),
			),
			"21"	=>	array(
				array(28, 'Abkhazia'),
				array(29, 'Ajaria'),
				array(30, 'Georgia'),
				array(31, 'South Ossetia'),
			),
			"23"	=>	array(
				array(32, 'Al QÄhira'),
				array(33, 'Aswan'),
				array(34, 'Asyut'),
				array(35, 'Beni Suef'),
				array(36, 'Gharbia'),
				array(37, 'Damietta'),
			),
			"24"	=>	array(
				array(38, 'Southern District'),
				array(39, 'Central District'),
				array(40, 'Northern District'),
				array(41, 'Haifa'),
				array(42, 'Tel Aviv'),
				array(43, 'Jerusalem'),
			),
			"25"	=>	array(
				array(44, 'Bangala'),
				array(45, 'Chhattisgarh'),
				array(46, 'Karnataka'),
				array(47, 'Uttaranchal'),
				array(48, 'Andhara Pradesh'),
				array(49, 'Assam'),
				array(50, 'Bihar'),
				array(51, 'Gujarat'),
				array(52, 'Jammu and Kashmir'),
				array(53, 'Kerala'),
				array(54, 'Madhya Pradesh'),
				array(55, 'Manipur'),
				array(56, 'Maharashtra'),
				array(57, 'Megahalaya'),
				array(58, 'Orissa'),
				array(59, 'Punjab'),
				array(60, 'Pondisheri'),
				array(61, 'Rajasthan'),
				array(62, 'Tamil Nadu'),
				array(63, 'Tripura'),
				array(64, 'Uttar Pradesh'),
				array(65, 'Haryana'),
				array(66, 'Chandigarh'),
			),
			"26"	=>	array(
				array(67, 'Azarbayjan-e Khavari'),
				array(68, 'Esfahan'),
				array(69, 'Hamadan'),
				array(70, 'Kordestan'),
				array(71, 'Markazi'),
				array(72, 'Sistan-e Baluches'),
				array(73, 'Yazd'),
				array(74, 'Kerman'),
				array(75, 'Kermanshakhan'),
				array(76, 'Mazenderan'),
				array(77, 'Tehran'),
				array(78, 'Fars'),
				array(79, 'Horasan'),
				array(79, 'Horasan'),
				array(80, 'Husistan'),
			),
			"30"	=>	array(
				array(81, 'Aktyubinskaya obl.'),
				array(82, 'Alma-Atinskaya obl.'),
				array(83, 'Vostochno-Kazahstanskaya obl.'),
				array(84, 'Gurevskaya obl.'),
				array(85, 'Zhambylskaya obl. (Dzhambulskaya obl.)'),
				array(86, 'Dzhezkazganskaya obl.'),
				array(87, 'Karagandinskaya obl.'),
				array(88, 'Kzyl-Ordinskaya obl.'),
				array(89, 'Kokchetavskaya obl.'),
				array(90, 'Kustanaiskaya obl.'),
				array(91, 'Mangystauskaya (Mangyshlakskaya obl.)'),
				array(92, 'Pavlodarskaya obl.'),
				array(93, 'Severo-Kazahstanskaya obl.'),
				array(94, 'Taldy-Kurganskaya obl.'),
				array(95, 'Turgaiskaya obl.'),
				array(96, 'Akmolinskaya obl. (Tselinogradskaya obl.)'),
				array(97, 'Chimkentskaya obl.'),
			),
		);

		
		/*
		(98, 31, 'Littoral'),
		(99, 31, 'Southwest Region'),
		(100, 31, 'North'),
		(101, 31, 'Central'),
		(102, 33, 'Government controlled area'),
		(103, 33, 'Turkish controlled area'),
		(104, 34, 'Issik Kulskaya Region'),
		(105, 34, 'Kyrgyzstan'),
		(106, 34, 'Narinskaya Region'),
		(107, 34, 'Oshskaya Region'),
		(108, 34, 'Tallaskaya Region'),
		(109, 37, 'al-Jahra'),
		(110, 37, 'al-Kuwait'),
		(111, 38, 'Latviya'),
		(112, 39, 'Tarabulus'),
		(113, 39, 'Bengasi'),
		(114, 40, 'Litva'),
		(115, 43, 'Moldova'),
		(116, 45, 'Auckland'),
		(117, 45, 'Bay of Plenty'),
		(118, 45, 'Canterbury'),
		(119, 45, 'Gisborne'),
		(120, 45, 'Hawke&#039;s Bay'),
		(121, 45, 'Manawatu-Wanganui'),
		(122, 45, 'Marlborough'),
		(123, 45, 'Nelson'),
		(124, 45, 'Northland'),
		(125, 45, 'Otago'),
		(126, 45, 'Southland'),
		(127, 45, 'Taranaki'),
		(128, 45, 'Tasman'),
		(129, 45, 'Waikato'),
		(130, 45, 'Wellington'),
		(131, 45, 'West Coast'),
		(132, 49, 'Saint-Denis'),
		(133, 50, 'Altaiskii krai'),
		(134, 50, 'Amurskaya obl.'),
		(135, 50, 'Arhangelskaya obl.'),
		(136, 50, 'Astrahanskaya obl.'),
		(137, 50, 'Bashkiriya obl.'),
		(138, 50, 'Belgorodskaya obl.'),
		(139, 50, 'Bryanskaya obl.'),
		(140, 50, 'Buryatiya'),
		(141, 50, 'Vladimirskaya obl.'),
		(142, 50, 'Volgogradskaya obl.'),
		(143, 50, 'Vologodskaya obl.'),
		(144, 50, 'Voronezhskaya obl.'),
		(145, 50, 'Nizhegorodskaya obl.'),
		(146, 50, 'Dagestan'),
		(147, 50, 'Evreiskaya obl.'),
		(148, 50, 'Ivanovskaya obl.'),
		(149, 50, 'Irkutskaya obl.'),
		(150, 50, 'Kabardino-Balkariya'),
		(151, 50, 'Kaliningradskaya obl.'),
		(152, 50, 'Tverskaya obl.'),
		(153, 50, 'Kalmykiya'),
		(154, 50, 'Kaluzhskaya obl.'),
		(155, 50, 'Kamchatskaya obl.'),
		(156, 50, 'Kareliya'),
		(157, 50, 'Kemerovskaya obl.'),
		(158, 50, 'Kirovskaya obl.'),
		(159, 50, 'Komi'),
		(160, 50, 'Kostromskaya obl.'),
		(161, 50, 'Krasnodarskii krai'),
		(162, 50, 'Krasnoyarskii krai'),
		(163, 50, 'Kurganskaya obl.'),
		(164, 50, 'Kurskaya obl.'),
		(165, 50, 'Lipetskaya obl.'),
		(166, 50, 'Magadanskaya obl.'),
		(167, 50, 'Marii El'),
		(168, 50, 'Mordoviya'),
		(169, 50, 'Moscow &amp; Moscow Region'),
		(170, 50, 'Murmanskaya obl.'),
		(171, 50, 'Novgorodskaya obl.'),
		(172, 50, 'Novosibirskaya obl.'),
		(173, 50, 'Omskaya obl.'),
		(174, 50, 'Orenburgskaya obl.'),
		(175, 50, 'Orlovskaya obl.'),
		(176, 50, 'Penzenskaya obl.'),
		(177, 50, 'Permskiy krai'),
		(178, 50, 'Primorskii krai'),
		(179, 50, 'Pskovskaya obl.'),
		(180, 50, 'Rostovskaya obl.'),
		(181, 50, 'Ryazanskaya obl.'),
		(182, 50, 'Samarskaya obl.'),
		(183, 50, 'Saint-Petersburg and Region'),
		(184, 50, 'Saratovskaya obl.'),
		(185, 50, 'Saha (Yakutiya)'),
		(186, 50, 'Sahalin'),
		(187, 50, 'Sverdlovskaya obl.'),
		(188, 50, 'Severnaya Osetiya'),
		(189, 50, 'Smolenskaya obl.'),
		(190, 50, 'Stavropolskii krai'),
		(191, 50, 'Tambovskaya obl.'),
		(192, 50, 'Tatarstan'),
		(193, 50, 'Tomskaya obl.'),
		(195, 50, 'Tulskaya obl.'),
		(196, 50, 'Tyumenskaya obl. i Hanty-Mansiiskii AO'),
		(197, 50, 'Udmurtiya'),
		(198, 50, 'Ulyanovskaya obl.'),
		(199, 50, 'Uralskaya obl.'),
		(200, 50, 'Habarovskii krai'),
		(201, 50, 'Chelyabinskaya obl.'),
		(202, 50, 'Checheno-Ingushetiya'),
		(203, 50, 'Chitinskaya obl.'),
		(204, 50, 'Chuvashiya'),
		(205, 50, 'Yaroslavskaya obl.'),
		(206, 51, 'Ahuachapán'),
		(207, 51, 'Cuscatlán'),
		(208, 51, 'La Libertad'),
		(209, 51, 'La Paz'),
		(210, 51, 'La Unión'),
		(211, 51, 'San Miguel'),
		(212, 51, 'San Salvador'),
		(213, 51, 'Santa Ana'),
		(214, 51, 'Sonsonate'),
		(215, 54, 'Paramaribo'),
		(216, 56, 'Gorno-Badakhshan Region'),
		(217, 56, 'Kuljabsk Region'),
		(218, 56, 'Kurgan-Tjube Region'),
		(219, 56, 'Sughd Region'),
		(220, 56, 'Tajikistan'),
		(221, 57, 'Ashgabat Region'),
		(222, 57, 'Krasnovodsk Region'),
		(223, 57, 'Mary Region'),
		(224, 57, 'Tashauz Region'),
		(225, 57, 'Chardzhou Region'),
		(226, 58, 'Grand Turk'),
		(227, 59, 'Bartin'),
		(228, 59, 'Bayburt'),
		(229, 59, 'Karabuk'),
		(230, 59, 'Adana'),
		(231, 59, 'Aydin'),
		(232, 59, 'Amasya'),
		(233, 59, 'Ankara'),
		(234, 59, 'Antalya'),
		(235, 59, 'Artvin'),
		(236, 59, 'Afion'),
		(237, 59, 'Balikesir'),
		(238, 59, 'Bilecik'),
		(239, 59, 'Bursa'),
		(240, 59, 'Gaziantep'),
		(241, 59, 'Denizli'),
		(242, 59, 'Izmir'),
		(243, 59, 'Isparta'),
		(244, 59, 'Icel'),
		(245, 59, 'Kayseri'),
		(246, 59, 'Kars'),
		(247, 59, 'Kodjaeli'),
		(248, 59, 'Konya'),
		(249, 59, 'Kirklareli'),
		(250, 59, 'Kutahya'),
		(251, 59, 'Malatya'),
		(252, 59, 'Manisa'),
		(253, 59, 'Sakarya'),
		(254, 59, 'Samsun'),
		(255, 59, 'Sivas'),
		(256, 59, 'Istanbul'),
		(257, 59, 'Trabzon'),
		(258, 59, 'Corum'),
		(259, 59, 'Edirne'),
		(260, 59, 'Elazig'),
		(261, 59, 'Erzincan'),
		(262, 59, 'Erzurum'),
		(263, 59, 'Eskisehir'),
		(264, 60, 'Jinja'),
		(265, 60, 'Kampala'),
		(266, 61, 'Andijon Region'),
		(267, 61, 'Buxoro Region'),
		(268, 61, 'Jizzac Region'),
		(269, 61, 'Qaraqalpaqstan'),
		(270, 61, 'Qashqadaryo Region'),
		(271, 61, 'Navoiy Region'),
		(272, 61, 'Namangan Region'),
		(273, 61, 'Samarqand Region'),
		(274, 61, 'Surxondaryo Region'),
		(275, 61, 'Sirdaryo Region'),
		(276, 61, 'Tashkent Region'),
		(277, 61, 'Fergana Region'),
		(278, 61, 'Xorazm Region'),
		(279, 62, 'Vinnitskaya obl.'),
		(280, 62, 'Volynskaya obl.'),
		(281, 62, 'Dnepropetrovskaya obl.'),
		(282, 62, 'Donetskaya obl.'),
		(283, 62, 'Zhitomirskaya obl.'),
		(284, 62, 'Zakarpatskaya obl.'),
		(285, 62, 'Zaporozhskaya obl.'),
		(286, 62, 'Ivano-Frankovskaya obl.'),
		(287, 62, 'Kievskaya obl.'),
		(288, 62, 'Kirovogradskaya obl.'),
		(289, 62, 'Krymskaya obl.'),
		(290, 62, 'Luganskaya obl.'),
		(291, 62, 'Lvovskaya obl.'),
		(292, 62, 'Nikolaevskaya obl.'),
		(293, 62, 'Odesskaya obl.'),
		(294, 62, 'Poltavskaya obl.'),
		(295, 62, 'Rovenskaya obl.'),
		(296, 62, 'Sumskaya obl.'),
		(297, 62, 'Ternopolskaya obl.'),
		(298, 62, 'Harkovskaya obl.'),
		(299, 62, 'Hersonskaya obl.'),
		(300, 62, 'Hmelnitskaya obl.'),
		(301, 62, 'Cherkasskaya obl.'),
		(302, 62, 'Chernigovskaya obl.'),
		(303, 62, 'Chernovitskaya obl.'),
		(304, 68, 'Estoniya'),
		(305, 69, 'Cheju'),
		(306, 69, 'Chollabuk'),
		(307, 69, 'Chollanam'),
		(308, 69, 'Chungcheongbuk'),
		(309, 69, 'Chungcheongnam'),
		(310, 69, 'Incheon'),
		(311, 69, 'Kangweon'),
		(312, 69, 'Kwangju'),
		(313, 69, 'Kyeonggi'),
		(314, 69, 'Kyeongsangbuk'),
		(315, 69, 'Kyeongsangnam'),
		(316, 69, 'Pusan'),
		(317, 69, 'Seoul'),
		(318, 69, 'Taegu'),
		(319, 69, 'Taejeon'),
		(320, 69, 'Ulsan'),
		(321, 70, 'Aichi'),
		(322, 70, 'Akita'),
		(323, 70, 'Aomori'),
		(324, 70, 'Wakayama'),
		(325, 70, 'Gifu'),
		(326, 70, 'Gunma'),
		(327, 70, 'Ibaraki'),
		(328, 70, 'Iwate'),
		(329, 70, 'Ishikawa'),
		(330, 70, 'Kagawa'),
		(331, 70, 'Kagoshima'),
		(332, 70, 'Kanagawa'),
		(333, 70, 'Kyoto'),
		(334, 70, 'Kochi'),
		(335, 70, 'Kumamoto'),
		(336, 70, 'Mie'),
		(337, 70, 'Miyagi'),
		(338, 70, 'Miyazaki'),
		(339, 70, 'Nagano'),
		(340, 70, 'Nagasaki'),
		(341, 70, 'Nara'),
		(342, 70, 'Niigata'),
		(343, 70, 'Okayama'),
		(344, 70, 'Okinawa'),
		(345, 70, 'Osaka'),
		(346, 70, 'Saga'),
		(347, 70, 'Saitama'),
		(348, 70, 'Shiga'),
		(349, 70, 'Shizuoka'),
		(350, 70, 'Shimane'),
		(351, 70, 'Tiba'),
		(352, 70, 'Tokyo'),
		(353, 70, 'Tokushima'),
		(354, 70, 'Tochigi'),
		(355, 70, 'Tottori'),
		(356, 70, 'Toyama'),
		(357, 70, 'Fukui'),
		(358, 70, 'Fukuoka'),
		(359, 70, 'Fukushima'),
		(360, 70, 'Hiroshima'),
		(361, 70, 'Hokkaido'),
		(362, 70, 'Hyogo'),
		(363, 70, 'Yoshimi'),
		(364, 70, 'Yamagata'),
		(365, 70, 'Yamaguchi'),
		(366, 70, 'Yamanashi'),
		(368, 73, 'Hong Kong'),
		(369, 74, 'Indonesia'),
		(370, 75, 'Jordan'),
		(371, 76, 'Malaysia'),
		(372, 77, 'Singapore'),
		(373, 78, 'Taiwan'),
		(374, 30, 'Kazahstan'),
		(375, 62, 'Ukraina'),
		(376, 25, 'India'),
		(377, 23, 'Egypt'),
		(378, 106, 'Damascus'),
		(379, 131, 'Isle of Man'),
		(380, 30, 'Zapadno-Kazahstanskaya obl.'),
		(381, 50, 'Adygeya'),
		(382, 50, 'Hakasiya'),
		(383, 93, 'Dubai'),
		(384, 50, 'Chukotskii AO'),
		(385, 99, 'Beirut'),
		(386, 137, 'Tegucigalpa'),
		(387, 138, 'Santo Domingo'),
		(388, 139, 'Ulan Bator'),
		(389, 23, 'Sinai'),
		(390, 140, 'Baghdad'),
		(391, 140, 'Basra'),
		(392, 140, 'Mosul'),
		(393, 141, 'Johannesburg'),
		(394, 104, 'Morocco'),
		(395, 104, 'Tangier'),
		(396, 50, 'Yamalo-Nenetskii AO'),
		(397, 122, 'Tunisia'),
		(398, 92, 'Thailand'),
		(399, 117, 'Mozambique'),
		(400, 84, 'Korea'),
		(401, 87, 'Pakistan'),
		(402, 142, 'Aruba'),
		(403, 80, 'Bahamas'),
		(404, 69, 'South Korea'),
		(405, 132, 'Jamaica'),
		(406, 93, 'Sharjah'),
		(407, 93, 'Abu Dhabi'),
		(409, 24, 'Ramat Hagolan'),
		(410, 115, 'Nigeria'),
		(411, 64, 'Ain'),
		(412, 64, 'Haute-Savoie'),
		(413, 64, 'Aisne'),
		(414, 64, 'Allier'),
		(415, 64, 'Alpes-de-Haute-Provence'),
		(416, 64, 'Hautes-Alpes'),
		(417, 64, 'Alpes-Maritimes'),
		(418, 64, 'Ard&egrave;che'),
		(419, 64, 'Ardennes'),
		(420, 64, 'Ari&egrave;ge'),
		(421, 64, 'Aube'),
		(422, 64, 'Aude'),
		(423, 64, 'Aveyron'),
		(424, 64, 'Bouches-du-Rh&ocirc;ne'),
		(425, 64, 'Calvados'),
		(426, 64, 'Cantal'),
		(427, 64, 'Charente'),
		(428, 64, 'Charente Maritime'),
		(429, 64, 'Cher'),
		(430, 64, 'Corr&egrave;ze'),
		(431, 64, 'Dordogne'),
		(432, 64, 'Corse'),
		(433, 64, 'C&ocirc;te d&#039;Or'),
		(434, 64, 'Sa&ocirc;ne et Loire'),
		(435, 64, 'C&ocirc;tes d&#039;Armor'),
		(436, 64, 'Creuse'),
		(437, 64, 'Doubs'),
		(438, 64, 'Dr&ocirc;me'),
		(439, 64, 'Eure'),
		(440, 64, 'Eure-et-Loire'),
		(441, 64, 'Finist&egrave;re'),
		(442, 64, 'Gard'),
		(443, 64, 'Haute-Garonne'),
		(444, 64, 'Gers'),
		(445, 64, 'Gironde'),
		(446, 64, 'Hérault'),
		(447, 64, 'Ille et Vilaine'),
		(448, 64, 'Indre'),
		(449, 64, 'Indre-et-Loire'),
		(450, 64, 'Isère'),
		(451, 64, 'Jura'),
		(452, 64, 'Landes'),
		(453, 64, 'Loir-et-Cher'),
		(454, 64, 'Loire'),
		(455, 64, 'Rh&ocirc;ne'),
		(456, 64, 'Haute-Loire'),
		(457, 64, 'Loire Atlantique'),
		(458, 64, 'Loiret'),
		(459, 64, 'Lot'),
		(460, 64, 'Lot-et-Garonne'),
		(461, 64, 'Loz&egrave;re'),
		(462, 64, 'Maine et Loire'),
		(463, 64, 'Manche'),
		(464, 64, 'Marne'),
		(465, 64, 'Haute-Marne'),
		(466, 64, 'Mayenne'),
		(467, 64, 'Meurthe-et-Moselle'),
		(468, 64, 'Meuse'),
		(469, 64, 'Morbihan'),
		(470, 64, 'Moselle'),
		(471, 64, 'Ni&egrave;vre'),
		(472, 64, 'Nord'),
		(473, 64, 'Oise'),
		(474, 64, 'Orne'),
		(475, 64, 'Pas-de-Calais'),
		(476, 64, 'Puy-de-D&ocirc;me'),
		(477, 64, 'Pyrénées-Atlantiques'),
		(478, 64, 'Hautes-Pyrénées'),
		(479, 64, 'Pyrénées-Orientales'),
		(480, 64, 'Bas Rhin'),
		(481, 64, 'Haut Rhin'),
		(482, 64, 'Haute-Sa&ocirc;ne'),
		(483, 64, 'Sarthe'),
		(484, 64, 'Savoie'),
		(485, 64, 'Paris'),
		(486, 64, 'Seine-Maritime'),
		(487, 64, 'Seine-et-Marne'),
		(488, 64, 'Yvelines'),
		(489, 64, 'Deux-S&egrave;vres'),
		(490, 64, 'Somme'),
		(491, 64, 'Tarn'),
		(492, 64, 'Tarn-et-Garonne'),
		(493, 64, 'Var'),
		(494, 64, 'Vaucluse'),
		(495, 64, 'Vendée'),
		(496, 64, 'Vienne'),
		(497, 64, 'Haute-Vienne'),
		(498, 64, 'Vosges'),
		(499, 64, 'Yonne'),
		(500, 64, 'Territoire de Belfort'),
		(501, 64, 'Essonne'),
		(502, 64, 'Hauts-de-Seine'),
		(503, 64, 'Seine-Saint-Denis'),
		(504, 64, 'Val-de-Marne'),
		(505, 64, 'Val-d&#039;Oise'),
		(506, 29, 'Piemonte - Torino'),
		(507, 29, 'Piemonte - Alessandria'),
		(508, 29, 'Piemonte - Asti'),
		(509, 29, 'Piemonte - Biella'),
		(510, 29, 'Piemonte - Cuneo'),
		(511, 29, 'Piemonte - Novara'),
		(512, 29, 'Piemonte - Verbania'),
		(513, 29, 'Piemonte - Vercelli'),
		(514, 29, 'Valle d&#039;Aosta - Aosta'),
		(515, 29, 'Lombardia - Milano'),
		(516, 29, 'Lombardia - Bergamo'),
		(517, 29, 'Lombardia - Brescia'),
		(518, 29, 'Lombardia - Como'),
		(519, 29, 'Lombardia - Cremona'),
		(520, 29, 'Lombardia - Lecco'),
		(521, 29, 'Lombardia - Lodi'),
		(522, 29, 'Lombardia - Mantova'),
		(523, 29, 'Lombardia - Pavia'),
		(524, 29, 'Lombardia - Sondrio'),
		(525, 29, 'Lombardia - Varese'),
		(526, 29, 'Trentino Alto Adige - Trento'),
		(527, 29, 'Trentino Alto Adige - Bolzano'),
		(528, 29, 'Veneto - Venezia'),
		(529, 29, 'Veneto - Belluno'),
		(530, 29, 'Veneto - Padova'),
		(531, 29, 'Veneto - Rovigo'),
		(532, 29, 'Veneto - Treviso'),
		(533, 29, 'Veneto - Verona'),
		(534, 29, 'Veneto - Vicenza'),
		(535, 29, 'Friuli Venezia Giulia - Trieste'),
		(536, 29, 'Friuli Venezia Giulia - Gorizia'),
		(537, 29, 'Friuli Venezia Giulia - Pordenone'),
		(538, 29, 'Friuli Venezia Giulia - Udine'),
		(539, 29, 'Liguria - Genova'),
		(540, 29, 'Liguria - Imperia'),
		(541, 29, 'Liguria - La Spezia'),
		(542, 29, 'Liguria - Savona'),
		(543, 29, 'Emilia Romagna - Bologna'),
		(544, 29, 'Emilia Romagna - Ferrara'),
		(545, 29, 'Emilia Romagna - Forlì-Cesena'),
		(546, 29, 'Emilia Romagna - Modena'),
		(547, 29, 'Emilia Romagna - Parma'),
		(548, 29, 'Emilia Romagna - Piacenza'),
		(549, 29, 'Emilia Romagna - Ravenna'),
		(550, 29, 'Emilia Romagna - Reggio Emilia'),
		(551, 29, 'Emilia Romagna - Rimini'),
		(552, 29, 'Toscana - Firenze'),
		(553, 29, 'Toscana - Arezzo'),
		(554, 29, 'Toscana - Grosseto'),
		(555, 29, 'Toscana - Livorno'),
		(556, 29, 'Toscana - Lucca'),
		(557, 29, 'Toscana - Massa Carrara'),
		(558, 29, 'Toscana - Pisa'),
		(559, 29, 'Toscana - Pistoia'),
		(560, 29, 'Toscana - Prato'),
		(561, 29, 'Toscana - Siena'),
		(562, 29, 'Umbria - Perugia'),
		(563, 29, 'Umbria - Terni'),
		(564, 29, 'Marche - Ancona'),
		(565, 29, 'Marche - Ascoli Piceno'),
		(566, 29, 'Marche - Macerata'),
		(567, 29, 'Marche - Pesaro - Urbino'),
		(568, 29, 'Lazio - Roma'),
		(569, 29, 'Lazio - Frosinone'),
		(570, 29, 'Lazio - Latina'),
		(571, 29, 'Lazio - Rieti'),
		(572, 29, 'Lazio - Viterbo'),
		(573, 29, 'Abruzzo - L´Aquila'),
		(574, 29, 'Abruzzo - Chieti'),
		(575, 29, 'Abruzzo - Pescara'),
		(576, 29, 'Abruzzo - Teramo'),
		(577, 29, 'Molise - Campobasso'),
		(578, 29, 'Molise - Isernia'),
		(579, 29, 'Campania - Napoli'),
		(580, 29, 'Campania - Avellino'),
		(581, 29, 'Campania - Benevento'),
		(582, 29, 'Campania - Caserta'),
		(583, 29, 'Campania - Salerno'),
		(584, 29, 'Puglia - Bari'),
		(585, 29, 'Puglia - Brindisi'),
		(586, 29, 'Puglia - Foggia'),
		(587, 29, 'Puglia - Lecce'),
		(588, 29, 'Puglia - Taranto'),
		(589, 29, 'Basilicata - Potenza'),
		(590, 29, 'Basilicata - Matera'),
		(591, 29, 'Calabria - Catanzaro'),
		(592, 29, 'Calabria - Cosenza'),
		(593, 29, 'Calabria - Crotone'),
		(594, 29, 'Calabria - Reggio Calabria'),
		(595, 29, 'Calabria - Vibo Valentia'),
		(596, 29, 'Sicilia - Palermo'),
		(597, 29, 'Sicilia - Agrigento'),
		(598, 29, 'Sicilia - Caltanissetta'),
		(599, 29, 'Sicilia - Catania'),
		(600, 29, 'Sicilia - Enna'),
		(601, 29, 'Sicilia - Messina'),
		(602, 29, 'Sicilia - Ragusa'),
		(603, 29, 'Sicilia - Siracusa'),
		(604, 29, 'Sicilia - Trapani'),
		(605, 29, 'Sardegna - Cagliari'),
		(606, 29, 'Sardegna - Nuoro'),
		(607, 29, 'Sardegna - Oristano'),
		(608, 29, 'Sardegna - Sassari'),
		(609, 28, 'Las Palmas'),
		(610, 28, 'Soria'),
		(611, 28, 'Palencia'),
		(612, 28, 'Zamora'),
		(613, 28, 'Cádiz'),
		(614, 28, 'Navarra'),
		(615, 28, 'Ourense'),
		(616, 28, 'Segovia'),
		(617, 28, 'Guip&uacute;zcoa'),
		(618, 28, 'Ciudad Real'),
		(619, 28, 'Vizcaya'),
		(620, 28, 'álava'),
		(621, 28, 'A Coruña'),
		(622, 28, 'Cantabria'),
		(623, 28, 'Almería'),
		(624, 28, 'Zaragoza'),
		(625, 28, 'Santa Cruz de Tenerife'),
		(626, 28, 'Cáceres'),
		(627, 28, 'Guadalajara'),
		(628, 28, 'ávila'),
		(629, 28, 'Toledo'),
		(630, 28, 'Castellón'),
		(631, 28, 'Tarragona'),
		(632, 28, 'Lugo'),
		(633, 28, 'La Rioja'),
		(634, 28, 'Ceuta'),
		(635, 28, 'Murcia'),
		(636, 28, 'Salamanca'),
		(637, 28, 'Valladolid'),
		(638, 28, 'Jaén'),
		(639, 28, 'Girona'),
		(640, 28, 'Granada'),
		(641, 28, 'Alacant'),
		(642, 28, 'Córdoba'),
		(643, 28, 'Albacete'),
		(644, 28, 'Cuenca'),
		(645, 28, 'Pontevedra'),
		(646, 28, 'Teruel'),
		(647, 28, 'Melilla'),
		(648, 28, 'Barcelona'),
		(649, 28, 'Badajoz'),
		(650, 28, 'Madrid'),
		(651, 28, 'Sevilla'),
		(652, 28, 'Val&egrave;ncia'),
		(653, 28, 'Huelva'),
		(654, 28, 'Lleida'),
		(655, 28, 'León'),
		(656, 28, 'Illes Balears'),
		(657, 28, 'Burgos'),
		(658, 28, 'Huesca'),
		(659, 28, 'Asturias'),
		(660, 28, 'Málaga'),
		(661, 144, 'Afghanistan'),
		(662, 210, 'Niger'),
		(663, 133, 'Mali'),
		(664, 156, 'Burkina Faso'),
		(665, 136, 'Togo'),
		(666, 151, 'Benin'),
		(667, 119, 'Angola'),
		(668, 102, 'Namibia'),
		(669, 100, 'Botswana'),
		(670, 134, 'Madagascar'),
		(671, 202, 'Mauritius'),
		(672, 196, 'Laos'),
		(673, 158, 'Cambodia'),
		(674, 90, 'Philippines'),
		(675, 88, 'Papua New Guinea'),
		(676, 228, 'Solomon Islands'),
		(677, 240, 'Vanuatu'),
		(678, 176, 'Fiji'),
		(679, 223, 'Samoa'),
		(680, 206, 'Nauru'),
		(681, 168, 'Cote D&#039;Ivoire'),
		(682, 198, 'Liberia'),
		(683, 187, 'Guinea'),
		(684, 189, 'Guyana'),
		(685, 98, 'Algeria'),
		(686, 147, 'Antigua and Barbuda'),
		(687, 127, 'Bahrain'),
		(688, 149, 'Bangladesh'),
		(689, 128, 'Barbados'),
		(690, 152, 'Bhutan'),
		(691, 155, 'Brunei'),
		(692, 157, 'Burundi'),
		(693, 159, 'Cape Verde'),
		(694, 130, 'Chad'),
		(695, 164, 'Comoros'),
		(696, 112, 'Congo (Brazzaville)'),
		(697, 169, 'Djibouti'),
		(698, 171, 'East Timor'),
		(699, 173, 'Eritrea'),
		(700, 121, 'Ethiopia'),
		(701, 180, 'Gabon'),
		(702, 181, 'Gambia'),
		(703, 105, 'Ghana'),
		(704, 197, 'Lesotho'),
		(705, 125, 'Malawi'),
		(706, 200, 'Maldives'),
		(707, 205, 'Myanmar (Burma)'),
		(708, 107, 'Nepal'),
		(709, 213, 'Oman'),
		(710, 217, 'Rwanda'),
		(711, 91, 'Saudi Arabia'),
		(712, 120, 'Sri Lanka'),
		(713, 232, 'Sudan'),
		(714, 234, 'Swaziland'),
		(715, 101, 'Tanzania'),
		(716, 236, 'Tonga'),
		(717, 239, 'Tuvalu'),
		(718, 242, 'Western Sahara'),
		(719, 243, 'Yemen'),
		(720, 116, 'Zambia'),
		(721, 96, 'Zimbabwe'),
		(722, 66, 'Aargau'),
		(723, 66, 'Appenzell Innerrhoden'),
		(724, 66, 'Appenzell Ausserrhoden'),
		(725, 66, 'Bern'),
		(726, 66, 'Basel-Landschaft'),
		(727, 66, 'Basel-Stadt'),
		(728, 66, 'Fribourg'),
		(729, 66, 'Gen&egrave;ve'),
		(730, 66, 'Glarus'),
		(731, 66, 'Graubünden'),
		(732, 66, 'Jura'),
		(733, 66, 'Luzern'),
		(734, 66, 'Neuch&acirc;tel'),
		(735, 66, 'Nidwalden'),
		(736, 66, 'Obwalden'),
		(737, 66, 'Sankt Gallen'),
		(738, 66, 'Schaffhausen'),
		(739, 66, 'Solothurn'),
		(740, 66, 'Schwyz'),
		(741, 66, 'Thurgau'),
		(742, 66, 'Ticino'),
		(743, 66, 'Uri'),
		(744, 66, 'Vaud'),
		(745, 66, 'Valais'),
		(746, 66, 'Zug'),
		(747, 66, 'Zürich'),
		(749, 48, 'Aveiro'),
		(750, 48, 'Beja'),
		(751, 48, 'Braga'),
		(752, 48, 'Braganca'),
		(753, 48, 'Castelo Branco'),
		(754, 48, 'Coimbra'),
		(755, 48, 'Evora'),
		(756, 48, 'Faro'),
		(757, 48, 'Madeira'),
		(758, 48, 'Guarda'),
		(759, 48, 'Leiria'),
		(760, 48, 'Lisboa'),
		(761, 48, 'Portalegre'),
		(762, 48, 'Porto'),
		(763, 48, 'Santarem'),
		(764, 48, 'Setubal'),
		(765, 48, 'Viana do Castelo'),
		(766, 48, 'Vila Real'),
		(767, 48, 'Viseu'),
		(768, 48, 'Azores'),
		(769, 55, 'Armed Forces Americas'),
		(770, 55, 'Armed Forces Europe'),
		(771, 55, 'Alaska'),
		(772, 55, 'Alabama'),
		(773, 55, 'Armed Forces Pacific'),
		(774, 55, 'Arkansas'),
		(775, 55, 'American Samoa'),
		(776, 55, 'Arizona'),
		(777, 55, 'California'),
		(778, 55, 'Colorado'),
		(779, 55, 'Connecticut'),
		(780, 55, 'District of Columbia'),
		(781, 55, 'Delaware'),
		(782, 55, 'Florida'),
		(783, 55, 'Federated States of Micronesia'),
		(784, 55, 'Georgia'),
		(786, 55, 'Hawaii'),
		(787, 55, 'Iowa'),
		(788, 55, 'Idaho'),
		(789, 55, 'Illinois'),
		(790, 55, 'Indiana'),
		(791, 55, 'Kansas'),
		(792, 55, 'Kentucky'),
		(793, 55, 'Louisiana'),
		(794, 55, 'Massachusetts'),
		(795, 55, 'Maryland'),
		(796, 55, 'Maine'),
		(797, 55, 'Marshall Islands'),
		(798, 55, 'Michigan'),
		(799, 55, 'Minnesota'),
		(800, 55, 'Missouri'),
		(801, 55, 'Northern Mariana Islands'),
		(802, 55, 'Mississippi'),
		(803, 55, 'Montana'),
		(804, 55, 'North Carolina'),
		(805, 55, 'North Dakota'),
		(806, 55, 'Nebraska'),
		(807, 55, 'New Hampshire'),
		(808, 55, 'New Jersey'),
		(809, 55, 'New Mexico'),
		(810, 55, 'Nevada'),
		(811, 55, 'New York'),
		(812, 55, 'Ohio'),
		(813, 55, 'Oklahoma'),
		(814, 55, 'Oregon'),
		(815, 55, 'Pennsylvania'),
		(816, 246, 'Puerto Rico'),
		(817, 55, 'Palau'),
		(818, 55, 'Rhode Island'),
		(819, 55, 'South Carolina'),
		(820, 55, 'South Dakota'),
		(821, 55, 'Tennessee'),
		(822, 55, 'Texas'),
		(823, 55, 'Utah'),
		(824, 55, 'Virginia'),
		(825, 55, 'Virgin Islands'),
		(826, 55, 'Vermont'),
		(827, 55, 'Washington'),
		(828, 55, 'West Virginia'),
		(829, 55, 'Wisconsin'),
		(830, 55, 'Wyoming'),
		(831, 94, 'Greenland'),
		(832, 18, 'Brandenburg'),
		(833, 18, 'Baden-Württemberg'),
		(834, 18, 'Bayern'),
		(835, 18, 'Hessen'),
		(836, 18, 'Hamburg'),
		(837, 18, 'Mecklenburg-Vorpommern'),
		(838, 18, 'Niedersachsen'),
		(839, 18, 'Nordrhein-Westfalen'),
		(840, 18, 'Rheinland-Pfalz'),
		(841, 18, 'Schleswig-Holstein'),
		(842, 18, 'Sachsen'),
		(843, 18, 'Sachsen-Anhalt'),
		(844, 18, 'Thüringen'),
		(845, 18, 'Berlin'),
		(846, 18, 'Bremen'),
		(847, 18, 'Saarland'),
		(848, 13, 'Scotland North'),
		(849, 13, 'England - East'),
		(850, 13, 'England - West Midlands'),
		(851, 13, 'England - South West'),
		(852, 13, 'England - North West'),
		(853, 13, 'England - Yorks &amp; Humber'),
		(854, 13, 'England - South East'),
		(855, 13, 'England - London'),
		(856, 13, 'Northern Ireland'),
		(857, 13, 'England - North East'),
		(858, 13, 'Wales South'),
		(859, 13, 'Wales North'),
		(860, 13, 'England - East Midlands'),
		(861, 13, 'Scotland Central'),
		(862, 13, 'Scotland South'),
		(863, 13, 'Channel Islands'),
		(864, 13, 'Isle of Man'),
		(865, 2, 'Burgenland'),
		(866, 2, 'Kärnten'),
		(867, 2, 'Niederösterreich'),
		(868, 2, 'Oberösterreich'),
		(869, 2, 'Salzburg'),
		(870, 2, 'Steiermark'),
		(871, 2, 'Tirol'),
		(872, 2, 'Vorarlberg'),
		(873, 2, 'Wien'),
		(874, 9, 'Bruxelles'),
		(875, 9, 'West-Vlaanderen'),
		(876, 9, 'Oost-Vlaanderen'),
		(877, 9, 'Limburg'),
		(878, 9, 'Vlaams Brabant'),
		(879, 9, 'Antwerpen'),
		(880, 9, 'LiÄge'),
		(881, 9, 'Namur'),
		(882, 9, 'Hainaut'),
		(883, 9, 'Luxembourg'),
		(884, 9, 'Brabant Wallon'),
		(887, 67, 'Blekinge Lan'),
		(888, 67, 'Gavleborgs Lan'),
		(890, 67, 'Gotlands Lan'),
		(891, 67, 'Hallands Lan'),
		(892, 67, 'Jamtlands Lan'),
		(893, 67, 'Jonkopings Lan'),
		(894, 67, 'Kalmar Lan'),
		(895, 67, 'Dalarnas Lan'),
		(897, 67, 'Kronobergs Lan'),
		(899, 67, 'Norrbottens Lan'),
		(900, 67, 'Orebro Lan'),
		(901, 67, 'Ostergotlands Lan'),
		(903, 67, 'Sodermanlands Lan'),
		(904, 67, 'Uppsala Lan'),
		(905, 67, 'Varmlands Lan'),
		(906, 67, 'Vasterbottens Lan'),
		(907, 67, 'Vasternorrlands Lan'),
		(908, 67, 'Vastmanlands Lan'),
		(909, 67, 'Stockholms Lan'),
		(910, 67, 'Skane Lan'),
		(911, 67, 'Vastra Gotaland'),
		(913, 46, 'Akershus'),
		(914, 46, 'Aust-Agder'),
		(915, 46, 'Buskerud'),
		(916, 46, 'Finnmark'),
		(917, 46, 'Hedmark'),
		(918, 46, 'Hordaland'),
		(919, 46, 'More og Romsdal'),
		(920, 46, 'Nordland'),
		(921, 46, 'Nord-Trondelag'),
		(922, 46, 'Oppland'),
		(923, 46, 'Oslo'),
		(924, 46, 'Ostfold'),
		(925, 46, 'Rogaland'),
		(926, 46, 'Sogn og Fjordane'),
		(927, 46, 'Sor-Trondelag'),
		(928, 46, 'Telemark'),
		(929, 46, 'Troms'),
		(930, 46, 'Vest-Agder'),
		(931, 46, 'Vestfold'),
		(933, 63, '&ETH;&bull;land'),
		(934, 63, 'Lapland'),
		(935, 63, 'Oulu'),
		(936, 63, 'Southern Finland'),
		(937, 63, 'Eastern Finland'),
		(938, 63, 'Western Finland'),
		(940, 22, 'Arhus'),
		(941, 22, 'Bornholm'),
		(942, 22, 'Frederiksborg'),
		(943, 22, 'Fyn'),
		(944, 22, 'Kobenhavn'),
		(945, 22, 'Staden Kobenhavn'),
		(946, 22, 'Nordjylland'),
		(947, 22, 'Ribe'),
		(948, 22, 'Ringkobing'),
		(949, 22, 'Roskilde'),
		(950, 22, 'Sonderjylland'),
		(951, 22, 'Storstrom'),
		(952, 22, 'Vejle'),
		(953, 22, 'Vestsjalland'),
		(954, 22, 'Viborg'),
		(956, 65, 'Hlavni Mesto Praha'),
		(957, 65, 'Jihomoravsky Kraj'),
		(958, 65, 'Jihocesky Kraj'),
		(959, 65, 'Vysocina'),
		(960, 65, 'Karlovarsky Kraj'),
		(961, 65, 'Kralovehradecky Kraj'),
		(962, 65, 'Liberecky Kraj'),
		(963, 65, 'Olomoucky Kraj'),
		(964, 65, 'Moravskoslezsky Kraj'),
		(965, 65, 'Pardubicky Kraj'),
		(966, 65, 'Plzensky Kraj'),
		(967, 65, 'Stredocesky Kraj'),
		(968, 65, 'Ustecky Kraj'),
		(969, 65, 'Zlinsky Kraj'),
		(971, 114, 'Berat'),
		(972, 114, 'Diber'),
		(973, 114, 'Durres'),
		(974, 114, 'Elbasan'),
		(975, 114, 'Fier'),
		(976, 114, 'Gjirokaster'),
		(977, 114, 'Korce'),
		(978, 114, 'Kukes'),
		(979, 114, 'Lezhe'),
		(980, 114, 'Shkoder'),
		(981, 114, 'Tirane'),
		(982, 114, 'Vlore'),
		(984, 145, 'Canillo'),
		(985, 145, 'Encamp'),
		(986, 145, 'La Massana'),
		(987, 145, 'Ordino'),
		(988, 145, 'Sant Julia de Loria'),
		(989, 145, 'Andorra la Vella'),
		(990, 145, 'Escaldes-Engordany'),
		(992, 6, 'Aragatsotn'),
		(993, 6, 'Ararat'),
		(994, 6, 'Armavir'),
		(995, 6, 'Geghark&#039;unik&#039;'),
		(996, 6, 'Kotayk&#039;'),
		(997, 6, 'Lorri'),
		(998, 6, 'Shirak'),
		(999, 6, 'Syunik&#039;'),
		(1000, 6, 'Tavush'),
		(1001, 6, 'Vayots&#039; Dzor'),
		(1002, 6, 'Yerevan'),
		(1004, 79, 'Federation of Bosnia and Herzegovina'),
		(1005, 79, 'Republika Srpska'),
		(1007, 11, 'Mikhaylovgrad'),
		(1008, 11, 'Blagoevgrad'),
		(1009, 11, 'Burgas'),
		(1010, 11, 'Dobrich'),
		(1011, 11, 'Gabrovo'),
		(1012, 11, 'Grad Sofiya'),
		(1013, 11, 'Khaskovo'),
		(1014, 11, 'Kurdzhali'),
		(1015, 11, 'Kyustendil'),
		(1016, 11, 'Lovech'),
		(1017, 11, 'Montana'),
		(1018, 11, 'Pazardzhik'),
		(1019, 11, 'Pernik'),
		(1020, 11, 'Pleven'),
		(1021, 11, 'Plovdiv'),
		(1022, 11, 'Razgrad'),
		(1023, 11, 'Ruse'),
		(1024, 11, 'Shumen'),
		(1025, 11, 'Silistra'),
		(1026, 11, 'Sliven'),
		(1027, 11, 'Smolyan'),
		(1028, 11, 'Sofiya'),
		(1029, 11, 'Stara Zagora'),
		(1030, 11, 'Turgovishte'),
		(1031, 11, 'Varna'),
		(1032, 11, 'Veliko Turnovo'),
		(1033, 11, 'Vidin'),
		(1034, 11, 'Vratsa'),
		(1035, 11, 'Yambol'),
		(1037, 71, 'Bjelovarsko-Bilogorska'),
		(1038, 71, 'Brodsko-Posavska'),
		(1039, 71, 'Dubrovacko-Neretvanska'),
		(1040, 71, 'Istarska'),
		(1041, 71, 'Karlovacka'),
		(1042, 71, 'Koprivnicko-Krizevacka'),
		(1043, 71, 'Krapinsko-Zagorska'),
		(1044, 71, 'Licko-Senjska'),
		(1045, 71, 'Medimurska'),
		(1046, 71, 'Osjecko-Baranjska'),
		(1047, 71, 'Pozesko-Slavonska'),
		(1048, 71, 'Primorsko-Goranska'),
		(1049, 71, 'Sibensko-Kninska'),
		(1050, 71, 'Sisacko-Moslavacka'),
		(1051, 71, 'Splitsko-Dalmatinska'),
		(1052, 71, 'Varazdinska'),
		(1053, 71, 'Viroviticko-Podravska'),
		(1054, 71, 'Vukovarsko-Srijemska'),
		(1055, 71, 'Zadarska'),
		(1056, 71, 'Zagrebacka'),
		(1057, 71, 'Grad Zagreb'),
		(1059, 143, 'Gibraltar'),
		(1060, 20, 'Evros'),
		(1061, 20, 'Rodhopi'),
		(1062, 20, 'Xanthi'),
		(1063, 20, 'Drama'),
		(1064, 20, 'Serrai'),
		(1065, 20, 'Kilkis'),
		(1066, 20, 'Pella'),
		(1067, 20, 'Florina'),
		(1068, 20, 'Kastoria'),
		(1069, 20, 'Grevena'),
		(1070, 20, 'Kozani'),
		(1071, 20, 'Imathia'),
		(1072, 20, 'Thessaloniki'),
		(1073, 20, 'Kavala'),
		(1074, 20, 'Khalkidhiki'),
		(1075, 20, 'Pieria'),
		(1076, 20, 'Ioannina'),
		(1077, 20, 'Thesprotia'),
		(1078, 20, 'Preveza'),
		(1079, 20, 'Arta'),
		(1080, 20, 'Larisa'),
		(1081, 20, 'Trikala'),
		(1082, 20, 'Kardhitsa'),
		(1083, 20, 'Magnisia'),
		(1084, 20, 'Kerkira'),
		(1085, 20, 'Levkas'),
		(1086, 20, 'Kefallinia'),
		(1087, 20, 'Zakinthos'),
		(1088, 20, 'Fthiotis'),
		(1089, 20, 'Evritania'),
		(1090, 20, 'Aitolia kai Akarnania'),
		(1091, 20, 'Fokis'),
		(1092, 20, 'Voiotia'),
		(1093, 20, 'Evvoia'),
		(1094, 20, 'Attiki'),
		(1095, 20, 'Argolis'),
		(1096, 20, 'Korinthia'),
		(1097, 20, 'Akhaia'),
		(1098, 20, 'Ilia'),
		(1099, 20, 'Messinia'),
		(1100, 20, 'Arkadhia'),
		(1101, 20, 'Lakonia'),
		(1102, 20, 'Khania'),
		(1103, 20, 'Rethimni'),
		(1104, 20, 'Iraklion'),
		(1105, 20, 'Lasithi'),
		(1106, 20, 'Dhodhekanisos'),
		(1107, 20, 'Samos'),
		(1108, 20, 'Kikladhes'),
		(1109, 20, 'Khios'),
		(1110, 20, 'Lesvos'),
		(1112, 14, 'Bacs-Kiskun'),
		(1113, 14, 'Baranya'),
		(1114, 14, 'Bekes'),
		(1115, 14, 'Borsod-Abauj-Zemplen'),
		(1116, 14, 'Budapest'),
		(1117, 14, 'Csongrad'),
		(1118, 14, 'Debrecen'),
		(1119, 14, 'Fejer'),
		(1120, 14, 'Gyor-Moson-Sopron'),
		(1121, 14, 'Hajdu-Bihar'),
		(1122, 14, 'Heves'),
		(1123, 14, 'Komarom-Esztergom'),
		(1124, 14, 'Miskolc'),
		(1125, 14, 'Nograd'),
		(1126, 14, 'Pecs'),
		(1127, 14, 'Pest'),
		(1128, 14, 'Somogy'),
		(1129, 14, 'Szabolcs-Szatmar-Bereg'),
		(1130, 14, 'Szeged'),
		(1131, 14, 'Jasz-Nagykun-Szolnok'),
		(1132, 14, 'Tolna'),
		(1133, 14, 'Vas'),
		(1134, 14, 'Veszprem'),
		(1135, 14, 'Zala'),
		(1136, 14, 'Gyor'),
		(1150, 14, 'Veszprem'),
		(1152, 126, 'Balzers'),
		(1153, 126, 'Eschen'),
		(1154, 126, 'Gamprin'),
		(1155, 126, 'Mauren'),
		(1156, 126, 'Planken'),
		(1157, 126, 'Ruggell'),
		(1158, 126, 'Schaan'),
		(1159, 126, 'Schellenberg'),
		(1160, 126, 'Triesen'),
		(1161, 126, 'Triesenberg'),
		(1162, 126, 'Vaduz'),
		(1163, 41, 'Diekirch'),
		(1164, 41, 'Grevenmacher'),
		(1165, 41, 'Luxembourg'),
		(1167, 85, 'Aracinovo'),
		(1168, 85, 'Bac'),
		(1169, 85, 'Belcista'),
		(1170, 85, 'Berovo'),
		(1171, 85, 'Bistrica'),
		(1172, 85, 'Bitola'),
		(1173, 85, 'Blatec'),
		(1174, 85, 'Bogdanci'),
		(1175, 85, 'Bogomila'),
		(1176, 85, 'Bogovinje'),
		(1177, 85, 'Bosilovo'),
		(1179, 85, 'Cair'),
		(1180, 85, 'Capari'),
		(1181, 85, 'Caska'),
		(1182, 85, 'Cegrane'),
		(1184, 85, 'Centar Zupa'),
		(1187, 85, 'Debar'),
		(1188, 85, 'Delcevo'),
		(1190, 85, 'Demir Hisar'),
		(1191, 85, 'Demir Kapija'),
		(1195, 85, 'Dorce Petrov'),
		(1198, 85, 'Gazi Baba'),
		(1199, 85, 'Gevgelija'),
		(1200, 85, 'Gostivar'),
		(1201, 85, 'Gradsko'),
		(1204, 85, 'Jegunovce'),
		(1205, 85, 'Kamenjane'),
		(1207, 85, 'Karpos'),
		(1208, 85, 'Kavadarci'),
		(1209, 85, 'Kicevo'),
		(1210, 85, 'Kisela Voda'),
		(1211, 85, 'Klecevce'),
		(1212, 85, 'Kocani'),
		(1214, 85, 'Kondovo'),
		(1217, 85, 'Kratovo'),
		(1219, 85, 'Krivogastani'),
		(1220, 85, 'Krusevo'),
		(1223, 85, 'Kumanovo'),
		(1224, 85, 'Labunista'),
		(1225, 85, 'Lipkovo'),
		(1228, 85, 'Makedonska Kamenica'),
		(1229, 85, 'Makedonski Brod'),
		(1234, 85, 'Murtino'),
		(1235, 85, 'Negotino'),
		(1238, 85, 'Novo Selo'),
		(1240, 85, 'Ohrid'),
		(1242, 85, 'Orizari'),
		(1245, 85, 'Petrovec'),
		(1248, 85, 'Prilep'),
		(1249, 85, 'Probistip'),
		(1250, 85, 'Radovis'),
		(1252, 85, 'Resen'),
		(1253, 85, 'Rosoman'),
		(1256, 85, 'Saraj'),
		(1260, 85, 'Srbinovo'),
		(1262, 85, 'Star Dojran'),
		(1264, 85, 'Stip'),
		(1265, 85, 'Struga'),
		(1266, 85, 'Strumica'),
		(1267, 85, 'Studenicani'),
		(1268, 85, 'Suto Orizari'),
		(1269, 85, 'Sveti Nikole'),
		(1270, 85, 'Tearce'),
		(1271, 85, 'Tetovo'),
		(1273, 85, 'Valandovo'),
		(1275, 85, 'Veles'),
		(1277, 85, 'Vevcani'),
		(1278, 85, 'Vinica'),
		(1281, 85, 'Vrapciste'),
		(1286, 85, 'Zelino'),
		(1289, 85, 'Zrnovci'),
		(1291, 86, 'Malta'),
		(1292, 44, 'La Condamine'),
		(1293, 44, 'Monaco'),
		(1294, 44, 'Monte-Carlo'),
		(1295, 47, 'Biala Podlaska'),
		(1296, 47, 'Bialystok'),
		(1297, 47, 'Bielsko'),
		(1298, 47, 'Bydgoszcz'),
		(1299, 47, 'Chelm'),
		(1300, 47, 'Ciechanow'),
		(1301, 47, 'Czestochowa'),
		(1302, 47, 'Elblag'),
		(1303, 47, 'Gdansk'),
		(1304, 47, 'Gorzow'),
		(1305, 47, 'Jelenia Gora'),
		(1306, 47, 'Kalisz'),
		(1307, 47, 'Katowice'),
		(1308, 47, 'Kielce'),
		(1309, 47, 'Konin'),
		(1310, 47, 'Koszalin'),
		(1311, 47, 'Krakow'),
		(1312, 47, 'Krosno'),
		(1313, 47, 'Legnica'),
		(1314, 47, 'Leszno'),
		(1315, 47, 'Lodz'),
		(1316, 47, 'Lomza'),
		(1317, 47, 'Lublin'),
		(1318, 47, 'Nowy Sacz'),
		(1319, 47, 'Olsztyn'),
		(1320, 47, 'Opole'),
		(1321, 47, 'Ostroleka'),
		(1322, 47, 'Pila'),
		(1323, 47, 'Piotrkow'),
		(1324, 47, 'Plock'),
		(1325, 47, 'Poznan'),
		(1326, 47, 'Przemysl'),
		(1327, 47, 'Radom'),
		(1328, 47, 'Rzeszow'),
		(1329, 47, 'Siedlce'),
		(1330, 47, 'Sieradz'),
		(1331, 47, 'Skierniewice'),
		(1332, 47, 'Slupsk'),
		(1333, 47, 'Suwalki'),
		(1335, 47, 'Tarnobrzeg'),
		(1336, 47, 'Tarnow'),
		(1337, 47, 'Torun'),
		(1338, 47, 'Walbrzych'),
		(1339, 47, 'Warszawa'),
		(1340, 47, 'Wloclawek'),
		(1341, 47, 'Wroclaw'),
		(1342, 47, 'Zamosc'),
		(1343, 47, 'Zielona Gora'),
		(1344, 47, 'Dolnoslaskie'),
		(1345, 47, 'Kujawsko-Pomorskie'),
		(1346, 47, 'Lodzkie'),
		(1347, 47, 'Lubelskie'),
		(1348, 47, 'Lubuskie'),
		(1349, 47, 'Malopolskie'),
		(1350, 47, 'Mazowieckie'),
		(1351, 47, 'Opolskie'),
		(1352, 47, 'Podkarpackie'),
		(1353, 47, 'Podlaskie'),
		(1354, 47, 'Pomorskie'),
		(1355, 47, 'Slaskie'),
		(1356, 47, 'Swietokrzyskie'),
		(1357, 47, 'Warminsko-Mazurskie'),
		(1358, 47, 'Wielkopolskie'),
		(1359, 47, 'Zachodniopomorskie'),
		(1361, 72, 'Alba'),
		(1362, 72, 'Arad'),
		(1363, 72, 'Arges'),
		(1364, 72, 'Bacau'),
		(1365, 72, 'Bihor'),
		(1366, 72, 'Bistrita-Nasaud'),
		(1367, 72, 'Botosani'),
		(1368, 72, 'Braila'),
		(1369, 72, 'Brasov'),
		(1370, 72, 'Bucuresti'),
		(1371, 72, 'Buzau'),
		(1372, 72, 'Caras-Severin'),
		(1373, 72, 'Cluj'),
		(1374, 72, 'Constanta'),
		(1375, 72, 'Covasna'),
		(1376, 72, 'Dambovita'),
		(1377, 72, 'Dolj'),
		(1378, 72, 'Galati'),
		(1379, 72, 'Gorj'),
		(1380, 72, 'Harghita'),
		(1381, 72, 'Hunedoara'),
		(1382, 72, 'Ialomita'),
		(1383, 72, 'Iasi'),
		(1384, 72, 'Maramures'),
		(1385, 72, 'Mehedinti'),
		(1386, 72, 'Mures'),
		(1387, 72, 'Neamt'),
		(1388, 72, 'Olt'),
		(1389, 72, 'Prahova'),
		(1390, 72, 'Salaj'),
		(1391, 72, 'Satu Mare'),
		(1392, 72, 'Sibiu'),
		(1393, 72, 'Suceava'),
		(1394, 72, 'Teleorman'),
		(1395, 72, 'Timis'),
		(1396, 72, 'Tulcea'),
		(1397, 72, 'Vaslui'),
		(1398, 72, 'Valcea'),
		(1399, 72, 'Vrancea'),
		(1400, 72, 'Calarasi'),
		(1401, 72, 'Giurgiu'),
		(1404, 224, 'Acquaviva'),
		(1405, 224, 'Chiesanuova'),
		(1406, 224, 'Domagnano'),
		(1407, 224, 'Faetano'),
		(1408, 224, 'Fiorentino'),
		(1409, 224, 'Borgo Maggiore'),
		(1410, 224, 'San Marino'),
		(1411, 224, 'Monte Giardino'),
		(1412, 224, 'Serravalle'),
		(1413, 52, 'Banska Bystrica'),
		(1414, 52, 'Bratislava'),
		(1415, 52, 'Kosice'),
		(1416, 52, 'Nitra'),
		(1417, 52, 'Presov'),
		(1418, 52, 'Trencin'),
		(1419, 52, 'Trnava'),
		(1420, 52, 'Zilina'),
		(1423, 53, 'Beltinci'),
		(1425, 53, 'Bohinj'),
		(1426, 53, 'Borovnica'),
		(1427, 53, 'Bovec'),
		(1428, 53, 'Brda'),
		(1429, 53, 'Brezice'),
		(1430, 53, 'Brezovica'),
		(1432, 53, 'Cerklje na Gorenjskem'),
		(1434, 53, 'Cerkno'),
		(1436, 53, 'Crna na Koroskem'),
		(1437, 53, 'Crnomelj'),
		(1438, 53, 'Divaca'),
		(1439, 53, 'Dobrepolje'),
		(1440, 53, 'Dol pri Ljubljani'),
		(1443, 53, 'Duplek'),
		(1447, 53, 'Gornji Grad'),
		(1450, 53, 'Hrastnik'),
		(1451, 53, 'Hrpelje-Kozina'),
		(1452, 53, 'Idrija'),
		(1453, 53, 'Ig'),
		(1454, 53, 'Ilirska Bistrica'),
		(1455, 53, 'Ivancna Gorica'),
		(1462, 53, 'Komen'),
		(1463, 53, 'Koper-Capodistria'),
		(1464, 53, 'Kozje'),
		(1465, 53, 'Kranj'),
		(1466, 53, 'Kranjska Gora'),
		(1467, 53, 'Krsko'),
		(1469, 53, 'Lasko'),
		(1470, 53, 'Ljubljana'),
		(1471, 53, 'Ljubno'),
		(1472, 53, 'Logatec'),
		(1475, 53, 'Medvode'),
		(1476, 53, 'Menges'),
		(1478, 53, 'Mezica'),
		(1480, 53, 'Moravce'),
		(1482, 53, 'Mozirje'),
		(1483, 53, 'Murska Sobota'),
		(1487, 53, 'Nova Gorica'),
		(1489, 53, 'Ormoz'),
		(1491, 53, 'Pesnica'),
		(1494, 53, 'Postojna'),
		(1497, 53, 'Radece'),
		(1498, 53, 'Radenci'),
		(1500, 53, 'Radovljica'),
		(1502, 53, 'Rogaska Slatina'),
		(1505, 53, 'Sencur'),
		(1506, 53, 'Sentilj'),
		(1508, 53, 'Sevnica'),
		(1509, 53, 'Sezana'),
		(1511, 53, 'Skofja Loka'),
		(1513, 53, 'Slovenj Gradec'),
		(1514, 53, 'Slovenske Konjice'),
		(1515, 53, 'Smarje pri Jelsah'),
		(1521, 53, 'Tolmin'),
		(1522, 53, 'Trbovlje'),
		(1524, 53, 'Trzic'),
		(1526, 53, 'Velenje'),
		(1528, 53, 'Vipava'),
		(1531, 53, 'Vrhnika'),
		(1532, 53, 'Vuzenica'),
		(1533, 53, 'Zagorje ob Savi'),
		(1535, 53, 'Zelezniki'),
		(1536, 53, 'Ziri'),
		(1537, 53, 'Zrece'),
		(1539, 53, 'Domzale'),
		(1540, 53, 'Jesenice'),
		(1541, 53, 'Kamnik'),
		(1542, 53, 'Kocevje'),
		(1544, 53, 'Lenart'),
		(1545, 53, 'Litija'),
		(1546, 53, 'Ljutomer'),
		(1550, 53, 'Maribor'),
		(1552, 53, 'Novo Mesto'),
		(1553, 53, 'Piran'),
		(1554, 53, 'Preddvor'),
		(1555, 53, 'Ptuj'),
		(1556, 53, 'Ribnica'),
		(1558, 53, 'Sentjur pri Celju'),
		(1559, 53, 'Slovenska Bistrica'),
		(1560, 53, 'Videm'),
		(1562, 53, 'Zalec'),
		(1564, 109, 'Seychelles'),
		(1565, 108, 'Mauritania'),
		(1566, 135, 'Senegal'),
		(1567, 154, 'Road Town'),
		(1568, 165, 'Congo'),
		(1569, 166, 'Avarua'),
		(1570, 172, 'Malabo'),
		(1571, 175, 'Torshavn'),
		(1572, 178, 'Papeete'),
		(1573, 184, 'St George&#039;s'),
		(1574, 186, 'St Peter Port'),
		(1575, 188, 'Bissau'),
		(1576, 193, 'Saint Helier'),
		(1577, 201, 'Fort-de-France'),
		(1578, 207, 'Willemstad'),
		(1579, 208, 'Noumea'),
		(1580, 212, 'Kingston'),
		(1581, 215, 'Adamstown'),
		(1582, 216, 'Doha'),
		(1583, 218, 'Jamestown'),
		(1584, 219, 'Basseterre'),
		(1585, 220, 'Castries'),
		(1586, 221, 'Saint Pierre'),
		(1587, 222, 'Kingstown'),
		(1588, 225, 'San Tome'),
		(1589, 226, 'Belgrade'),
		(1590, 227, 'Freetown'),
		(1591, 229, 'Mogadishu'),
		(1592, 235, 'Fakaofo'),
		(1593, 237, 'Port of Spain'),
		(1594, 241, 'Mata-Utu'),
		(1596, 89, 'Amazonas'),
		(1597, 89, 'Ancash'),
		(1598, 89, 'Apurímac'),
		(1599, 89, 'Arequipa'),
		(1600, 89, 'Ayacucho'),
		(1601, 89, 'Cajamarca'),
		(1602, 89, 'Callao'),
		(1603, 89, 'Cusco'),
		(1604, 89, 'Huancavelica'),
		(1605, 89, 'Huánuco'),
		(1606, 89, 'Ica'),
		(1607, 89, 'Junín'),
		(1608, 89, 'La Libertad'),
		(1609, 89, 'Lambayeque'),
		(1610, 89, 'Lima'),
		(1611, 89, 'Loreto'),
		(1612, 89, 'Madre de Dios'),
		(1613, 89, 'Moquegua'),
		(1614, 89, 'Pasco'),
		(1615, 89, 'Piura'),
		(1616, 89, 'Puno'),
		(1617, 89, 'San Martín'),
		(1618, 89, 'Tacna'),
		(1619, 89, 'Tumbes'),
		(1620, 89, 'Ucayali'),
		(1622, 110, 'Alto Paraná'),
		(1623, 110, 'Amambay'),
		(1624, 110, 'Boquerón'),
		(1625, 110, 'Caaguaz&uacute;'),
		(1626, 110, 'Caazapá'),
		(1627, 110, 'Central'),
		(1628, 110, 'Concepción'),
		(1629, 110, 'Cordillera'),
		(1630, 110, 'Guairá'),
		(1631, 110, 'Itap&uacute;a'),
		(1632, 110, 'Misiones'),
		(1633, 110, 'Neembuc&uacute;'),
		(1634, 110, 'Paraguarí'),
		(1635, 110, 'Presidente Hayes'),
		(1636, 110, 'San Pedro'),
		(1637, 110, 'Alto Paraguay'),
		(1638, 110, 'Canindey&uacute;'),
		(1639, 110, 'Chaco'),
		(1642, 111, 'Artigas'),
		(1643, 111, 'Canelones'),
		(1644, 111, 'Cerro Largo'),
		(1645, 111, 'Colonia'),
		(1646, 111, 'Durazno'),
		(1647, 111, 'Flores'),
		(1648, 111, 'Florida'),
		(1649, 111, 'Lavalleja'),
		(1650, 111, 'Maldonado'),
		(1651, 111, 'Montevideo'),
		(1652, 111, 'Paysand&uacute;'),
		(1653, 111, 'Río Negro'),
		(1654, 111, 'Rivera'),
		(1655, 111, 'Rocha'),
		(1656, 111, 'Salto'),
		(1657, 111, 'San José'),
		(1658, 111, 'Soriano'),
		(1659, 111, 'Tacuarembó'),
		(1660, 111, 'Treinta y Tres'),
		(1662, 81, 'Región de Tarapacá'),
		(1663, 81, 'Región de Antofagasta'),
		(1664, 81, 'Región de Atacama'),
		(1665, 81, 'Región de Coquimbo'),
		(1666, 81, 'Región de Valparaíso'),
		(1667, 81, 'Región del Libertador General Bernardo O&#039;Higgins'),
		(1668, 81, 'Región del Maule'),
		(1669, 81, 'Región del Bío Bío'),
		(1670, 81, 'Región de La Araucanía'),
		(1671, 81, 'Región de Los Lagos'),
		(1672, 81, 'Región Aisén del General Carlos Ibáñez del Campo'),
		(1673, 81, 'Región de Magallanes y de la Antártica Chilena'),
		(1674, 81, 'Región Metropolitana de Santiago'),
		(1676, 185, 'Alta Verapaz'),
		(1677, 185, 'Baja Verapaz'),
		(1678, 185, 'Chimaltenango'),
		(1679, 185, 'Chiquimula'),
		(1680, 185, 'El Progreso'),
		(1681, 185, 'Escuintla'),
		(1682, 185, 'Guatemala'),
		(1683, 185, 'Huehuetenango'),
		(1684, 185, 'Izabal'),
		(1685, 185, 'Jalapa'),
		(1686, 185, 'Jutiapa'),
		(1687, 185, 'Petén'),
		(1688, 185, 'Quetzaltenango'),
		(1689, 185, 'Quiché'),
		(1690, 185, 'Retalhuleu'),
		(1691, 185, 'Sacatepéquez'),
		(1692, 185, 'San Marcos'),
		(1693, 185, 'Santa Rosa'),
		(1694, 185, 'Sololá'),
		(1695, 185, 'Suchitepequez'),
		(1696, 185, 'Totonicapán'),
		(1697, 185, 'Zacapa'),
		(1699, 82, 'Amazonas'),
		(1700, 82, 'Antioquia'),
		(1701, 82, 'Arauca'),
		(1702, 82, 'Atlántico'),
		(1703, 82, 'Caquetá'),
		(1704, 82, 'Cauca'),
		(1705, 82, 'César'),
		(1706, 82, 'Chocó'),
		(1707, 82, 'Córdoba'),
		(1708, 82, 'Guaviare'),
		(1709, 82, 'Guainía'),
		(1710, 82, 'Huila'),
		(1711, 82, 'La Guajira'),
		(1712, 82, 'Meta'),
		(1713, 82, 'Narino'),
		(1714, 82, 'Norte de Santander'),
		(1715, 82, 'Putumayo'),
		(1716, 82, 'Quindío'),
		(1717, 82, 'Risaralda'),
		(1718, 82, 'San Andrés y Providencia'),
		(1719, 82, 'Santander'),
		(1720, 82, 'Sucre'),
		(1721, 82, 'Tolima'),
		(1722, 82, 'Valle del Cauca'),
		(1723, 82, 'Vaupés'),
		(1724, 82, 'Vichada'),
		(1725, 82, 'Casanare'),
		(1726, 82, 'Cundinamarca'),
		(1727, 82, 'Distrito Especial'),
		(1730, 82, 'Caldas'),
		(1731, 82, 'Magdalena'),
		(1733, 42, 'Aguascalientes'),
		(1734, 42, 'Baja California'),
		(1735, 42, 'Baja California Sur'),
		(1736, 42, 'Campeche'),
		(1737, 42, 'Chiapas'),
		(1738, 42, 'Chihuahua'),
		(1739, 42, 'Coahuila de Zaragoza'),
		(1740, 42, 'Colima'),
		(1741, 42, 'Distrito Federal'),
		(1742, 42, 'Durango'),
		(1743, 42, 'Guanajuato'),
		(1744, 42, 'Guerrero'),
		(1745, 42, 'Hidalgo'),
		(1746, 42, 'Jalisco'),
		(1747, 42, 'México'),
		(1748, 42, 'Michoacán de Ocampo'),
		(1749, 42, 'Morelos'),
		(1750, 42, 'Nayarit'),
		(1751, 42, 'Nuevo León'),
		(1752, 42, 'Oaxaca'),
		(1753, 42, 'Puebla'),
		(1754, 42, 'Querétaro de Arteaga'),
		(1755, 42, 'Quintana Roo'),
		(1756, 42, 'San Luis Potosí'),
		(1757, 42, 'Sinaloa'),
		(1758, 42, 'Sonora'),
		(1759, 42, 'Tabasco'),
		(1760, 42, 'Tamaulipas'),
		(1761, 42, 'Tlaxcala'),
		(1762, 42, 'Veracruz-Llave'),
		(1763, 42, 'Yucatán'),
		(1764, 42, 'Zacatecas'),
		(1766, 124, 'Bocas del Toro'),
		(1767, 124, 'Chiriquí'),
		(1768, 124, 'Coclé'),
		(1769, 124, 'Colón'),
		(1770, 124, 'Darién'),
		(1771, 124, 'Herrera'),
		(1772, 124, 'Los Santos'),
		(1773, 124, 'Panamá'),
		(1774, 124, 'San Blas'),
		(1775, 124, 'Veraguas'),
		(1777, 123, 'Chuquisaca'),
		(1778, 123, 'Cochabamba'),
		(1779, 123, 'El Beni'),
		(1780, 123, 'La Paz'),
		(1781, 123, 'Oruro'),
		(1782, 123, 'Pando'),
		(1783, 123, 'Potosí'),
		(1784, 123, 'Santa Cruz'),
		(1785, 123, 'Tarija'),
		(1787, 36, 'Alajuela'),
		(1788, 36, 'Cartago'),
		(1789, 36, 'Guanacaste'),
		(1790, 36, 'Heredia'),
		(1791, 36, 'Limón'),
		(1792, 36, 'Puntarenas'),
		(1793, 36, 'San José'),
		(1795, 103, 'Galápagos'),
		(1796, 103, 'Azuay'),
		(1797, 103, 'Bolívar'),
		(1798, 103, 'Canar'),
		(1799, 103, 'Carchi'),
		(1800, 103, 'Chimborazo'),
		(1801, 103, 'Cotopaxi'),
		(1802, 103, 'El Oro'),
		(1803, 103, 'Esmeraldas'),
		(1804, 103, 'Guayas'),
		(1805, 103, 'Imbabura'),
		(1806, 103, 'Loja'),
		(1807, 103, 'Los Ríos'),
		(1808, 103, 'Manabí'),
		(1809, 103, 'Morona-Santiago'),
		(1810, 103, 'Pastaza'),
		(1811, 103, 'Pichincha'),
		(1812, 103, 'Tungurahua'),
		(1813, 103, 'Zamora-Chinchipe'),
		(1814, 103, 'Sucumbíos'),
		(1815, 103, 'Napo'),
		(1816, 103, 'Orellana'),
		(1818, 5, 'Buenos Aires'),
		(1819, 5, 'Catamarca'),
		(1820, 5, 'Chaco'),
		(1821, 5, 'Chubut'),
		(1822, 5, 'Córdoba'),
		(1823, 5, 'Corrientes'),
		(1824, 5, 'Distrito Federal'),
		(1825, 5, 'Entre Ríos'),
		(1826, 5, 'Formosa'),
		(1827, 5, 'Jujuy'),
		(1828, 5, 'La Pampa'),
		(1829, 5, 'La Rioja'),
		(1830, 5, 'Mendoza'),
		(1831, 5, 'Misiones'),
		(1832, 5, 'Neuquén'),
		(1833, 5, 'Río Negro'),
		(1834, 5, 'Salta'),
		(1835, 5, 'San Juan'),
		(1836, 5, 'San Luis'),
		(1837, 5, 'Santa Cruz'),
		(1838, 5, 'Santa Fe'),
		(1839, 5, 'Santiago del Estero'),
		(1840, 5, 'Tierra del Fuego'),
		(1841, 5, 'Tucumán'),
		(1843, 95, 'Amazonas'),
		(1844, 95, 'Anzoategui'),
		(1845, 95, 'Apure'),
		(1846, 95, 'Aragua'),
		(1847, 95, 'Barinas'),
		(1848, 95, 'Bolívar'),
		(1849, 95, 'Carabobo'),
		(1850, 95, 'Cojedes'),
		(1851, 95, 'Delta Amacuro'),
		(1852, 95, 'Falcón'),
		(1853, 95, 'Guárico'),
		(1854, 95, 'Lara'),
		(1855, 95, 'Mérida'),
		(1856, 95, 'Miranda'),
		(1857, 95, 'Monagas'),
		(1858, 95, 'Nueva Esparta'),
		(1859, 95, 'Portuguesa'),
		(1860, 95, 'Sucre'),
		(1861, 95, 'Táchira'),
		(1862, 95, 'Trujillo'),
		(1863, 95, 'Yaracuy'),
		(1864, 95, 'Zulia'),
		(1865, 95, 'Dependencias Federales'),
		(1866, 95, 'Distrito Capital'),
		(1867, 95, 'Vargas'),
		(1869, 209, 'Boaco'),
		(1870, 209, 'Carazo'),
		(1871, 209, 'Chinandega'),
		(1872, 209, 'Chontales'),
		(1873, 209, 'Estelí'),
		(1874, 209, 'Granada'),
		(1875, 209, 'Jinotega'),
		(1876, 209, 'León'),
		(1877, 209, 'Madriz'),
		(1878, 209, 'Managua'),
		(1879, 209, 'Masaya'),
		(1880, 209, 'Matagalpa'),
		(1881, 209, 'Nueva Segovia'),
		(1882, 209, 'Rio San Juan'),
		(1883, 209, 'Rivas'),
		(1884, 209, 'Zelaya'),
		(1886, 113, 'Pinar del Rio'),
		(1887, 113, 'Ciudad de la Habana'),
		(1888, 113, 'Matanzas'),
		(1889, 113, 'Isla de la Juventud'),
		(1890, 113, 'Camaguey'),
		(1891, 113, 'Ciego de Avila'),
		(1892, 113, 'Cienfuegos'),
		(1893, 113, 'Granma'),
		(1894, 113, 'Guantanamo'),
		(1895, 113, 'La Habana'),
		(1896, 113, 'Holguin'),
		(1897, 113, 'Las Tunas'),
		(1898, 113, 'Sancti Spiritus'),
		(1899, 113, 'Santiago de Cuba'),
		(1900, 113, 'Villa Clara'),
		(1901, 12, 'Acre'),
		(1902, 12, 'Alagoas'),
		(1903, 12, 'Amapa'),
		(1904, 12, 'Amazonas'),
		(1905, 12, 'Bahia'),
		(1906, 12, 'Ceara'),
		(1907, 12, 'Distrito Federal'),
		(1908, 12, 'Espirito Santo'),
		(1909, 12, 'Mato Grosso do Sul'),
		(1910, 12, 'Maranhao'),
		(1911, 12, 'Mato Grosso'),
		(1912, 12, 'Minas Gerais'),
		(1913, 12, 'Para'),
		(1914, 12, 'Paraiba'),
		(1915, 12, 'Parana'),
		(1916, 12, 'Piaui'),
		(1917, 12, 'Rio de Janeiro'),
		(1918, 12, 'Rio Grande do Norte'),
		(1919, 12, 'Rio Grande do Sul'),
		(1920, 12, 'Rondonia'),
		(1921, 12, 'Roraima'),
		(1922, 12, 'Santa Catarina'),
		(1923, 12, 'Sao Paulo'),
		(1924, 12, 'Sergipe'),
		(1925, 12, 'Goias'),
		(1926, 12, 'Pernambuco'),
		(1927, 12, 'Tocantins'),
		(1930, 83, 'Akureyri'),
		(1931, 83, 'Arnessysla'),
		(1932, 83, 'Austur-Bardastrandarsysla'),
		(1933, 83, 'Austur-Hunavatnssysla'),
		(1934, 83, 'Austur-Skaftafellssysla'),
		(1935, 83, 'Borgarfjardarsysla'),
		(1936, 83, 'Dalasysla'),
		(1937, 83, 'Eyjafjardarsysla'),
		(1938, 83, 'Gullbringusysla'),
		(1939, 83, 'Hafnarfjordur'),
		(1943, 83, 'Kjosarsysla'),
		(1944, 83, 'Kopavogur'),
		(1945, 83, 'Myrasysla'),
		(1946, 83, 'Neskaupstadur'),
		(1947, 83, 'Nordur-Isafjardarsysla'),
		(1948, 83, 'Nordur-Mulasysla'),
		(1949, 83, 'Nordur-Tingeyjarsysla'),
		(1950, 83, 'Olafsfjordur'),
		(1951, 83, 'Rangarvallasysla'),
		(1952, 83, 'Reykjavik'),
		(1953, 83, 'Saudarkrokur'),
		(1954, 83, 'Seydisfjordur'),
		(1956, 83, 'Skagafjardarsysla'),
		(1957, 83, 'Snafellsnes- og Hnappadalssysla'),
		(1958, 83, 'Strandasysla'),
		(1959, 83, 'Sudur-Mulasysla'),
		(1960, 83, 'Sudur-Tingeyjarsysla'),
		(1961, 83, 'Vestmannaeyjar'),
		(1962, 83, 'Vestur-Bardastrandarsysla'),
		(1964, 83, 'Vestur-Isafjardarsysla'),
		(1965, 83, 'Vestur-Skaftafellssysla'),
		(1966, 35, 'Anhui'),
		(1967, 35, 'Zhejiang'),
		(1968, 35, 'Jiangxi'),
		(1969, 35, 'Jiangsu'),
		(1970, 35, 'Jilin'),
		(1971, 35, 'Qinghai'),
		(1972, 35, 'Fujian'),
		(1973, 35, 'Heilongjiang'),
		(1974, 35, 'Henan'),
		(1975, 35, 'Hebei'),
		(1976, 35, 'Hunan'),
		(1977, 35, 'Hubei'),
		(1978, 35, 'Xinjiang'),
		(1979, 35, 'Xizang'),
		(1980, 35, 'Gansu'),
		(1981, 35, 'Guangxi'),
		(1982, 35, 'Guizhou'),
		(1983, 35, 'Liaoning'),
		(1984, 35, 'Nei Mongol'),
		(1985, 35, 'Ningxia'),
		(1986, 35, 'Beijing'),
		(1987, 35, 'Shanghai'),
		(1988, 35, 'Shanxi'),
		(1989, 35, 'Shandong'),
		(1990, 35, 'Shaanxi'),
		(1991, 35, 'Sichuan'),
		(1992, 35, 'Tianjin'),
		(1993, 35, 'Yunnan'),
		(1994, 35, 'Guangdong'),
		(1995, 35, 'Hainan'),
		(1996, 35, 'Chongqing'),
		(1997, 97, 'Central'),
		(1998, 97, 'Coast'),
		(1999, 97, 'Eastern'),
		(2000, 97, 'Nairobi Area'),
		(2001, 97, 'North-Eastern'),
		(2002, 97, 'Nyanza'),
		(2003, 97, 'Rift Valley'),
		(2004, 97, 'Western'),
		(2006, 195, 'Gilbert Islands'),
		(2007, 195, 'Line Islands'),
		(2008, 195, 'Phoenix Islands'),
		(2010, 1, 'Australian Capital Territory'),
		(2011, 1, 'New South Wales'),
		(2012, 1, 'Northern Territory'),
		(2013, 1, 'Queensland'),
		(2014, 1, 'South Australia'),
		(2015, 1, 'Tasmania'),
		(2016, 1, 'Victoria'),
		(2017, 1, 'Western Australia'),
		(2018, 27, 'Dublin'),
		(2019, 27, 'Galway'),
		(2020, 27, 'Kildare'),
		(2021, 27, 'Leitrim'),
		(2022, 27, 'Limerick'),
		(2023, 27, 'Mayo'),
		(2024, 27, 'Meath'),
		(2025, 27, 'Carlow'),
		(2026, 27, 'Kilkenny'),
		(2027, 27, 'Laois'),
		(2028, 27, 'Longford'),
		(2029, 27, 'Louth'),
		(2030, 27, 'Offaly'),
		(2031, 27, 'Westmeath'),
		(2032, 27, 'Wexford'),
		(2033, 27, 'Wicklow'),
		(2034, 27, 'Roscommon'),
		(2035, 27, 'Sligo'),
		(2036, 27, 'Clare'),
		(2037, 27, 'Cork'),
		(2038, 27, 'Kerry'),
		(2039, 27, 'Tipperary'),
		(2040, 27, 'Waterford'),
		(2041, 27, 'Cavan'),
		(2042, 27, 'Donegal'),
		(2043, 27, 'Monaghan'),
		(2044, 50, 'Karachaeva-Cherkesskaya Respublica'),
		(2045, 50, 'Raimirskii (Dolgano-Nenetskii) AO'),
		(2046, 50, 'Respublica Tiva'),
		(2047, 32, 'Newfoundland'),
		(2048, 32, 'Nova Scotia'),
		(2049, 32, 'Prince Edward Island'),
		(2050, 32, 'New Brunswick'),
		(2051, 32, 'Quebec'),
		(2052, 32, 'Ontario'),
		(2053, 32, 'Manitoba'),
		(2054, 32, 'Saskatchewan'),
		(2055, 32, 'Alberta'),
		(2056, 32, 'British Columbia'),
		(2057, 32, 'Nunavut'),
		(2058, 32, 'Northwest Territories'),
		(2059, 32, 'Yukon Territory'),
		(2060, 19, 'Drenthe'),
		(2061, 19, 'Friesland'),
		(2062, 19, 'Gelderland'),
		(2063, 19, 'Groningen'),
		(2064, 19, 'Limburg'),
		(2065, 19, 'Noord-Brabant'),
		(2066, 19, 'Noord-Holland'),
		(2067, 19, 'Utrecht'),
		(2068, 19, 'Zeeland'),
		(2069, 19, 'Zuid-Holland'),
		(2071, 19, 'Overijssel'),
		(2072, 19, 'Flevoland'),
		(2073, 138, 'Duarte'),
		(2074, 138, 'Puerto Plata'),
		(2075, 138, 'Valverde'),
		(2076, 138, 'María Trinidad Sánchez'),
		(2077, 138, 'Azua'),
		(2078, 138, 'Santiago'),
		(2079, 138, 'San Cristóbal'),
		(2080, 138, 'Peravia'),
		(2081, 138, 'Elías Piña'),
		(2082, 138, 'Barahona'),
		(2083, 138, 'Monte Plata'),
		(2084, 138, 'Salcedo'),
		(2085, 138, 'La Altagracia'),
		(2086, 138, 'San Juan'),
		(2087, 138, 'Monseñor Nouel'),
		(2088, 138, 'Monte Cristi'),
		(2089, 138, 'Espaillat'),
		(2090, 138, 'Sánchez Ramírez'),
		(2091, 138, 'La Vega'),
		(2092, 138, 'San Pedro de Macorís'),
		(2093, 138, 'Independencia'),
		(2094, 138, 'Dajabón'),
		(2095, 138, 'Baoruco'),
		(2096, 138, 'El Seibo'),
		(2097, 138, 'Hato Mayor'),
		(2098, 138, 'La Romana'),
		(2099, 138, 'Pedernales'),
		(2100, 138, 'Samaná'),
		(2101, 138, 'Santiago Rodríguez'),
		(2102, 138, 'San José de Ocoa'),
		(2103, 70, 'Chiba'),
		(2104, 70, 'Ehime'),
		(2105, 70, 'Oita'),
		(2106, 85, 'Skopje'),
		(2108, 35, 'Schanghai'),
		(2109, 35, 'Hongkong'),
		(2110, 35, 'Neimenggu'),
		(2111, 35, 'Aomen'),
		(2112, 92, 'Amnat Charoen'),
		(2113, 92, 'Ang Thong'),
		(2114, 92, 'Bangkok'),
		(2115, 92, 'Buri Ram'),
		(2116, 92, 'Chachoengsao'),
		(2117, 92, 'Chai Nat'),
		(2118, 92, 'Chaiyaphum'),
		(2119, 92, 'Chanthaburi'),
		(2120, 92, 'Chiang Mai'),
		(2121, 92, 'Chiang Rai'),
		(2122, 92, 'Chon Buri'),
		(2124, 92, 'Kalasin'),
		(2126, 92, 'Kanchanaburi'),
		(2127, 92, 'Khon Kaen'),
		(2128, 92, 'Krabi'),
		(2129, 92, 'Lampang'),
		(2131, 92, 'Loei'),
		(2132, 92, 'Lop Buri'),
		(2133, 92, 'Mae Hong Son'),
		(2134, 92, 'Maha Sarakham'),
		(2137, 92, 'Nakhon Pathom'),
		(2139, 92, 'Nakhon Ratchasima'),
		(2140, 92, 'Nakhon Sawan'),
		(2141, 92, 'Nakhon Si Thammarat'),
		(2143, 92, 'Narathiwat'),
		(2144, 92, 'Nong Bua Lam Phu'),
		(2145, 92, 'Nong Khai'),
		(2146, 92, 'Nonthaburi'),
		(2147, 92, 'Pathum Thani'),
		(2148, 92, 'Pattani'),
		(2149, 92, 'Phangnga'),
		(2150, 92, 'Phatthalung'),
		(2154, 92, 'Phichit'),
		(2155, 92, 'Phitsanulok'),
		(2156, 92, 'Phra Nakhon Si Ayutthaya'),
		(2157, 92, 'Phrae'),
		(2158, 92, 'Phuket'),
		(2159, 92, 'Prachin Buri'),
		(2160, 92, 'Prachuap Khiri Khan'),
		(2162, 92, 'Ratchaburi'),
		(2163, 92, 'Rayong'),
		(2164, 92, 'Roi Et'),
		(2165, 92, 'Sa Kaeo'),
		(2166, 92, 'Sakon Nakhon'),
		(2167, 92, 'Samut Prakan'),
		(2168, 92, 'Samut Sakhon'),
		(2169, 92, 'Samut Songkhran'),
		(2170, 92, 'Saraburi'),
		(2172, 92, 'Si Sa Ket'),
		(2173, 92, 'Sing Buri'),
		(2174, 92, 'Songkhla'),
		(2175, 92, 'Sukhothai'),
		(2176, 92, 'Suphan Buri'),
		(2177, 92, 'Surat Thani'),
		(2178, 92, 'Surin'),
		(2180, 92, 'Trang'),
		(2182, 92, 'Ubon Ratchathani'),
		(2183, 92, 'Udon Thani'),
		(2184, 92, 'Uthai Thani'),
		(2185, 92, 'Uttaradit'),
		(2186, 92, 'Yala'),
		(2187, 92, 'Yasothon'),
		(2188, 69, 'Busan'),
		(2189, 69, 'Daegu'),
		(2191, 69, 'Gangwon'),
		(2192, 69, 'Gwangju'),
		(2193, 69, 'Gyeonggi'),
		(2194, 69, 'Gyeongsangbuk'),
		(2195, 69, 'Gyeongsangnam'),
		(2196, 69, 'Jeju'),
		(2201, 25, 'Delhi');
		(2202, 81, 'Región de Los Ríos'),
		(2203, 81, 'Región de Arica y Parinacota'),*/
	}



}
