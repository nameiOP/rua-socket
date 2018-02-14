<?php


namespace rsk\io\proxy;





use rsk\able\socketAble;

class socketProxy implements socketAble
{





    /**
     * 客户端socket连接服务器
     * @param string $location
     * @return resource
     */
    public function connect($location){



        list($protocol,$host,$port) = explode(':',$location);
        $host = ltrim($host,'//');


        //创建socket
        if (($socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
            console("socket_create() failed: reason: " . socket_strerror(socket_last_error()) );
            return false;
        }

        //连接服务器
        if(!socket_connect($socket, $host, $port)){
            console("socket_create() failed: reason: " . socket_strerror(socket_last_error()) );
            return false;
        }

        return $socket;
    }




    /**
     * 服务端socket监听端口
     * @param string $listen
     * @param int $backlog backlog是增加并发的关键
     * @return resource|bool
     *
     */
    public function listen($listen,$backlog=0){


        list($protocol,$host,$port) = parse_listen($listen);


        //创建socket
        if (($socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
            console("socket_create() failed: reason: " . socket_strerror(socket_last_error()) );
            return false;
        }


        //开启地址重复利用
        if (!socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1)) {
            console('Unable to set option on socket: '. socket_strerror(socket_last_error($socket)));
            return false;
        }



        if (socket_bind($socket, $host, $port) === false) {
            console( "socket_bind() failed: reason: " . socket_strerror(socket_last_error($socket)));
            return false;
        }


        if (socket_listen($socket, $backlog) === false) {
            console( "socket_listen() failed: reason: " . socket_strerror(socket_last_error($socket)));
            return false;
        }


        return $socket;
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
        $clientSocket = socket_accept($listenSocket);
        return $clientSocket;
    }






    /**
     * 获取客户端连接信息
     * @param resource $clientSocket
     * @return array
     */
    public function getPeer($clientSocket){
        socket_getpeername($clientSocket,$address,$port);
        return array($address,$port);
    }





    /**
     * 发送消息
     * @param $socket
     * @param $data
     * @return int
     */
    public function send($socket,$data){
        if( socket_write($socket, $data, strlen($data)) ){
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

        $buffer = '';
        if(socket_recv($socket,$buffer,$buffer_size,$flag)){
            return $buffer;
        }else{
            //console( "socket_recv() failed: reason: " . socket_strerror(socket_last_error($socket)));
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
        $buffer = socket_read($socket, $buffer_size, $read_type);
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
        return socket_select($read, $write, $except, $tv_sec, $tv_usec);
    }



    /**
     * 设置socket为非阻塞模式
     * @param $socket
     * @return bool
     */
    public function setNonBlock($socket){

        if(socket_set_nonblock($socket)){
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
        if(socket_set_block($socket)){
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
        socket_close($socket);
        return true;
    }

}
