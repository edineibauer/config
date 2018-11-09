<?php

class TesteConnection extends Conn
{
    private $result;
    
    public function __construct()
    {
        parent::getConn();
        $this->result = parent::getResult();
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }
}
