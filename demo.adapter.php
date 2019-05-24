<?php
//============================================
//			百宝云PHP类适配器例程
//						- 红船作品 20170211
//============================================

ini_set("display_errors","on");
include 'bby.php';

//请求适配器定义，必须继承bbyAdapter类
class MyAdapter extends bbyAdapter
{
	//_send方法在request发送到百宝云前调用，可以在此函数内校验data是否符合要求或修改data值，最终发送给百宝云的数据为_send返回值
	function _send($data, $method){
		//假设要求$data必须是数组，如果不符合要求，则返回false并设置错误信息
		//同理，可在_recv判断百宝云返回值是否符合要求
		if(!is_array($data)){
			$this->error = 'data必须为数组';
			return false;
		}
		return parent::_send($data, $method);
	}
	//_recv方法在百宝云返回后调用，可以在此函数内校验result是否符合要求或修改result值，request函数最终返回值为_recv返回值
	function _recv($result, $method){
		//将返回数据强制转换为大写
		//同理，可在_send中修改data的值再发送给百宝云
		$result = strtoupper($result);
		return parent::_recv($result, $method);
	}
}

//参数3：绑定请求适配器
$bby = new bbyApp('577c362764bd4c33a3a5dca8c792e55d', 10, new MyAdapter());

//亦可后期设置请求适配器，效果同上
//$bby->setAdapter(new MyAdapter());

//===========_send测试==============

//由于Adapter内限制data必须为数组，所以此处会返回false
var_dump($bby->request('test_string', 'get'));
echo '<br>';

//输出错误信息，可以看到错误为Adapter内设置的错误
print_r($bby->getError());
echo '<br>';

//===========_recv测试==============

//无论百宝云返回值是大写或是小写，经过Adapter转换后都是大写
print_r($bby->request(['key' => 'value'], 'get'));
echo '<br>';