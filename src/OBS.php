<?php
namespace bingher\obs;

use ReflectionClass;
use ReflectionException;
use bingher\obs\DriverType;

/**
 * 对象存储客户端
 *
 * @mixin Driver
 */
class OBS
{
    protected string $namespace = __NAMESPACE__;
    protected Driver $driver;

    /**
     * 客户端配置数组
     *
     * @var array{
     *     type: string,
     *     endpoint?: string,
     *     key?: string,
     *     secret?: string,
     *     bucket?: string,
     *     region?: string,
     *     ssl_verify?: bool,
     *     timeout?: int,
     *     connect_timeout?: int,
     *     driver_options?: array<string, mixed>,
     *     ...
     * }|
     * array<string, mixed>
     */
    public array $config = [];

    /**
     * 构造函数
     *
     * @param array{
     *     type: string,
     *     endpoint?: string,
     *     key?: string,
     *     secret?: string,
     *     bucket?: string,
     *     region?: string,
     *     ssl_verify?: bool,
     *     timeout?: int,
     *     connect_timeout?: int,
     *     driver_options?: array<string, mixed>,
     *     ...
     * } $config 配置参数
     */
    public function __construct(array $config)
    {
        $this->config = array_merge($this->config, $config);

        $type = $config['type'];
        $this->setDriver($type);
    }

    /**
     * 切换驱动
     *
     * @param string $type 驱动类型（推荐使用DriverType常量）
     * @return $this
     * @throws \Exception
     */
    public function setDriver(string $type): self
    {
        $this->config['type'] = $type;
        
        // 使用DriverType验证驱动类型
        if (!DriverType::isValid($type)) {
            throw new \Exception(
                'Invalid driver type: ' . $type . 
                '. Must be one of: ' . implode(', ', DriverType::getValues())
            );
        }
        
        try {
            $class   = $this->namespace . '\\driver\\' . $type;
            $reflect = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            // 检查是否是类不存在的错误
            if (strpos($e->getMessage(), 'Class') !== false && strpos($e->getMessage(), 'not found') !== false) {
                throw new \Exception('Driver class not exists: ' . $class . '. Please check if the class file exists.');
            }
            // 检查是否是缺少依赖的错误
            $missingDeps = $this->checkMissingDependencies($type);
            if (!empty($missingDeps)) {
                throw new \Exception(
                    'Missing dependencies for driver ' . $type . '. Please install: ' . 
                    implode(', ', $missingDeps) . 
                    '. Use: composer require ' . implode(' ', $missingDeps)
                );
            }
            throw new \Exception('Failed to load driver: ' . $class . '. Error: ' . $e->getMessage());
        }
        $this->driver = $reflect->newInstance($this->config);
        return $this;
    }

    /**
     * 检查驱动所需的依赖包是否已安装
     *
     * @param string $driver 驱动名称
     * @return array 缺少的依赖包名称列表
     */
    protected function checkMissingDependencies(string $driver): array
    {
        $missing = [];
        
        // 映射驱动到所需的依赖包
        $dependencies = [
            'AliOSS' => ['aliyuncs/oss-sdk-php'],
            'COS' => ['qcloud/cos-sdk-v5'],
            'HwOBS' => ['obs/esdk-obs-php'],
            'MinIO' => ['aws/aws-sdk-php'],
            'S3' => ['aws/aws-sdk-php'],
            'SeaweedFS' => ['aws/aws-sdk-php'],
            'RustFS' => ['aws/aws-sdk-php'],
        ];
        
        // 检查该驱动是否有对应的依赖定义
        if (isset($dependencies[$driver])) {
            foreach ($dependencies[$driver] as $package) {
                // 简单检查方法：尝试加载该包的关键类
                switch ($package) {
                    case 'aliyuncs/oss-sdk-php':
                        if (!class_exists('OSS\OssClient')) {
                            $missing[] = $package;
                        }
                        break;
                    case 'qcloud/cos-sdk-v5':
                        if (!class_exists('Qcloud\Cos\Client')) {
                            $missing[] = $package;
                        }
                        break;
                    case 'obs/esdk-obs-php':
                        if (!class_exists('Obs\ObsClient')) {
                            $missing[] = $package;
                        }
                        break;
                    case 'aws/aws-sdk-php':
                        if (!class_exists('Aws\S3\S3Client')) {
                            $missing[] = $package;
                        }
                        break;
                }
            }
        }
        
        return $missing;
    }
    
    /**
     * 调用驱动方法
     *
     * @param string $method 方法名
     * @param array<int, mixed> $args 参数
     * @return mixed
     */
    public function __call(string $method, array $args): mixed
    {
        return call_user_func_array([$this->driver, $method], $args);
    }
}
