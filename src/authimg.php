<?
/*

* Filename: authimg.php

*/
//生成验证码图片
Header("Content-type: image/PNG");

srand((double)microtime()*1000000);

$im = imagecreate(58,22);

$black = ImageColorAllocate($im, 0,0,0);

$white = ImageColorAllocate($im, 255,255,255);

$gray = ImageColorAllocate($im, 200,200,200);

imagefill($im,10,10,$black);



//将四位整数验证码绘入图片

imagestring($im, 5, 10, 2, $HTTP_GET_VARS['authcode'], $white);
//imagestring($im, 5, 10, 8, $_SESSION['authcode'], $white);



for($i=0;$i<50;$i++) //加入干扰象素

{

imagesetpixel($im, rand()%70 , rand()%30 , $gray);

}



ImagePNG($im);

ImageDestroy($im);

　　?> 