<?php
/**
 * 文件上传类
 * @category
 * @package
 * @subpackage
 * @author    niutou
 */
namespace Models\UploadFile;

use Model\Model;

class UploadFile
{
    private $config = array(
        'maxSize'           => -1,    // 上传文件的最大值
        'supportMulti'      => true,  // 是否支持多文件上传
        'allowExts'         => array(), // 允许上传的文件后缀 留空不作后缀检查
        'allowTypes'        => array(), // 允许上传的文件类型 留空不做检查
        'thumb'             => false, // 使用对上传图片进行缩略图处理
        'forceThumb'        => 0,     // 是否强制压缩原图并保存
        'thumbMaxWidth'     => '',    // 缩略图最大宽度
        'thumbMaxHeight'    => '',    // 缩略图最大高度
        'thumbPrefix'       => 'thumb_', // 缩略图前缀
        'thumbSuffix'       => '',
        'thumbPath'         => '',    // 缩略图保存路径
        'thumbFile'         => '',    // 缩略图文件名
        'thumbExt'          => '',    // 缩略图扩展名
        'thumbRemoveOrigin' => false, // 是否移除原图
        'zipImages'         => false, // 压缩图片文件上传
        'autoSub'           => false, // 启用子目录保存文件
        'subType'           => 'hash', // 子目录创建方式 可以使用hash date custom
        'subDir'            => '',    // 子目录名称 subType为custom方式后有效
        'dateFormat'        => 'Ymd',
        'hashLevel'         => 1,     // hash的目录层次
        'savePath'          => '',    // 上传文件保存路径
        'autoCheck'         => false, // 是否自动检查附件
        'uploadReplace'     => false, // 存在同名是否覆盖
        'saveRule'          => 'uniqid', // 上传文件命名规则
        'hashType'          => 'md5_file', // 上传文件Hash规则函数名
    );

    // 错误信息
    private $error = '';
    // 上传成功的文件信息
    private $uploadFileInfo;

    public function __get($name)
    {
        if (isset($this->config[$name])) {
            return $this->config[$name];
        }
        return null;
    }

    public function __set($name, $value)
    {
        if (isset($this->config[$name])) {
            $this->config[$name] = $value;
        }
    }

    public function __isset($name)
    {
        return isset($this->config[$name]);
    }

