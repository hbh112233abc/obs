<?php

use bingher\obs\driver\S3;
use bingher\obs\tests\TestHelper;
use PHPUnit\Framework\TestCase;

/**
 * S3驱动实际运行测试
 */
class S3Test extends TestCase
{
    /**
     * @var S3
     */
    protected $s3;

    /**
     * 测试文件路径
     * @var string
     */
    protected $testFilePath;

    /**
     * 测试对象key
     * @var string
     */
    protected $testKey;

    /**
     * 设置测试环境
     */
    protected function setUp(): void
    {
        // 加载配置
        $config = TestHelper::loadConfig('S3');
        if (! $config) {
            $this->markTestSkipped('S3配置不存在');
        }

        // 创建真实的S3实例
        $this->s3 = new S3($config);

        // 准备测试数据
        $this->testKey      = 'test_' . uniqid() . '.txt';
        $this->testFilePath = TestHelper::createTestFile('This is a test file for S3.');
    }

    /**
     * 清理测试资源
     */
    protected function tearDown(): void
    {
        // 清理测试文件
        TestHelper::cleanupTestFile($this->testFilePath);

        // 尝试删除上传的对象
        try {
            $this->s3->delete($this->testKey);
        } catch (\Exception $e) {
            // 忽略删除失败的异常
        }
    }

    /**
     * 测试put方法 - 实际上传文件
     */
    public function testPut()
    {
        // 执行实际上传
        $result = $this->s3->put($this->testKey, $this->testFilePath);

        // 验证上传结果
        $this->assertTrue($result, '文件上传失败: ' . $this->s3->getError());

        // 验证文件确实存在
        $exists = $this->s3->exist($this->testKey);
        $this->assertTrue($exists, '上传后文件不存在');
    }

    /**
     * 测试get方法 - 实际下载文件
     */
    public function testGet()
    {
        // 先上传文件
        $this->s3->put($this->testKey, $this->testFilePath);

        // 创建下载目标路径
        $downloadPath = dirname($this->testFilePath) . '/download_' . basename($this->testFilePath);

        // 执行实际下载
        $result = $this->s3->get($this->testKey, $downloadPath);

        // 验证下载结果
        $this->assertTrue($result, '文件下载失败: ' . $this->s3->getError());
        $this->assertFileExists($downloadPath);

        // 清理下载的文件
        TestHelper::cleanupTestFile($downloadPath);
    }

    /**
     * 测试delete方法 - 实际删除文件
     */
    public function testDelete()
    {
        // 先上传文件
        $this->s3->put($this->testKey, $this->testFilePath);

        // 验证文件存在
        $this->assertTrue($this->s3->exist($this->testKey));

        // 执行实际删除
        $result = $this->s3->delete($this->testKey);

        // 验证删除结果
        $this->assertTrue($result, '文件删除失败: ' . $this->s3->getError());
        $this->assertFalse($this->s3->exist($this->testKey), '删除后文件仍然存在');
    }

    /**
     * 测试exist方法 - 实际检查文件是否存在
     */
    public function testExist()
    {
        // 初始状态应该不存在
        $this->assertFalse($this->s3->exist($this->testKey), '测试开始前文件已存在');

        // 上传后应该存在
        $this->s3->put($this->testKey, $this->testFilePath);
        $this->assertTrue($this->s3->exist($this->testKey), '上传后文件不存在');

        // 删除后应该不存在
        $this->s3->delete($this->testKey);
        $this->assertFalse($this->s3->exist($this->testKey), '删除后文件仍然存在');
    }

    /**
     * 测试url方法 - 实际获取预签名URL
     */
    public function testUrl()
    {
        // 先上传文件
        $this->s3->put($this->testKey, $this->testFilePath);

        // 获取预签名URL
        $url = $this->s3->url($this->testKey, 3600);

        // 验证URL格式
        $this->assertNotEmpty($url, '获取URL失败: ' . $this->s3->getError());
        $this->assertStringStartsWith('https://', $url);
        $this->assertStringContainsString($this->testKey, $url);
    }

    /**
     * 测试静态url方法（不带过期时间）
     */
    public function testStaticUrl()
    {
        // 先上传文件
        $this->s3->put($this->testKey, $this->testFilePath);

        // 获取静态URL
        $url = $this->s3->url($this->testKey, -1);

        // 验证URL格式
        $this->assertNotEmpty($url, '获取静态URL失败: ' . $this->s3->getError());
        $this->assertStringContainsString($this->testKey, $url);
    }

    /**
     * 测试putUrl方法 - 实际获取上传预签名URL
     */
    public function testPutUrl()
    {
        // 获取上传预签名URL
        $result = $this->s3->putUrl($this->testKey, 'text/plain', 3600);

        // 验证结果
        $this->assertIsArray($result, '获取上传URL失败: ' . $this->s3->getError());
        $this->assertCount(2, $result);
        $this->assertStringStartsWith('https://', $result[0]);
        $this->assertNotEmpty($result[1]);
    }
}
