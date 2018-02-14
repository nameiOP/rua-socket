<?php
namespace rsk\able;


/**
 * bufferAble
 * Interface bufferAble
 * @package rua\able
 */
interface ioAble{




    /**
     * 事件 loop
     * @param $timeout
     * @return mixed
     */
    public function loop($timeout);




    /**
     * $listenSocket可读时触发,接受新的客户端连接
     * @param resource $listenSocket
     * @return mixed
     */
    public function acceptCallBack($listenSocket);




    /**
     * buffer读取成功后触发
     * @param $fd
     * @param $buffer
     * @return void
     */
    public function readCallBack($fd,$buffer);




    /**
     * buffer写入成功后触发
     * @param $bufferEvent
     * @param $fd
     * @return mixed
     */
    public function writeCallBack($bufferEvent,$fd);


    /**
     * 客户端错误时触发
     * @param $event
     * @param $fd
     * @return mixed
     */
    public function errorCallBack($bufferEvent,$fd);




    /**
     * [ 私有方法 ]
     * 从 bufferEvent 中读取 buffer ,由 io.bufferEvent 触发
     * @param $bufferEvent
     * @param $fd
     * @param int|null $bufferSize
     * @return void
     *
     * @see readCallBack()
     */
    public function setReadBuffer($bufferEvent, $fd, $bufferSize=null);




    /**
     * [ 公共方法 ]
     * 向bufferEvent中写入 buffer ,由 server.send() 触发
     * 写入成功后,再调用 writeCallBack()
     * @param $bufferEvent
     * @param $fd
     * @return void
     *
     * @see writeCallBack()
     */
    public function setWriteBuffer($bufferEvent,$fd);

}