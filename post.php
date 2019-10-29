<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/29
 * Time: 13:47
 */

include './index.php';

$files = $_FILES['media'];
// 得到扩展名
$extname = pathinfo($files['name'], PATHINFO_EXTENSION);
// 上传后的文件名
$name = time().'.'.$extname;
//上传到服务器的路径
$realpath = __DIR__.'/uploads/'.$name;
move_uploaded_file($files['tmp_name'], $realpath);

$wx = new WechatTest();
echo $wx->uploadMaterial($realpath, 'image', $_POST['is_forever']);