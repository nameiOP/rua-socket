<?php

namespace rsk\loop;



use Builder;
use rsk\server\server;
use rsk\server\connect;
use rsk\event\stopEvent;
use rsk\event\connectEvent;
use rsk\event\receiveEvent;


/**
 * 同步轮询
 * Class ioloop
 * @package rsk\loop
 */
class ioloop extends loop {


    /**
     * @param $timeout
     */
    public function loop($timeout=null)
    {

        $server = Builder::$server;
        $sock = $server->getSocket();

        /**
         * 传递事件对象
         */
        $connectEvent = new connectEvent();
        $connectEvent->server = $server;

        $stopEvent = new stopEvent();
        $stopEvent->server = $server;

        $receiveEvent = new receiveEvent();
        $receiveEvent->server = $server;

        //此处设置非阻塞模式,会造成大量CPU资源浪费
        //$server->socketSetNonBlock($sock);

        do {

            console('io - loop - begin');

            //接受客户端链接（创建新的客户端 socket）
            $connect = new connect($sock);
            if($connect->getStatus() === connect::STATUS_CLOSE){
                //如果是非阻塞模式,需要注销 $connect
                unset($connect);
                console('no client connect');
                continue;
            }


            $fd = $connect->getFd();

            //加入链接队列
            if($server->addConn($fd,$connect)){

                //绑定接收消息事件
                $connect->on(connect::EVENT_RECEIVE,[$connect,'receive']);
                $connectEvent->fd = $fd;
                console('connect - fd - '.$connectEvent->fd);
                $server->trigger(server::EVENT_RSK_CONNECT,$connectEvent);

            }else{
                //超过最大连接数
                unset($connect);
                continue;
            }




            //触发客户端接收消息事件
            $connect->trigger(connect::EVENT_RECEIVE);
            $receive_data = $connect->getData();


            if(is_empty($receive_data) || connect::STATUS_CLOSE === $connect->getStatus()){
                //无消息
                $stopEvent->fd = $fd;
                $server->removeConn($fd);
                $server->trigger(server::EVENT_RSK_STOP,$stopEvent);

            }else{
                //有消息
                $receiveEvent->fd = $fd;
                $receiveEvent->receive_data = $receive_data;
                console('ioloop - receive - fd - '.$connectEvent->fd);
                $server->trigger(server::EVENT_RSK_RECEIVE,$receiveEvent);

            }

            console('io - loop - end');
            sleep(1);
        } while(true);





    }
}
