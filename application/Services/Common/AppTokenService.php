<?php


namespace app\Services\Common;

use app\Consts\CacheKeys;
use think\facade\Cache;
use think\facade\Log;
use think\facade\Env;

class AppTokenService
{
	public $log;
	public $redis;
	public function __construct()
	{
		$this->redis = Cache::store('redis');
	}
	
	/**
	 * encode jwt
	 *
	 * @param string $uid
	 * @param string $id
	 * @param mixed $params
	 * @param string $alg
	 * @param int    $jwt_expire

	 
	 * @return string
	 */
	public  function encode($uid, $identity, $params = [],  $jwt_expire = 0)
	{
		try {
			$app_id = Env::get('develop.DEVELOP_APP_ID');
			$app_secret = Env::get('develop.DEVELOP_APP_SECRET');
			$jwt_expire = ($jwt_expire > 0) ? $jwt_expire : Env::get('develop.DEVELOP_TOKEN_EXPIRE');
			$header = ['type' => 'JWT', 'alg' => 'SHA256'];
			$header = base64_encode(json_encode($header));
			$payload = [
				'iss' => 'developer@zhiguohulian.com',
				'iat' => time(),
				'exp' => time() + $jwt_expire,
				'uid' => $uid,                         //uid
				'id' => $identity,                     //identify agent,user,develop
			];
			$payload['params'] = $params;
			$payload = base64_encode(json_encode($payload));                //可自定义对称加密算法
			$sign = hash_hmac('SHA256', "{$header}.{$payload}", $app_secret);
			$this->redis->set(CacheKeys::DEVELOPER_TOKEN_SIGN .$app_id.':'. strtoupper($identity).':'.$uid, $sign, $jwt_expire);
			return "{$header}.{$payload}.{$sign}.{$app_id}";
		}catch(\Exception $e) {
			Log::record($e->getFile().':'.$e->getLine().'//'.$e->getMessage(),'error');
			return false;
		}
	}
	
	/**
	 * decode jwt
	 *
	 * @param string $token
	 * @return bool|mixed
	 */
	public function decode($token)
	{
		try {
			$aToken = explode('.', $token);
			if (count($aToken) != 4) {
				return false;
			}

			list($header, $payload, $sign, $app_id) = $aToken;
			$aHeader = json_decode(base64_decode($header), true);
			if (!isset($aHeader['alg']) && $aHeader['alg'] != 'SHA256') {
				return false;
			}
			$app_secret = Env::get('develop.DEVELOP_APP_SECRET');

			if (!$app_secret) {
				return false;
			}
			if ($sign != hash_hmac('SHA256', "{$header}.{$payload}", $app_secret)) {
				return false;
			}
			$payload = json_decode(base64_decode($payload), true);
			if ($payload['exp'] < time()) {
				return false;
			}
			if ($payload['iat'] > time()) {
				return false;
			}

			$token_sign = $this->redis->get(Cachekeys::DEVELOPER_TOKEN_SIGN.$app_id.':' .strtoupper($payload['id']).':'. $payload['uid']);

			if ($token_sign != $sign) {
				return false;
			}
			return $payload;
		}catch(\Exception $e){
			Log::record($e->getFile().':'.$e->getLine().'//'.$e->getMessage(),'error');
			return false;
		}
	}
	
	/**
	 * @param $identity
	 * @param $uid
	 * @return bool
	 */
	public function destroy($identity, $uid)
	{
		$app_id = Env::get('develop.DEVELOP_APP_ID');
		return $this->redis->rm(Cachekeys::DEVELOPER_TOKEN_SIGN.$app_id.':' .strtoupper($identity).':'. $uid);
	}
}