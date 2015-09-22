<?php
namespace App\Controller\Component;
use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Event\Event;

/**
 * Class FileUploadComponent
 * @cakephp 3.x
 * @author Sandip Ghadge
 * @version 3.0
 */
class FileUploadComponent extends Component {


    protected $_defaultConfig = [
        'fields'=>array(),
        'allowedTypes' => array(),
        'uploadDir' => null,
        'thumbArray' => array()
    ];


    protected function _setUploadDir(){
        $dir = WWW_ROOT.'uploads';
        if(!file_exists($dir)){
            $oldmask = umask(0);
            mkdir($dir, 0777, true);
            chmod($dir, 0755);
            umask($oldmask);
        }
        $this->_defaultConfig['uploadDir'] = $dir;
    }

    public function __construct(ComponentRegistry $registry, array $config = [])
    {
        $this->_registry = $registry;
        $this->_defaultConfig = array_merge($this->_defaultConfig,$config);
        if(!isset($config['uploadDir'])){
            $this->_setUploadDir();
        }
    }



    public function beforeFilter(Event $event)
    {
        //pr($event);exit;
        //echo '<pre>'; print_r($this->_defaultConfig); exit;
    }


    /**
     * Initialize properties.
     *
     * @param array $config The config data.
     * @return void
     */
    public function initialize(array $config)
    {
        $controller = $this->_registry->getController();
        $this->eventManager($controller->eventManager());
        $this->response =& $controller->response;
        $this->session = $controller->request->session();
    }

    /**
     * doFileUpload method
     * this function does generate unique file name, does the file upload and then creates thumbnail image in thub dir.
     * @author sandip
     * @return void
     */

    function doFileUpload($file_data,$file_path,$thumb_arr = array()){

        if($file_data['error']!=0){
            return false;
        }

        $this->setUploadDir($file_path);
        $new_file_name = $this->generateUniqueFilename($file_data['name']);

        if($this->handleFileUpload($file_data,$new_file_name)){
            $type=explode('/',$file_data['type']);
            if($type[0] == 'image'){

                $thumb_arr = array_merge($this->default_thumb,$thumb_arr);
                foreach ($thumb_arr as $thumb=>$dimension){
                    $this->thumbnail($new_file_name,$thumb,$dimension[0],$dimension[1]);
                }

                //$this->thumbnail($new_file_name, 'small',  25,  30); // small thumbnail
                //$this->thumbnail($new_file_name, 'medium', 75,  90); // small thumbnail
                //$this->thumbnail($new_file_name, 'large',  120, 150); // medium thumbnail
            }
            return $new_file_name;
        }else{
            return false;
        }

    }




    /**
     * generateUniqueFilename
     * this function does generate unique file name.
     * @author sandip
     * @return void
     *
     */


    function generateUniqueFilename($fileName){

        $ext = trim(substr(strrchr($fileName,'.'),1));
        $new_name = trim(substr(md5(microtime()), 2, -5));
        $fileName = $new_name.'.'.$ext;
        $no=1;
        $newFileName = $fileName;
        while (file_exists(Configure::read('upload_dir').$newFileName)) {
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
    function handleFileUpload($file_data, $fileName){
        if (is_uploaded_file($file_data['tmp_name']) && $file_data['error']==4){
            return 'file_not_uploaded';
        }
        if (is_uploaded_file($file_data['tmp_name']) && $file_data['error']==0)
        {
            if (move_uploaded_file($file_data['tmp_name'], Configure::read('upload_dir').$fileName)){
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
     * @param string $inputFileName
     * @param string $thumb_size
     * @param int width
     * @param int height
     *
     */

    function thumbnail($inputFileName, $thumb_size = 'small', $width = 46, $height = 60, $maintainAspectRatio = false){

        $src = Configure::read('upload_dir').$inputFileName;
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
            if ($width_orig > $height_orig) {
                $new_width = $width;
                $height = intval($height_orig * $new_width / $width_orig);
            } else {
                $new_height = $height;
                $width = intval($width_orig * $new_height / $height_orig);
            }
        }

        $tn = @imagecreatetruecolor($width, $height) ;

        @imagecopyresampled($tn, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
        $dir = Configure::read('upload_dir').'thumb' . DS . $thumb_size . DS;
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
     * create thumbail for existing image in dir structure
     *
     * @param string $file_name
     * @param string $path
     * @param array $thumb_arr
     */
    public function createThumb($file_name, $path, $thumb_arr = array(),$marge_with_default = true, $maintainAspectRatio = false){
        Configure::write('upload_dir', $path);
        $this->thumbnail($file_name);

        // echo Configure::read('upload_dir'); exit;
        if($marge_with_default){
            $thumb_arr = array_merge($this->default_thumb,$thumb_arr);
        }else{
            $thumb_arr = $thumb_arr;
        }

        //print_r($thumb_arr); exit;
        foreach ($thumb_arr as $thumb=>$dimension){
            $this->thumbnail($file_name,$thumb,$dimension[0],$dimension[1],$maintainAspectRatio);
        }
    }


    function createImageFrombase64($ImageDatawithType,$path,$thumbArray,$merge,$maintainAspectRatio){
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
     * deletes image and thumbail images
     *
     * @param unknown_type $file_name
     * @param unknown_type $file_path
     */
    public function removeFile($file_name,$file_path){
        //deletes thumbails file
        if(file_exists($file_path . $file_name)){
            $this->deleted = @unlink($file_path . $file_name);
        }

        //deletes thumbails images
        foreach ($this->default_thumb as $thumb=>$dimensions){
            if(file_exists($file_path.'thumb' .DS. $thumb .DS. $file_name)){
                @unlink($file_path.'thumb' . DS . $thumb . DS . $file_name);
            }
        }
        return $this->deleted;
    }


    /**
     * will move file from source to destination
     *
     * @param string $file_source
     * @param string $file_destination
     */
    public function moveFile($file_source, $file_destination){
        if(copy($file_source, $file_destination)){
            return true;
        }else{
            return false;
        }
    }

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
}