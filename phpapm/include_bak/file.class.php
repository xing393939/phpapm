<?php
define('VIMAGE', '/project/images/');
define('VIMAGE_PATH', './project/images/');

//上传,文件处理中心
class file
{

    /**
     * @desc   WHAT?
     * @author
     * @since  2012-07-16 11:21:04
     * @throws 注意:无DB异常处理
     */
    function _cut_image($filepath, $config = array())
    {
        list($width, $height, $type, $attr) = getimagesize($filepath);
        $source = null;
        if ($type == 1)
            $source = imagecreatefromgif($filepath);
        if ($type == 2)
            $source = imagecreatefromjpeg($filepath);
        if ($type == 3)
            $source = imagecreatefrompng($filepath);
        if ($type == 6) {
            include_once "bmp.php";
            $source = imagecreatefrombmp($filepath);
        }

        if (!$source)
            return null;

        if (isset($config['width']) && isset($config['height'])) {
            $new_width = $config['width'];
            $new_height = $config['height'];
            $thumb_bg = imagecreatetruecolor($new_width, $new_height);

            $percent = $new_width / $new_height;
            if ($width / $height > $percent) {
                $height2 = ($new_height / $new_width) * $width;
                $thumb_bg2 = imagecreatetruecolor($width, $height2);
                $fff = imagecolorallocate($thumb_bg2, 255, 255, 255);
                imagefill($thumb_bg2, 0, 0, $fff);
                $q_h = ($height2 - $height) / 2;
                imagecopyresampled($thumb_bg2, $source, 0, $q_h, 0, 0, $width, $height, $width, $height);
                // Resize
                imagecopyresampled($thumb_bg, $thumb_bg2, 0, 0, 0, 0, $new_width, $new_height, $width, $height2);
            } else {
                $width2 = ($new_width / $new_height) * $height;
                $thumb_bg2 = imagecreatetruecolor($width2, $height);
                $fff = imagecolorallocate($thumb_bg2, 255, 255, 255);
                imagefill($thumb_bg2, 0, 0, $fff);
                $q_h = ($width2 - $width) / 2;
                imagecopyresampled($thumb_bg2, $source, $q_h, 0, 0, 0, $width, $height, $width, $height);
                // Resize
                imagecopyresampled($thumb_bg, $thumb_bg2, 0, 0, 0, 0, $new_width, $new_height, $width2, $height);
            }
        } elseif ($config['width']) {
            $new_width = $config['width'];
            $new_height = $height * ($config['width'] / $width);
            $thumb_bg = imagecreatetruecolor($new_width, $new_height);
            imagecopyresampled($thumb_bg, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        } elseif ($config['height']) {
            $new_width = $width * ($config['height'] / $height);
            $new_height = $config['height'];
            $thumb_bg = imagecreatetruecolor($new_width, $new_height);
            imagecopyresampled($thumb_bg, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        } else {
            $new_width = $width;
            $new_height = $height;
            $thumb_bg = $source;
            if ($config['maxheight']) {
                $new_height = $config['maxheight'];
                $new_width = $width / $height * $new_height;
                $thumb_bg = imagecreatetruecolor($new_width, $new_height);
                imagecopyresampled($thumb_bg, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            }
            if ($config['maxwidth']) {
                $new_width = $config['maxwidth'];
                $new_height = $height / $width * $new_width;
                $thumb_bg = imagecreatetruecolor($new_width, $new_height);
                imagecopyresampled($thumb_bg, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            }
        }
        //再次裁剪,限定最大宽高度
        if ($config['maxheight'] && $new_height > $config['maxheight']) {
            $source = $thumb_bg;
            $thumb_bg = imagecreatetruecolor($new_width, $config['maxheight']);
            $fff = imagecolorallocate($thumb_bg, 255, 255, 255);
            imagefill($thumb_bg, 0, 0, $fff);
            $new_width2 = $new_width / $new_height * $config['maxheight'];

            $thumb_bg2 = imagecreatetruecolor($new_width2, $config['maxheight']);
            $fff = imagecolorallocate($thumb_bg2, 255, 255, 255);
            imagefill($thumb_bg2, 0, 0, $fff);
            //等比缩放图
            imagecopyresampled($thumb_bg2, $source, 0, 0, 0, 0, $new_width2, $config['maxheight'], $new_width, $new_height);
            $q_w = ($new_width - $new_width2) / 2;
            imagecopyresampled($thumb_bg, $thumb_bg2, $q_w, 0, 0, 0, $new_width2, $config['maxheight'], $new_width2, $config['maxheight']);
        }
        if ($config['maxwidth'] && $new_width > $config['maxwidth']) {
            $source = $thumb_bg;
            $thumb_bg = imagecreatetruecolor($config['maxwidth'], $new_height);
            $fff = imagecolorallocate($thumb_bg, 255, 255, 255);
            imagefill($thumb_bg, 0, 0, $fff);

            $new_height2 = $new_height / $new_width * $config['maxwidth'];
            $thumb_bg2 = imagecreatetruecolor($config['maxwidth'], $new_height2);
            $fff = imagecolorallocate($thumb_bg2, 255, 255, 255);
            imagefill($thumb_bg2, 0, 0, $fff);

            //等比缩放图
            imagecopyresampled($thumb_bg2, $source, 0, 0, 0, 0, $config['maxwidth'], $new_height2, $new_width, $new_height);
            $q_h = ($new_height - $new_height2) / 2;
            imagecopyresampled($thumb_bg, $thumb_bg2, 0, $q_h, 0, 0, $config['maxwidth'], $new_height2, $config['maxwidth'], $new_height2);
        }

        return $thumb_bg;
    }

    /**
     * @desc   WHAT?
     * @author
     * @since  2012-07-05 16:56:01
     * @throws 注意:无DB异常处理
     * @param $config ['width']按照宽度缩放,$config['height']按照高度缩放,都设置,按照比例裁剪再缩放
     */
    function fetchimg($url, $filename = null, $config = array())
    {
        if (!$filename)
            $filename = md5($url);
        $imageData = _curl($chinfo, $url);
        if ($imageData) {
            $date = date('Y-m') . '/';
            $path = VIMAGE_PATH . $date;
            if (!is_dir($path))
                mkdir($path);
            $filepath = $path . $filename . ".jpg";

            $bool = file_put_contents($filepath, $imageData);
            if ($config) {
                $thumb_bg = $this->_cut_image($filepath, $config);
                $bool = imagejpeg($thumb_bg, $filepath, 100);
            }
            list($width, $height, $type, $attr) = getimagesize($filepath);

            if ($bool)
                return array(
                    'url' => VIMAGE . $date . $filename . '.jpg',
                    'width' => $width,
                    'height' => $height
                );
        }
        return null;
    }

    /**
     * @desc   WHAT?
     * @author
     * @since  2012-07-05 16:34:43
     * @throws 注意:无DB异常处理
     * @param $config ['width']按照宽度缩放,$config['height']按照高度缩放,都设置,按照比例裁剪再缩放,$config['maxheight']最大的高度,$config['maxwidth']=420最大宽度
     */
    function uploadimg($filepath, $filename, $config = array())
    {
        $date = date('Y-m') . '/';
        $path = VIMAGE_PATH . $date;
        if (!is_dir($path))
            mkdir($path);
        $file = $path . $filename . '.jpg';

        if ($config) {
            $thumb_bg = $this->_cut_image($filepath, $config);
            $bool = imagejpeg($thumb_bg, $file, 100);
        } else
            $bool = copy($filepath, $file);
        list($width, $height, $type, $attr) = getimagesize($file);
        if ($bool)
            return array(
                'url' => VIMAGE . $date . $filename . '.jpg',
                'width' => $width,
                'height' => $height
            );
        else
            return null;
    }

}

?>