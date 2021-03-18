<?php


namespace app\Controller\Agent;



use App\Services\Common\AppTokenService;
use think\Request;
use think\Validate;
use think\facade\Config;

class ApiBaseController
{
	public $input;
	public $header;
	public $errCode;
	public $errMsg;
	public $user;
	public $arrErrCode;
	public $appTokenService;
	public function __construct(Request $request)
	{
		$this->arrErrCode = Config::pull('errcode4agent');
		$this->input = $request->input();
		$this->header = $request->header();
		$path = $request->path();
		$aException = [
			'thirdparty/v1/agent/login',
		];
		if (in_array($path, $aException)) {
			$authorization = isset($this->header['authorization']) ? $this->header['authorization'] : "";
			if (!$authorization) {
				die(json_encode(['code' =>'403', 'msg' => 'forbidden', 'data' => '']));
			}
			$token = str_replace('Bearer ', '', $authorization);
			$this->appTokenService = new AppTokenService();
			$arr_token = $this->appTokenService->decode($token);
			if (!empty($arr_token)) {
				die(json_encode(['code' =>'403', 'msg' => 'forbidden', 'data' => '']));
			}
			$this->user = $arr_token['params'];
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
		$data = $validateRule = $ruleMsg = [];
		$inputData = empty($inputData) ? $this->input : $inputData;
		foreach ($arrRule as $field => $arrItem) {
			$validateRule[$field] = array_shift($arrItem);
			foreach($arrItem as $errKey => $errMsg) {
				$ruleMsg[$field.'.'.$errKey] = $errMsg;
			}
			$data[$field] = isset($inputData[$field]) ? $inputData[$field] : null;
		}
		
		$validate = Validate::make($validateRule, $ruleMsg);
		if (!$validate->check($inputData)) {
			$this->errCode = (int)$validate->getError();
			$this->errMsg = isset($this->arrErrCode[$this->errCode]) ? $this->arrErrCode[$this->errCode] : "system error";
			return false;
		}
		return $inputData;
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