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

    // 构造方法，执行方法前进行判断
    public function __construct()
    {
        if (!empty($_GET['echostr']))
        {
            echo $this->checkSign();
        }
    }

    private function checkSign()
    {
        $input = $_GET;
        // 将signature，timestamp取出
        $signature = $input['signature'];
        $echostr = $input['echostr'];
        $input['token'] = self::TOKEN;

        //删除signature，timestamp， 对input进行排序拼接
        unset($input['signature'], $input['echostr']);
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

    // 写日志
    private function writeLog(string $xml, int $flag = 1)
    {
        $flagstr = $flag == 1 ? '接受' : '发送';
        $prevstr = '【'.$flagstr.'】'.date('Y-m-d')."-----------------------------\n";
        $log = $prevstr.$xml."\n---------------------------------------------\n";
        // 写日志                       追加的形式去写入
        file_put_contents('wx_log.xml', $log, FILE_APPEND);
        return true;
    }

    //接收消息
    private function acceptMsg()
    {
        $xml = file_get_contents('php://input');
        # 把xml转换为object对象来处理
        $obj = simplexml_load_string($xml,'SimpleXMLElement',LIBXML_NOCDATA);
        // 写日志
        $this->writeLog($xml, 1);

        $msgType = $obj->MsgType;

        $fun = $msgType.'Fun';
        echo $ret = $this->$fun($obj);

        // 写发送日志
        $this->writeLog($ret,2);
    }

    // 处理文本消息
    private function textFun($obj)
    {
        $content = $obj->Content;
        $content = '公众号回复你：'.$content;
        return $this->createText($obj, $content);
    }

    // 创建回复文本消息
    private function createText($obj, $content)
    {
        $xml = '<xml>
 <ToUserName><![CDATA[%s]]></ToUserName>
 <FromUserName><![CDATA[%s]]></FromUserName>
 <CreateTime>%s</CreateTime>
 <MsgType><![CDATA[text]]></MsgType>
 <Content><![CDATA[%s]]></Content>
 </xml>';

        $str = sprintf($xml, $obj->FromUserName,$obj->ToUserName,time(),$content);
        return $str;
    }
}

$wechat = new WechatTest();

