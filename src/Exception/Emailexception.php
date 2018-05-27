<?php

namespace xfxstudios\Exception;

class Emailexception extends \Exception
{
    public function __construct($message = null, $code = 0)
    {
        parent::__construct($message, $code);
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

    public function funciónPersonalizada() {
        echo "Una función personalizada para este tipo de excepción\n";
    }
}