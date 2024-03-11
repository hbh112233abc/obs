# OBS

> API for object storage server

## Support platform

- [x] huawei OBS
- [x] minio
- [x] seaweedFS
- [x] amazon S3
- [x] aliyun OSS
- [x] tencent COS

## Installation

- Install Base library [==requied==]

```shell
composer require bingher/obs
```

- [HwOBS](https://support.huaweicloud.com/sdk-php-devg-obs/obs_28_0105.html) [==optional==]

```shell
composer require obs/esdk-obs-php
```

- [S3](https://docs.aws.amazon.com/aws-sdk-php/v3/api/namespace-Aws.S3.html) [==optional==]

```shell
composer require aws/aws-sdk-php
```

- [MinIO](https://min.io/docs/minio) [==optional==]

```shell
composer require aws/aws-sdk-php
```

- [AliOSS](https://help.aliyun.com/document_detail/85580.html) [==optional==]

```shell
composer require aliyuncs/oss-sdk-php
```

- [COS](https://cloud.tencent.com/document/product/436/12266) [==optional==]

```shell
composer require qcloud/cos-sdk-v5
```

## Usage

```php
$obs = new bingher\obs\OBS(
    [
        'type'     => 'HwOBS', //[HwOBS,S3,MinIO,AliOSS,COS]
        'endpoint' => 'https://endpoint',
        'bucket'   => 'bucket name',
        'key'      => 'access key',
        'secret'   => 'access secret',
    ]
);

//put object
$obs->put($key,$filePath);

//get object
$obs->get($key,$filePath);

//object preview url
$obs->url($key);

//object put url
$obs->putUrl($key);

//delete object
$obs->delete($key);

//check object exist
$obs->exist($key);

//call any public method of sdk ex:doesObjectExist
$obs->doesObjectExist($key);
```
