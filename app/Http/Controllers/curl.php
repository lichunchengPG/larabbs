<?php
/**
 * Created by PhpStorm.
 * User: lichuncheng
 * Date: 2018/5/3
 * Time: 22:51
 */

// 过滤img
$url = "http://www.baidu.com";
$str = file_get_contents($url);
$preg = '/<img[^>]*>/';
preg_match_all($preg, $str, $matches);
$matches = $matches[0];
$tmp = implode('', $matches);
echo $str;
exit;

// 获取src中的链接
$arr = [];
foreach ($matches as $v){
    $preg = '/http:\/\/*.jpg/';
    preg_match_all($preg, $v, $match);
    $arr[] = $match[0][0];
}
echo $arr;