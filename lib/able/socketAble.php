<?php
namespace rsk\able;



interface socketAble{


    /**
     * 监听
     * @param string $listen
     * @param int $backlog
     * @return mixed
     */
    public function listen($listen,$backlog=0);


    /**
     * @param resource $socket
     * @return mixed
     */
    public function accept($socket);


    /**
     * 发送消息
     * @param $msg
     * @param $socket
     * @return mixed
     */
    public function send($msg,$socket);


    /**
     * 读取消息
     * @param $socket
     * @param $buffer_size
     * @param int $flag
     * @return mixed
     */
    public function receive($socket,$buffer_size,$flag=MSG_DONTWAIT);


    /**
     * 读取消息
     * @param $socket
     * @param $buffer_size
     * @param int $read_type
     * @return mixed
     */
    public function read($socket,$buffer_size,$read_type = PHP_BINARY_READ);


    /**
     * @param resource $socket
     * @return mixed
     */
    public function close($socket);


}
