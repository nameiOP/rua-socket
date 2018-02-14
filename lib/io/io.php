<?php
namespace rsk\io;

use rsk\able\ioAble;
use rua\able\runnable;
use rsk\io\proxy\socketProxy;



abstract class io implements runnable,ioAble{


    /**
     * @var $socketAble \rsk\io\proxy\socketProxy
     */
    public $socketProxy;



    /**
     * 保存所有socket
     * @var array
     */
    protected $_sockets = array();





    /**
     * @var int 读缓冲低水位
     */
    protected $_readLowWaterLine = 0;


    /**
     * @var int 写缓冲区低水位
     */
    protected $_writeLowWaterLine = 0;


    /**
     * 超时时间
     * @var
     */
    public $time_out;


    /**
     * 读取字符长度
     * @var int
     */
    public $buffer_size = 65535;



    /**
     * 构造器
     * @param socketProxy $socketProxy
     */
    public function __construct( socketProxy $socketProxy){
        $this->socketProxy = $socketProxy;
    }






    /**
     * 创建监听 listenSocket,开始loop
     */
    public function run(){

        $listenServer = \Builder::$server->server;

        foreach($listenServer as $listen){
            //创建 listenSocket
            $listenSocket = $this->socketProxy->listen($listen);
            if(!$listenSocket){
                exit('listen socket error');
            }

            // fd
            $fd                     = socket_to_fd($listenSocket);
            $this->_sockets[$fd]    = $listenSocket;
            $this->addListenEvent($listenSocket);


            //三元组
            \Builder::$server->listen[$fd] = parse_listen($listen);
        }


        $this->loop($this->time_out);
    }




    /**
     * $listenSocket可读时触发,接受新的客户端连接
     * @param resource $listenSocket
     * @return mixed
     */
    public function acceptCallBack($listenSocket)
    {

        //fd
        $listenFd       = socket_to_fd($listenSocket);


        // client socket
        $clientSocket   = $this->socketProxy->accept($listenSocket);
        $clientPeer     = $this->socketProxy->getPeer($clientSocket);


        //添加bufferEvent,让其处于监听状态
        $this->addBufferEvent($clientSocket);


        //通知 服务器 保存 $connect
        \Builder::$server->setConnect($clientSocket,$listenFd,$clientPeer);
    }


    /**
     * buffer读取成功后触发
     * @param $fd
     * @param $buffer
     * @return void
     */
    public function readCallBack($fd,$buffer){



        console($buffer,'io');

        //connect将会根据 协议 去判断一个包的数据是否完成,
        //如果包完整,则触发server的receive函数,如果不完整,则继续等待
        //$connect = \Builder::$server->getConnect($fd);
    }



    /**
     * buffer写入成功后触发
     * @param $bufferEvent
     * @param $fd
     * @return mixed
     */
    public function writeCallBack($bufferEvent,$fd){
        //一般用来打酱油
    }





    /**
     * 客户端错误时触发
     * @param $event
     * @param $fd
     * @return mixed
     */
    public function errorCallBack($event,$fd){

        //关闭 socket event
        $this->removeEvent($fd);

        //通知 server 注销 $connect
        //\Builder::$server->removeConnect($fd);
    }




    /**
     * 关闭连接
     * @param int $fd
     *
     */
    protected function removeEvent($fd){

        //关闭 socket
        $socket = $this->_sockets[$fd];
        $this->socketProxy->close($socket);

        //关闭bufferEvent
        $this->removeBufferEvent($fd);
    }







    /**
     * 添加event
     * @param resource $listenSocket
     */
    abstract protected function addListenEvent($listenSocket);
    abstract protected function removeListenEvent($fd);




    /**
     * 添加 bufferEvent
     * @param $clientSocket
     */
    abstract protected function addBufferEvent($clientSocket);
    abstract protected function removeBufferEvent($fd);






    /**
     * socket 信号处理
     */
    protected function installSignal(){


        /**
         * 忽略 SIGPIPE 信号
         *
         * 该连接的写半部关闭(主动发送FIN包的TCP连接)。对这样的套接字的写操作将会产生SIGPIPE信号。
         * 所以我们的网络程序基本都要自定义处理SIGPIPE信号。因为SIGPIPE信号的默认处理方式是程序退出。
         * 服务器端socket主动关闭客户端连接时,继续send数据,则会产生该 SIGPIPE 信号。
         */
        //pcntl_signal(SIGPIPE, SIG_IGN, false);


    }
}