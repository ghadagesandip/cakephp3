<?php
namespace FileUpload\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;

class FileUploadComponent extends Component{

    /**
     * Default config
     *
     * - `authenticate` - An array of authentication objects to use for authenticating users.
     *   You can configure multiple adapters and they will be checked sequentially
     *   when users are identified.
     *
     *   ```
     *   $this->FileUpload->config('FileUpload',[
     *      'defaultThumb'=> [
     *          'small' => [ 255,200 ],
     *          'medium' => [ 510,360 ]
     *      ],
     *     'uploadDir' =>'/var/www/html',
     *     'maintainAspectRation'=>true
     *   ]
     * );
     *   ```
     *
     * maintainAspectRation possible values  are true, false, h and w
     */



    /**
     * @param array $config
     */
    public function initialize(array $config){
        //pr($config);exit;
        $controller = $this->_registry->getController();
       // $this->eventManager($controller->eventManager());
        $this->response =& $controller->response;
        $this->session = $controller->request->session();
    }


    /**
     * @param $file_data
     * @return bool|mixed|string
     */
    function doFileUpload($file_data){
        //pr($file_data);exit;
        if($file_data['error']!=0){
            return false;
        }

        $new_file_name = $this->_generateUniqueFilename($file_data['name']);

        if($this->_handleFileUpload($file_data, $new_file_name)){
            $type = explode('/',$file_data['type']);
            if($type[0] == 'image'){

                if(!empty($this->_config['defaultThumb'])){
                    foreach ($this->_config['defaultThumb'] as $thumb=>$dimension){
                        $this->thumbnail($new_file_name,$thumb,$dimension[0],$dimension[1], $this->_config['maintainAspectRation']);
                    }
                }
            }
            return $new_file_name;
        }else{
            return false;
        }

    }



    /**
     * this function does generate unique file name.
     *
     * @param $fileName
     * @return mixed|string
     */
    function _generateUniqueFilename($fileName){

        $ext = trim(substr(strrchr($fileName,'.'),1));
        $new_name = trim(substr(md5(microtime()), 2, -5));
        $fileName = $new_name.'.'.$ext;
        $no=1;
        $newFileName = $fileName;
        while (file_exists($this->_config['uploadDir'].$newFileName)) {
            $no++;
            $newFileName = substr_replace($fileName, "_$no.", strrpos($fileName, "."), 1);
        }

        return $newFileName;
    }


    /**
     * function will move uploaded file to a dir.
     *
     * @param unknown_type $file_data
     * @param unknown_type $fileName
     * @return unknown
     */
    function _handleFileUpload($file_data, $fileName){
        if (is_uploaded_file($file_data['tmp_name']) && $file_data['error']==4){
            return 'file_not_uploaded';
        }
        if (is_uploaded_file($file_data['tmp_name']) && $file_data['error']==0)
        {

            if (move_uploaded_file($file_data['tmp_name'], $this->_config['uploadDir'].$fileName)){
                return TRUE;
            }else{
                return false;
            }
        }

    }



