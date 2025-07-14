<?php
namespace bingher\obs;

use ReflectionClass;
use ReflectionException;

/**
 * 对象存储客户端
 *
 * @mixin Driver
 */
class OBS
{
    protected $namespace = __NAMESPACE__;
    protected $driver;

    /**
     * config array
     *
     * @var array
     */
    public $config = [];

    public function __construct(array $config)
    {
        $this->config = array_merge($this->config, $config);

        $type = $config['type'];
        try {
            $class   = $this->namespace . '\\driver\\' . $type;
            $reflect = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            $drivers = json_encode(
                array_map(
                    function ($dir) {
                        return str_replace('.php', '', $dir);
                    },
                    scandir(__DIR__ . '/driver')
                )
            );
            throw new \Exception('class not exists: ' . $class . ' must one of ' . $drivers);
        }

        $this->driver = $reflect->newInstance($config);
    }

    public function __call($method, $args)
    {
        return call_user_func_array([$this->driver, $method], $args);
    }
}
