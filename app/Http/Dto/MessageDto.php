<?php
namespace App\Http\Dto;

class MessageDto{
    private $codeStatus=0;
    private $message;

    public function __construct($codeStatus, $message)
    {
        $this->codeStatus = $codeStatus;
        $this->message = $message;
    }

    public function getCodeStatus()
    {
        return $this->codeStatus;
    }

    public function getMessage()
    {
        return $this->message;
    }
}

