<?php


namespace rsk\loop;



use server\queue\queue;
use server\connect\connect;



class select extends loop {



    /**
     * 所有的客户端链接
     * @var array
     */
    public $sockets = [];

    public $master_socket;





    /**
     * socket select loop
     * @author liu.bin 2017/9/26 11:43
     */
    public function loop($timeout=null)
    {


        $server = $this->get_server();
        queue::addServer($server);



        while(true){


            //重置所有链接队列
            $change_socket_queue = queue::sockets();

            // socket 选择
            @socket_select($change_socket_queue,$write=NULL,$timeout,NULL);


            foreach($change_socket_queue as $socket){

                if($socket == $this->master_socket){

                    //接受客户端链接（创建新的客户端 socket）
                    $connect = new connect($this->server);

                    //加入链接队列
                    if(queue::add($connect)){
                        //触发连接事件
                        $this->trigger(self::EVENT_CONNECT,array($server,$connect->getId()));
                    }else{
                        unset($connect);
                    }

                }else{


                    //接收客户端消息
                    $connect = queue::findConnBySocket($socket);
                    $receive_data = $connect->receive();

                    if(empty($receive_data) || connect::STATUS_CLOSE == $connect->getStatus()){
                        //触发关闭事件
                        $this->trigger(self::EVENT_CLOSE,array($server,$connect->getId()));
                        $this->get_server()->close($connect->getId());
                        break;
                    }

                    //触发接收消息事件
                    $this->trigger(self::EVENT_RECEIVE,array($server,$connect->getId(),$receive_data));

                }
            }


        }


    }




}