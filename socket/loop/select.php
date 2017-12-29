<?php


namespace rsk\loop;


use Builder;
use rsk\server\server;
use rsk\server\connect;
use rsk\event\stopEvent;
use rsk\event\connectEvent;
use rsk\event\receiveEvent;



class select extends loop {



    /**
     * 同步非阻塞
     * socket select loop
     * @author liu.bin 2017/9/26 11:43
     */
    public function loop($timeout=null)
    {

		$server = Builder::$server;


        /**
         * 传递事件对象
         */
        $connectEvent = new connectEvent();
        $connectEvent->server = $server;

        $stopEvent = new stopEvent();
        $stopEvent->server = $server;

        $receiveEvent = new receiveEvent();
        $receiveEvent->server = $server;


        /**
         * 设置非阻塞
         * 如果是同步阻塞模式,socket_accept 则会发出大量警告,
         * 此处会有socketSelect阻塞,有了连接才会调用 socket_accept,则不会出现警告
         */
        $server->socketSetNonBlock($server->getSocket());
        //$server->socketSetBlock($server->getSocket());


        if($server->block){
            console('socket is block','==block==');
        }else{
            console('socket is non block','==block==');
        }


        while(true){

            //console('======== begin =======');
            //重置所有链接队列
            $socketReadQueue = $server->socketReadCollect->toArray();
            $socketWriteQueue = $server->socketWriteCollect->toArray();
            $socketExceptQueue = array();


            console('queue num is : ' . count($socketReadQueue),' == queue == ');

            // socket 选择
            $result = $server->socketSelect($socketReadQueue, $socketWriteQueue, $socketExceptQueue, $timeout);

            if(!$result){
                continue;
            }

            //console('select result is  : '.$result,'select');


            /**
             * 读
             */
            foreach($socketReadQueue as $socket){

                if($socket == $server->getSocket()){

                    //接受客户端链接（创建新的客户端 socket）
					$connect = new connect($server->getSocket());
                    if($connect->getStatus() == connect::STATUS_CLOSE){
                        break;
                    }

                    //加入链接队列
                    if($server->addConn($connect->getFd(),$connect)){

                        //绑定接收消息事件
                        $connect->on(connect::EVENT_RECEIVE,[$connect,'receive']);
                        $connectEvent->fd = $connect->getFd();
                        //console('connect - fd - '.$connectEvent->fd,'select');
                        $server->trigger(server::EVENT_RSK_CONNECT,$connectEvent);

                    }else{
                        unset($connect);
                        break;

                    }

                }else{


					//接收客户端消息
					$fd = socket_to_fd($socket);
                    $connect = $server->connCollect->get($fd);
                    //触发消息接收
                    $connect->trigger(connect::EVENT_RECEIVE);
                    $receive_data = $connect->getData();


					if(is_empty($receive_data) || connect::STATUS_CLOSE == $connect->getStatus()){
                        //console('receive empty','select - '.$fd);
                        $stopEvent->fd = $fd;
                        $server->trigger(server::EVENT_RSK_STOP,$stopEvent);
						$server->removeConn($connect->getFd());

                    }else{
                        $receiveEvent->fd = $fd;
                        $receiveEvent->receive_data = $receive_data;
                        //console('receive data : '.$receive_data,'select - '.$fd);
                        $server->trigger(server::EVENT_RSK_RECEIVE,$receiveEvent);

                    }


                }
            }

            //console('select loop end');
        }


    }


}
