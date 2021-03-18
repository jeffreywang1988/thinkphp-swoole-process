<?php


namespace app\Services\Develop;


use app\Models\LogsModel;
use think\facade\Log;
use think\Db;
class LogService extends BaseService
{
	public $logModel;

	public function __construct()
	{
		$this->logModel = new LogsModel();
	}
	
	public function load($data)
	{
		Db::startTrans();
		try {
			$this->logModel->store($data);
			Db::commit();
		}catch(\Exception $e){
			Db::rollback();
			Log::record($e->getFile().':'.$e->getLine().'//'.$e->getMessage());
			return false;
		}
		//第一次保存
		if ($data['log_type'] == 11) {
		
		} else if ($data['log_type'] == 12) {
		
		} else if ($data['log_type'] == 13) {
		
		}
	}
}