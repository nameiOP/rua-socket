<?php


namespace rsk\io\proxy;


use rsk\able\socketAble;

class streamSocketProxy implements socketAble
{



    private $_peer;

    /**
     * 客户端socket连接服务器
     * @param string $location
     * @return resource
     */
    public function connect($location){

        $err_no     = 0;
        $err_str    = '';
        $socket     = stream_socket_client($location, $err_no, $err_str, 5);
        if($socket){
            return $socket;
        }else{
            return false;
        }

    }




    /**
     * 服务端socket监听端口
     * @param string $listen
     * @param int $backlog backlog是增加并发的关键
     * @return resource|bool
     *
     */
    public function listen($listen,$backlog=0){


        //初始化socket配置
        $context_option = array();



        //backlog 是增加并发的关键
        if (!isset($context_option['socket']['backlog'])) {
            $context_option['socket']['backlog'] = $backlog;
        }


        /**
         * 开启地址重复利用
         * http://php.net/manual/en/context.socket.php
         * http://blog.csdn.net/yaokai_assultmaster/article/details/68951150
         */
        if(!isset($context_option['socket']['so_reuseport'])){
            $context_option['socket']['so_reuseport'] = 1;
        }


        //创建 server 监听
        $_context   = stream_context_create($context_option);
        $socket     = stream_socket_server($listen, $errNo, $errStr,STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,$_context);
        if ($socket) {
            return $socket;
        }else{
            return false;
        }

    }





    /**
     * 接受socket请求
     * 此方法可以创建客户端socket对象
     * @param resource $listenSocket
     * @return bool|resource
     *
     * @author liu.bin
     */
    public function accept($listenSocket){


        /**
         * 服务端socket处理客户端socket连接是需要一定时间的。
         * ServerSocket有一个队列，存放还没有来得及处理的客户端Socket，这个队列的容量就是backlog的含义。
         * 如果队列已经被客户端socket占满了，如果还有新的连接过来，那么ServerSocket会拒绝新的连接。
         * 也就是说 backlog 提供了容量限制功能，避免太多的客户端socket占用太多服务器资源。
         * 客户端每次创建一个Socket对象，服务端的队列长度就会增加1个。
         *
         * 服务端每次accept()，就会从队列中取出一个元素。
         */
        $peer_name      = '';
        $clientSocket   = @stream_socket_accept($listenSocket,5,$peer_name);
        if(is_resource($clientSocket)){
            $this->_peer = explode(':',$peer_name);
            return $clientSocket;
        }
        return false;
    }



    /**
     * 获取客户端连接信息
     * @param resource $clientSocket
     * @return array
     */
    public function getPeer($clientSocket){
        return $this->_peer;
    }





    /**
     * 发送消息
     * @param $socket
     * @param $data
     * @return int
     */
    public function send($socket,$data){
        if( stream_socket_sendto($socket, $data, strlen($data)) ){
            return true;
        }else{
            return false;
        }
    }



    /**
     * 从socket读取数据
     *
     *
     * socket_recv:
     *      1:MSG_OOB           协议的实现为了提高效率，往往在应用层传来少量的数据时不马上发送，而是等到数据缓冲区里有了一定量的数据时才一起发送，
     *                          但有些应用本身数据量并不多，而且需要马上发送，这时，就用紧急指针，这样数据就会马上发送，而不需等待有大量的数据。
     *
     *      2:MSG_PEEK          从接受队列的起始位置接收数据，但不将他们从接受队列中移除。
     *                          MSG_PEEK标志会将套接字接收队列中的可读的数据拷贝到缓冲区，但不会使套接子接收队列中的数据减少，
     *                          常见的是：例如调用recv或read后，导致套接字接收队列中的数据被读取后而减少，而指定了MSG_PEEK标志，
     *                          可通过返回值获得可读数据长度，并且不会减少套接字接收缓冲区中的数据，所以可以供程序的其他部分继续读取。
     *
     *      3:MSG_WAITALL       [阻塞读取]     在接收到指定长度的字符之前,进程将一直阻塞,一般用作消息长度是固定的协议;
     *
     *      4:MSG_DONTWAIT      [非阻塞模式]   接收指定长度的值,如果缓冲区没有数据,则立即返回。有数据,则按最大的读,并立即返回;
     *
     *
     * @param $socket
     * @param $buffer_size
     * @param $flag
     * @return bool|string
     *
     */
    public function receive($socket,$buffer_size,$flag=MSG_DONTWAIT){

        if(MSG_DONTWAIT == $flag){
            //非阻塞读(默认)
            $buffer = stream_socket_recvfrom($socket,$buffer_size);
        }elseif(MSG_WAITALL == $flag){

            //阻塞读 stream_socket_recvfrom不支持阻塞读,需要用到while
            $read_buffer_len = 0;
            $buffer = '';
            while($read_buffer_len < $buffer_size){
                $buffer .= stream_socket_recvfrom($socket,$buffer_size);
                $read_buffer_len = strlen($buffer);
            }
        }else{
            $buffer = stream_socket_recvfrom($socket,$buffer_size,$flag);
        }

        if($buffer){
            console("buffer is :".$buffer.' -- length: '.strlen($buffer),'stream_socket');
            return $buffer;
        }else{
            return false;
        }

    }


