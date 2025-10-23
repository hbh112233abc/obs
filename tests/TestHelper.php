<?php
namespace bingher\obs\tests;

/**
 * 测试辅助类，用于加载配置和提供测试工具
 */
class TestHelper
{
    /**
     * 加载.env.ini配置文件
     *
     * @param string $driver 驱动名称
     * @return array|null 配置数组或null
     */
    public static function loadConfig(string $driver): ?array
    {
        $iniFile = __DIR__ . '/../.env.ini';
        if (! file_exists($iniFile)) {
            return null;
        }

        $config = parse_ini_file($iniFile, true);

        if (isset($config[$driver])) {
            $driverConfig = $config[$driver];

            // 转换为统一配置格式
            return [
                'endpoint'        => $driverConfig['endpoint'] ?? '',
                'key'             => $driverConfig['key'] ?? '',
                'secret'          => $driverConfig['secret'] ?? '',
                'bucket'          => $driverConfig['bucket'] ?? '',
                'region'          => $driverConfig['region'] ?? '', // 提供默认region值
                'ssl_verify'      => false,                         // 开发环境禁用SSL证书验证
                'timeout'         => 30,
                'connect_timeout' => 10,
                'driver_options'  => [],
            ];
        }

        return null;
    }

    /**
     * 创建测试文件
     *
     * @param string $content 测试文件内容
     * @return string 文件路径
     */
    public static function createTestFile(string $content = 'This is a test file content.'): string
    {
        $testDir = __DIR__ . '/temp';
        if (! is_dir($testDir)) {
            mkdir($testDir, 0777, true);
        }

        $fileName = 'test_' . uniqid() . '.txt';
        $filePath = $testDir . '/' . $fileName;
        file_put_contents($filePath, $content);

        return $filePath;
    }

    /**
     * 清理测试文件
     *
     * @param string $filePath 文件路径
     */
    public static function cleanupTestFile(string $filePath): void
    {
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}
