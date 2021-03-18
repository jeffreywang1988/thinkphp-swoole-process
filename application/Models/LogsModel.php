<?php


namespace app\Models;


use think\Model;

class LogsModel extends Model
{
	protected $pk = 'log_id';
	protected $table = 'zg_logs';
	protected $prefix = 'zg_';
	protected $fields = [
		'uid',
		'app_id',
		'user_uid',
		'user_type',
		'log_type',
		'log_data',
		'log_status',
		'log_md5',
		'created_at',
		'updated_at',
		'updated_at',
	];
	
	public function makeUid($salt='')
	{
		return md5(microtime() . $salt . rand(10000, getrandmax()));
	}
	public function store($data)
	{
		$data['uid'] = $this->makeUid();
		$this->table = 'zg_logs_'.date('Ym');
		if (!$this->field(['log_md5'])->where('log_md5', $data['log_md5'])->find()) {
			return $this->data($data)->save($data);
		}
	}
}