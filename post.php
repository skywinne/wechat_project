<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/29
 * Time: 13:47
 */

include './index.php';

$files = $_FILES['media'];
// �õ���չ��
$extname = pathinfo($files['name'], PATHINFO_EXTENSION);
// �ϴ�����ļ���
$name = time().'.'.$extname;
//�ϴ�����������·��
$realpath = __DIR__.'/uploads/'.$name;
move_uploaded_file($files['tmp_name'], $realpath);

$wx = new WechatTest();
echo $wx->uploadMaterial($realpath, 'image', $_POST['is_forever']);