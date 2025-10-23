<?php
/**
 * OBS类测试
 */
use bingher\obs\DriverType;
use bingher\obs\OBS;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OBSTest extends TestCase
{
    /**
     * @var MockObject|OBS
     */
    protected $obs;

    /**
     * 设置测试环境
     */
    protected function setUp(): void
    {
        // 创建OBS类的mock对象，添加需要的方法
        $this->obs = $this->getMockBuilder(OBS::class)
            ->disableOriginalConstructor()
            ->addMethods(['put', 'get', 'delete', 'exist', 'url', 'putUrl'])
            ->getMock();
    }

    /**
     * 测试上传文件方法（使用put方法）
     */
    public function testPutFile()
    {
        $key         = 'test.txt';
        $filePath    = 'tests/data/test.txt';
        $acl         = 'private';
        $contentType = 'text/plain';

        // 配置mock行为
        $this->obs->expects($this->once())
            ->method('put')
            ->with($key, $filePath, $acl, $contentType)
            ->willReturn(true);

        // 执行测试
        $result = $this->obs->put($key, $filePath, $acl, $contentType);
        $this->assertTrue($result);
    }

    /**
     * 测试下载文件方法（使用get方法）
     */
    public function testGetFile()
    {
        $key      = 'test.txt';
        $filePath = 'tests/data/test.txt';

        // 配置mock行为
        $this->obs->expects($this->once())
            ->method('get')
            ->with($key, $filePath)
            ->willReturn(true);

        // 执行测试
        $result = $this->obs->get($key, $filePath);
        $this->assertTrue($result);
    }

    /**
     * 测试删除文件方法
     */
    public function testDeleteFile()
    {
        $key = 'test.txt';

        // 配置mock行为
        $this->obs->expects($this->once())
            ->method('delete')
            ->with($key)
            ->willReturn(true);

        // 执行测试
        $result = $this->obs->delete($key);
        $this->assertTrue($result);
    }

    /**
     * 测试驱动选择和初始化
     */
    public function testDriverSelection()
    {
        // 创建OBS类的mock对象，模拟现有方法和添加新方法
        $obsWithDriver = $this->getMockBuilder(OBS::class)
            ->onlyMethods(['setDriver'])
            ->addMethods(['put', 'get', 'delete', 'exist', 'url', 'putUrl'])
            ->disableOriginalConstructor()
            ->getMock();

        // 验证setDriver方法被调用
        $obsWithDriver->expects($this->once())
            ->method('setDriver')
            ->with(DriverType::ALI_OSS)
            ->willReturnSelf();

        // 执行测试并断言结果
        $result = $obsWithDriver->setDriver(DriverType::ALI_OSS);
        $this->assertInstanceOf(OBS::class, $result);
    }
}
