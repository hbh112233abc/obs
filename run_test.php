<?php

// 加载自动加载文件
require __DIR__ . '/vendor/autoload.php';

// 设置环境变量以显示详细输出
putenv('XDEBUG_CONFIG=idekey=VSCODE');

echo "开始运行测试...\n";

// 使用更简单的方式运行PHPUnit测试
// 直接运行命令行方式，避免API兼容性问题
$testFile = 'tests/DriverTypeTest.php';

// 使用系统命令运行PHPUnit
$command = 'php ' . __DIR__ . '/vendor/bin/phpunit --debug --testdox ' . $testFile;
echo "执行命令: $command\n";

// 执行命令并显示输出
$output = [];
$returnVar = 0;

exec($command, $output, $returnVar);

// 显示输出结果
foreach ($output as $line) {
    echo $line . "\n";
}

echo "测试完成，退出码: $returnVar\n";