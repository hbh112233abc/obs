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

    public function testPutUrl()
    {
        $key = 'test.txt';
        $url = $this->obs->putUrl($key);
        $this->assertStringContainsString($key, $url);
    }

}
