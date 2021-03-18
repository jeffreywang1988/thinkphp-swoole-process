<?php


namespace app\Controller\Develop;



use app\Services\Common\AppTokenService;
use think\Request;
use think\Validate;
use think\facade\Config;
use think\facade\Log;

class ApiBaseController
{
	public $log;
	public $input;
	public $header;
	public $errCode;
	public $errMsg;
	public $develop;
	public $arrErrCode;
	public $appTokenService;
	public function __construct(Request $request)
	{
		try {

			$this->input = $request->param();
			$this->header = $request->header();
			$path = $request->path();
			$this->arrErrCode = Config::pull('errcode4develop');
			$aException = [
				'develop/v1/login',
			];
			if (!in_array($path, $aException)) {
				$authorization = isset($this->header['authorization']) ? $this->header['authorization'] : "";
				if (!$authorization) {
					die(json_encode(['code' => '403', 'msg' => 'forbidden', 'data' => '']));
				}
				$token = str_replace('Bearer ', '', $authorization);
				$this->appTokenService = new AppTokenService();
				$arr_token = $this->appTokenService->decode($token);
				if (empty($arr_token)) {
					die(json_encode(['code' => '403', 'msg' => 'forbidden', 'data' => '']));
				}
				$this->develop = $arr_token['params'];
			}
			$this->_initialize();
		}catch(\Exception $e) {
			Log::record($e->getFile().':'.$e->getLine().'//'.$e->getMessage());
		}
	}

	public function _initialize()
	{
	
	}
	
	/**
	 *  统一输入验证
	 *
	 * @param $arrRule
	 * @param array $inputData
	 * @return array|bool|mixed
	 */
	public function validateInput($arrRule, $inputData = [])
	{
		try {
			$data = $validateRule = $ruleMsg = [];
			$inputData = empty($inputData) ? $this->input : $inputData;
			foreach ($arrRule as $field => $arrItem) {
				$validateRule[$field] = array_shift($arrItem);
				foreach ($arrItem as $errKey => $errMsg) {
					$ruleMsg[$field . '.' . $errKey] = $errMsg;
				}
				$data[$field] = isset($inputData[$field]) ? $inputData[$field] : null;
			}
			
			$validate = Validate::make($validateRule, $ruleMsg);
			if (!$validate->check($inputData)) {
				$this->errCode = (int)$validate->getError() == 0 ? 500 : (int)$validate->getError();
				$this->errMsg = isset($this->arrErrCode[$this->errCode]) ? $this->arrErrCode[$this->errCode] : "system error";
				return false;
			}
			return $inputData;
		}catch(\Exception $e) {
			Log::record($e->getFile().':'.$e->getFile().'//'.$e->getMessage());
			return false;
		}
	}
	
	/**
	 * 统一输出
	 *
	 * @param array $data
	 * @return \think\response\Json
	 */
	public function responseData($data = [])
	{
		$ret = ['code' => 200, 'msg' => 'success'];
		if (is_numeric($data)) {
			$ret['code'] = $data;
			$ret['msg'] = isset($this->arrErrCode[$data]) ? $this->arrErrCode[$data] : '';
		} else {
			if ($this->errCode) {
				$ret = ['code' => $this->errCode, 'msg' => $this->errMsg];
			}
			if ($data) {
				$ret['data'] = $data;
			}
		}
		return json($ret);
	}
}