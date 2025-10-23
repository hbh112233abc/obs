<?php
use PHPUnit\Framework\TestCase;
use bingher\obs\Driver;
use bingher\obs\tests\TestHelper;

/**
 * Driver抽象类测试
 */
class DriverTest extends TestCase
{
    /**
     * 测试normalizeConfig方法的配置标准化功能
     */
    public function testNormalizeConfig()
    {
        // 创建一个测试用的匿名类继承Driver并实现所有抽象方法
        $mockDriver = new class extends Driver {
            public function __construct(array $config = [])
            {
                // 简单实现构造函数
                $this->config = $this->normalizeConfig($config);
            }
            
            public function normalizeConfig(array $config): array
            {
                return parent::normalizeConfig($config);
            }
            
            // 实现所有必需的抽象方法
            public function put(string $key, string $filePath, string $acl = 'private', string $contentType = ''): bool { return true; }
            public function get(string $key, string $filePath): bool { return true; }
            public function delete(string $key): bool { return true; }
            public function exist(string $key): bool { return false; }
            public function url(string $key, int $expire = 3600, string $contentType = ''): string { return ''; }
            public function putUrl(string $key, string $contentType = 'application/octet-stream', int $expire = 3600): array { return ['', []]; }
        };

        // 尝试从TestHelper加载实际配置
        $actualConfig = [];
        try {
            $actualConfig = TestHelper::loadConfig('alioss');
        } catch (\Exception $e) {
            // TestHelper可能不存在，继续使用默认配置
        }
        
        // 测试基本配置标准化
        $basicConfig = $actualConfig ?: [
            'key' => 'test-key',
            'secret' => 'test-secret',
            'endpoint' => 'https://test-endpoint',
            'bucket' => 'test-bucket'
        ];
        
        $normalizedConfig = $mockDriver->normalizeConfig($basicConfig);
        
        // 验证必需字段存在且值正确
        $this->assertArrayHasKey('key', $normalizedConfig);
        $this->assertArrayHasKey('secret', $normalizedConfig);
        $this->assertArrayHasKey('endpoint', $normalizedConfig);
        $this->assertArrayHasKey('bucket', $normalizedConfig);
        $this->assertEquals($basicConfig['key'], $normalizedConfig['key']);
        $this->assertEquals($basicConfig['secret'], $normalizedConfig['secret']);
        $this->assertEquals($basicConfig['endpoint'], $normalizedConfig['endpoint']);
        $this->assertEquals($basicConfig['bucket'], $normalizedConfig['bucket']);
        
        // 验证默认值
        $this->assertEquals(false, $normalizedConfig['ssl_verify']);
        $this->assertEquals(30, $normalizedConfig['timeout']);
        $this->assertEquals('', $normalizedConfig['region']);
        
        // 测试完整配置
        $fullConfig = [
            'key' => 'full-key',
            'secret' => 'full-secret',
            'endpoint' => 'https://full-endpoint',
            'bucket' => 'full-bucket',
            'region' => 'cn-north-1',
            'ssl_verify' => false,
            'timeout' => 60,
            'driver_options' => ['option1' => 'value1']
        ];
        
        $normalizedFullConfig = $mockDriver->normalizeConfig($fullConfig);
        
        // 验证所有字段都被正确保留
        $this->assertEquals('full-key', $normalizedFullConfig['key']);
        $this->assertEquals('full-secret', $normalizedFullConfig['secret']);
        $this->assertEquals('https://full-endpoint', $normalizedFullConfig['endpoint']);
        $this->assertEquals('full-bucket', $normalizedFullConfig['bucket']);
        $this->assertEquals('cn-north-1', $normalizedFullConfig['region']);
        $this->assertEquals(false, $normalizedFullConfig['ssl_verify']);
        $this->assertEquals(60, $normalizedFullConfig['timeout']);
        $this->assertEquals(['option1' => 'value1'], $normalizedFullConfig['driver_options']);
    }
    
    /**
     * 测试getDriverConfig方法
     */
    public function testGetDriverConfig()
    {
        // 创建一个测试用的匿名类继承Driver
        $mockDriver = new class extends Driver {
            public function __construct(array $config = [])
            {
                // 简单实现构造函数
                $this->config = $this->normalizeConfig($config);
            }
            
            public function getDriverConfig(): array
            {
                return parent::getDriverConfig();
            }
            
            public function normalizeConfig(array $config): array
            {
                return parent::normalizeConfig($config);
            }
            
            // 实现抽象方法以匹配父类签名
            public function put(string $key, string $filePath, string $acl = 'private', string $contentType = ''): bool
            { return true; }
            
            public function get(string $key, string $filePath): bool
            { return true; }
            
            public function delete(string $key): bool
            { return true; }
            
            public function exist(string $key): bool
            { return false; }
            
            public function url(string $key, int $expire = 3600, string $contentType = ''): string
            { return ''; }
            
            public function putUrl(string $key, string $contentType = 'application/octet-stream', int $expire = 3600): array
            { return ['', []]; }
        };
        
        // 设置配置
        $config = [
            'key' => 'test-key',
            'secret' => 'test-secret',
            'endpoint' => 'https://test-endpoint',
            'bucket' => 'test-bucket',
            'driver_options' => ['custom' => 'option']
        ];
        
        // 调用构造函数会自动调用normalizeConfig
        $reflection = new ReflectionClass($mockDriver);
        $constructor = $reflection->getConstructor();
        $constructor->invoke($mockDriver, $config);
        
        // 测试getDriverConfig返回正确的驱动特定配置
        $driverConfig = $mockDriver->getDriverConfig();
        // 验证getDriverConfig返回完整配置
        $this->assertArrayHasKey('driver_options', $driverConfig);
        $this->assertEquals(['custom' => 'option'], $driverConfig['driver_options']);
    }
}


