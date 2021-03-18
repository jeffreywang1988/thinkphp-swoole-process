<?php


namespace app\Http\Middleware;

use think\facade\Env;
use think\facade\Log;
class ApiRequest
{
	public $debug;
	public function __construct()
	{
		$this->debug = Env::get('api.HTTP_DEBUG_SWITCH');
	}
	
	public function handle($request, \Closure $next)
	{
		$this->debug && Log::record('request:');
		$this->debug && Log::record($request->param(),'info');
		return $next($request);
	}
}