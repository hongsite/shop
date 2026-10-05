<?php
/*
*@author 牛头
*@company 鸿思特科技
*@function 微信二维码生成
*@date 2018-06-23
*@version hst-v1.0.0
*/
namespace Models\Wxqrcode;
class Wxqrcode{
	private $saveRule='uniqid';	
	public function getpng($arr=null){		
		$qrfile = ROOT.'App/Model/phpqrcode.php';
		if(!is_file($qrfile)){
			echo '文件不存在';
			return ;
		}
		require $qrfile;		
		$js['msg'] = '';
		$js['status'] = false;
		
		$url = isset($arr['url']) ? $arr['url']:'';
		
		if($url==''){
			$js['msg'] = '网址为空';
			return $js;
		}	
		$filenamelogo = isset($arr['logo']) ? $arr['logo']:'';
		if($filenamelogo!='' && !is_file($filenamelogo)){
			$js['msg'] = 'logo地址不存在';
			return $js;
		}
		$month = date('Ym');
		$path = ROOT.'static/qrcode/'.$month.'/';
		creatdir($path);
		$filename = $this->getSaveName('png');
		$newfilename = $month.'/'.$filename;
		$fullfilename = $path.$filename;

		if($filenamelogo==''){
			\QRcode::png($url,$fullfilename);
			$this->resize($fullfilename,300,300);
			if(file_exists($fullfilename)){
				if(file_exists($fullfilename)){				
					$this->addborder($fullfilename,4);
				}				
				$js['status'] = true;
				$js['data'] = $newfilename;
			}else{
				$js['msg'] = '生成文件失败';
			}			
			return $js;
		}else{				
			\QRcode::png($url,$fullfilename);
			$this->resize($fullfilename,300,300);
			if(file_exists($fullfilename)){				
				$this->addborder($fullfilename,4);
			}
			if(file_exists($filenamelogo)){
				$QR_string = imagecreatefromstring(file_get_contents($fullfilename));  
				$logo_string = imagecreatefromstring(file_get_contents($filenamelogo));  
				$QR_width = imagesx($QR_string);  
				$QR_height = imagesy($QR_string);  
				$logo_width = imagesx($logo_string);  
				$logo_height = imagesy($logo_string);  
				$logo_qr_width = $QR_width / 5;  
				$scale = $logo_width / $logo_qr_width;  
				$logo_qr_height = $logo_height / $scale;  
				$from_width = ($QR_width - $logo_qr_width) / 2;  
				imagecopyresampled($QR_string, $logo_string, $from_width, $from_width, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);				
				
				imagepng($QR_string,$fullfilename);					
				if(file_exists($fullfilename)){					
					$js['status'] = true;
					$js['data'] = $newfilename;
				}else{
					$js['msg'] = '生成文件失败';
				}
			}else{
				$js['msg'] = 'LOGO文件不存在';				
			}
			return $js;
		}
	}
	public static function png($text, $outfile = false, $level = QR_ECLEVEL_L, $size = 3, $margin = 4, $saveandprint=false){
        $enc = QRencode::factory($level, $size, $margin);
        return $enc->encodePNG($text, $outfile, $saveandprint=false);
    }
	public static function factory($level = QR_ECLEVEL_L, $size = 3, $margin = 4){
            $enc = new QRencode();
            $enc->size = $size;
            $enc->margin = $margin;
            
            switch ($level.'') {
                case '0':
                case '1':
                case '2':
                case '3':
                        $enc->level = $level;
                    break;
                case 'l':
                case 'L':
                        $enc->level = QR_ECLEVEL_L;
                    break;
                case 'm':
                case 'M':
                        $enc->level = QR_ECLEVEL_M;
                    break;
                case 'q':
                case 'Q':
                        $enc->level = QR_ECLEVEL_Q;
                    break;
                case 'h':
                case 'H':
                        $enc->level = QR_ECLEVEL_H;
                    break;
            }
            
            return $enc;
    }
	function addborder($filename,$px=4){
		$ext = getext($filename);
		if($ext=='png') {
			$src_img = imagecreatefrompng($filename);
		} elseif($ext== 'jpg'||$ext=='jpeg'){
			$src_img = imagecreatefromjpeg($filename );
		}

		//通过php的函数imagesx()获得图像资源的宽度、imagesy()获得图像资源的高度
		$src_w = imagesx($src_img);
		$src_h = imagesy($src_img);
		
		//1. 绘制图像资源（创建一个画布）
		$image = imagecreatetruecolor(($src_w+$px*2),($src_h+$px*2));
		//2. 先分配一个绿色
		$green = imagecolorallocate($image, 255, 255, 255);
		//3. 使用绿色填充画布
		imagefill($image, 0, 0, $green);

		//4. 在画布中绘制图像
		$bai = imagecolorallocate($image, 255, 255, 255);
		//参数1：$dst_img  destination，目标图像资源
		//参数2：$src_img  原图资源,通过imagecreatefromjpeg png等创建的
		//参数3、4：目标图像资源的x、y坐标
		//参数5、6：原图采集的起点x、y坐标
		//参数7、8：原图的宽度、高度		
		imagecopy($image,$src_img,$px,$px,0,0,$src_w,$src_h);

		if($ext=='png'){
			$ret = imagepng($image,$filename );
		} elseif($ext== 'jpg' ||$ext=='jpeg'){
			$ret = imagejpeg($image,$filename );
		}

		//6. 销毁图像资源
		imagedestroy($image);
	}
	function bebingbg($fullfilename,$filenamelogo){
		$QR_string = imagecreatefromstring(file_get_contents($fullfilename));  
		$logo_string = imagecreatefromstring(file_get_contents($filenamelogo));  
		$QR_width = imagesx($QR_string);  
		$QR_height = imagesy($QR_string);  
		$logo_width = imagesx($logo_string);  
		$logo_height = imagesy($logo_string);  
		$logo_qr_width = $QR_width / 2;  
		$scale = $logo_width / $logo_qr_width;  
		$logo_qr_height = $logo_height / $scale;  
		$from_width = ($QR_width - $logo_qr_width) / 2;  
		imagecopyresampled($QR_string, $logo_string, $from_width, $from_width, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);				
		imagejpeg($QR_string,$filenamelogo);//这里第二个参数为你要保存的路径
	}

