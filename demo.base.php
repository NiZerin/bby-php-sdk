<?php
//============================================
//			百宝云PHP类基本例程
//						- 红船作品 20170211
//============================================

ini_set("display_errors","on");
include 'bby.php';

//参数1：云应用Token
//参数2：全局超时秒数
//参数3：绑定请求适配器，参见demo.adapter.php
$bby = new bbyApp('577c362764bd4c33a3a5dca8c792e55d', 10);

//参数1：需要发送的数据，可以是字符串，也可以是数组
//参数2：请求方式，固定值get或post（不区分大小写，传入其他值函数返回false），默认为post
//返回值：失败，返回false；成功，则返回百宝云返回的值
//		（若百宝云返回json，则自动解析为数组，也可自定义解析方法，参见demo.adapter.php）
print_r($bby->request('字符串', 'get'));
echo '<br>';

print_r($bby->request([1,2,3], 'get'));
echo '<br>';

//调用request函数后可以读取result成员，值永远等于上一次调用request函数返回值
print_r($bby->result);
echo '<br>';

//若request/download/upload函数返回false，可调用此函数查看上次发生错误时，产生的错误信息
print_r($bby->getError());
echo '<br>';