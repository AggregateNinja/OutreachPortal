<?php
/*
 * Reference: http://stackoverflow.com/a/10945097
 */

require_once 'BaseObject.php';
require_once 'Utility/Auth.php';

class Proxy extends BaseObject {

    protected $Data = array(
        "Cipher" => "",
        "IVLength" => "",
        "Options" => 0,
        "InitVector" => "",
        "Key" => "",
        "RawData" => array(),
        "EncData" => array()
    );

    private $Cipher = "";
    private $IVLength = "";
    private $Options = 0;
    private $InitVector = "";
    private $Key = "";

    public function __construct(array $data) {

        $this->Data['Cipher'] = "AES-128-CTR";
        //$this->Data['IVLength'] = openssl_cipher_iv_length($this->Data['Cipher']);
        //$this->Data['InitVector'] = '1234567891011121';
        //$this->Data['Key'] = "GeeksforGeeks";

        parent::__construct($data);

        $this->setPublicKeys();
    }

    private function setPublicKeys() {
        if (empty($this->Data['Key'])) {
            /*$key_size = mcrypt_get_key_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CFB);
            $encryption_key = openssl_random_pseudo_bytes($key_size, $strong);*/

            $encryption_key = Auth::generateVerificationCode2(16);

            $this->Data['Key'] = $encryption_key;
        }

        if (empty($this->Data['InitVector'])) {
            /*$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CFB);
            $iv = mcrypt_create_iv($iv_size, MCRYPT_DEV_URANDOM); // 16 bytes output*/

            $iv = Auth::generateVerificationCode2(16);

            $this->Data['InitVector'] = $iv;
        }
    }

    public function encrypt() {
        $encryption_key = $this->Data['Key'];
        $iv = $this->Data['InitVector'];

        if (is_array($this->Data['RawData']) && count($this->Data['RawData']) > 0) {
            foreach ($this->Data['RawData'] as $key => $value) {
                //$this->Data['EncData'][$key] = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $encryption_key, $value, MCRYPT_MODE_CFB, $iv);

                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        $this->Data['EncData'][$key][$k] = openssl_encrypt(
                            $v,
                            $this->Data['Cipher'],
                            $encryption_key,
                            $this->Data['Options'],
                            $iv);
                    }
                } else {
                    $this->Data['EncData'][$key] = openssl_encrypt(
                        $value,
                        $this->Data['Cipher'],
                        $encryption_key,
                        $this->Data['Options'],
                        $iv);
                }


            }
        }
    }

    public function decrypt() {
        $encryption_key = $this->Data['Key'];
        $iv = $this->Data['InitVector'];

        if (is_array($this->Data['EncData']) && count($this->Data['EncData']) > 0) {
            foreach ($this->Data['EncData'] as $key => $value) {
                //$this->Data['RawData'][$key] = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $encryption_key, $value, MCRYPT_MODE_CFB, $iv);

                $this->Data['RawData'][$key] = openssl_decrypt(
                    $value,
                    $this->Data['Cipher'],
                    $encryption_key,
                    $this->Data['Options'],
                    $iv);
            }
        }
    }

    public function getEncData() {
        return $this->Data['EncData'];
    }
    public function getRawData() {
        return $this->Data['RawData'];
    }

    public function getKey() {
        return $this->Data['Key'];
    }

    public function addRawData($key, $value) {
        $this->Data['RawData'][$key] = $value;
    }

    public function getInitVector() {
        return $this->Data['InitVector'];
    }
}
?>
