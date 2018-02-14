<?php
namespace rsk\protocol\server;


use rsk\protocol\protocol;


abstract class serverProtocol extends protocol
{


    /**
     * 构造器
     */
    public function __construct(){

    }








    /**
     * 重置数据
     * @author liu.bin 2017/9/30 10:51
     */
    public function bufferRecovery()
    {
        $this->buffer       = '';
        $this->readBuffer   = '';
        $this->readLength   = 0;
    }


}