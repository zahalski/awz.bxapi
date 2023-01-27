<?
namespace Awz\BxApi;

class Log {

    public $fileName = null;
    public $fileDir = null;
    public $disable = false;

    function __construct($config=array()){

        if($config['FILE_NAME']) $this->fileName = $config['FILE_NAME'];
        if($config['FILE_DIR']) $this->fileDir = $config['FILE_DIR'];

        if(!$this->fileName && defined("DEBUG_FILE_NAME")) $this->fileName = DEBUG_FILE_NAME;
        if(!$this->fileDir && defined("DEFAULT_DIR")) $this->fileDir = DEFAULT_DIR;

        if(!$this->fileName || !$this->fileDir) $this->disable = true;
    }

    public function add($data, $title = ''){
        if($this->disable) return;
        if (!$this->fileName || !$this->fileDir)
            return false;

        $log = "\n------------------------\n";
        $log .= date("Y.m.d G:i:s")."\n";
        $log .= (strlen($title) > 0 ? $title : 'DEBUG')."\n";
        $log .= print_r($data, 1);
        $log .= "\n------------------------\n";

        //file_put_contents(DEFAULT_DIR."/".DEBUG_FILE_NAME, $log, FILE_APPEND);
        file_put_contents($this->fileDir."/".$this->fileName, $log, FILE_APPEND);

        return true;
    }

    public function disable(){
        $this->disable = true;
    }

    public function enable(){
        $this->disable = false;
    }

    public function reverse($in='')
    {
        $lines = explode("\n", trim($in));
        if (trim($lines[0]) != 'Array') {
            // bottomed out to something that isn't an array
            return trim($in);
        } else {
            // this is an array, lets parse it
            if (preg_match("/(\\s{5,})\\(/", $lines[1], $match)) {
                // this is a tested array/recursive call to this function
                // take a set of spaces off the beginning
                $spaces = $match[1];
                $spaces_length = strlen($spaces);
                $lines_total = count($lines);
                for ($i = 0; $i < $lines_total; $i++) {
                    if (substr($lines[$i], 0, $spaces_length) == $spaces) {
                        $lines[$i] = substr($lines[$i], $spaces_length);
                    }
                }
            }
            array_shift($lines);
            // Array
            array_shift($lines);
            // (
            array_pop($lines);
            // )
            $in = implode("\n", $lines);
            // make sure we only match stuff with 4 preceding spaces (stuff for this array and not a nested one)
            preg_match_all("/^\\s{4}\\[(.+?)\\] \\=\\> /m", $in, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
            $pos = array();
            $previous_key = '';
            $in_length = strlen($in);
            // store the following in $pos:
            // array with key = key of the parsed array's item
            // value = array(start position in $in, $end position in $in)
            foreach ($matches as $match) {
                $key = $match[1][0];
                $start = $match[0][1] + strlen($match[0][0]);
                $pos[$key] = array($start, $in_length);
                if ($previous_key != '') {
                    $pos[$previous_key][1] = $match[0][1] - 1;
                }
                $previous_key = $key;
            }
            $ret = array();
            foreach ($pos as $key => $where) {
                // recursively see if the parsed out value is an array too
                $ret[$key] = $this->reverse(substr($in, $where[0], $where[1] - $where[0]));
            }
            return $ret;
        }
    }

}