<?php
namespace xfxstudios\general;

use \Mailjet\Resources;

class Myemail
{
    private $apiKey;
    private $apiSecret;
    private $t;
    private $m;

    public function __construct(){
        $this->ci =& get_instance();
        $this->ini = parse_ini_file(SYSDIR.'/services/d.ini');
        $this->apiKey = $this->ini['keyMail'];
        $this->apiSecret = $this->ini['secretMail'];
        $this->mj = new \Mailjet\Client($this->apikey, $this->apisecret,true,['version' => $this->ini['versionMail']]);
    }

    public function test($X){
        $this->t = $X;
    }

    public function medio($X){
        $this->m = $X;
    }

    public function testb(){
        return $this->t.$this->m;
    }

}
