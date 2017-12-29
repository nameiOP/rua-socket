<?php


namespace rsk\traits;

trait streamsocketable
{




    /**
     * @var int 连接编号
     */
    protected $fd = 0;




    /**
     * php 套接字
     * @var
     */
    protected $socket;




    /**
     * @var bool socket 阻塞模式
     * true 阻塞模式
     * false 非阻塞模式
     */
    public $block = true;


    /**
     * 打印编号
     * @return string
     * @author liu.bin 2017/9/28 14:17
     */
    public function __toString()
    {
        return (string)$this->getFd();
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
    public function createSocket($host,$port){


        //backlog 是增加并发的关键
        $context_option = array();
        if (!isset($context_option['socket']['backlog'])) {
            $context_option['socket']['backlog'] = 102400;
        }
        $_context = stream_context_create($context_option);


        $param = 'tcp://'.$host.':'.$port;
        $socket = stream_socket_server($param, $errNo, $errStr,STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,$_context);


        if (!$socket) {
            throw new \Exception($errStr, $errNo);
        }

		$this->socket = $socket;
        $this->fd = socket_to_fd($this->socket);
        return true;
    }



	/**
	 * 接受socket请求
	 * 此方法可以创建客户端socket对象
	 */
	public function socketAccept($socket){
        //console('ready to accept');
		$this->socket = stream_socket_accept($socket,0);
        //console('new socket accept');
        if(!$this->socket){
            return false;
        }
		$this->fd = socket_to_fd($this->socket);
		return true;
	}



    /**
     * 发送消息
     */
    public function socketSend($socket,$data){
        return stream_socket_sendto($socket,$data);
    }



    /**
     * 从socket读取数据
     */
    public function socketReceive($socket,$buffer_size){

        //console('receive - '.socket_to_fd($socket).' - begin and [buffer_size] = '.$buffer_size);

        $buffer = stream_socket_recvfrom($socket,$buffer_size);
        if($buffer){
            //console('receive - '.socket_to_fd($socket).' - end ok');
            return $buffer;
        }else{
            //console('receive - '.socket_to_fd($socket).' - end empty');
            return false;
        }
    }


    /**
     * socket select
     * @param $socketReadQueue
     * @param $socketWriteQueue
     * @param $except
     * @param $tv_sec
     * @param null $tv_usec
     */
    public function socketSelect(array &$read, array &$write, array &$except, $tv_sec, $tv_usec = null){
        return stream_select($read, $write, $except, $tv_sec, $tv_usec);
    }




    /**
     * 设置socket为非阻塞模式
     * stream家族函数,都是非阻塞
     * @param $socket
     * @return bool
     */
    public function socketSetNonBlock($socket){

        if(stream_set_blocking($socket,0)){
            $this->block = false;
            return true;
        }
        return false;
    }



    /**
     * 设置socket为阻塞模式
     * stream家族函数,都是非阻塞
     * @param $socket
     * @return bool
     */
    public function socketSetBlock($socket){
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
    public function socketClose($socket,$how=STREAM_SHUT_WR){
        stream_socket_shutdown($socket,$how);
        return fclose($socket);
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
    public function getFd(){
        return $this->fd;
    }

}
