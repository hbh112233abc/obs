<?php
namespace bingher\obs;

abstract class Driver
{
    /**
     * 配置信息数组
     *
     * @var array
     */
    protected $config;
    /**
     * 连接客户端
     *
     * @var object
     */
    public $client;

    /**
     * 桶名称
     *
     * @var string
     */
    protected $bucket;

    /**
     * 错误信息
     *
     * @var string
     */
    protected $error;

    /**
     * 构造函数
     *
     * @param array $config 配置参数
     */
    abstract public function __construct(array $config = []);

    /**
     * 写入
     *
     * @param string $key      对象key
     * @param string $filePath 本地文件路径
     *
     * @return bool
     */
    abstract public function put(string $key, string $filePath);

    /**
     * 取出
     *
     * @param string $key      对象key
     * @param string $filePath 本地文件路径
     *
     * @return bool
     */
    abstract public function get(string $key, string $filePath);

    /**
     * 获取文件访问链接
     *
     * @param string $key         对象key
     * @param int    $expire      有效期(秒)
     * @param string $contentType 响应头部类型
     *
     * @return string
     */
    abstract public function url(string $key, int $expire = 3600, string $contentType = '');

    /**
     * 获取预授权链接
     *
     * @param string $key          对象key
     * @param string $contentType 头部类型
     * @param int    $expire      有效期(秒)
     *
     * @return array [url,host]
     */
    abstract public function putUrl(string $key, string $contentType, int $expire = 3600);

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
    public function download(string $url, string $filePath)
    {
        $dir = dirname($filePath);
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $client   = new \GuzzleHttp\Client(['verify' => false]);
        $response = $client->get($url);
        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('下载失败[' . $response->getStatusCode() . ']' . $url);
        }
        $data = $response->getBody();
        file_put_contents($filePath, $data);
        return is_file($filePath);
    }

    /**
     * 获取或设置bucket
     *
     * @param string $bucket 桶名称,空值表示获取当前桶名称
     *
     * @return string|self
     */
    public function bucket(string $bucket = '')
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
     * @param  array  $args   传参
     *
     * @return mixed         执行结果
     */
    public function __call($method, $args)
    {
        return call_user_func_array([$this->client, $method], $args);
    }
}
