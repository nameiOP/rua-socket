<?php
namespace rsk\server;


/**
 * Class server
 * @package server
 *
 */
class server extends baseServer{




	/**
	 * 客户端连接
	 * @var array
	 */
	private $_connect = array();


	/**
	 * 启动后界面
	 */
	public function displayUI(){

		echo '==================================================='.PHP_EOL;
		echo '---------   PHP VERSION:' .PHP_VERSION .'           ----------'.PHP_EOL;
		echo '---------   rua socket version :0.0.1    ----------'.PHP_EOL;
		echo '---------   io model : '.\Builder::$app->get('io').'   ----------'.PHP_EOL;
		echo '---------   listener  '.\Builder::$app->get('protocol')->name .'://'.$this->host . ':' . $this->port . ' ----------'.PHP_EOL;
		echo '==================================================='.PHP_EOL;
		echo '---------   please ctrl+c to stop server ----------'.PHP_EOL;
		echo PHP_EOL.PHP_EOL.PHP_EOL.PHP_EOL;

	}


	/**
	 * 创建 connect
	 * @param $clientFd
	 * @param $listenFd
	 * @param $peer
	 */
	public function setConnect($clientFd,$listenFd,$peer){

		$connConfig     = [
			'class'				=> '\rsk\server\connect',
			'status'			=> connect::STATUS_ACTIVE,
			'fd'				=> $clientFd,
			'peer'				=> $peer,
			'listenFd'			=> $listenFd,
			'acceptTime'		=> time(),
		];
		$connect = \Builder::createObject($connConfig);
		$this->_connect[$clientFd] = $connect;

	}




	/**
	 * 获取客户端连接
	 * @param $fd
	 * @return mixed
	 */
	public function getConnect($fd){
		return $this->_connect[$fd];
	}




	/**
	 * 注销 $connect
	 *
	 * @desc 全双工通信[客户端 和 服务端 都可以触发]
	 * @param $fd
	 */
	public function removeConnect($fd){
		// $server 注销 $connect
		unset($this->_connect[$fd]);
	}




}
