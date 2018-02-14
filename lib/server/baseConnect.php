<?php

namespace rsk\server;


/**
 * Class connect
 * @package rsk\server
 */
abstract class baseConnect {



    //活动状态:长连接
    const STATUS_ACTIVE = 1;

    //等待状态(准备关闭):短连接
    const STATUS_PEND = 2;

    //关闭状态
    const STATUS_CLOSE = 3;

    //事件 读数据
    const READ = 'read';




    /**
     * connect status
     * @var
     */
    public $status;



    /**
     * clientSocket fd
     * @var int connect fd
     */
    public $fd = 0;


    /**
     * listenSocket fd
     * @var int
     */
    public $listenFd = 0;



    /**
     * client peer
     * @var array
     */
    public $peer = array();







}

