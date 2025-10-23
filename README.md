# OBS

> 统一对象存储服务API客户端，支持多种云存储服务平台，提供简洁一致的接口。

## 项目介绍

OBS是一个轻量级的统一对象存储客户端库，为多种云存储服务提供一致的API接口。通过OBS，您可以用相同的代码轻松切换不同的存储服务提供商，无需关心底层实现细节。

## 支持平台

- [x] 阿里云OSS (AliOSS)
- [x] 腾讯云COS (COS)
- [x] 华为云OBS (HwOBS)
- [x] MinIO (MinIO)
- [x] AWS S3 (S3)
- [x] SeaweedFS (SeaweedFS)
- [x] RustFS (RustFS)

## 安装方法

### 基础库安装 [必须]

```shell
composer require bingher/obs
```

### 驱动依赖安装 [按需选择]

根据您使用的存储服务类型，安装相应的依赖：

- **阿里云OSS** [官方文档](https://help.aliyun.com/document_detail/85580.html)
  ```shell
  composer require aliyuncs/oss-sdk-php
  ```

- **腾讯云COS** [官方文档](https://cloud.tencent.com/document/product/436/12266)
  ```shell
  composer require qcloud/cos-sdk-v5
  ```

- **华为云OBS** [官方文档](https://support.huaweicloud.com/sdk-php-devg-obs/obs_28_0105.html)
  ```shell
  composer require obs/esdk-obs-php
  ```

- **MinIO/AWS S3/SeaweedFS/RustFS** [官方文档](https://docs.aws.amazon.com/aws-sdk-php/v3/api/namespace-Aws.S3.html)
  ```shell
  composer require aws/aws-sdk-php
  ```

## 快速开始

### 基本使用

```php
<?php

use bingher\obs\OBS;
use bingher\obs\DriverType;

// 配置参数
$config = [
    'type'      => DriverType::MIN_IO, // 使用DriverType枚举
    'endpoint'  => 'http://localhost:9000',
    'key'       => 'minioadmin',
    'secret'    => 'minioadmin',
    'bucket'    => 'test-bucket',
    'region'    => 'us-east-1',
    'ssl_verify' => false, // 开发环境可设置为false
];

// 创建OBS客户端实例
$obs = new OBS($config);

// 上传文件
$localFilePath = '/path/to/local/file.txt';
$objectKey = 'remote/file.txt';
$acl = 'public-read'; // 可选: private, public-read
$result = $obs->put($objectKey, $localFilePath, $acl);
var_dump($result); // bool

// 获取文件
$downloadPath = '/path/to/download/file.txt';
$result = $obs->get($objectKey, $downloadPath);
var_dump($result); // bool

// 获取文件URL
$url = $obs->url($objectKey, 3600); // 有效期3600秒
var_dump($url); // string

// 检查文件是否存在
$exists = $obs->exist($objectKey);
var_dump($exists); // bool

// 删除文件
$deleted = $obs->delete($objectKey);
var_dump($deleted); // bool

// 获取预授权上传链接
$putUrlInfo = $obs->putUrl($objectKey, 'text/plain', 3600);
var_dump($putUrlInfo); // array
```

### 切换驱动

```php
// 创建后切换驱动类型
$obs->setDriver(DriverType::ALI_OSS);

// 也可以直接使用字符串（不推荐）
// $obs->setDriver('AliOSS');
```

## 配置说明

### 通用配置参数

| 配置项 | 类型 | 说明 | 是否必填 | 默认值 |
|--------|------|------|----------|--------|
| type | string | 驱动类型（推荐使用DriverType枚举） | 是 | - |
| endpoint | string | 服务端点URL | 是 | - |
| key | string | 访问密钥ID | 是 | - |
| secret | string | 访问密钥Secret | 是 | - |
| bucket | string | 存储桶名称 | 是 | - |
| region | string | 区域 | 否 | us-east-1 |
| ssl_verify | bool | 是否验证SSL证书 | 否 | false |
| timeout | int | 超时时间(秒) | 否 | 30 |
| connect_timeout | int | 连接超时时间(秒) | 否 | 10 |
| driver_options | array | 驱动特定配置 | 否 | [] |

## API参考

### OBS类方法

#### `__construct(array $config)`
创建OBS客户端实例
- **参数**: `$config` - 配置数组
- **返回值**: OBS实例

#### `setDriver(string $type): self`
切换存储驱动
- **参数**: `$type` - 驱动类型（推荐使用DriverType常量）
- **返回值**: 当前OBS实例（支持链式调用）
- **异常**: 当驱动类型无效或类不存在时抛出异常

#### 以下方法通过魔术方法调用对应驱动的实现

#### `put(string $key, string $filePath, string $acl = 'private', string $contentType = ''): bool`
上传文件
- **参数**:
  - `$key` - 对象键名
  - `$filePath` - 本地文件路径
  - `$acl` - 权限控制（默认为'private'）
  - `$contentType` - 内容类型
- **返回值**: 上传是否成功

#### `get(string $key, string $filePath): bool`
下载文件
- **参数**:
  - `$key` - 对象键名
  - `$filePath` - 本地保存路径
- **返回值**: 下载是否成功

#### `url(string $key, int $expire = 3600, string $contentType = ''): string`
获取文件访问链接
- **参数**:
  - `$key` - 对象键名
  - `$expire` - 有效期（秒）
  - `$contentType` - 内容类型
- **返回值**: 文件访问URL

#### `putUrl(string $key, string $contentType, int $expire = 3600): array`
获取预授权上传链接
- **参数**:
  - `$key` - 对象键名
  - `$contentType` - 内容类型
  - `$expire` - 有效期（秒）
- **返回值**: 包含上传URL和表单参数的数组

#### `exist(string $key): bool`
检查文件是否存在
- **参数**: `$key` - 对象键名
- **返回值**: 文件是否存在

#### `delete(string $key): bool`
删除文件
- **参数**: `$key` - 对象键名
- **返回值**: 删除是否成功

#### `download(string $url, string $filePath): bool`
下载远程文件到本地
- **参数**:
  - `$url` - 文件URL
  - `$filePath` - 本地保存路径
- **返回值**: 下载是否成功

## DriverType枚举

OBS提供了`DriverType`枚举类，用于安全地指定驱动类型：

```php
use bingher\obs\DriverType;

// 枚举常量
DriverType::ALI_OSS    // 阿里云OSS
DriverType::COS        // 腾讯云COS
DriverType::HW_OBS     // 华为云OBS
DriverType::MIN_IO     // MinIO
DriverType::RUST_FS    // RustFS
DriverType::S3         // AWS S3
DriverType::SEAWEED_FS // SeaweedFS

// 枚举方法
DriverType::all()      // 获取所有驱动类型和标签的映射
DriverType::isValid()  // 验证驱动类型是否有效
DriverType::getValues() // 获取所有驱动类型值
DriverType::getLabel() // 获取驱动类型的中文标签
```

## 运行测试

项目包含完整的测试用例，可通过以下方式运行：

1. 首先确保安装了所有依赖
   ```shell
   composer install
   ```

2. 创建`.env.ini`配置文件，填入测试所需的存储服务配置

3. 运行测试
   ```shell
   php run_test.php tests/DriverTypeTest.php  # 运行特定测试
   # 或
   vendor/bin/phpunit  # 运行所有测试
   ```

## 系统要求

- PHP 7.4 或更高版本
- Composer

## 许可证

本项目基于MIT许可证开源。详见[LICENSE](LICENSE)文件。
