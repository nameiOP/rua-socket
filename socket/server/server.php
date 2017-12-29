<?php
namespace rsk\server;


use rsk\loop\ioloop;
use rsk\loop\select;
use rsk\event\startEvent;
use rua\traits\eventable;
use rua\traits\macroable;
use rsk\traits\socketable;
use rsk\traits\streamsocketable;

/**
 * Class server
 * @package server
 *
 *
 * 服务器消息由轮训或事件触发自动接收，采用自定义协议处理数据包，通过回调通知应用程序
 *
 */
class server {


    //use streamsocketable,macroable,eventable;
	use socketable,macroable,eventable;

	/**
	 * 服务器启动
	 * @var string
	 */
	const EVENT_RSK_START = 'rua_server_start';


	/**
	 * 服务器重启
	 * @var string
	 */
	const EVENT_RKS_RESTART = 'rua_server_restart';


	/**
	 * 服务器连接
	 * @var string
	 */
	const EVENT_RSK_CONNECT = 'rua_server_connect';


	/**
	 * 接收消息
	 * @var string
	 */
	const EVENT_RSK_RECEIVE = 'rua_server_receive';

	/**
	 * 服务器关闭
	 * @var string
	 */
	const EVENT_RSK_STOP = 'rua_server_stop';



	/**
	 * 连接对象集合
	 * @var \rua\helpers\collection
	 */
	public $connCollect;


	/**
	 * socket读 集合
	 * @var \rua\helpers\collection
	 */
	public $socketReadCollect;




	/**
	 * socket写 集合
	 * @var \rua\helpers\collection
	 */
	public $socketWriteCollect;


	/**
	 * 主机
	 * @var string
	 */
	public $host = '';


	/**
	 * 协议
	 * @var string
	 */
	public $protocol = '';


	/**
	 * 协议类
	 * @var string
	 */
	public $protocolClass = '';



	/**
	 * 端口
	 * @var int
	 */
	public $port = 0;


	/**
	 * 启动后界面
	 */
	public function displayUI(){

		echo '========================================'.PHP_EOL;
		echo '----- PHP VERSION:' .PHP_VERSION .'           -----'.PHP_EOL;
		echo '----- rua socket version :0.0.1    -----'.PHP_EOL;
		echo '----- listener:'.$this->protocol .'://'.$this->host . ':' . $this->port . ' -----'.PHP_EOL;
		echo '----- please ctrl+c to stop server -----'.PHP_EOL;
		echo '========================================'.PHP_EOL;

	}


    /**
     * todo
     * 初始化init
     * @author liu.bin 2017/10/31 11:54
     */
    public function init(){


    }


    /**
     * 启动服务器
     * @author liu.bin 2017/9/27 14:56
     */
    public function start(){


		/**
		 * 创建 socket
		 */
        $result = $this->createSocket($this->host,$this->port);
		if(false === $result){
			//console('socket error');
            throw new \Exception('socket error');
		}


        //初始化服务器信息
        $this->init();

        //展示ui
        $this->displayUI();

		//server socket
		$this->addConn($this->fd,$this);

		//触发事件
		$startEvent = new startEvent();
		$startEvent->server = $this;
		$this->trigger(self::EVENT_RSK_START,$startEvent);


        //默认采用socket_select方式接收客户端连接
        (new select())->loop();
		//(new ioloop())->loop();

	}


	/**
	 * 发送消息
	 */
	public function send($fd,$data){
		$socket = $this->socketReadCollect->get($fd);
		return $this->socketSend($socket,$data);
	}


	/**
	 * 关闭
	 */
	public function close($fd){
		$socket = $this->socketReadCollect->get($fd);
		$this->removeConn($fd);
		return $this->socketClose($socket);

	}


	/**
	 *
	 * @param $id int
	 * @param $conn \rsk\server\connect | \rsk\server\server
	 * @return bool
	 */
	public function addConn($fd,$conn){
		if(empty($this->connCollect)){
			$this->connCollect = collect([$conn]);
		}else{
			$this->connCollect->put($fd,$conn);
		}

		if(empty($this->socketReadCollect)){
			$this->socketReadCollect = collect([$conn->getSocket()]);
		}else{
			$this->socketReadCollect->put($fd,$conn->getSocket());
		}


		if(empty($this->socketWriteCollect)){
			$this->socketWriteCollect = collect([$conn->getSocket()]);
		}else{
			$this->socketWriteCollect->put($fd,$conn->getSocket());
		}


		return true;
	}

	/**
	 * 移除连接对象
	 *
	 */
	public function removeConn($fd){
	
		if(! empty($this->connCollect)){
			$this->connCollect->forget($fd);
		}

		if(! empty($this->socketReadCollect)){
			$this->socketReadCollect->forget($fd);
		}


		if(! empty($this->socketWriteCollect)){
			$this->socketWriteCollect->forget($fd);
		}

	}

}
