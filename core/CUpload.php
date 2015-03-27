<?php
class CUpload {
    public $maxSize; //in byte eg:10485760=10 MB
    public $minSize; ////in byte
    public $allowedType; //predefined types such as 'image' or user defined types in array
    public $type; //$_FILES['userfile']['type']
    public $extension; //uploaded file extension
    public $name; //$_FILES['userfile']['name']
    public $size; //$_FILES['userfile']['size'](in bytes)
    public $tmpName; //$_FILES['userfile']['tmp_name']
    public $error;
    public $errorMessage;
    public $errorType;
    /*
     *file upload error types
    */
    const OK = 0; //no error
    const MAX_SIZE = 1; //max size error
    const MIN_SIZE = 2; //min size error
    const PARTIAL = 3; // The uploaded file was only partially uploaded.
    const NO_FILE = 4; //No file was uploaded.
    const EXT = 5; //extension error
    const TMP_DIR = 6; //problem in finding tmp directory
    const CANT_WRITE = 7; // Failed to write file to disk.
    const OTHER = 8;
    private $image = array('gif', 'jpeg', 'jpg', 'png', 'tiff', 'jpe', 'pjpeg', 'psd', 'svg', 'ps', 'ai',);
    private $archive = array('zip', 'zipx', 'rar', 'bz2', 'gz', 'lz', 'lzma', 'lzo', 'rz', 'gzip', 'xz', 'z', 'Z', '7z', 's7z', 'ace', 'cab', 'gca', 'sda', 'xar', 'zz', 'bzip2', 'lzip', 'iso', 'vcd', 'sit', 'sitx');
    private $executive = array('jar', 'rpm', 'deb', 'pkg', 'exe', 'msi', 'dll', 'bat', 'cgi', 'app', 'com', 'gadget', 'pif', 'vb', 'wsf', 'sav', 'torrent');
    private $document = array('doc', 'docx', 'docm', 'dotx', 'dotm', 'ppt', 'pptx', 'pps', 'ppsx', 'xls', 'xlr', 'xlsx', 'rtf', 'wpd', 'wp', 'wp7', 'wps', 'fb2', 'odt', 'sxw', 'pages', 'asp', 'html', 'mht', 'htm', 'xhtml', 'pdf', 'djvu', 'epub', 'tex', 'txt', 'sql', 'php', 'xml', 'log', 'msg', 'css', 'js', 'thm', 'thmx', 'rss', 'jsp', 'dtd', 'torrent');
    private $video = array('mpeg', 'mpg', 'mpe', 'avi', 'wmv', 'mov', 'flv', '3gp', 'asf', 'asx', 'mp4', 'rm', 'swf', 'vob', 'dat');
    private $font = array('fnt', 'fon', 'ttf', 'otf',);
    private $audio = array('mp3', 'wav', 'aiff', 'aif', 'amr', 'aif', 'iff', 'm3u', 'm4a', 'mid', 'mpa', 'ra',);
    public function reset() {
        unset($this->type);
        unset($this->extension);
        unset($this->name);
        unset($this->size);
        unset($this->tmpName);
        unset($this->error);
        unset($this->errorMessage);
    }
    /*
     * return: TRUE or FALSE
    */
    public function run($field, $required = TRUE) {
        if (!isset($_FILES[$field])) {
            if ($required === TRUE) {
                $this->errorMessage = 'آپلود فایل الزامی است.';
                $this->errorType = self::NO_FILE;
                return FALSE;
            } else {
                return TRUE;
            }
        }
        $this->reset(); //if we have multiple file upload with only one class!
        $allowedFormats = '';
        if (is_array($this->allowedType)) {
            $allowedFormats = $this->allowedType;
        } elseif (is_string($this->allowedType)) {
            $temp = $this->allowedType;
            if (is_array($this->$temp)) $allowedFormats = $this->$temp;
        }
        if (!is_array($allowedFormats)) {
            $allowedFormats = array_merge((array)$this->archive, (array)$this->audio, (array)$this->document, (array)$this->executive, (array)$this->font, (array)$this->image, (array)$this->video);
        }
        if ($this->allowedExtension($field, $allowedFormats) === FALSE) {
            $this->errorMessage = 'این فرمت فایل مجاز نمی باشد.';
            $this->errorType = self::EXT;
            return FALSE;
        }
        if (!empty($this->maxSize)) {
            if ($_FILES[$field]["size"] > $this->maxSize) {
                $this->errorMessage = 'حداکثر اندازه فایل ' . $this->maxSize . ' بایت می‌تواند باشد.';
                $this->errorType = self::MAX_SIZE;
                return FALSE;
            }
        }
        if (!empty($this->minSize)) {
            if ($_FILES[$field]["size"] < $this->minSize) {
                $this->errorMessage = 'حداقل اندازه فایل ' . $this->minSize . ' بایت می‌تواند باشد.';
                $this->errorType = self::MIN_SIZE;
                return FALSE;
            }
        }
        if ($_FILES[$field]["error"] == UPLOAD_ERR_OK) {
            $tmp = explode(".", $_FILES[$field]["name"]);
            $this->extension = end($tmp);
            $this->name = $_FILES[$field]["name"];
            $this->type = $_FILES[$field]["type"];
            $this->tmpName = $_FILES[$field]["tmp_name"];
            $this->size = $_FILES[$field]["size"];
            $this->error = $_FILES[$field]["error"];
            $this->errorType = self::OK;
            return TRUE;
        } elseif ($_FILES[$field]["error"] == 4) {
            $this->error = $_FILES[$field]["error"];
            if ($required === TRUE) {
                $this->errorMessage = 'آپلود فایل الزامی است.';
                $this->errorType = self::NO_FILE;
                return FALSE;
            } else {
                return TRUE;
            }
        } else {
            $this->errorMessage = 'مشکلی در آپلود فایل پیش آمده است.';
            $this->error = $_FILES[$field]["error"];
            $this->errorType = self::OTHER;
            return FALSE;
        }
    }
    public function saveAs($file, $deleteTempFile = TRUE) {
        if ($this->error == UPLOAD_ERR_OK) {
            if ($deleteTempFile) return move_uploaded_file($this->tmpName, $file);
            else if (is_uploaded_file($this->tempName)) return copy($this->tempName, $file);
            else return FALSE;
        } else return FALSE;
    }
    private function allowedExtension($field, $extensions) {
        if (!empty($_FILES[$field]["name"])) {
            $tmp = explode(".", $_FILES[$field]["name"]);
            return in_array(end($tmp), $extensions);
        }
        return TRUE;
    }
}