<?php


namespace app\Services\Develop;


use app\Services\Common\AppTokenService;
use think\Facade\Log;
use think\Facade\Env;

class DevelopService extends BaseService
{
	public $appTokenService;
	public function __construct()
	{
		$this->appTokenService = new AppTokenService();
	}
	
	public function login($data)
	{
		if ($data['username'] != Env::get('develop.DEVELOP_USERNAME')) {
			return 1004;
		}
		if (!password_verify(trim($data['password']), Env::get('develop.DEVELOP_PASSWORD'))) {
			return 1005;
		}
		$param = [
			'uid' => Env::get('develop.DEVELOP_UID'),
			'app_id' => Env::get('develop.DEVELOP_APP_ID'),
		];
		try {
			$token = $this->appTokenService->encode(Env::get('develop.DEVELOP_UID'), 'develop', $param);
			return [ "token" => $token];
		}catch(\Exception $e) {
			Log::record($e->getFile().':'.$e->getLine().'//'.$e->getMessage());
			return 500;
		}
		
	}
}