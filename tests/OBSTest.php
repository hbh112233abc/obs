<?php
/**
 * Class OBSTest
 */
use PHPUnit\Framework\TestCase;

class OBSTest extends TestCase
{
    /**
     * @var \bingher\obs\OBS
     */
    protected $obs;

    /**
     * Set up
     */
    protected function setUp()
    {
        $this->obs = new \bingher\obs\OBS();
    }

    /**
     * Test upload file
     */
    public function testUploadFile()
    {
        $this->assertTrue($this->obs->uploadFile('test.txt', 'test content'));
    }

    /**
     * Test download file
     */
    public function testDownloadFile()
    {
        $this->assertEquals('test content', $this->obs->downloadFile('test.txt'));
    }

    /**
     * Test delete file
     */
    public function testDeleteFile()
    {
        $this->assertTrue($this->obs->deleteFile('test.txt'));
    }
}
