<?
DEFINE('IPTC_OBJECT_NAME', '005');
DEFINE('IPTC_EDIT_STATUS', '007');
DEFINE('IPTC_PRIORITY', '010');
DEFINE('IPTC_CATEGORY', '015');
DEFINE('IPTC_SUPPLEMENTAL_CATEGORY', '020');
DEFINE('IPTC_FIXTURE_IDENTIFIER', '022');
DEFINE('IPTC_KEYWORDS', '025');
DEFINE('IPTC_RELEASE_DATE', '030');
DEFINE('IPTC_RELEASE_TIME', '035');
DEFINE('IPTC_SPECIAL_INSTRUCTIONS', '040');
DEFINE('IPTC_REFERENCE_SERVICE', '045');
DEFINE('IPTC_REFERENCE_DATE', '047');
DEFINE('IPTC_REFERENCE_NUMBER', '050');
DEFINE('IPTC_CREATED_DATE', '055');
DEFINE('IPTC_CREATED_TIME', '060');
DEFINE('IPTC_ORIGINATING_PROGRAM', '065');
DEFINE('IPTC_PROGRAM_VERSION', '070');
DEFINE('IPTC_OBJECT_CYCLE', '075');
DEFINE('IPTC_BYLINE', '080');
DEFINE('IPTC_BYLINE_TITLE', '085');
DEFINE('IPTC_CITY', '090');
DEFINE('IPTC_PROVINCE_STATE', '095');
DEFINE('IPTC_COUNTRY_CODE', '100');
DEFINE('IPTC_COUNTRY', '101');
DEFINE('IPTC_ORIGINAL_TRANSMISSION_REFERENCE',     '103');
DEFINE('IPTC_HEADLINE', '105');
DEFINE('IPTC_CREDIT', '110');
DEFINE('IPTC_SOURCE', '115');
DEFINE('IPTC_COPYRIGHT_STRING', '116');
DEFINE('IPTC_CAPTION', '120');
DEFINE('IPTC_LOCAL_CAPTION', '121');

class iptc {
    var $meta=Array();
    var $hasmeta=false;
    var $file=false;


    function iptc($filename) {
        $size = getimagesize($filename,$info);
        $this->hasmeta = isset($info["APP13"]);
        if($this->hasmeta)
            $this->meta = iptcparse ($info["APP13"]);
        $this->file = $filename;
    }
    function set($tag, $data) {
        $this->meta ["2#$tag"]= Array( $data );
        $this->hasmeta=true;
    }
    function get($tag) {
        return isset($this->meta["2#$tag"]) ? $this->meta["2#$tag"][0] : false;
    }

    function dump() {
        print_r($this->meta);
    }
    function binary() {
        $iptc_new = '';
        foreach (array_keys($this->meta) as $s) {
            $tag = str_replace("2#", "", $s);
            $iptc_new .= $this->iptc_maketag(2, $tag, $this->meta[$s][0]);
        }
        return $iptc_new;
    }
    function iptc_maketag($rec,$dat,$val) {
        $len = strlen($val);
        if ($len < 0x8000) {
            return chr(0x1c).chr($rec).chr($dat).
                chr($len >> 8).
                chr($len & 0xff).
                $val;
        } else {
            return chr(0x1c).chr($rec).chr($dat).
                chr(0x80).chr(0x04).
                chr(($len >> 24) & 0xff).
                chr(($len >> 16) & 0xff).
                chr(($len >> 8 ) & 0xff).
                chr(($len ) & 0xff).
                $val;

        }
    }
    function write() {
        if(!function_exists('iptcembed')) return false;
        $mode = 0;
        $content = iptcembed($this->binary(), $this->file, $mode);
        $filename = $this->file;

        @unlink($filename); #delete if exists

        $fp = fopen($filename, "w");
        fwrite($fp, $content);
        fclose($fp);
    }

    #requires GD library installed
    function removeAllTags() {
        $this->hasmeta=false;
        $this->meta=Array();
        $img = imagecreatefromstring(implode(file($this->file)));
        @unlink($this->file); #delete if exists
        imagejpeg($img,$this->file,100);
    }
};
?>
<?
$filename = 'test.jpg';

if(file_exists ($filename)) {
    if(!unlink ( $filename)) {
        echo "failed to copy $filename...\n";
    }
}

if (!copy('source/'.$filename, $filename)) {
    echo "failed to copy $filename...\n";
}

$fields = get_defined_constants(true)['user'];

?>
<form method="POST">
<?
foreach ($fields as $field => $value) {
    ?>
    <label><?=$field ?>:
        <input
            type="text"
            name="<?=$field ?>"
            value="<?=(isset($_POST[$field]) ? $_POST[$field] : '')?>"
        ><br />
    </label>
    <?
}
?>
    <input type="submit" name="submit_iptc" value="Submit IPTC">
</form>

<?
if(isset($_POST['submit_iptc'])) {
    $i = new iptc($filename);

    foreach ($fields as $field => $value) {
        if(isset($_POST[$field])) {
            $i->set($value, $_POST[$field]);
            echo $value . " : " . $_POST[$field] . "<br>";
        }
    }

    $i->write();
}
?>