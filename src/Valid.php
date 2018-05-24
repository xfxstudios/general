<?php
namespace xfxstudios\general;

use Firebase\JWT\JWT;

class Valid
   
    {
        private static $_secret_key = 'Sdw1s9x8@';
        private static $_encrypt = ['HS256'];
        private static $_aud = null;

        public function __construct(){
            $ini = parse_ini_file(APPPATH.'services/d.ini');
            $this->_secret_key = $ini['secret_key'];
            $this->_encrypt = $ini['encript'];
        }
        
        public static function _SignIn($data)
        {
            $time = time();
            
            $token = array(
                'exp' => $time + (60*60),
                'aud' => self::_Aud(),
                'data' => $data
            );
    
            return JWT::encode($token, self::$secret_key);
        }
        
        public static function _Check($token)
        {
            if(empty($token))
            {
                throw new Exception("Invalid token supplied.");
            }
            
            $decode = JWT::decode(
                $token,
                self::$secret_key,
                self::$encrypt
            );
            
            if($decode->aud !== self::_Aud())
            {
                throw new Exception("Invalid user logged in.");
            }
        }
        
        public static function _GetData($token)
        {
            return JWT::decode(
                $token,
                self::$secret_key,
                self::$encrypt
            )->data;
        }
        
        private static function _Aud()
        {
            $aud = '';
            
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $aud = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $aud = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                $aud = $_SERVER['REMOTE_ADDR'];
            }
            
            $aud .= @$_SERVER['HTTP_USER_AGENT'];
            $aud .= gethostname();
            
            return sha1($aud);
        }
    }
