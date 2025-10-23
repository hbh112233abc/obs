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
            throw new \Exception('Driver class not exists: ' . $class . '. Error: ' . $e->getMessage());
        }
        $this->driver = $reflect->newInstance($this->config);
        return $this;
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