	function resize($imgsrc, $width, $height = 0) {  //参数定义为：源图片，目标宽度，目标高度 
		$data = file_get_contents($imgsrc);
		$img_s = imagecreatefromstring($data);  // 获得源图片资源
		$width_s = imagesx($img_s);  // 获得源图片宽度
		$height_s = imagesy($img_s);  // 获得源图片高度

		$width_t = $width;  // 获得目标图片宽度
		$height_t = ($height == 0) ? $width : $height;  // 获得目标图片高度

		$image_t = imagecreatetruecolor($width_t, $height_t); //创建一个彩色的底图 
		$white = imagecolorallocate($image_t, 255, 255, 255);
		imagefill($image_t, 0, 0, $white); // 初始化背景为白色

		if (($width_s / $height_s) < ($width_t / $height_t)) {
			$_final['width'] = $width_s * $height_t / $height_s;
			$_final['height'] = $height_t;
			$dst['x'] = ($width_t - $_final['width']) / 2;
			$dst['y'] = 0;
		} else {
			$_final['width'] = $width_t;
			$_final['height'] = $width_t * $height_s / $width_s;
			$dst['x'] = 0;
			$dst['y'] = ($height_t - $_final['height']) / 2;
		}

		ImageCopyResized($image_t, $img_s, $dst['x'], $dst['y'], 0, 0, $_final['width'], $_final['height'], $width_s, $height_s);
		$rel = imagepng($image_t, $imgsrc);
		imagedestroy($img_s);
		imagedestroy($image_t);
		return $rel ? $imgsrc : false;
	}

	function get_hash(){
		
		
		if(function_exists('md5_file')) {
        $fun =  'md5_file';
        return  $fun($this->autoCharset($file['savepath'].$file['savename'],'utf-8','gbk'));
        }else{
			return false;
		}

		
		
		$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()+-';
		$random = $chars[mt_rand(0,73)].$chars[mt_rand(0,73)].$chars[mt_rand(0,73)].$chars[mt_rand(0,73)].$chars[mt_rand(0,73)];//Random 5 times
		$content = uniqid().$random;//类似5443e09c27bf4aB4uT
		return sha1($content);
	}
	private function getSaveName($ext) {
        $rule = $this->saveRule;
        if(function_exists($rule)) {
			//使用函数生成一个唯一文件标识号
			$saveName = $rule().".".$ext;
        }else {
			//使用给定的文件名作为标识号
			$saveName = $rule.".".$ext;
        }

        return $saveName;
    }
	// 自动转换字符集 支持数组转换
    private function autoCharset($fContents, $from='gbk', $to='utf-8') {
        $from   = strtoupper($from) == 'UTF8' ? 'utf-8' : $from;
        $to     = strtoupper($to) == 'UTF8' ? 'utf-8' : $to;
        if (strtoupper($from) === strtoupper($to) || empty($fContents) || (is_scalar($fContents) && !is_string($fContents))) {
            //如果编码相同或者非字符串标量则不转换
            return $fContents;
        }
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($fContents, $to, $from);
        } elseif (function_exists('iconv')) {
            return iconv($from, $to, $fContents);
        } else {
            return $fContents;
        }
    }
}