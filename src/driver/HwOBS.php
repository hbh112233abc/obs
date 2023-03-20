<?php
namespace bingher\obs\driver;

use bingher\obs\Driver;
use Obs\ObsClient;
use Obs\ObsException;

class HwOBS extends Driver
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
        'ssl_verify'      => false,
        'max_retry_count' => 3,
        'socket_timeout'  => 20,
        'connect_timeout' => 20,
        'chunk_size'      => 8196,
    ];

    /**
     * 构造函数
     *
     * @param array $config 配置参数
     */
    public function __construct(array $config = [])
    {
        $this->config['endpoint'] = $config['endpoint'] ?? '';
        $this->config['key']      = $config['key'] ?? '';
        $this->config['secret']   = $config['secret'] ?? '';
        $this->bucket             = $config['bucket'] ?? '';

        $this->client = new ObsClient($this->config);
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
        $param = [
            'Bucket' => $this->bucket,
            'Key'    => $key,
        ];
        if (is_file($filePath)) {
            $param['SourceFile'] = $filePath;
        } else {
            $param['Body'] = $filePath;
        }

        try {
            $this->client->putObject($param);
            return true;
        } catch (ObsException $obsException) {
            $this->error = $obsException->getExceptionMessage();
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
            $resp = $this->client->getObject(
                [
                    'Bucket'     => $this->bucket,
                    'Key'        => $key,
                    'SaveAsFile' => $filePath,
                ]
            );
            return true;
        } catch (ObsException $obsException) {
            $this->error = $obsException->getExceptionMessage();
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
        $domain = '*';
        $this->client->setBucketCors(
            [
                'Bucket'    => $this->bucket,
                'CorsRules' => [
                    [
                        'ID'            => $id,
                        'AllowedMethod' => ['PUT', 'POST', 'GET', 'DELETE', 'HEAD'],
                        'AllowedOrigin' => [$domain],
                        'AllowedHeader' => ['*'],
                        'MaxAgeSeconds' => 600,
                    ],
                ],
            ]
        );
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
        try {
            $this->checkCors();
            // 生成下载对象的带授权信息的URL
            $resp = $this->client->createSignedUrl(
                [
                    'Method'  => 'GET',
                    'Bucket'  => $this->bucket,
                    'Key'     => $key,
                    'Expires' => $expire,
                ]
            );
            return $resp['SignedUrl'];
        } catch (ObsException $obsException) {
            $this->error = $obsException->getExceptionMessage();
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
            $this->checkCors();
            // 生成下载对象的带授权信息的URL
            $resp = $this->client->createSignedUrl(
                [
                    'Method'  => 'PUT',
                    'Bucket'  => $this->bucket,
                    'Key'     => $key,
                    'Expires' => $expire,
                    'Headers' => ['content-type' => $contentType],
                ]
            );
            return [$resp['SignedUrl'], $resp['ActualSignedRequestHeaders']['Host']];
        } catch (ObsException $obsException) {
            $this->error = $obsException->getExceptionMessage();
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
    public function delete(string $key)
    {
        try {
            $this->client->delete(
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
        try {
            $this->client->getObjectMetadata(
                [
                    'Bucket' => $this->bucket,
                    'Key'    => $key,
                ]
            );
            return true;
        } catch (\Obs\ObsException $th) {
            return false;
        }
    }
}
