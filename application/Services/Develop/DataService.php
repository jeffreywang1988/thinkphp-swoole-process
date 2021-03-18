<?php


namespace app\Services\Develop;


use app\Consts\CacheKeys;
use Redis;
use think\Facade\Log;
use think\Facade\Env;

class DataService
{
	public $redis;
	public function __construct()
	{
		$this->redis = new Redis();
		$this->redis->connect(Env::get('redis.REDIS_HOST'), Env::get('redis.REDIS_PORT'));
	}
	
	public function store($app, $data)
	{
		try {
			$log = [
				'app_id' => $app['app_id'],
				'user_uid' => isset($data['agent_uid'])? $data['agent_uid'] : (isset($data['user_uid']) ? $data['user_uid'] : ""),
				'user_type' => isset($data['agent_uid'])? 1 : (isset($data['user_uid']) ? 2 : 3),
				'log_type' => $data['type'],    //11:新增管理员，12:更新管理员,13注销管理员
				'log_data' => is_string($data['data']) ? $data['data'] : json_encode($data['data']),
				'log_status' => 1,      //状态1：待执行，2已执行，3已检查
				'log_md5' => $data['md5'],
			];
			$this->redis->lPush(CacheKeys::DATA_QUEUE,json_encode($log));
		} catch(\Exception $e) {
			Log::error("sync data fail.".$e->getLine().'//'.$e->getMessage());
			return 500;
		}
		return 200;
	}
}