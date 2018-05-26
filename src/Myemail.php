<?php
namespace xfxstudios\general;

use \Mailjet\Resources;

class Myemail
{
    private $apiKey;
    private $apiSecret;
    private $for;
    private $forName;
    private $from;
    private $fromName;
    private $subject;
    private $template = FALSE;
    private $templateName;
    private $info;
    private $plainText;
    private $copy = FALSE;
    private $copyMail;
    private $copyName;
    private $hiddenCopy = FALSE;
    private $hiddenCopyMail;
    private $hiddenCopName;
    private $attachment = FALSE;
    private $cType;
    private $fileName;
    private $codeFile;

    public function __construct(){
        $this->ci =& get_instance();
        $this->ini = parse_ini_file(SYSDIR.'/services/d.ini');
        $this->apiKey = $this->ini['keyMail'];
        $this->apiSecret = $this->ini['secretMail'];
        $this->mj = new \Mailjet\Client($this->apiKey, $this->apiSecret,true,['version' => $this->ini['versionMail']]);
    }

    public function from($X){
        $this->from     = $X[0];
        $this->fromName = $X[1];
        $this->subject  = $X[2];
        return $this;
    }

    public function for($X){
        $this->for       = $X[0];
        $this->forName   = $X[1];
        $this->info      = $X[2];
        $this->plainText = $X[3];
        return $this;
    }

    public function copy($X){
        $this->copy     = $X[0];
        $this->copyMail = $X[1];
        $this->copyName = $X[2];
        return $this;
    }
    
    public function hiddenCopy($X){
        $this->hiddenCopy     = $X[0];
        $this->hiddenCopyMail = $X[1];
        $this->hiddenCopyName = $X[2];
        return $this;
    }

    public function template($X){
        $this->template     = $X[0];
        $this->templateName = $X[1];
        return $this;
    }
    
    public function attachFile($X){
        $this->attachment = $X[0];
        $this->cType       = $this->contentType($X[1]);
        $this->fileName    = $X[2];
        $this->codeFile    = $X[3];
        return $this;
    }
    

    private function contentType($X){
        switch ($X) {
            case 'jpg':
            case 'jpeg':
                return 'image/jpeg';
            break;
            
            case 'png':
                return 'image/png';
            break;
            
            case 'doc':
                return 'application/msword';
            break;
            
            case 'docx':
                return 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
            break;
            
            case 'pdf':
                return 'application/pdf';
            break;
            
            case 'ppt':
                return 'application/vnd.ms-powerpoint';
            break;
            
            case 'pptx':
                return 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
            break;
            
            case 'xls':
                return 'application/vnd.ms-excel';
            break;
            
            case 'xlsx':
                return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            break;
            
            case 'json':
                return 'application/json';
            break;
            
            case 'csv':
                return 'text/csv';
            break;
        }

    }

    private function armed(){
        $data = [];
        $data['From'] = [
            'Email'=>$this->from,
            'Name'=>$this->fromName
        ];
        $data['To'] = [
            [
                'Email'=>$this->for,
                'Name'=>$this->forName
            ]
        ];
        if($this->copy){
            $data['Cc'] = [
                [
                    'Email'=>$this->copyMail,
                    'Name'=>$this->copyName
                ]
            ];
        }
        if($this->hiddenCopy){
            $data['Bcc'] = [
                [
                    'Email'=>$this->hiddenCopyMail,
                    'Name'=>$this->hiddenCopyName
                ]
            ];
        }
        return $data;
    }

    public function send(){
        $data = null;
        if($this->template){
            $file = file_get_contents(APPPATH.'/plantillas/'.$this->templateName);
            $file = str_replace("%info%",$this->info,$file);
            $data = $file;
        }else{
            $data = $this->info;
        }

        $datos = $this->armed();
        $datos['Subject'] = $this->subject;
        $datos['TextPart'] = $this->plainText;
        $datos['HTMLPart'] = $data;

        if($this->attachment){
            $datos['Attachments'] = [
                [
                    'ContentType'   => $this->cType,
                    'Filename'      => $this->fileName,
                    'Base64Content' => $this->codeFile
                ]
            ];
        }

        $body = [
            'Messages' => [
                $datos
            ]
        ];
        
        $response = $this->mj->post(Resources::$Email, ['body' => $body]);
        if ($response->success())
            return $response->getData();
        else
            //return $response->getStatus();
            return $response->getData();
    }//

    public function ex(){
        $v = 0;

        try{
            if($v==0){
                throw new Exception("Error Processing Request", 1);
            }
        }catch(Emailexception $e){
            return $e->getMessage();
        }
        

    }

}

