<?php
namespace bingher\obs\driver;

use bingher\obs\Driver;
use OSS\Core\OssException;
use OSS\Model\CorsConfig;
use OSS\Model\CorsRule;
use OSS\OssClient;

class AliOSS extends Driver
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
        'bucket'          => 'bucket name',
        'ssl_verify'      => false,
        'max_retry_count' => 3,
        'socket_timeout'  => 20,
        'connect_timeout' => 20,
        'chunk_size'      => 8196,
    ];
    function __construct(array $config)
    {
        empty($config['endpoint']) ?: $this->config['endpoint'] = $config['endpoint'];
        empty($config['key']) ?: $this->config['key'] = $config['key'];
        empty($config['secret']) ?: $this->config['secret'] = $config['secret'];
        empty($config['bucket']) ?: $this->bucket = $this->config['bucket'];

        $this->bucket = $this->config['bucket'];

        $this->client = new OssClient($this->config['key'], $this->config['secret'], $this->config['endpoint']);
        $this->client->setUseSSL(false);
        $this->checkCors();
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
        try {
            $this->client->uploadFile($this->bucket, $key, $filePath);
            return true;
        } catch (OssException $e) {
            $this->error = $e->getMessage();
            return false;
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
            $options = [
                OssClient::OSS_FILE_DOWNLOAD => $filePath,
            ];
            $this->client->getObject($this->bucket, $key, $options);
            return true;
        } catch (OssException $e) {
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
    public function checkCors(string $domain = "*")
    {
        $corsConfig = new CorsConfig;
        $corsRule   = new CorsRule;
        $corsRule->addAllowedOrigin($domain);
        $corsRule->addAllowedMethod("PUT");
        $corsRule->addAllowedMethod("GET");
        $corsRule->setMaxAgeSeconds(600);
        $corsRule->addAllowedHeader('*');
        $corsConfig->addRule($corsRule);
        $this->client->putBucketCors($this->bucket, $corsConfig);
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
        $options = [
            "response-content-disposition" => "inline",
        ];

        try {
            // 生成下载对象的带授权信息的URL
            $resp = $this->client->signUrl(
                $this->bucket,
                $key,
                $expire,
                'GET',
                $options
            );
            $resp = str_replace('-internal', '', $resp);
            $resp = str_replace('http://', 'https://', $resp);
            return $resp;
        } catch (OssException $e) {
            $this->error = $e->getMessage();
            return '';
        }
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
            $options = [
                'Content-Type' => $contentType,
            ];
            // 生成签名URL
            $signedUrl = $this->client->signUrl(
                $this->bucket,
                $key,
                $expire,
                "PUT",
                $options
            );
            $signedUrl = str_replace('-internal', '', $signedUrl);
            $signedUrl = str_replace('http://', 'https://', $signedUrl);
            return [$signedUrl, $this->bucket . '.' . str_replace('https://', '', $this->config['endpoint'])];
        } catch (OssException $e) {
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
            $this->client->deleteObject($this->bucket, $key);
            return true;
        } catch (OssException $e) {
            $this->error = $e->getMessage();
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
        return $this->client->doesObjectExist($this->bucket, $key);
    }
}
