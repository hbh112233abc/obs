<?php
namespace bingher\obs;

abstract class Driver
{
    /**
     * 统一的配置信息数组结构
     *
     * @var array{
     *     endpoint: string,
     *     key: string,
     *     secret: string,
     *     bucket: string,
     *     region: string,
     *     ssl_verify: bool,
     *     timeout: int,
     *     connect_timeout: int,
     *     driver_options: array<string, mixed>
     * }
     */
    protected array $config = [
        // 基础配置
        'endpoint'    => '', // 服务端点URL
        'key'         => '', // 访问密钥ID
        'secret'      => '', // 访问密钥Secret
        'bucket'      => '', // 存储桶名称
        'region'      => '', // 区域
        
        // 可选配置
        'ssl_verify'  => false, // 是否验证SSL证书
        'timeout'     => 30, // 超时时间(秒)
        'connect_timeout' => 10, // 连接超时时间(秒)
        
        // 驱动特定配置(可选)
        'driver_options' => [],
    ];
    
    /**
     * 连接客户端
     *
     * @var mixed
     */
    public $client;

    /**
     * 桶名称
     *
     * @var string
     */
    protected string $bucket = '';

    /**
     * 错误信息
     *
     * @var string
     */
    protected string $error = '';

    /**
     * 构造函数
     *
     * @param array<string, mixed> $config 统一格式的配置参数
     */
    abstract public function __construct(array $config = []);
    
    /**
     * 标准化配置格式
     * 将用户提供的配置转换为统一格式
     *
     * @param array<string, mixed> $config 用户配置
     * @return array{
     *     endpoint: string,
     *     key: string,
     *     secret: string,
     *     bucket: string,
     *     region: string,
     *     ssl_verify: bool,
     *     timeout: int,
     *     connect_timeout: int,
     *     driver_options: array<string, mixed>
     * }
     */
    protected function normalizeConfig(array $config): array
    {
        // 合并用户配置到默认配置
        $normalized = array_merge($this->config, $config);
        
        // 特殊处理：确保桶名称也在独立属性中设置
        if (!empty($normalized['bucket'])) {
            $this->bucket = $normalized['bucket'];
        }
        
        return $normalized;
    }
    
    /**
     * 获取驱动特定的配置
     * 子类可以覆盖此方法来转换统一配置为驱动所需的特定格式
     *
     * @return array<string, mixed>
     */
    protected function getDriverConfig(): array
    {
        return $this->config;
    }

    /**
     * 写入
     *
     * @param string $key      对象key
     * @param string $filePath 本地文件路径
     * @param string $acl      权限名称 private,public-read
     * @param string $contentType 响应头部类型
     *
     * @return bool
     */
    abstract public function put(string $key, string $filePath, string $acl = 'private', string $contentType = ''): bool;

    /**
     * 取出
     *
     * @param string $key      对象key
     * @param string $filePath 本地文件路径
     *
     * @return bool
     */
    abstract public function get(string $key, string $filePath): bool;

    /**
     * 获取文件访问链接
     *
     * @param string $key         对象key
     * @param int    $expire      有效期(秒)
     * @param string $contentType 响应头部类型
     *
     * @return string
     */
    abstract public function url(string $key, int $expire = 3600, string $contentType = ''): string;

    /**
     * 获取预授权链接
     *
     * @param string $key          对象key
     * @param string $contentType 头部类型
     * @param int    $expire      有效期(秒)
     *
     * @return array<string, string>
     */
    abstract public function putUrl(string $key, string $contentType, int $expire = 3600): array;

    /**
     * 判断对象是否存在
     *
     * @param  string $key 对象ObjectKey
     *
     * @return bool        是否存在
     */
    abstract public function exist(string $key): bool;

    /**
     * 删除对象
     *
     * @param  string $key 对象ObjectKey
     *
     * @return bool        删除结果
     */
    abstract public function delete(string $key): bool;

    /**
     * 下载文件到本地
     *
     * @param string $url  文件地址
     * @param string $filePath 存放路径
     *
     * @return bool
     */
    public function download(string $url, string $filePath): bool
    {
        $dir = dirname($filePath);
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $client = null;
        $response = null;
        $body = null;

        try {
            $client   = new \GuzzleHttp\Client(['verify' => false]);
            $response = $client->get($url);
            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException('下载失败[' . $response->getStatusCode() . ']' . $url);
            }
            $body = $response->getBody();
            file_put_contents($filePath, $body);
            return is_file($filePath);
        } finally {
            // 确保资源正确关闭
            if ($body instanceof \Psr\Http\Message\StreamInterface) {
                $body->close();
            }
            // Guzzle客户端和响应对象在PHP 7.4+中通常会自动进行垃圾回收
        }
    }

    /**
     * 获取或设置bucket
     *
     * @param string $bucket 桶名称,空值表示获取当前桶名称
     *
     * @return string|self
     */
    public function bucket(string $bucket = ''): string|self
    {
        if (empty($bucket)) {
            return $this->bucket;
        }
        $this->bucket = $bucket;
        return $this;
    }

    /**
     * 获取错误信息
     *
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * 调用原始方法
     *
     * @param  string $method 方法名
     * @param  array<int, mixed> $args   传参
     *
     * @return mixed         执行结果
     */
    public function __call(string $method, array $args): mixed
    {
        return call_user_func_array([$this->client, $method], $args);
    }
}
