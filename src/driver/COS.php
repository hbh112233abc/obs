<?php
namespace bingher\obs\driver;

use bingher\obs\Driver;
use bingher\obs\MimeType;
use Qcloud\Cos\Client as CosClient;

class COS extends Driver
{
    /**
     * 连接客户端
     *
     * @var CosClient
     */
    public $client;

    /**
     * 构造函数
     *
     * @param array<string, mixed> $config 统一格式的配置参数
     */
    public function __construct(array $config = [])
    {
        // 使用父类的标准化配置方法
        $this->config = $this->normalizeConfig($config);
        
        // 转换统一配置为腾讯COS SDK所需的特定格式
        $cosConfig = $this->getDriverConfig();
        
        $this->client = new CosClient($cosConfig);
    }
    
    /**
     * 获取腾讯COS驱动特定的配置
     *
     * @return array{
     *     region: string,
     *     schema: string,
     *     credentials: array{
     *         secretId: string,
     *         secretKey: string
     *     },
     *     timeout: int,
     *     connect_timeout: int,
     *     ...
     * }
     */
    protected function getDriverConfig(): array
    {
        $config = [
            'region'      => $this->config['region'] ?? '',
            'schema'      => $this->config['ssl_verify'] ? 'https' : 'http',
            'credentials' => [
                'secretId'  => $this->config['key'],
                'secretKey' => $this->config['secret'],
            ],
            'timeout'     => $this->config['timeout'],
            'connect_timeout' => $this->config['connect_timeout'],
        ];
        
        // 合并驱动特定选项
        return array_merge($config, $this->config['driver_options'] ?? []);
    }

    /**
     * 上传文件
     *
     * @param string $key      对象key
     * @param string $filePath 本地文件路径
     * @param string $acl      权限名称 private,public-read,public-read-write
     * @param string $contentType 响应头部类型
     *
     * @return bool
     */
    public function put(string $key, string $filePath, string $acl = 'private', string $contentType = ''): bool
    {
        $options = [];
        if (is_file($filePath)) {
            $contentType = empty($contentType) ? MimeType::fileMime($filePath) : $contentType;
            $options     = ['ContentType' => $contentType, 'ACL' => $acl];
            $body        = fopen($filePath, 'rb');
        } else {
            $body = $filePath;
            if (! empty($contentType)) {
                $options['ContentType'] = $contentType;
            }
        }

        try {
            $this->client->Upload($this->bucket, $key, $body, $options);
            return true;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            return false;
        } finally {
            if (is_resource($body)) {
                fclose($body);
            }
        }
    }

    /**
     * 下载对象
     *
     * @param string $key      对象key
     * @param string $filePath 存储文件路径
     *
     * @return bool
     */
    public function get(string $key, string $filePath): bool
    {
        $dir = dirname($filePath);
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        try {
            $this->client->getObject(
                [
                    'Bucket' => $this->bucket,
                    'Key'    => $key,
                    'SaveAs' => $filePath,
                ]
            );
            return true;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * 检查并设置跨域支持
     *
     * @param string $domain 允许跨域的域名
     * @param string $id     跨域规则id
     *
     * @return void
     */
    public function checkCors(string $domain = "*", string $id = 'all'): void
    {
    }

    /**
     * 获取预授权链接
     *
     * @param string $key         对象key
     * @param int    $expire      有效期(秒)
     * @param string $contentType 响应头部类型
     *
     * @return string
     */
    public function url(string $key, int $expire = 3600, string $contentType = ''): string
    {
        $option = [];
        if (! empty($contentType)) {
            $options['ResponseContentType'] = $contentType;
        }
        if ($expire === -1) {
            return $this->client->getObjectUrlWithoutSign($this->bucket, $key, $option);
        }
        return $this->client->getObjectUrl($this->bucket, $key, $expire, $option);
    }

    /**
     * 获取上传文件预授权链接
     *
     * @param string $key         对象key
     * @param string $contentType 头部类型
     * @param int    $expire      过期时间(秒),默认3600
     *
     * @return array<string, string>
     */
    public function putUrl(string $key, string $contentType = "application/octet-stream", int $expire = 3600): array
    {
        try {
            $signedUrl = $this->client->getPreSignedUrl(
                'putObject',
                [
                    'Bucket'  => $this->bucket,
                    //存储桶，格式：BucketName-APPID
                    'Key'     => $key,
                    //对象在存储桶中的位置，即对象键
                    'Body'    => 'string',
                    //可为空或任意字符串
                    'Params'  => [],
                    //http 请求参数，传入的请求参数需与实际请求相同，能够防止用户篡改此HTTP请求的参数,默认为空
                    'Headers' => [
                        'Content-Type' => $contentType,
                    ],
                    //http 请求头部，传入的请求头部需包含在实际请求中，能够防止用户篡改签入此处的HTTP请求头部,默认签入host
                ],
                $expire
            ); //签名的有效时间
            return [$signedUrl, $this->config['endpoint']];
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            return [];
        }
    }

    /**
     * 删除对象
     *
     * @param  string $key 对象ObjectKey
     *
     * @return bool        删除结果
     */
    public function delete(string $key): bool
    {
        try {
            $this->client->deleteObject(
                [
                    'Bucket' => $this->bucket,
                    'Key'    => $key,
                ]
            );
            return true;
        } catch (\Throwable $th) {
            $this->error = $th->getMessage();
            return false;
        }
    }

    /**
     * 判断对象是否存在
     *
     * @param  string $key 对象ObjectKey
     *
     * @return bool        是否存在
     */
    public function exist(string $key): bool
    {
        return $this->client->doesObjectExist(
            $this->bucket,
            $key
        );
    }
}
