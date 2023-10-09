<?php
namespace bingher\obs\driver;

use Aws\Exception\MultipartUploadException;
use Aws\S3\MultipartUploader;
use Aws\S3\ObjectUploader;
use Aws\S3\S3Client;
use bingher\obs\Driver;

class S3 extends Driver
{
    /**
     * 配置信息数组
     *
     * @var array
     */
    protected $config = [
        'version'                 => 'latest',
        'region'                  => 'cn-hw',
        'endpoint'                => 'http://192.168.103.38:9000',
        'use_path_style_endpoint' => true,
        'http'                    => ['verify' => false],
        'credentials'             => [
            'key'    => 'minioadmin',
            'secret' => 'minioadmin',
        ],
    ];

    /**
     * 构造函数
     *
     * @param array $config 配置信息
     */
    public function __construct(array $config = [])
    {
        $this->config['endpoint']              = $config['endpoint'] ?? '';
        $this->config['credentials']['key']    = $config['key'] ?? '';
        $this->config['credentials']['secret'] = $config['secret'] ?? '';
        $this->bucket                          = $config['bucket'] ?? '';

        $this->client = new S3Client($this->config);
    }

    /**
     * 上传文件
     *
     * @param string $key      对象key
     * @param string $filePath 本地文件路径
     * @param string $acl      权限名称 private,public
     *
     * @return bool
     */
    public function put(string $key, string $filePath, string $acl = 'private'): bool
    {
        if (!is_file($filePath)) {
            throw new \Exception("File not found: " . $filePath);
        }
        // Using stream instead of file path
        $source = fopen($filePath, 'rb');

        $uploader = new ObjectUploader(
            $this->client,
            $this->bucket,
            $key,
            $source,
            $acl
        );

        do {
            try {
                $result = $uploader->upload();
                if ($result["@metadata"]["statusCode"] == '200') {
                    // print('<p>File successfully uploaded to ' . $result["ObjectURL"] . '.</p>');
                }
                // dump($result);
            } catch (MultipartUploadException $e) {
                rewind($source);
                $uploader = new MultipartUploader(
                    $this->client,
                    $source,
                    [
                        'acl'   => $acl,
                        'state' => $e->getState(),
                    ]
                );
            } catch (\Exception $e) {
                $this->error = $e->getMessage();
                return false;
            }
        } while (!isset($result));

        fclose($source);

        return true;
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
            // 下载文件的内容
            $this->client->getObject(
                [
                    'Bucket' => $this->bucket,
                    'Key'    => $key,
                    'SaveAs' => $filePath,
                ]
            );
            return true;
        } catch (\Throwable $th) {
            return false;
        }
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
        if ($expire === -1) {
            $this->client->putObjectAcl([
                'Bucket' => $this->bucket,
                'Key'    => $key,
                'ACL'    => 'public-read',
            ]);
            return $this->client->getObjectUrl($this->bucket, $key);
        }
        // 从client中获得一个commad对象
        $command = $this->client->getCommand(
            'GetObject',
            [
                'Bucket' => $this->bucket,
                'Key'    => $key,
            ]
        );

        // 获得带有效期的pre-signed URL
        $expireTime       = date('Y-m-d H:i:s', time() + $expire);
        $presignedRequest = $this->client->createPresignedRequest(
            $command,
            $expireTime
        );

        // 获得presigned-url
        $presignedUrl = (string) $presignedRequest->getUri();
        return $presignedUrl;
    }
    /**
     * 获取预授权链接
     *
     * @param string $key         对象key
     * @param string $contentType 头部类型
     * @param int    $expire      过期时间(秒),默认3600
     *
     * @return array [url,host]
     */
    public function putUrl(string $key, string $contentType = "application/octet-stream", int $expire = 3600): array
    {
        // 从client中获得一个commad对象
        $command = $this->client->getCommand(
            'PutObject',
            [
                'Bucket' => $this->bucket,
                'Key'    => $key,
            ]
        );
        // 获得带有效期的pre-signed URL
        $expireTime       = date('Y-m-d H:i:s', time() + $expire);
        $presignedRequest = $this->client->createPresignedRequest(
            $command,
            $expireTime
        );
        // 获得presigned-url
        $presignedUrl = (string) $presignedRequest->getUri();
        return [$presignedUrl, $this->config['endpoint']];
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
        return $this->client->doesObjectExist($this->bucket, $key);
    }
}
