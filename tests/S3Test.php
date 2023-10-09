<?php

use PHPUnit\Framework\TestCase;
use bingher\obs\driver\S3;
use Noodlehaus\Config;

class S3Test extends TestCase
{
    protected $s3;

    protected function setUp(): void
    {
        $conf     = Config::load(__DIR__ . '/../.env.ini');
        $this->s3 = new S3($conf['S3']);
    }

    public function testPut()
    {
        $key      = 'test.txt';
        $filePath = './test.txt';
        $result   = $this->s3->put($key, $filePath);
        $this->assertTrue($result);
    }

    public function testGet()
    {
        $key      = 'test.txt';
        $filePath = './test.txt';
        $result   = $this->s3->get($key, $filePath);
        $this->assertTrue($result);
    }

    public function testDelete()
    {
        $key    = 'test.txt';
        $result = $this->s3->delete($key);
        $this->assertTrue($result);
    }

    public function testExist()
    {
        $key    = 'test.txt';
        $result = $this->s3->exist($key);
        $this->assertFalse($result);
    }

    public function testUrl()
    {
        $key    = 'test.txt';
        $expire = 3600;
        $url    = $this->s3->url($key, $expire);
        $this->assertStringContainsString($key, $url);
    }

    public function testStaticUrl()
    {
        $key = '002iRMxrly1gvrjfdko17g60b4069npe02.gif';
        $url = $this->s3->url($key, -1);
        var_dump($url);
        $this->assertStringEndsWith($key, $url);

    }

    public function testPutUrl()
    {
        $key         = 'test.txt';
        $contentType = 'text/plain';
        $expire      = 3600;

        $result = $this->s3->putUrl($key, $contentType, $expire);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result[0]);
        $this->assertNotEmpty($result[1]);
    }
}
