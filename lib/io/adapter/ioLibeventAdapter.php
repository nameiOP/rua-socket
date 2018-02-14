<?php

namespace rsk\io\adapter;


use rsk\io\io;
use rsk\io\proxy\socketProxy;


class ioLibeventAdapter extends io{




    private $_base;
    private $_events        = array();
    private $_bufferEvents  = array();


    /**
     * 构造器
     * @param socketProxy $socketProxy
     */
    public function __construct(socketProxy $socketProxy){

        //* base event
        $this->_base	= event_base_new();
        parent::__construct($socketProxy);
    }


    /**
     *
     * @param $timeout
     * @return mixed|void
     */
    protected function loop($timeout=null)
    {
        event_base_loop($this->_base);
    }





    /**
     * 添加事件
     * @param resource $listenSocket
     */
    protected function addListenEvent($listenSocket){

        //* master socket accept event
        $fd     = socket_to_fd($listenSocket);
        $event	= event_new();
        event_set($event, $listenSocket, EV_READ | EV_PERSIST, 'acceptCallBack',$fd);
        event_base_set($event, $this->_base);
        event_add($event);
        $this->_events[$fd] = $event;

    }

    // 移除 事件
    protected function removeListenEvent($fd)
    {
        $event = $this->_events[$fd];
        event_free($event);
        unset($this->_events[$fd]);
    }




    /**
     * 添加buffer事件
     * @param resource $clientSocket
     */
    protected function addBufferEvent($clientSocket){


        $fd             = socket_to_fd($clientSocket);
        $bufferEvent    = event_buffer_new($clientSocket, array($this,'setReadBuffer'), array($this,'writeCallBack'), array($this,'errorCallBack'),$fd);
        event_buffer_timeout_set($bufferEvent, 30, 30);
        event_buffer_watermark_set($bufferEvent, EV_READ, $this->_readLowWaterLine, 0xffffff);
        event_buffer_priority_set($bufferEvent, 10);
        event_buffer_enable($bufferEvent, EV_READ | EV_PERSIST);
        event_buffer_base_set($bufferEvent, $this->_base);
        $this->_bufferEvents[$fd] = $bufferEvent;
        $this->_sockets[$fd] = $clientSocket;
    }




    // 移除 bufferEvent
    protected function removeBufferEvent($fd)
    {
        $bufferEvent = $this->_bufferEvents[$fd];
        event_buffer_disable($bufferEvent, EV_READ | EV_WRITE);
        event_buffer_free($bufferEvent);
        unset($this->_bufferEvents[$fd]);
    }





    /**
     * $bufferEvent 可读时触发;
     *
     * @param $bufferEvent
     * @param int $fd
     * @param int|null $bufferSize
     *
     */
    public function setReadBuffer($bufferEvent,$fd,$bufferSize=null){

        $bufferSize = is_null($bufferSize) ? $this->buffer_size : $bufferSize;

        $msg = '';
        while ($read = event_buffer_read($bufferEvent, $bufferSize)) {
            $msg .= $read;
        }
        $this->readCallBack($fd,$msg);
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
        // writeCallBack()由 event_buffer_write()自动触发
        event_buffer_write($bufferEvent,$msg);
    }









}