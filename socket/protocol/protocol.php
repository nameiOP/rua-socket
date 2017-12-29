<?php
namespace rsk\protocol;


/**
 * Class protocol
 * @package rsk\protocol
 *
 *
 * 协议抽象类
 * 子类不负责数据包的读取操作,数据包的读取有connect类处理
 *
 *
 * 只负责数据的分包和解包,解包方式分两种:
 *  1) eof边界检测 : socket有消息后,connect读取固定长度(bufferSize)的数据包,如果没有遇到eof结束符,
 *                  协议会通知connect继续读取buffer数据,直到遇到eof结束符
 *     适用范围 : 适合一次只发一个包,响应后即断开。比如http的get模式
 *     优势 : 数据包简单,分包
 *     缺点 : 没有合包功能,所以适用于短连接
 *
 *  2) 固定包头+包体 : socket有消息后,connect读取固定长度(bufferSize)的数据包,先解包固定长度的包头
 *                   获取整体数据包长度,再通知connect读取指定长度的buffer数据
 *     适用范围 : 长连接,短连接,以数据流的形式传播
 *     优势 : 分包,合包
 *     缺点 : 数据包相对复杂,需要拆包
 *
 *  3) 只读一次 : socket有消息后,connect读取固定长度(bufferSize)的数据包,数据包超出则截取,后面不再读取
 *     适用范围 : 长连接,短连接,开发调试,心跳检测
 */
abstract class protocol
{




	/**
     * 缓冲区数据
     */
	protected $buffer = '';





    /**
     * 一次读取缓冲区数据大小
     * @var int
     */
	protected $bufferSize = 10;







    /**
     * 已读取的buffer总数据
     * @var string
     */
	protected $readBuffer = '';







    /**
     * 已读取buffer的长度
     * @var int
     */
    protected $readLength = 0;








    /**
     * 单包接收的输入长度
     * 因为tcp是数据流，如果定义的包的概念，在没有完整接收整个包的时候，会一直接收下去，造成内存泄漏
     */
	protected $maxReadLength = 100;






	/**
	 * 构造器
	 */
	public function __construct(){

	}





    /**
     * 获取buffer size
     * @author liu.bin 2017/9/29 13:37
     */
	public function getBufferSize(){
	    return $this->bufferSize;
    }





    /**
     * 读取buffer
     * @author liu.bin 2017/9/29 14:42
     */
    public function getBuffer(){
        $buffer = $this->decode($this->buffer);
	    return $buffer;
    }







    /**
     * 返回已接收的数据
     * @author liu.bin 2017/9/30 10:08
     */
    public function getData(){
        $data = $this->readBuffer;
        $this->over();
        return $data;
    }






    /**
     * 是否继续读取buffer
     * @param string $buffer
     * @return bool false:不需要继续接收消息 ，true:继续接收消息
     * @author liu.bin 2017/9/29 14:37
     */
    abstract public function readBuffer($buffer='');








    /**
     * 数据解包
     * @param $buffer string
     * @return string
     * */
    abstract public function decode($buffer);








    /**
     * 数据打包
     * @param $buffer string
     * @return string
     * */
    abstract public function encode($buffer);








    /**
     * 读取结束
     * @return mixed
     * @author liu.bin 2017/9/30 9:57
     */
    abstract public function over();

}