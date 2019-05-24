<?php
//============================================
//			百宝云PHP类加解密例程
//						- 红船作品 20170211
//============================================

ini_set("display_errors","on");
include 'bby.php';

//所有加密函数/哈希函数的参数/返回值均与百宝云语法保持同步
//参见百宝云官方文档：
//http://help.baibaoyun.com/#%25E5%258A%259F%25E8%2583%25BD%25E8%258B%25B1%25E6%2596%2587(EN)&md5

echo 'MD5 : ';
print_r(bby::md5('123'));
echo '<br>';

echo 'AES : ';
print_r(bby::aesencrypt('1234567890', '123'));
echo '<br>';