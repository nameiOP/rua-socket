<?php


namespace rsk\io\adapter;


use rsk\io\io;
use rsk\io\proxy\socketProxy;




class ioSelectAdapter extends io {





    private $_base;
    private $_events        = array();
    private $_bufferEvents  = array();



    /**
     * 构造器
     * @param socketProxy $socketProxy
     */
    public function __construct(socketProxy $socketProxy){
        parent::__construct($socketProxy);
    }



    protected function loop($timeOut=null){



        //循环(保证多客户端连接)
        while(true){

            $socketReadQueue    = $this->_base;
            $socketWriteQueue   = array();
            $socketExceptQueue  = array();

            /**
             * socket select io复用模型
             *
             * $socketReadQueue:
             *      这个集合中应该包括文件描述符，我们是要监视这些文件描述符的读变化的，即我们关心是否可以从这些文件中读取数据了，
             *      如果这个集合中有一个文件可读，select就会返回一个大于0的值，表示有文件可读，如果没有可读的文件，则根据timeout参数再判断是否超时，
             *      若超出timeout的时间，select返回0，若发生错误返回负值。可以传入NULL值，表示不关心任何文件的读变化。
             *
             *
             * $socketWriteQueue:
             *      这个集合中应该包括文件描述符，我们是要监视这些文件描述符的写变化的，即我们关心是否可以向这些文件中写入数据了，
             *      如果这个集合中有一个文件可写，select就会返回一个大于0的值，表示有文件可写，如果没有可写的文件，则根据timeout再判断是否超时，
             *      若超出timeout的时间，select返回0，若发生错误返回负值。可以传入NULL值，表示不关心任何文件的写变化。
             *
             *
             * $socketExceptQueue:
             *      同上,用来监视文件异常。
             *
             * $timeout :
             *
             *      NULL:
             *          若将NULL以形参传入，即不传入时间结构，就是将select置于[阻塞状态]，一定等到监视文件描述符集合中某个文件描述符发生变化为止；
             *      0:
             *          若将时间值设为0秒0毫秒，就变成一个纯粹的[非阻塞函数]，不管文件描述符是否有变化，都立刻返回继续执行，文件无变化返回0，有变化返回一个正值；
             *      >0:
             *          timeout的值大于0，这就是等待的超时时间，即select在timeout时间内[阻塞]，超时时间之内有事件到来就返回了，否则在超时后不管怎样一定返回，返回值同上述。
             *
             *
             * [socketSelect] 如果有消息可读,socketSelect将一直触发,需要尽快将消息读出;如果没有消息可读,则会阻塞
             *
             */
            if( !$this->socketProxy->select($socketReadQueue, $socketWriteQueue, $socketExceptQueue, $timeOut) ){
                //处理在非阻塞的模式下,不会往下执行,否则 socket_accept()会有警告
                continue;
            }

            //处理 read
            foreach($socketReadQueue as $socket){
                if( in_array($socket,$this->_events) ){
                    //接收客户端连接
                    $this->acceptCallBack($socket);
                }else{
                    //客户端消息可读
                    $fd = socket_to_fd($socket);
                    $this->setReadBuffer($socket,$fd);
                }
            }


        }


    }


    /**
     * 添加事件
     * @param resource $listenSocket
     */
    protected function addListenEvent($listenSocket){
        $fd                 = socket_to_fd($listenSocket);
        $this->_events[$fd] = $listenSocket;
        $this->_base[$fd]   = $listenSocket;

    }


    /**
     * @param $fd
     */
    protected function removeListenEvent($fd)
    {
        unset($this->_events[$fd]);
        unset($this->_base[$fd]);
    }





    /**
     * 添加buffer事件
     * @param resource $clientSocket
     */
    protected function addBufferEvent($clientSocket){

        $fd                         = socket_to_fd($clientSocket);
        $this->_bufferEvents[$fd]   = $clientSocket;
        $this->_base[$fd]           = $clientSocket;
    }

    /**
     * @param $fd
     */
    protected function removeBufferEvent($fd)
    {
        unset($this->_bufferEvents[$fd]);
        unset($this->_base[$fd]);
    }





    /**
     * $socket 可读时触发;
     *
     * @param $bufferEvent
     * @param int $fd
     * @param int|null $bufferSize
     *
     */
    public function setReadBuffer($bufferEvent,$fd,$bufferSize=null){

        $bufferSize = is_null($bufferSize) ? $this->buffer_size : $bufferSize;

        $buffer = '';
        while ($read = $this->socketProxy->read($bufferEvent,1024)) {
            $buffer .= $read;
        }
        if(is_empty($buffer)){
            $this->errorCallBack($bufferEvent,$fd);
            return ;
        }else{
            $this->readCallBack($fd,$buffer);
        }
    }




    /**
     * server.send() 时触发;
     *
     * 写入成功 触发 io.writeCallBack()
     *
     * @param $fd
     * @param $msg
     *
     */
    public function setWriteBuffer($fd,$msg)
    {
        $bufferEvent = $this->_bufferEvents[$fd];
        if($this->socketProxy->send($bufferEvent,$msg)){
            //此处手动触发 writeCallBack()
            $this->writeCallBack($bufferEvent,$fd);
        }
    }




}
