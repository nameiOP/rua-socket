<?php
namespace rsk\server;


use rua\traits\eventable;
use rua\traits\macroable;
use rsk\traits\socketable;


/**
 * Class server
 * @package server
 *
 *
 * 服务器消息由轮训或事件触发自动接收，采用自定义协议处理数据包，通过回调通知应用程序
 *
 */
abstract class server {


    use socketable,macroable,eventable;



    /**
     * 设置服务器信息
     * @return mixed
     * @author liu.bin 2017/10/31 11:28
     */
    abstract public function setServer();



    abstract public function displayUI();


    /**
     * todo
     * 初始化init
     * @author liu.bin 2017/10/31 11:54
     */
    public function init(){
        //
    }


    /**
     * 启动服务器
     * @author liu.bin 2017/9/27 14:56
     */
    public function start(){


        $serverConfig = $this->setServer();
        $result = $this->createSocket($serverConfig['protocol'],$serverConfig['ip'],$serverConfig['port']);
        if(false === $result){
            return false;
        }


        //初始化服务器信息
        $this->init();

        //展示ui
        $this->displayUI();

        //默认采用socket_select方式接收客户端连接

        \Builder::

        $this->loop = new select($this);
        $this->loop->loop();

    }




}