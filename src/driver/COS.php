<?php
namespace bingher\obs\driver;

use Qcloud\Cos\Client as CosClient;

class COS extends Driver
{
    /**
     * 配置信息数组
     *
     * @var array
     */
    protected $config = [
        'key'             => '*** Provide your Access Key ***',
        'secret'          => '*** Provide your Secret Key ***',
        'endpoint'        => 'https://your-endpoint',
        'region'          => '*** Provide server Region ***',
        'ssl_verify'      => false,
        'max_retry_count' => 3,
        'socket_timeout'  => 20,
        'connect_timeout' => 20,
        'chunk_size'      => 8196,
    ];
    function __construct(array $config)
    {
        $this->config['endpoint'] = $config['endpoint'] ?? '';
        $this->config['key']      = $config['key'] ?? '';
        $this->config['secret']   = $config['secret'] ?? '';
        $this->config['region']   = $config['region'] ?? '';
        $this->bucket             = $config['bucket'] ?? '';

        $options      = [
            'region'      => $this->config['region'],
            'schema'      => $this->config['ssl_verify'] ? 'https' : 'http',
            'credentials' => [
                'secretId'  => $this->config['key'],
                'secretKey' => $this->config['secret'],
            ],
        ];
        $this->client = new CosClient($options);
    }

    /**
     * 上传文件
     *
     * @param string $key      对象key
     * @param string $filePath 本地文件路径
     *
     * @return bool
     */
    public function put(string $key, string $filePath): bool
    {
        if (is_file($filePath)) {
            $body = fopen($filePath, 'rb');
        } else {
            $body = $filePath;
        }

        try {
            $this->client->Upload($this->bucket, $key, $body);
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
        if (!is_dir($dir)) {
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
    public function checkCors(string $domain = "*", string $id = 'all')
    {
    }

    /**
     * 获取预授权链接
     *
     * @param string $key    对象key
     * @param int    $expire 过期时间(秒),默认3600
     *
     * @return string
     */
    public function url(string $key, int $expire = 3600): string
    {
        return $this->client->getObjectUrl($this->bucket, $key, $expire);
    }

    /**
     * 获取上传文件预授权链接
     *
     * @param string $key         对象key
     * @param string $contentType 头部类型
     * @param int    $expire      过期时间(秒),默认3600
     *
     * @return array
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
                        'content-type' => $contentType,
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
    }
}
