<?php
/**
 * 公众号被动接受处理类
 */

$wx = new Wx();

class Wx {
    // 微信后台设置的token值 php7.1之后可以加权限 private
    const TOKEN = 'weixin';

    // 构造方法
    public function __construct(){
        // 判断是否是第1次接入 echostr
        if (!empty($_GET['echostr'])) {
            echo $this->checkSign();
        }else{
            // 接受处理数据
            $this->acceptMsg();
        }
    }

    /**
     * 接收公众号发过来的数据
     * @return [type] [description]
     */
    private function acceptMsg(){
        // 获取原生请求数据
        $xml = file_get_contents('php://input');
        # 把xml转换为object对象来处理
        $obj = simplexml_load_string($xml,'SimpleXMLElement',LIBXML_NOCDATA);
        // 写接受日志
        $this->writeLog($xml);
        // 处理回复消息
        // 1、判断消息类型
        // 2、根据不同的类型，回复处理不同信息
        // 判断类型
        $MsgType = $obj->MsgType;
        /*switch ($MsgType) {
            case 'text':
            $str = '<xml>
            <ToUserName><![CDATA[%s]]>
            </ToUserName>
            <FromUserName><![CDATA[%s]]>
            </FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[text]]>
            </MsgType>
            <Content><![CDATA[%s]]>
            </Content>
            </xml>';
            // 格式化替换输出
            $str = sprintf($str,$obj->FromUserName,$obj->ToUserName,time(),'公众号：'.$obj->Content);
            // 写日志
            $this->writeLog($str,2);
            echo $str;
            break;
        }*/
        $fun = $MsgType.'Fun';

        // 调用方法
        //echo $ret = $this->$fun($obj);
        echo $ret = call_user_func([$this,$fun],$obj);
        // 写发送日志
        $this->writeLog($ret,2);
    }


    // 处理回复文本
    private function textFun($obj){
        $content = $obj->Content;
        // 回复文本
        if('音乐' == $content){
            return $this->musicFun($obj);
        }
        $content = '公众号：'.$content;
        return $this->createText($obj,$content);
    }

    // 回复图片消息
    private function imageFun($obj){
        $mediaid = $obj->MediaId;
        return $this->createImage($obj,$mediaid);
    }

    // 回复音乐
    private function musicFun($obj){
        // 图片媒体ID
        $mediaid = '1QgKrdNTGOexznSBvGTiN7DTN3rPm1is0UhZ1Axfq7dBtMIf2zFL-MQH6Wb95DXc';
        // 音乐播放地址
        $url = 'https://wx.1314000.cn/mp3/ykz.mp3';
        return $this->createMusic($obj,$url,$mediaid);
    }

    // 生成文本消息XML
    private function createText($obj,string $content){
        $xml = '<xml>
		<ToUserName><![CDATA[%s]]>
		</ToUserName>
		<FromUserName><![CDATA[%s]]>
		</FromUserName>
		<CreateTime>%s</CreateTime>
		<MsgType><![CDATA[text]]>
		</MsgType>
		<Content><![CDATA[%s]]>
		</Content>
		</xml>';
        // 格式化替换输出
        $str = sprintf($xml,$obj->FromUserName,$obj->ToUserName,time(),$content);
        return $str;
    }

    // 生成图片消息xml
    private function createImage($obj,string $mediaid){
        $xml = '<xml>
		<ToUserName><![CDATA[%s]]>
		</ToUserName>
		<FromUserName><![CDATA[%s]]>
		</FromUserName>
		<CreateTime>%s</CreateTime>
		<MsgType><![CDATA[image]]>
		</MsgType>
		<Image>
		<MediaId><![CDATA[%s]]>
		</MediaId>
		</Image>
		</xml>';
        // 格式化替换输出
        $str = sprintf($xml,$obj->FromUserName,$obj->ToUserName,time(),$mediaid);
        return $str;
    }

    // 生成音乐XML消息
    private function createMusic($obj,string $url,string $mediaid){
        $xml = '<xml>
		<ToUserName><![CDATA[%s]]>
		</ToUserName>
		<FromUserName><![CDATA[%s]]>
		</FromUserName>
		<CreateTime>%s</CreateTime>
		<MsgType><![CDATA[music]]>
		</MsgType>
		<Music>
		<Title><![CDATA[夜空中最亮的星]]>
		</Title>
		<Description><![CDATA[一首非常好的歌]]>
		</Description>
		<MusicUrl><![CDATA[%s]]>
		</MusicUrl>
		<HQMusicUrl><![CDATA[%s]]>
		</HQMusicUrl>
		<ThumbMediaId><![CDATA[%s]]>
		</ThumbMediaId>
		</Music>
		</xml>';
        // 格式化替换输出
        $str = sprintf($xml,$obj->FromUserName,$obj->ToUserName,time(),$url,$url,$mediaid);
        return $str;
    }

    /**
     * 写日志
     * @param  string      $xml  写入的xml
     * @param  int|integer $flag 标识 1：请求 2：发送
     * @return [type]            [description]
     */
    private function writeLog(string $xml,int $flag=1){
        $flagstr = $flag == 1 ? '接受' : '发送';
        $prevstr = '【'.$flagstr.'】'.date('Y-m-d')."-----------------------------\n";
        $log = $prevstr.$xml."\n---------------------------------------------\n";
        // 写日志                       追加的形式去写入
        file_put_contents('wx.xml',$log,FILE_APPEND);
        return true;
    }




    /**
     * 初次接入校验
     * @return [type] [description]
     */
    private function checkSign(){
        // 得到微信公众号发过来的数据
        $input = $_GET;
        // 把echostr放在临时变量中
        $echostr = $input['echostr'];
        $signature = $input['signature'];
        // 在数组中删除掉
        unset($input['echostr'],$input['signature']);
        // 在数据中添加一个字段token
        $input['token'] = self::TOKEN;
        // 进行字典排序
        $tmpStr = implode( $input );
        // 进行加密操作
        $tmpStr = sha1( $tmpStr );

        // 进行比对
        if ($tmpStr === $signature) {
            return $echostr;
        }
        return '';
    }



}