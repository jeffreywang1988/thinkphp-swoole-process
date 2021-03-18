<?php


namespace app\Controller\Develop\V1;


use app\Controller\Develop\ApiBaseController;
use app\Services\Develop\DevelopService;

class DevelopController extends ApiBaseController
{
	public $developService;
	public function _initialize()
	{
		parent::_initialize(); // TODO: Change the autogenerated stub
		$this->developService = new DevelopService();
	}
	
	public function login()
	{
		$rule = [
			'username' => ['require|max:11','require' => 1000, 'max' => 1001],
			'password' => ['require', 'require' => 1002]
		];
		if (!$this->validateInput($rule)) {
			return $this->responseData();
		}
		$data = $this->developService->login($this->input);
		return $this->responseData($data);
	}
}