    /**
     * 架构函数
     * @access public
     * @param array $config 上传参数
     */
    public function __construct($config = array())
    {
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        }
    }

    /**
     * 压缩图片
     *
     * @param string $imgsrc 图片路径
     * @param int    $maxw   最大宽度
     */
    public function compressedImage($imgsrc, $maxw = 0)
    {
        $info = @getimagesize($imgsrc);
        if ($info === false) {
            return;
        }

        list($width, $height, $type) = $info;

        $new_width  = $width;
        $new_height = $height;

        if ($maxw > 0 && $width >= $maxw) {
            $per        = $maxw / $width;
            $new_width  = (int)($width * $per);
            $new_height = (int)($height * $per);
        }

        $image_wp = imagecreatetruecolor($new_width, $new_height);

        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
            imagealphablending($image_wp, false);
            imagesavealpha($image_wp, true);
            $alpha = imagecolorallocatealpha($image_wp, 0, 0, 0, 127);
            imagefill($image_wp, 0, 0, $alpha);
        }

        $image = $this->imgCreateFrom($imgsrc);
        if (!$image) {
            imagedestroy($image_wp);
            return;
        }

        imagecopyresampled(
            $image_wp, $image,
            0, 0, 0, 0,
            $new_width, $new_height,
            $width, $height
        );

        $this->savefile($image_wp, $imgsrc, 10);

        imagedestroy($image_wp);
        imagedestroy($image);
    }

    /**
     * 从文件创建图像资源
     *
     * @param string $img_src
     * @return resource|false
     */
    public function imgCreateFrom($img_src)
    {
        $ext = getextension($img_src, '.');
        $ext = strtolower($ext);

        switch ($ext) {
            case 'gif':
                return imagecreatefromgif($img_src);
            case 'jpg':
            case 'jpeg':
                return imagecreatefromjpeg($img_src);
            case 'png':
                return imagecreatefrompng($img_src);
        }
        return false;
    }

    /**
     * 保存图像资源
     *
     * @param resource $qrstring
     * @param string   $tosource
     * @param int      $sf
     */
    public function savefile($qrstring, $tosource = '', $sf = 9)
    {
        $ext = getextension($tosource);
        $ext = strtolower($ext);

        switch ($ext) {
            case 'gif':
                imagegif($qrstring, $tosource);
                break;
            case 'jpg':
            case 'jpeg':
                imagejpeg($qrstring, $tosource, 90);
                break;
            case 'png':
                imagepng($qrstring, $tosource, 9);
                break;
        }
    }

    /**
     * 上传一个文件
     * @access private
     * @param array $file 上传的文件信息
     * @return bool
     */
    private function save($file)
    {
        $filename = $file['savepath'] . $file['savename'];

        if (!$this->uploadReplace && is_file($filename)) {
            // 不覆盖同名文件
            $this->error = '文件已经存在！' . $filename;
            return false;
        }

        // 如果是图像文件 检测文件格式
        if (in_array(strtolower($file['extension']), array('gif', 'jpg', 'jpeg', 'bmp', 'png', 'swf'))) {
            $info = @getimagesize($file['tmp_name']);
            if (false === $info || ('gif' == strtolower($file['extension']) && empty($info['bits']))) {
                $this->error = '非法图像文件';
                return false;
            }
        }

        if (!move_uploaded_file($file['tmp_name'], $this->autoCharset($filename, 'utf-8', 'gbk'))) {
            $this->error = '文件上传保存错误！';
            return false;
        }

        if ($this->forceThumb > 0) {
            $this->compressedImage($filename, $this->forceThumb);
            return true;
        }

        if ($this->thumb && in_array(strtolower($file['extension']), array('gif', 'jpg', 'jpeg', 'bmp', 'png'))) {
            $image = @getimagesize($filename);
            if (false !== $image) {
                // 是图像文件生成缩略图
                $thumbWidth  = explode(',', $this->thumbMaxWidth);
                $thumbHeight = explode(',', $this->thumbMaxHeight);
                $thumbPrefix = explode(',', $this->thumbPrefix);
                $thumbSuffix = explode(',', $this->thumbSuffix);
                $thumbFile   = explode(',', $this->thumbFile);
                $thumbPath   = $this->thumbPath ? $this->thumbPath : dirname($filename) . '/';
                $thumbExt    = $this->thumbExt ? $this->thumbExt : $file['extension'];

                for ($i = 0, $len = count($thumbWidth); $i < $len; $i++) {
                    if (!empty($thumbFile[$i])) {
                        $thumbname = $thumbFile[$i];
                    } else {
                        $prefix    = isset($thumbPrefix[$i]) ? $thumbPrefix[$i] : $thumbPrefix[0];
                        $suffix    = isset($thumbSuffix[$i]) ? $thumbSuffix[$i] : $thumbSuffix[0];
                        $thumbname = $prefix . basename($filename) . $suffix;
                    }

                    $this->makeThumb(
                        $filename,
                        $thumbPath . $thumbname,
                        isset($thumbWidth[$i]) ? (int)$thumbWidth[$i] : 0,
                        isset($thumbHeight[$i]) ? (int)$thumbHeight[$i] : 0
                    );
                }

                if ($this->thumbRemoveOrigin) {
                    // 生成缩略图之后删除原图
                    @unlink($filename);
                }
            }
        }

        if ($this->zipImages) {
            // TODO 对图片压缩包在线解压
        }

        return true;
    }

    /**
     * 生成单个缩略图
     *
     * @param string $source
     * @param string $target
     * @param int    $maxWidth
     * @param int    $maxHeight
     */
    private function makeThumb($source, $target, $maxWidth, $maxHeight)
    {
        $info = @getimagesize($source);
        if ($info === false) {
            return;
        }

        list($srcW, $srcH, $type) = $info;

        $scale = 1;
        if ($maxWidth > 0 && $srcW > $maxWidth) {
            $scale = min($scale, $maxWidth / $srcW);
        }
        if ($maxHeight > 0 && $srcH > $maxHeight) {
            $scale = min($scale, $maxHeight / $srcH);
        }

        $dstW = max(1, (int)($srcW * $scale));
        $dstH = max(1, (int)($srcH * $scale));

        $srcImage = $this->createImageFromType($source, $type);
        if (!$srcImage) {
            return;
        }

        $dstImage = imagecreatetruecolor($dstW, $dstH);

        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
            imagealphablending($dstImage, false);
            imagesavealpha($dstImage, true);
            $transparent = imagecolorallocatealpha($dstImage, 0, 0, 0, 127);
            imagefill($dstImage, 0, 0, $transparent);
        }

        imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);

        $this->saveImageByType($dstImage, $target, $type);

        imagedestroy($srcImage);
        imagedestroy($dstImage);
    }

    /**
     * 根据图像类型创建资源
     *
     * @param string $file
     * @param int    $type
     * @return resource|false
     */
    private function createImageFromType($file, $type)
    {
        switch ($type) {
            case IMAGETYPE_GIF:
                return imagecreatefromgif($file);
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($file);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($file);
        }
        return false;
    }

    /**
     * 根据图像类型保存资源
     *
     * @param resource $image
     * @param string   $target
     * @param int      $type
     */
    private function saveImageByType($image, $target, $type)
    {
        switch ($type) {
            case IMAGETYPE_GIF:
                imagegif($image, $target);
                break;
            case IMAGETYPE_JPEG:
                imagejpeg($image, $target, 90);
                break;
            case IMAGETYPE_PNG:
                imagepng($image, $target, 9);
                break;
        }
    }

    /**
     * 上传所有文件
     * @access public
     * @param string $savePath 上传文件保存路径
     * @return bool
     */
    public function upload($savePath = '')
    {
        // 如果不指定保存文件名，则由系统默认
        if (empty($savePath)) {
            $savePath = $this->savePath;
        }

        // 检查上传目录
        if (!is_dir($savePath)) {
            // 检查目录是否编码后的
            if (is_dir(base64_decode($savePath))) {
                $savePath = base64_decode($savePath);
            } else {
                // 尝试创建目录
                if (!mkdir($savePath, 0777, true)) {
                    $this->error = '上传目录' . $savePath . '不存在';
                    return false;
                }
            }
        } else {
            if (!is_writeable($savePath)) {
                $this->error = '上传目录' . $savePath . '不可写';
                return false;
            }
        }

        $fileInfo = array();
        $isUpload = false;

        // 获取上传的文件信息
        // 对 $_FILES 数组信息处理
        $files = $this->dealFiles($_FILES);
        foreach ($files as $key => $file) {
            // 过滤无效的上传
            if (!empty($file['name'])) {
                // 登记上传文件的扩展信息
                if (!isset($file['key'])) {
                    $file['key'] = $key;
                }
                $file['extension'] = $this->getExt($file['name']);
                $file['savepath']  = $savePath;
                $file['savename']  = $this->getSaveName($file);

                // 自动检查附件
                if ($this->autoCheck) {
                    if (!$this->check($file)) {
                        return false;
                    }
                }

                // 保存上传文件
                if (!$this->save($file)) {
                    return false;
                }
                if (function_exists($this->hashType)) {
                    $fun = $this->hashType;
                    $file['hash'] = $fun($this->autoCharset($file['savepath'] . $file['savename'], 'utf-8', 'gbk'));
                }
                // 上传成功后保存文件信息，供其他地方调用
                unset($file['tmp_name'], $file['error']);
                $fileInfo[] = $file;
                $isUpload = true;
            }
        }

        if ($isUpload) {
            $this->uploadFileInfo = $fileInfo;
            return true;
        } else {
            $this->error = '没有选择上传文件';
            return false;
        }
    }

    /**
     * 上传单个上传字段中的文件 支持多附件
     * @access public
     * @param array  $file     上传文件信息
     * @param string $savePath 上传文件保存路径
     * @return array|false
     */
    public function uploadOne($file, $savePath = '')
    {
        // 如果不指定保存文件名，则由系统默认
        if (empty($savePath)) {
            $savePath = $this->savePath;
        }

        // 检查上传目录
        if (!is_dir($savePath)) {
            // 尝试创建目录
            if (!mkdir($savePath, 0777, true)) {
                $this->error = '上传目录' . $savePath . '不存在';
                return false;
            }
        } else {
            if (!is_writeable($savePath)) {
                $this->error = '上传目录' . $savePath . '不可写';
                return false;
            }
        }

        // 过滤无效的上传
        if (!empty($file['name'])) {
            $fileArray = array();
            if (is_array($file['name'])) {
                $keys  = array_keys($file);
                $count = count($file['name']);
                for ($i = 0; $i < $count; $i++) {
                    foreach ($keys as $key) {
                        $fileArray[$i][$key] = $file[$key][$i];
                    }
                }
            } else {
                $fileArray[] = $file;
            }

            $info = array();
            foreach ($fileArray as $key => $file) {
                // 登记上传文件的扩展信息
                $file['extension'] = $this->getExt($file['name']);
                $file['savepath']  = $savePath;
                $file['savename']  = $this->getSaveName($file);

                // 自动检查附件
                if ($this->autoCheck) {
                    if (!$this->check($file)) {
                        return false;
                    }
                }

                // 保存上传文件
                if (!$this->save($file)) {
                    return false;
                }
                if (function_exists($this->hashType)) {
                    $fun = $this->hashType;
                    $file['hash'] = $fun($this->autoCharset($file['savepath'] . $file['savename'], 'utf-8', 'gbk'));
                }
                unset($file['tmp_name'], $file['error']);
                $info[] = $file;
            }

            // 返回上传的文件信息
            return $info;
        } else {
            $this->error = '没有选择上传文件';
            return false;
        }
    }

    /**
     * 转换上传文件数组变量为正确的方式
     * @access private
     * @param array $files 上传的文件变量
     * @return array
     */
    private function dealFiles($files)
    {
        $fileArray = array();
        $n = 0;
        foreach ($files as $key => $file) {
            if (is_array($file['name'])) {
                $keys  = array_keys($file);
                $count = count($file['name']);
                for ($i = 0; $i < $count; $i++) {
                    $fileArray[$n]['key'] = $key;
                    foreach ($keys as $_key) {
                        $fileArray[$n][$_key] = $file[$_key][$i];
                    }
                    $n++;
                }
            } else {
                $fileArray[$key] = $file;
            }
        }
        return $fileArray;
    }

    /**
     * 获取错误代码信息
     * @access public
     * @param string $errorNo 错误号码
     * @return void
     */
    protected function error($errorNo)
    {
        switch ($errorNo) {
            case 1:
                $this->error = '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值';
                break;
            case 2:
                $this->error = '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值';
                break;
            case 3:
                $this->error = '文件只有部分被上传';
                break;
            case 4:
                $this->error = '没有文件被上传';
                break;
            case 6:
                $this->error = '找不到临时文件夹';
                break;
            case 7:
                $this->error = '文件写入失败';
                break;
            default:
                $this->error = '未知上传错误！';
        }
        return;
    }

    /**
     * 根据上传文件命名规则取得保存文件名
     * @access private
     * @param array $filename 数据
     * @return string
     */
    private function getSaveName($filename)
    {
        $rule = $this->saveRule;
        if (empty($rule)) {
            // 没有定义命名规则，则保持文件名不变
            $saveName = $filename['name'];
        } else {
            if (function_exists($rule)) {
                // 使用函数生成一个唯一文件标识号
                $saveName = $rule() . '.' . $filename['extension'];
            } else {
                // 使用给定的文件名作为标识号
                $saveName = $rule . '.' . $filename['extension'];
            }
        }

        if ($this->autoSub) {
            // 使用子目录保存文件
            $filename['savename'] = $saveName;
            $saveName = $this->getSubName($filename) . $saveName;
        }
        return $saveName;
    }

    /**
     * 获取子目录的名称
     * @access private
     * @param array $file 上传的文件信息
     * @return string
     */
    private function getSubName($file)
    {
        switch ($this->subType) {
            case 'custom':
                $dir = $this->subDir;
                break;
            case 'date':
                $dir = date($this->dateFormat, time()) . '/';
                break;
            case 'hash':
            default:
                $name = md5($file['savename']);
                $dir = '';
                for ($i = 0; $i < $this->hashLevel; $i++) {
                    $dir .= $name[$i] . '/';
                }
                break;
        }

        if (!is_dir($file['savepath'] . $dir)) {
            mkdir($file['savepath'] . $dir, 0777, true);
        }
        return $dir;
    }

    /**
     * 检查上传的文件
     * @access private
     * @param array $file 文件信息
     * @return bool
     */
    private function check($file)
    {
        if ($file['error'] !== 0) {
            // 文件上传失败
            // 捕获错误代码
            $this->error($file['error']);
            return false;
        }

        // 文件上传成功，进行自定义规则检查
        // 检查文件大小
        if (!$this->checkSize($file['size'])) {
            $this->error = '上传文件大小不符！';
            return false;
        }

        // 检查文件 Mime 类型
        if (!$this->checkType($file['type'])) {
            $this->error = '上传文件MIME类型不允许！';
            return false;
        }

        // 检查文件类型
        if (!$this->checkExt($file['extension'])) {
            $this->error = '上传文件类型不允许';
            return false;
        }

        // 检查是否合法上传
        if (!$this->checkUpload($file['tmp_name'])) {
            $this->error = '非法上传文件！';
            return false;
        }

        return true;
    }

    /**
     * 自动转换字符集 支持数组转换
     *
     * @param mixed  $fContents
     * @param string $from
     * @param string $to
     * @return mixed
     */
    private function autoCharset($fContents, $from = 'gbk', $to = 'utf-8')
    {
        $from = strtoupper($from) == 'UTF8' ? 'utf-8' : $from;
        $to   = strtoupper($to) == 'UTF8' ? 'utf-8' : $to;

        if (strtoupper($from) === strtoupper($to) || empty($fContents) || (is_scalar($fContents) && !is_string($fContents))) {
            // 如果编码相同或者非字符串标量则不转换
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

    /**
     * 检查上传的文件类型是否合法
     * @access private
     * @param string $type 数据
     * @return bool
     */
    private function checkType($type)
    {
        if (!empty($this->allowTypes)) {
            return in_array(strtolower($type), $this->allowTypes);
        }
        return true;
    }

    /**
     * 检查上传的文件后缀是否合法
     * @access private
     * @param string $ext 后缀名
     * @return bool
     */
    private function checkExt($ext)
    {
        if (!empty($this->allowExts)) {
            return in_array(strtolower($ext), $this->allowExts, true);
        }
        return true;
    }

    /**
     * 检查文件大小是否合法
     * @access private
     * @param int $size 数据
     * @return bool
     */
    private function checkSize($size)
    {
        return !($size > $this->maxSize) || (-1 == $this->maxSize);
    }

    /**
     * 检查文件是否非法提交
     * @access private
     * @param string $filename 文件名
     * @return bool
     */
    private function checkUpload($filename)
    {
        return is_uploaded_file($filename);
    }

    /**
     * 取得上传文件的后缀
     * @access private
     * @param string $filename 文件名
     * @return string
     */
    private function getExt($filename)
    {
        $pathinfo = pathinfo($filename);
        return isset($pathinfo['extension']) ? $pathinfo['extension'] : '';
    }

    /**
     * 取得上传文件的信息
     * @access public
     * @return array|null
     */
    public function getUploadFileInfo()
    {
        return $this->uploadFileInfo;
    }

    /**
     * 取得最后一次错误信息
     * @access public
     * @return string
     */
    public function getErrorMsg()
    {
        return $this->error;
    }
}