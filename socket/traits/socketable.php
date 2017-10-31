<?php


namespace rsk\traits;

trait socketable
{


    /**
     * php 套接字
     * @var
     */
    protected $socket;


    /**
     * @var int 连接编号
     */
    protected $socketId=0;



    /**
     * 打印编号
     * @return string
     * @author liu.bin 2017/9/28 14:17
     */
    public function __toString()
    {
        return (string)$this->getId();
    }


    /**
     * 创建socket套接字
     * @param $protocol
     * @param $ip
     * @param $port
     * @return bool
     * @throws \Exception
     * @author liu.bin 2017/10/26 15:10
     */
    public function createSocket($protocol,$ip,$port){

        $param = $protocol.'://'.$ip.':'.$port;
        $socket = @stream_socket_server($param, $errNo, $errStr);
        if (!$socket) {
            throw new \Exception($errStr, $errNo);
        }

        //非阻塞模式
        //stream_set_blocking($socket, 0);

        $this->socketId = (int)$this->socket;
        return true;
    }




    /**
     * 获取socket
     * @return mixed
     * @author liu.bin 2017/9/28 15:06
     */
    public function getSocket(){
        return $this->socket;
    }





    /**
     * 获取连接编号
     * @author liu.bin 2017/9/28 14:18
     */
    public function getId(){
        return $this->socketId;
    }

}