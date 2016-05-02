<?php
/**
 * Created by mohuishou<1@lailin.xyz>.
 * User: mohuishou<1@lailin.xyz>
 * Date: 2016/5/1 0001
 * Time: 20:29
 */

namespace ImageOCR;
class Image{

    const HASH_W = 10;
    const HASH_H = 10;

    const CHAR_NUM=4;

    /**
     * 图像的大小信息
     * 宽度：$_image_size[0]，高度：$_image_size[1]
     * @var array
     */
    protected $_image_w;
    protected $_image_y;
    /**
     * 输入图像的句柄
     * @var resource
     */
    public $_in_img;

    protected $_hash_data;

    protected $_img_hash;


    public function __construct($imgPath) {
        //判断图像的类型
        $res = exif_imagetype($imgPath);

        switch($res) {
            case 1:
                $this->_in_img = ImageCreateFromGif($imgPath);
                break;
            case 2:
                $this->_in_img = ImageCreateFromJpeg($imgPath);
                break;
            case 3:
                $this->_in_img = ImageCreateFromPng($imgPath);
                break;
            case 6:
                $this->_in_img = ImageCreateFromWbmp($imgPath);
                break;
            default:
                throw new Exception("不支持的图片格式!");
                break;
        }

        //获取图像的大小信息
        $this->_image_w=getimagesize($imgPath)[0];
        $this->_image_h=getimagesize($imgPath)[1];

        $this->imageHash();

    }

    /**
     * @author mohuishou<1@lailin.xyz>
     */
    public function imageHash(){
        for($i = 0; $i < $this->_image_h; $i++) {
            for ($j = 0; $j < $this->_image_w; $j++) {
                $rgb = imagecolorat($this->_in_img,$j,$i);
                $rgb_array = imagecolorsforindex($this->_in_img, $rgb);
                if($rgb_array['red']<190&&$rgb_array['green']<190&&$rgb_array['blue']<190){

                    $data[$i][$j]=1;
                }else{

                    $data[$i][$j]=0;
                }
            }

        }


        $data=$this->removeHotSpots($data);
        $data=$this->removeHotSpots($data);
        $data=$this->removeHotSpots($data);
        $this->_hash_data=$data;
        $this->_hash_data=$this->removeHotSpots($data);
        $this->removeZero();


        $this->drew();

    }

    /**
     * 去除噪点
     * @author mohuishou<1@lailin.xyz>
     * @param $hash_data
     * @return mixed
     */
    public function removeHotSpots($hash_data){
        for($i = 0; $i < $this->_image_h; $i++) {
            for ($j = 0; $j < $this->_image_w; $j++) {
                if($hash_data[$i][$j]){
                    if($this->isHotSpots($i,$j,$hash_data)) $hash_data[$i][$j]=0;
                }
            }
        }
        return $hash_data;
    }

    /**
     * 判断是否是噪点
     * @author mohuishou<1@lailin.xyz>
     * @param $i
     * @param $j
     * @param $hash_data
     * @return bool ture:是噪点,false:不是
     */
    public function isHotSpots($i,$j,$hash_data){
        if($i == 0 || $j == 0 || $i == ($this->_image_h - 1) || $j == ($this->_image_w - 1)) return true;


        //待检查点为中心的九个点
        $points[0]=$hash_data[$i-1][$j-1];
        $points[1]=$hash_data[$i-1][$j];
        $points[2]=$hash_data[$i-1][$j+1];
        $points[3]=$hash_data[$i][$j-1];
        $points[4]=$hash_data[$i][$j];//待检查点
        $points[5]=$hash_data[$i][$j+1];
        $points[6]=$hash_data[$i+1][$j-1];
        $points[7]=$hash_data[$i+1][$j];
        $points[8]=$hash_data[$i+1][$j+1];

        $count=0;

        foreach ($points as $v){
            if($v){
                $count++;
            }
        }

        return $count<4;
    }


    /**
     * @author mohuishou<1@lailin.xyz>
     * @param null $data
     * @return null
     */
    public function removeZero($data=null){
        if($data==null) $data=&$this->_hash_data;
        foreach ($data as $k=>$v){
            if(implode("",$v)==0) unset($data[$k]);
        }

        return $data;
    }

    /**
     * @author mohuishou<1@lailin.xyz>
     * @param null $data
     */
    public function drew($data=null){
        if($data==null) $data=$this->_hash_data;
        foreach ($data as $v){
            foreach ($v as $val){
                if($val){
                    echo "■";
                }else{
                    echo "□";
                }
            }
            echo "\n";
        }
    }

