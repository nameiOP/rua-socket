<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/28
 * Time: 11:44
 */
namespace rsk\server;



use Builder;
use rua\traits\eventable;
use rsk\traits\socketable;
use rsk\traits\streamsocketable;

class connect {


	//use streamsocketable,eventable;
    use socketable,eventable;


    //活动状态
    const STATUS_ACTIVE = 1;
    //等待状态
    const STATUS_PEND = 2;
    //关闭状态
    const STATUS_CLOSE = 3;




    /**
     * 接收消息事件
     */
    const EVENT_RECEIVE = 'receive';



    //当前连接状态
    private $status;


    /**
     * @var \rsk\protocol\protocol
     */
    protected $protocol;



    /**
     * 初始化
     * @param $socket
     */
    public function __construct($socket){
        $this->socketAccept($socket);
        $this->status = $this->fd ? self::STATUS_ACTIVE : self::STATUS_CLOSE;
    }




	/*
     * 获取连接状态
     * @return integer
     * @author liu.bin 2017/9/28 14:21
     */
    public function getStatus(){
        return $this->status;
    }





    /**
     * 设置连接状态
     * @param $status integer
     * @author liu.bin 2017/9/28 14:57
     */
    public function setStatus($status){
        $this->status = $status;
    }




    /**
     * 获取通信协议
     * @return mixed
     */
    private function getProtocol(){

        if(!$this->protocol){
            $protocol = Builder::$server->protocolClass ? Builder::$server->protocolClass : 'rsk\protocol\server\\' . Builder::$server->protocol;
            $this->protocol = new $protocol;
        }
        return $this->protocol;
    }





    /**
     * 接收客户端消息
     * @author liu.bin 2017/9/29 13:24
     */
    public function receive(){

        $protocol = $this->getProtocol();
        //连接关闭,不再接收客户端消息
        if(self::STATUS_ACTIVE !== $this->status){
            return false;
        }
        //获取buffer_size ,固定包头+包体的协议中，buffer_size会变化；边界检测的协议，buffer_size固定
        $bufferSize = $protocol->getBufferSize();
        //读取消息
        $buffer = $this->socketReceive($this->socket,$bufferSize);

        if (is_empty($buffer)) {
            $this->status = self::STATUS_CLOSE;
            return false;
        }
        //是否触发接收消息事件:true 继续读取，false:读取结束
        if($protocol->readBuffer($buffer)){
            $this->trigger(self::EVENT_RECEIVE);
        }
        return true;
    }


    /**
     * 获取消息
     * @return string
     */
    public function getData(){
        //return true;
        return $this->getProtocol()->getData();
    }


}

