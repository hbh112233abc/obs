<?php
use PHPUnit\Framework\TestCase;
use bingher\Driver;
class DriverTest extends TestCase
{
    private $driver;

    public function setUp(): void
    {
        $this->driver = new Driver(['key' => 'your-access-key', 'secret' => 'your-secret-key', 'endpoint' => 'your-endpoint', 'bucket' => 'your-bucket']);
    }

    public function testDownload()
    {
        // Create a mock response
        $mockResponse = 'This is the content of the file';
        $streamContext = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => http_build_query([]),
                'ignore_errors' => true,
                'protocol_version' => 1.1,
                'response' => $mockResponse
            ]
        ]);
        
        // Use the mock response in the download method
        $result = $this->driver->download($url, $filePath, $streamContext);
        
        $this->assertFileExists($result);
        $this->assertEquals($mockResponse, file_get_contents($result));
    }

    public function testBucket()
    {
        $this->assertEquals('your_bucket', $this->driver->bucket());

        $this->driver->bucket('new_bucket');

        $this->assertEquals('new_bucket', $this->driver->bucket());
    }
}


