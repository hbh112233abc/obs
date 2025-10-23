<?php
namespace bingher\obs;

/**
 * 驱动类型枚举类
 * 
 * 用于定义所有支持的对象存储驱动类型
 */
class DriverType
{
    /**
     * 阿里云对象存储服务
     */
    public const ALI_OSS = 'AliOSS';
    
    /**
     * 腾讯云对象存储服务
     */
    public const COS = 'COS';
    
    /**
     * 华为云对象存储服务
     */
    public const HW_OBS = 'HwOBS';
    
    /**
     * MinIO对象存储服务
     */
    public const MIN_IO = 'MinIO';
    
    /**
     * RustFS对象存储服务
     */
    public const RUST_FS = 'RustFS';
    
    /**
     * AWS S3对象存储服务
     */
    public const S3 = 'S3';
    
    /**
     * SeaweedFS对象存储服务
     */
    public const SEAWEED_FS = 'SeaweedFS';
    
    /**
     * 获取所有驱动类型列表
     * 
     * @return array<string, string> 驱动类型映射数组
     */
    public static function all(): array
    {
        return [
            self::ALI_OSS => '阿里云OSS',
            self::COS => '腾讯云COS',
            self::HW_OBS => '华为云OBS',
            self::MIN_IO => 'MinIO',
            self::RUST_FS => 'RustFS',
            self::S3 => 'AWS S3',
            self::SEAWEED_FS => 'SeaweedFS',
        ];
    }
    
    /**
     * 检查驱动类型是否有效
     * 
     * @param string $type 驱动类型
     * @return bool 是否有效
     */
    public static function isValid(string $type): bool
    {
        return in_array($type, self::getValues(), true);
    }
    
    /**
     * 获取所有驱动类型值
     * 
     * @return array<string> 驱动类型值数组
     */
    public static function getValues(): array
    {
        return [
            self::ALI_OSS,
            self::COS,
            self::HW_OBS,
            self::MIN_IO,
            self::RUST_FS,
            self::S3,
            self::SEAWEED_FS,
        ];
    }
    
    /**
     * 获取驱动类型的中文名称
     * 
     * @param string $type 驱动类型
     * @return string|null 中文名称或null
     */
    public static function getLabel(string $type): ?string
    {
        $all = self::all();
        return $all[$type] ?? null;
    }
}