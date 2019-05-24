<?php
//============================================
//			百宝云PHP类上下载例程
//						- 红船作品 20170211
//============================================

ini_set("display_errors","on");
include 'bby.php';

$bby = new bbyApp('577c362764bd4c33a3a5dca8c792e55d', 30);

//参数1：本地文件路径
//参数2：百宝云保存的文件路径，默认与本地文件名相同
//参数3：参见百宝云文档->POST上传文件->ondup参数，默认为类成员upload_ondup的值
//返回值：失败返回false；成功返回百宝云存储的文件全路径
var_dump($bby->upload('test.txt'));
echo '<br>';

//参数1：百宝云文件路径
//参数2：本地保存的文件路径，默认则不保存（返回值有区别！）
//返回值：失败返回false；成功时，若参数2填入路径，则返回file_put_contents的返回值，若参数2未填，则直接返回文件原始数据
var_dump($bby->download('test.txt'));
echo '<br>';

exit;
//==========复杂用法,注释掉exit后测试===========

$bby->upload_ondup = 'overwrite';

//把test.txt上传到百宝云，并存储为test.upload.txt，ondup为$bby->upload_ondup的值
var_dump($bby->upload('test.txt', 'test.upload.txt'));
echo '<br>';

//把test.txt上传到百宝云，并存储到folder/test.upload.txt，ondup为newcopy
var_dump($bby->upload('test.txt', 'folder/test.upload.txt', 'newcopy'));
echo '<br>';

//从百宝云下载folder/test.txt文件，并保存为test.download.txt
var_dump($bby->download('folder/test.txt', 'test.download.txt'));
echo '<br>';