<?php


namespace app\Console\Commands;

use app\Consts\CacheKeys;
use app\Services\Develop\LogService;
use think\cache\driver\Redis;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Env;
use think\facade\Log;
use think\console\input\Argument;
use think\console\input\Option;

class DataReportQueue extends Command
{
	protected $logService;
	protected $log;
	protected $pidName = '';
	protected $pidFile = '';
	protected $pidDir = '';
	protected $mpid = 0;
	protected $works = [];
	protected $maxPrecess = 1;
	protected $new_index = 0;
	protected $debug;
	protected $redis;
	protected function configure()
	{
		$this->pidDir = Env::get('runtime_path').'/proc/';
		$this->setName('data:report')
			->addArgument('name',Argument::OPTIONAL,'agent')
			->addOption('child',null,Option::VALUE_OPTIONAL,'1')
			->setDescription('to make admin by develop request');
		$this->debug = Env::get('console.DATA_REPORT_DEBUG_SWITCH');
		$this->logService = new LogService();
	}
	
	protected function execute(Input $input, Output $output)
	{
		$this->maxPrecess = $input->getOption('child');
		echo $this->maxPrecess;
		//任务运行中...
		if ($this->getPid('data.report.queue')) {
			$output->writeln('data sync queue is running');
			return true;
		}
		//初始化进程id
		$this->setPid('data.report.queue');
		try {
			swoole_set_process_name(sprintf('/usr/local/php/bin/php '.Env::get('think_path').'/think data:report:queue master'));
			$this->mpid = posix_getpid();
			for ($i = 0; $i < $this->maxPrecess; $i++) {
				$this->createProcess($i);
			}
			$this->processWait();
		} catch (\Exception $e) {
			LOG::record('create data report queue process fail, because:'.$e->getMessage(),'error');
			return false;
		}
	}
	
	/**
	 * @param $index
	 * @return int
	 */
	public function createProcess($index)
	{
		$process_name = 'data.report.queue.'.$index;
		$this->setPid($process_name);
		$this->new_index = $index;
		$process = new \swoole_process([$this, 'queue'], false, false);
		$pid = $process->start();
		$this->works[$index] = $pid;
		return $pid;
	}
	
	/**
	 * 队列
	 *
	 * @throws \ErrorException
	 */
	public function queue($worker)
	{
		ini_set('default_socket_timeout', -1);
		$this->redis = new Redis();
		$this->redis->pconnect(Env::get('redis.REDIS_HOST'),Env::get('redis.REDIS_PORT'));
		swoole_set_process_name(sprintf('/usr/local/php/bin/php '.Env::get('think_path').'/artisan data:report:queue child=%d',$this->new_index));
		while(1){
			$this->checkMpid($worker);
			while ($json = $this->redis->rpop(CacheKeys::DATA_QUEUE)) {
				$this->debug && Log::record($json);
				$this->checkMpid($worker);
				$arr_data = json_decode($json,true);
				if($arr_data) {
					$this->logService->load($arr_data);
				}
				unset($json,$arr_data);
				usleep(50000);
			}
			usleep(50000);
		}
	}
	
	/**
	 * @param $worker
	 * @param $callback
	 */
	public function checkMpid(&$worker)
	{
		if (function_exists('pcntl_signal_dispatch')){
			pcntl_signal_dispatch();
		}
		if (!\swoole_process::kill($this->mpid, 0)){
			if (is_null($worker)) {
				$this->log->error(sprintf("child pid:%d,parent pid:%d,index:%d, fail",posix_getpid(),$this->mpid,$this->new_index));
				exit();
			} else {
				$worker->exit();
			}
			// 这句提示,实际是看不到的.需要写到日志中
			Log::record("Master process exited, I [{$worker['pid']}] also quit","error");
		}
	}
	
	/**
	 * @param $ret
	 * @throws \Exception
	 */
	public function rebootProcess($ret)
	{
		$pid = $ret['pid'];
		$index = array_search($pid, $this->works);
		if ($index !== false) {
			$index = intval($index);
			$new_pid = $this->createProcess($index);
			Log::record("rebootProcess: {$index}={$new_pid} Done",'error');
			return;
		}
		throw new \Exception('rebootProcess Error: no pid');
	}
	
	/**
	 * @throws \Exception
	 */
	public function processWait()
	{
		while (1) {
			if (count($this->works)) {
				$ret = \swoole_process::wait();
				if ($ret) {
					$this->rebootProcess($ret);
				}
			}else{
				break;
			}
		}
	}
	/**
	 * 获取pid
	 *
	 * @return int
	 */
	protected function getPid($name)
	{
		$this->pidName = strtoupper($name);
		$this->pidFile = $this->pidDir.$this->pidName.".pid";
		$pid = 0;
		if(!file_exists($this->pidFile)) {
			return $pid;
		}
		$pid = intval(file_get_contents($this->pidFile));
		if (!posix_kill($pid, SIG_DFL)) {
			$pid = 0;
		}
		Log::record($this->pidName.' process id:'.$pid,'info');
		return $pid;
	}
	
	/**
	 * 设置pid
	 *
	 */
	protected function setPid($name)
	{
		$this->pidName = strtoupper($name);
		$this->pidFile = $this->pidDir.$this->pidName.".pid";
		try {
			if (!is_dir($this->pidDir)) {
				mkdir($this->pidDir);
			}
			$fp = fopen($this->pidFile, 'w');
			fwrite($fp, posix_getpid());
			fclose($fp);
		} catch(\Exception $e) {
			Log::record('set pid fail at:'.$e->getLine().'//'.$e->getMessage(),'error');
		}
	}
}