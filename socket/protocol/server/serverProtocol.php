<?php
namespace rsk\protocol\server;


use rsk\protocol\protocol;
abstract class serverProtocol extends protocol
{




    /**
     * 重置数据
     * @author liu.bin 2017/9/30 10:51
     */
    public function over()
    {
        $this->buffer = '';
        $this->readBuffer = '';
        $this->readLength = 0;
    }


}