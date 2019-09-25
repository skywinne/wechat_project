<?php
/**
 * Created by PhpStorm.
 * User: dsc1697
 * Date: 2019/9/25
 * Time: 10:37
 */

class WechatTest
{
    const TOKEN = 'wechatdsc';

    private function __construct()
    {
        if (!empty($_GET('echostr')))
        {
            echo $this->checkSign();
        }
    }

    private function checkSign()
    {
        $input = $_GET;
        echo $input;
        // 将signature，timestamp取出
        $signature = $input['signature'];
        $echostr = $input['echostr'];
        $input['token'] = self::TOKEN;

        //删除signature，timestamp， 对input进行排序拼接
        unset($input['signature'], $input['timestamp']);
        $tmpStr = implode($input);
        // 进行加密操作
        $tmpStr = sha1( $tmpStr );

        //将数据进行比对
        if($tmpStr == $signature)
        {
            return $echostr;
        }else{
            return '';
        }
    }
}