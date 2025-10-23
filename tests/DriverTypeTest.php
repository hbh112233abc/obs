<?php

namespace bingher\obs\tests;

use PHPUnit\Framework\TestCase;
use bingher\obs\DriverType;

/**
 * DriverType枚举类测试
 */
class DriverTypeTest extends TestCase
{
    /**
     * 测试获取所有驱动类型
     */
    public function testAll()
    {
        $types = DriverType::all();
        
        $this->assertIsArray($types);
        $this->assertCount(7, $types);
        $this->assertEquals('阿里云OSS', $types[DriverType::ALI_OSS]);
        $this->assertEquals('腾讯云COS', $types[DriverType::COS]);
        $this->assertEquals('华为云OBS', $types[DriverType::HW_OBS]);
        $this->assertEquals('MinIO', $types[DriverType::MIN_IO]);
        $this->assertEquals('RustFS', $types[DriverType::RUST_FS]);
        $this->assertEquals('AWS S3', $types[DriverType::S3]);
        $this->assertEquals('SeaweedFS', $types[DriverType::SEAWEED_FS]);
    }
    
    /**
     * 测试验证驱动类型有效性
     */
    public function testIsValid()
    {
        $this->assertTrue(DriverType::isValid(DriverType::ALI_OSS));
        $this->assertTrue(DriverType::isValid(DriverType::COS));
        $this->assertTrue(DriverType::isValid(DriverType::HW_OBS));
        $this->assertTrue(DriverType::isValid(DriverType::MIN_IO));
        $this->assertTrue(DriverType::isValid(DriverType::RUST_FS));
        $this->assertTrue(DriverType::isValid(DriverType::S3));
        $this->assertTrue(DriverType::isValid(DriverType::SEAWEED_FS));
        $this->assertFalse(DriverType::isValid('InvalidType'));
        $this->assertFalse(DriverType::isValid(''));
    }
    
    /**
     * 测试获取所有驱动类型值
     */
    public function testGetValues()
    {
        $values = DriverType::getValues();
        
        $this->assertIsArray($values);
        $this->assertCount(7, $values);
        $this->assertContains(DriverType::ALI_OSS, $values);
        $this->assertContains(DriverType::COS, $values);
        $this->assertContains(DriverType::HW_OBS, $values);
        $this->assertContains(DriverType::MIN_IO, $values);
        $this->assertContains(DriverType::RUST_FS, $values);
        $this->assertContains(DriverType::S3, $values);
        $this->assertContains(DriverType::SEAWEED_FS, $values);
    }
    
    /**
     * 测试获取驱动类型标签
     */
    public function testGetLabel()
    {
        $this->assertEquals('阿里云OSS', DriverType::getLabel(DriverType::ALI_OSS));
        $this->assertEquals('腾讯云COS', DriverType::getLabel(DriverType::COS));
        $this->assertEquals('华为云OBS', DriverType::getLabel(DriverType::HW_OBS));
        $this->assertEquals('MinIO', DriverType::getLabel(DriverType::MIN_IO));
        $this->assertEquals('RustFS', DriverType::getLabel(DriverType::RUST_FS));
        $this->assertEquals('AWS S3', DriverType::getLabel(DriverType::S3));
        $this->assertEquals('SeaweedFS', DriverType::getLabel(DriverType::SEAWEED_FS));
        $this->assertNull(DriverType::getLabel('InvalidType'));
    }
}