    /**
     * function generate thumbnail images for uploaded image file
     * moves the uploaded file from tmp dir to upload dir.
     * @author sandip
     * @return void
     * @param $inputFileName
     * @param string $thumb_size
     * @param int $width
     * @param int $height
     * @param bool $maintainAspectRatio true/false, w/h
     */
    function thumbnail($inputFileName, $thumb_size = 'small', $width = 46, $height = 60, $maintainAspectRatio = false){

        $src = $this->_config['uploadDir'].$inputFileName;

        $filename = explode('.',$inputFileName);
        $thname = $filename[0];

        $file_extension = substr($src, strrpos($src, '.')+1);

        switch(strtolower($file_extension)) {
            case "gif":  $image = @imagecreatefromgif($src); break;
            case "png":  $image = @imagecreatefrompng($src);break;
            case "bmp":  $image = @imagecreatefromwbmp($src);break;
            case "jpeg":
            case "jpg":  $image = @imagecreatefromjpeg($src);break;
        }

        list($width_orig, $height_orig, $type, $attr) = @getimagesize($src);

        if($maintainAspectRatio){
            if($maintainAspectRatio == 'w'){
                $new_width = $width;
                $height = intval($height_orig * $new_width / $width_orig);
            }else if($maintainAspectRatio == 'h'){
                $new_height = $height;
                $width = intval($width_orig * $new_height / $height_orig);
            }else{
                if ($width_orig > $height_orig) {
                    $new_width = $width;
                    $height = intval($height_orig * $new_width / $width_orig);
                } else {
                    $new_height = $height;
                    $width = intval($width_orig * $new_height / $height_orig);
                }
            }

        }

        $tn = @imagecreatetruecolor($width, $height) ;

        @imagecopyresampled($tn, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
        $dir = $this->_config['uploadDir'].'thumb' . DS . $thumb_size . DS;
        if (!file_exists($dir)) {
            //umask(0777);
            $oldmask = umask(0);
            mkdir($dir, 0777, true);
            chmod($dir, 0755);
            umask($oldmask);

        }

        switch(strtolower($file_extension)) {
            case "gif": imagegif($tn, $dir.$thname.'.'.$file_extension); break;
            case "png": imagealphablending($tn,false);
                imagesavealpha($tn,true);
                imagecopyresampled($tn,$image,0,0,0,0,$width,$height,$width_orig,$height_orig);
                imagepng($tn, $dir.$thname.'.'.$file_extension,9); break;
            case "bmp": imagewbmp($tn, $dir.$thname.'.'.$file_extension); break;
            case "jpeg":
            case "jpg": imagejpeg($tn, $dir.$thname.'.'.$file_extension,95); break;

        }
    }


    /**
     * @param $file_name
     * @param $path
     * @param bool $merge_with_default
     * @param bool $maintainAspectRatio
     */
    public function createThumb($file_name, $path, $thumbArr = array(), $merge_with_default = true, $maintainAspectRatio = false){


        $this->_config['uploadDir'] = isset($path) ? $path :$this->_config['uploadDir'];

        if($merge_with_default){
            $this->_config['defaultThumb'] = array_merge($this->_config['defaultThumb'], $thumbArr);
        }else{
            $this->_config['defaultThumb'] = $thumbArr;
        }

        //print_r($this->_config); exit;
        foreach ($this->_config['defaultThumb'] as $thumb=>$dimension){
            $this->thumbnail($file_name, $thumb, $dimension[0], $dimension[1], $maintainAspectRatio);
        }
    }


    /**
     * @param $ImageDatawithType
     * @param $path
     * @param $thumbArray
     * @param $merge
     * @param $maintainAspectRatio
     * @return bool|string
     */
    function createImageFrombase64($ImageDatawithType, $path, $thumbArray, $merge, $maintainAspectRatio){
        //echo '<pre>'; print_r($ImageData);exit;
        // $ImageDatawithType will be of this format =  data:image/jpeg;base64,/9j/4AAQSk.....
        list($type, $ImageData) = explode(';', $ImageDatawithType);
        list(, $data)      = explode(',', $ImageData);
        $data = base64_decode($data);
        list(,$ext) = explode('/',$type);
        $imageFileName = uniqid() . '.'.$ext;
        $file = $path.$imageFileName;
        $success = file_put_contents($file, $data);
        //chmod($file,0777);
        $this->createThumb($imageFileName,$path,$thumbArray,$merge, $maintainAspectRatio);
        return $fileData =  $success ? $imageFileName :false;
    }



    /**
     * @param $file_name
     * @param $file_path
     * @return bool
     */
    public function removeFile($file_name, $file_path = null){

        $file_path = isset($file_path) ? $file_path : $this->_config['uploadDir'];

        if(file_exists($file_path . $file_name)){
            @unlink($file_path . $file_name);
        }


        foreach ($this->_config['uploadDir'] as $thumb=>$dimensions){
            if(file_exists($file_path.'thumb' .DS. $thumb .DS. $file_name)){
                @unlink($file_path.'thumb' . DS . $thumb . DS . $file_name);
            }
        }
        return true;
    }


    /**
     * @param $file_source
     * @param $file_destination
     * @return bool
     */
    public function moveFile($file_source, $file_destination){
        if(move_uploaded_file($file_source, $file_destination)){
            return true;
        }else{
            return false;
        }
    }


    /**
     * @param $file
     * @param $path
     * @return string
     */
    public function changeImageToJpeg($file,$path){

        $img = $path.$file;
        $dst = pathinfo($path.$file);
        if(in_array($dst['extension'],array('jpg','jpeg'))){
            return $file;
        }
        if (($img_info = getimagesize($img)) === FALSE)
            die("Image not found or not an image");

        $width = $img_info[0];
        $height = $img_info[1];

        switch ($img_info[2]) {
            case IMAGETYPE_GIF  : $src = imagecreatefromgif($img);  break;
            case IMAGETYPE_JPEG : $src = imagecreatefromjpeg($img); break;
            case IMAGETYPE_PNG  : $src = imagecreatefrompng($img);  break;
            default : die("Unknown filetype");
        }

        $tmp = imagecreatetruecolor($width, $height);
        imagecopyresampled($tmp, $src, 0, 0, 0, 0, $width, $height, $width, $height);
        imagejpeg($tmp, $dst['dirname'].'/'.$dst['filename'].".jpg");
        //$this->removeFile($file,$path);
        return $dst['filename'].".jpg";
    }


    /**
     * @param $file_data
     * @param $min
     * @param $max
     * @return bool
     */
    public function calImgSizeRatio($file_data,$min,$max){
        if($file_data['error']!=0){
            return false;
        }
        list($width_orig, $height_orig, $type, $attr) = @getimagesize($file_data['tmp_name']);
        $ratio = $height_orig/$width_orig;
        if($ratio > $min && $ratio < $max){
            return true;
        }
        return false;
    }
}