<?php

use PHPUnit\Framework\TestCase;
use bingher\obs\driver\HwOBS;
use Noodlehaus\Config;

class HwOBSTest extends TestCase
{
    protected $obs;

    protected function setUp(): void
    {
        $conf      = Config::load(__DIR__ . '/../.env.ini');
        $this->obs = new HwOBS($conf['HwOBS']);
    }

    public function testPut()
    {
        $key      = 'test.txt';
        $filePath = './test.txt';
        $result   = $this->obs->put($key, $filePath);
        $this->assertTrue($result);
    }

    public function testGet()
    {
        $key      = 'test.txt';
        $filePath = './test.txt';
        $result   = $this->obs->get($key, $filePath);
        $this->assertTrue($result);
    }

    public function testDelete()
    {
        $key    = 'test.txt';
        $result = $this->obs->delete($key);
        $this->assertTrue($result);
    }

    public function testExist()
    {
        $key    = 'test.txt';
        $result = $this->obs->exist($key);
        $this->assertFalse($result);
    }

    public function testUrl()
    {
        $key = 'test.txt';
        $url = $this->obs->url($key);
        $this->assertStringContainsString($key, $url);
    }

    public function testStaticUrl()
    {
        $key = '1ae0dc66667511ee93f34ccc6a463368.png';
        $url = $this->obs->url($key, -1);
        var_dump($url);
        $this->assertStringEndsWith($key, $url);
    }

    public function testPutUrl()
    {
        $key = 'test.txt';
        $url = $this->obs->putUrl($key);
        $this->assertStringContainsString($key, $url);
    }

    public function testUrlResponseContentType()
    {
        $key = '2346dcf731a4a604d931764a1d4e3d72/SRCGC2400649/32cebf00b5b711eeb2a6da1422e042c9_1.pdf';
        $url = $this->obs->url($key, 360, 'application/pdf');
        print_r($url);
        print_r('');
        $respHeaders = get_headers($url, true);
        print_r($respHeaders);
        $this->assertEquals('application/pdf', $respHeaders['Content-Type']);
    }

}