    /**
     * socket_read:
     *      1:PHP_NORMAL_READ(无协议)    按最大长度读取,遇到 PHP_EOL 返回,会过滤掉 PHP_EOL 字符;
     *                                  下一次消息就从下一行开始读取;
     *                                  这种模式适用于没有协议的情况,用于快速读取终端发送过来的数据,比如telnet;
     *
     *      2:PHP_BINARY_READ(协议)      最大按$buffer_size读取,有数据即返回;不会过滤掉PHP_EOL;
     *                                  这种模式适用于自定义协议的情况,由自定义协议判断数据中是否包涵PHP_EOL;
     *
     *
     * 从客户端socket读取消息
     * @param $socket
     * @param $buffer_size
     * @param int $read_type
     * @return bool|string
     *
     * @author liu.bin
     */
    public function read($socket,$buffer_size,$read_type=PHP_BINARY_READ){
        if( PHP_BINARY_READ == $read_type ){
            //按最大长度读取,有值就立马返回
            $buffer = stream_socket_recvfrom($socket,$buffer_size,0);

        }elseif(PHP_NORMAL_READ == $read_type){
            //按最大长度和PHP_EOL读取,谁先满足就立即返回

            //不删除buffer读取
            $buffer = stream_socket_recvfrom($socket,$buffer_size,STREAM_PEEK);
            //已经读满$buffer_size && 读取的数据包涵 PHP_EOL
            if( $buffer && ( false !== ($str_pos = strpos($buffer,PHP_EOL)) ) ){
                //删除buffer读取
                $buffer = stream_socket_recvfrom($socket,($str_pos + strlen(PHP_EOL)));
                $buffer = substr($buffer,0,-(strlen(PHP_EOL)));
            }else{
                $buffer = stream_socket_recvfrom($socket,$buffer_size);
            }

        }else{
            //默认 按$read_type模式读取(STREAM_PEEK,STREAM_OOB)
            $buffer = stream_socket_recvfrom($socket,$buffer_size,$read_type);
        }

        if($buffer){
            return $buffer;
        }else{
            return false;
        }
    }




    /**
     * socket select
     * @param array $read
     * @param array $write
     * @param array $except
     * @param $tv_sec
     * @param int $tv_usec
     * @return int
     */
    public function select(array &$read, array &$write, array &$except, $tv_sec, $tv_usec = 0){
        return stream_select($read, $write, $except, $tv_sec, $tv_usec);
    }



    /**
     * 设置socket为非阻塞模式
     * @param $socket
     * @return bool
     */
    public function setNonBlock($socket){

        if(stream_set_blocking($socket,0)){
            $this->block = false;
            return true;
        }
        return false;
    }



    /**
     * 设置socket为阻塞模式
     * @param $socket
     * @return bool
     */
    public function setBlock($socket){
        if(stream_set_blocking($socket,1)){
            $this->block = true;
            return true;
        }
        return false;
    }





    /**
     * 关闭客户端连接
     * @param $socket
     * @return bool
     */
    public function close($socket){
        $how = STREAM_SHUT_WR;
        stream_socket_shutdown($socket,$how);
        return fclose($socket);
    }

}
