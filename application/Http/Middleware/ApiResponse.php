<?php


namespace app\Http\Middleware;


use think\facade\Log;
use think\facade\Env;
class ApiResponse
{
	public $debug;
	public function __construct()
	{
		$this->debug = Env::get('api.HTTP_DEBUG_SWITCH');
	}
	
	public function handle($request, \Closure $next)
	{
		$response = $next($request);
		// 添加中间件执行代码
		$this->debug && Log::record($response->getContent(),'info');
		return $response;
	}
}