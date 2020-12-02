<?php
session_start();
session_register('code');
$height = 30;//������ ��������
$width = 120;//������ ��������
/*���������� ��������� �����, ��� ����*/
$_SESSION['code'] = rand(10000, 99999);//���������� 5-�� ������� �����
for($i = 0; $i < 5; $i++)
{
  	$m[$i] = substr($_SESSION['code'], $i, 1);
}
$image = imagecreate($width, $height); //������� ����������� 100x20
$backgroundColor = imagecolorallocate($image, 204, 216, 6); //������ ���� ��� ����
$noiseColor = imagecolorallocate($image, 100, 120, 180);//������ ���� �����
$color = imagecolorallocate($image, 239, 8, 8);//������ ���� ��� �����
for( $i = 0; $i < ($width*$height)/3; $i++ )
{
	imagefilledellipse($image, mt_rand(0,$width), mt_rand(0,$height), 1, 1, $noiseColor);
}
/* ������ �����*/
for( $i = 0; $i < ($width * $height)/150; $i++ )
{
	imageline($image, mt_rand(0, $width), mt_rand(0, $height), mt_rand(0, $width), mt_rand(0, $height), $noiseColor);
}

for($i = 0; $i < 5;$i++)
{
    imagestring($image, 5, $y += 15, 3, $m[$i], $color);
}

header("Cache-Control: no-cache, must-revalidate"); 
header("Pragma: no-cache"); 
header('Content-Type: image/jpeg');

imagejpeg($image);
?>
