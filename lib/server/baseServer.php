<?php
namespace rsk\server;


use rua\traits\eventable;
use rua\traits\macroable;


/**
 * Class server
 * @package server
 *
 *
 * 服务器消息由轮训或事件触发自动接收，采用自定义协议处理数据包，通过回调通知应用程序
 *
 */
abstract class baseServer {



	use macroable,eventable;


	/**
	 * 服务器启动
	 * @var string
	 */
	const START = 'rua_server_start';


	/**
	 * 服务器重启
	 * @var string
	 */
	const RESTART = 'rua_server_restart';


	/**
	 * 服务器连接
	 * @var string
	 */
	const ACCEPT = 'rua_server_accept';


	/**
	 * 接收消息
	 * @var string
	 */
	const MESSAGE = 'rua_server_message';




	/**
	 * 服务器关闭
	 * @var string
	 */
	const STOP = 'rua_server_stop';




	/**
	 * 监听服务器
	 * @var array
	 *
	 * tcp://0.0.0.0:8000
	 */
	public $server = array();




	/**
	 * listen socket 三元组
	 *
	 * @var array
	 */
	public $listen = array();



	/**
	 * 构造器
	 */
	public function __construct(){
		$this->init();
	}




	/**
	 * 初始化
	 */
	private function init(){}




    /**
     * 启动服务器
     * @author liu.bin 2017/9/27 14:56
     */
    public function start(){


        //展示ui
        $this->displayUI();
		//触发事件
		$this->trigger(self::START);
        // 启动 io 模型
		\Builder::$app->get('io')->run();

	}




	/**
	 * 发送消息
	 * @param int $fd socket id
	 * @param string $data
	 * @return int
	 */
	public function send($fd,$data){
		return \Builder::$app->get('io')->setWriteBuffer($fd,$data);
	}




	/**
	 * 关闭连接,服务端主动触发
	 *
	 *
	 * @param int $fd socket id
	 * @return bool
	 */
	public function close($fd){

		//注销 $connect
		$this->removeConnect($fd);

		//通知 io 关闭 socket
		\Builder::$app->get('io')->removeEvent($fd);
	}





	/**
	 * 注销 $connect
	 *
	 * @desc 全双工通信[客户端 和 服务端 都可以触发]
	 * @param $fd
	 */
	abstract public function removeConnect($fd);





	/**
	 * 启动界面
	 */
	abstract public function displayUI();

}
