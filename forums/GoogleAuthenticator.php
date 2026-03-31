<?php
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
//      http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.


include_once("FixedByteNotation.php");


class GoogleAuthenticator {
    static $PASS_CODE_LENGTH = 6;
    static $PIN_MODULO;
    static $SECRET_LENGTH = 32;
    
    public function __construct() {
        self::$PIN_MODULO = pow(10, self::$PASS_CODE_LENGTH);
    }
    
    public function checkCode($secret,$code) {
        $time = floor(time() / 30);
        for ( $i = -1; $i <= 1; $i++) {
            
            if ($this->getCode($secret,$time + $i) == $code) {
                return true;
            }
        }
        
        return false;
        
    }
    
    public function getCode($secret,$time = null) {
        $secret = $this->normalizeSecret($secret);
        
        if (!$time) {
            $time = floor(time() / 30);
        }
        $base32 = new FixedBitNotation(5, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567', TRUE, TRUE);
        $secret = $base32->decode($secret);
        
        $time = pack("N", $time);
        $time = str_pad($time,8, chr(0), STR_PAD_LEFT);
        
        $hash = hash_hmac('sha1',$time,$secret,true);
        $offset = ord(substr($hash,-1));
        $offset = $offset & 0xF;
        
        $truncatedHash = self::hashToInt($hash, $offset) & 0x7FFFFFFF;
        $pinValue = str_pad($truncatedHash % self::$PIN_MODULO,6,"0",STR_PAD_LEFT);;
        return $pinValue;
    }

    public function normalizeSecret($secret) {
        $secret = strtoupper(trim((string) $secret));
        return preg_replace('/[^A-Z2-7]/', '', $secret);
    }
    
    protected  function hashToInt($bytes, $start) {
        $input = substr($bytes, $start, strlen($bytes) - $start);
        $val2 = unpack("N",substr($input,0,4));
        return $val2[1];
    }
    
    public function getUrl($user, $hostname, $secret) {
        $issuer = trim((string) $hostname);
        $account = trim((string) $user);
        $secret = $this->normalizeSecret($secret);
        $label = rawurlencode($account);

        if ($issuer !== '') {
            $label = rawurlencode($issuer).':'.rawurlencode($account);
        }

        return 'otpauth://totp/'.$label.'?'.http_build_query(array(
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => self::$PASS_CODE_LENGTH,
            'period' => 30,
        ), '', '&', PHP_QUERY_RFC3986);
        
    }
    
    public function generateSecret() {
        $secret = random_bytes(self::$SECRET_LENGTH);
        $base32 = new FixedBitNotation(5, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567', TRUE, TRUE);
        return $this->normalizeSecret($base32->encode($secret));
        
        
    }
    
}
