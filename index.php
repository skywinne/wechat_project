<?php
/**
 * Created by PhpStorm.
 * User: dsc1697
 * Date: 2019/9/25
 * Time: 10:37
 */
$menu_list = include "./menu.php";

class WechatTest
{
    const TOKEN = 'wechatdsc';
    const APPID = 'wx352619ac0448bb52';
    const APPSECRET = '828c28f7c873b0db885fac549ed020c8';

    // 构造方法，执行方法前进行判断
    public function __construct()
    {
        if (!empty($_GET['echostr']))
        {
            echo $this->checkSign();
        }
//        else
//        {
//            $this->acceptMsg();
//        }
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
    private function createText($obj, string $content)
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

    //处理图片消息
    private function imageFun($obj)
    {
        return $this->createImage($obj);
    }

    //创建图片回复消息
    private function createImage($obj)
    {
        $xml = '<xml>
  <ToUserName><![CDATA[%s]]></ToUserName>
  <FromUserName><![CDATA[%s]]></FromUserName>
  <CreateTime>%s</CreateTime>
  <MsgType><![CDATA[image]]></MsgType>
  <Image>
    <MediaId><![CDATA[%s]]></MediaId>
  </Image>
</xml>';
        $str = sprintf($xml, $obj->FromUserName, $obj->ToUserName, time(), $obj->MediaId);
        return $str;
    }

    /**
     * @param string $url 要请求的url
     * @param string $ret post请求参数
     * @return mixed|string
     */
    private function http_request(string $url)
    {
        $ch = curl_init();
        //设置请求的url
        curl_setopt($ch, CURLOPT_URL, $url);
        //请求头关闭
        curl_setopt($ch, CURLOPT_HEADER, 0);
        //请求结果以字符串方式返回
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //关闭SSL验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        $data = curl_exec($ch);
        //判断有无出错
        if (curl_errno($ch) > 0)
        {
            echo curl_error($ch);
            $data = 'http请求出错！'.'['.curl_error($ch).']';
        }

        curl_close($ch);
        return $data;
    }

    /**
     * curl 提交post数据
     */
    private function http_request_post($url, $data)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        //判断有无出错
        if (curl_errno($curl) > 0)
        {
            echo curl_error($curl);
            $output = 'http请求出错！'.'['.curl_error($curl).']';
        }
        curl_close($curl);
        return $output;
    }

    /**
     * 获取accesstoken
     */
    public function getAccessToken()
    {
        $mem = new Memcache();
        $mem->connect('127.0.0.1', 11211);
        $men_name = 'auth_'.self::APPID;
        $value = $mem->get($men_name);
        if ($value)
        {
            return $value;
        }
        $wechat_url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s';
        $wechat_url = sprintf($wechat_url, self::APPID, self::APPSECRET);
        $json_data = $this->http_request($wechat_url);
        $arr = json_decode($json_data, True);
        $access_token = $arr['access_token'];
        $mem->add($men_name, $access_token, 0, 7200);
        return $access_token;
    }

    public function createMenu($menu)
    {
        if (is_array($menu))
        {
            $menu = json_encode($menu, JSON_UNESCAPED_UNICODE);
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=%s';
        $url = sprintf($url, $this->getAccessToken());
        $data = $this->http_request_post($url, $menu);
        return $data;
    }
}

$wechat = new WechatTest();
echo $wechat->createMenu($menu_list);
