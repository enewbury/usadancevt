<?php
/**
 * Created by enewbury.
 * Date: 12/21/15
 */

namespace EricNewbury\DanceVT\Util;


use EricNewbury\DanceVT\Models\Exceptions\InternalErrorException;

class UrlTool
{

    public static function myDomain(){
        return (!empty($_SERVER['HTTPS'])) ? 'https://'.$_SERVER['HTTP_HOST'] : 'http://'.$_SERVER['HTTP_HOST'];
    }
    public static function hasSameDomain($url1, $url2){
        $parsed1 = parse_url($url1);
        $parsed2 = parse_url($url2);

        $host1 = null;
        $host2 = null;
        if(array_key_exists('host',$parsed1)){
            $host1 = $parsed1['host'];
        }
        if(array_key_exists('host',$parsed2)){
            $host2 = $parsed2['host'];
        }

        return ($host1 == $host2);
    }

    public static function camelCase($str, array $noStrip = [])
    {
        // non-alpha and non-numeric characters become spaces
        $str = preg_replace('/[^a-z0-9' . implode("", $noStrip) . ']+/i', ' ', $str);
        $str = trim($str);
        // uppercase the first character of each word
        $str = ucwords($str);
        $str = str_replace(" ", "", $str);
        $str = lcfirst($str);

        return $str;
    }

    public static function getUrlVar($urlArray){

    }

    /**
     * @param string $url
     * @param array $data
     * @return \stdClass
     * @throws InternalErrorException
     */
    public static function postToUrl($url, $data){
        $data = http_build_query($data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt( $ch, CURLOPT_POST, 1);
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        if(curl_errno($ch)) {
            throw new InternalErrorException('Failed to connect to reCaptcha server. Try again later.');
        }
        curl_close($ch);
        return json_decode($result);
    }
    
}