    /**
     * @author mohuishou<1@lailin.xyz>
     * @param $n
     * @return array
     */
    public function splitImage($n){
        $data=[];
        $a=$this->_image_w/self::CHAR_NUM;
        for($i=$n*$a;$i<($n+1)*$a;$i++){
            $column=array_column($this->_hash_data,$i);
            if(implode("",$column)!=0){
                $data[]=$column;
            }
        }

        $out_img_w=count($data)+4;
        $out_img_h=count($data[0])+4;

        $out_img = imagecreatetruecolor($out_img_w,$out_img_h);//创建一幅真彩色图像
        $bg=imagecolorallocate($out_img, 255, 255, 255);//背景色画为白色
        imagefill($out_img, 0,0, $bg);

        //一列一列的进行画图
        foreach ($data as $k=>$v){
            foreach ($v as $key=> $val){
                $color=255;
                if($val) $color=0;
                $c = imagecolorallocate($out_img, $color, $color, $color);
                imagesetpixel($out_img, $k+2,$key+2, $c);
            }
        }



//        imagepng($out_img,'./test.png');

        //图像标准化
        $hash_img=$this->imageStandard($out_img,$out_img_w,$out_img_h);

        //图像二值化
        for($i = 0; $i <self::HASH_H; $i++) {
            for ($j = 0; $j <self::HASH_H; $j++) {
                $rgb = imagecolorat($hash_img,$j,$i);
//                echo $rgb."\n";
                if($rgb<10000000){
                    $hash_img_data[]=1;
                }else{
                    $hash_img_data[]=0;
                }
            }
        }
        imagepng($hash_img,'./test1.png');
        return $hash_img_data;





    }

    /**
     * 图像标准化，将旋转的图像标准化
     * @author mohuishou<1@lailin.xyz>
     * @param $img
     * @return resource 标准的图像资源句柄
     */
    public function imageStandard($img){
        $min_w=999;
        $oimg=$img;

        $c=imagecolorallocate($img, 255, 255, 255);
        for($i=-30;$i<30;$i++){
            $simg=imagerotate($img,$i,$c);
//            //计算字符宽度
            $simg_hash_data=$this->getWidth($simg);
            $w=count($simg_hash_data);
            if($w<$min_w){
                $oimg_hash_data=$simg_hash_data;
                $min_w=$w;
            }

        }

        $out_img_w=count($oimg_hash_data);
        $out_img_h=count($oimg_hash_data[0]);

        $out_img = imagecreatetruecolor($out_img_w,$out_img_h);//创建一幅真彩色图像
        $bg=imagecolorallocate($out_img, 255, 255, 255);//背景色画为白色
        imagefill($out_img, 0,0, $bg);

        //一列一列的进行画图
        foreach ($oimg_hash_data as $k=>$v){
            foreach ($v as $key=> $val){
                $color=255;
                if($val) $color=0;
                $c = imagecolorallocate($out_img, $color, $color, $color);
                imagesetpixel($out_img, $k,$key, $c);
            }
        }

//        imagepng($out_img,'./0.png');


        $hash_img = imagecreatetruecolor(self::HASH_W, self::HASH_H);
        imagecopyresized($hash_img, $out_img, 0, 0, 0, 0, self::HASH_W,self::HASH_H,$out_img_w,$out_img_h);


        return $hash_img;

    }

    /**
     * 获取图像的宽度
     * @author mohuishou<1@lailin.xyz>
     * @param $img 图像资源句柄
     * @return int
     */
    public function getWidth($img){
        //根据资源句柄获取整个图像的高与宽
        $img_w=imagesx($img);
        $img_h=imagesy($img);

        //图像二值化
        for($i = 0; $i <$img_h; $i++) {
            for ($j = 0; $j <$img_w; $j++) {
                $rgb = imagecolorat($img,$j,$i);
                if($rgb==0){
                    $data[$i][$j]=1;
                }else{
                    $data[$i][$j]=0;
                }
            }
        }

        //去掉零行
        $data=$this->removeZero($data);

        //按列取图像获取宽度
        for($i=0;$i<$img_w;$i++){
            $column=array_column($data,$i);
            if(implode("",$column)!=0){
                $data1[]=$column;
            }
        }


        //返回
        return $data1;


    }


    /**
     * @author mohuishou<1@lailin.xyz>
     * @return array
     */
    public function find() {
        $res = [];

        require_once "DB.class.php";
        $db=new DB();
        $samples=$db->get();

        for($i = 0; $i < self::CHAR_NUM; $i++) {
            $hash= $this->splitImage($i);

            $res[]=$this->compare($hash,$samples);
        }

        return $res;
    }

    /**
     * @author mohuishou<1@lailin.xyz>
     * @param $hash
     * @param $samples
     * @return mixed
     */
    public function compare($hash,$samples) {


        $s = 0;
        foreach($samples as $value) {
            $samples_hash_data=str_split($value['hash_data']);
            $c = count( array_intersect_assoc ($samples_hash_data, $hash) );
            if($c>$s){
                $s=$c;
                $code=$value['code'];
            }
//            if($s>0.8*count($samples_hash_data)){
//                echo 0.8*count($samples_hash_data);
//                return $value['code'];
//
//                break;
//            }
        }
        return $code;
    }
}