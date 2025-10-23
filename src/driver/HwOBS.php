<?php
namespace bingher\obs\driver;

use bingher\obs\Driver;
use bingher\obs\MimeType;
use Obs\ObsClient;
use Obs\ObsException;

class HwOBS extends Driver
{
    /**
     * 连接客户端
     *
     * @var ObsClient
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
        
        // 转换统一配置为华为OBS SDK所需的特定格式
        $obsConfig = $this->getDriverConfig();
        
        $this->client = new ObsClient($obsConfig);
    }
    
    /**
     * 获取华为OBS驱动特定的配置
     *
     * @return array{
     *     key: string,
     *     secret: string,
     *     endpoint: string,
     *     region: string,
     *     ssl_verify: bool,
     *     socket_timeout: int,
     *     connect_timeout: int,
     *     ...
     * }
     */
    protected function getDriverConfig(): array
    {
        $config = [
            'key'             => $this->config['key'],
            'secret'          => $this->config['secret'],
            'endpoint'        => $this->config['endpoint'],
            'region'          => $this->config['region'] ?? '',
            'ssl_verify'      => $this->config['ssl_verify'],
            'socket_timeout'  => $this->config['timeout'],
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
     * @param string $acl      权限名称 private,public-read
     * @param string $contentType 头部类型
     *
     * @return bool
     */
    public function put(string $key, string $filePath, string $acl = 'private', string $contentType = ''): bool
    {
        $param = [
            'Bucket' => $this->bucket,
            'Key'    => $key,
        ];
        if (is_file($filePath)) {
            $param['SourceFile'] = $filePath;
            $contentType         = empty($contentType) ? MimeType::fileMime($filePath) : $contentType;
            $param['Headers']    = ['Content-Type' => $contentType, 'x-obs-acl' => $acl];
        } else {
            $param['Body'] = $filePath;
            if (! empty($contentType)) {
                $param['Headers']['Content-Type'] = $contentType;
            }
            $param['Headers']['x-obs-acl'] = $acl;
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
        if (! is_dir($dir)) {
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
    public function checkCors(string $domain = "*", string $id = 'all'): void
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
     * @param string $key         对象key
     * @param int    $expire      有效期(秒)
     * @param string $contentType 响应头部类型
     *
     * @return string
     */
    public function url(string $key, int $expire = 3600, string $contentType = ''): string
    {
        try {
            if ($expire === -1) {
                //获得永久静态链接
                $this->client->setObjectAcl(
                    [
                        'Bucket' => $this->bucket,
                        'Key'    => $key,
                        'ACL'    => ObsClient::AclPublicRead,
                    ]
                );
                $prefix = $this->config['endpoint'];
                if (str_ends_with($prefix, '/')) {
                    $prefix = substr($prefix, 0, -1);
                }
                if (strpos($prefix, 'https://') !== 0) {
                    $prefix = 'https://' . $prefix;
                }
                $prefix = str_replace('https://', 'https://' . $this->bucket . '.', $prefix);
                return $prefix . '/' . $key;
            }
            $this->checkCors();
            $params = [
                'Method'  => 'GET',
                'Bucket'  => $this->bucket,
                'Key'     => $key,
                'Expires' => $expire,
            ];
            if (! empty($contentType)) {
                $params['QueryParams']['ResponseContentType'] = $contentType;
            }
            // 生成下载对象的带授权信息的URL
            $resp = $this->client->createSignedUrl($params);
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
     * @return array<string, string>
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
                    'Headers' => ['Content-Type' => $contentType],
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
    public function delete(string $key): bool
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
