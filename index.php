<?php
/**
 * Created by mohuishou<1@lailin.xyz>.
 * User: mohuishou<1@lailin.xyz>
 * Date: 2016/5/1 0001
 * Time: 20:44
 */
require_once 'Image.class.php';

//$image=new \ImageOCR\Image("./img/inImgTemp.png");
$image=new \ImageOCR\Image("http://www.169ol.com/Mall/Code/getCode&1462104790492");
//$image->splitImage(2, __DIR__ . "/gray0.jpg");
//$image->imageHash();
$a=$image->find();
print_r($a);