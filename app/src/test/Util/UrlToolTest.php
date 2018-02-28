<?php
/**
 * Created by enewbury.
 * Date: 12/21/15
 */

namespace EricNewbury\DanceVT\Util;


use PHPUnit\Framework\TestCase;

class UrlToolTest extends TestCase
{

    public function testNoExceptionThrownWhenUrlIsNull(){
        $url1 = "http://www.google.com";
        $url2 = null;

        $this->assertFalse(UrlTool::hasSameDomain($url1,$url2));
    }

    public function testNonEqualUrl(){
        $url1 = "http://www.yahoo.com";
        $url2 = "http://www.google.com";

        $this->assertFalse(UrlTool::hasSameDomain($url1,$url2));
    }

    public function testEquals(){
        $url1 = "http://www.google.com/feel-the-bern";
        $url2 = "http://www.google.com/login";

        $this->assertTrue(UrlTool::hasSameDomain($url1,$url2));
    }
}