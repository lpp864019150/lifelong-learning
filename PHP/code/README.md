# 测试
```shell
# 运行所有测试
vendor/bin/phpunit --bootstrap vendor/autoload.php tests

# 运行指定测试
vendor/bin/phpunit --bootstrap vendor/autoload.php tests --filter testGet

# 运行指定测试并输出测试结果
vendor/bin/phpunit --bootstrap vendor/autoload.php tests --filter testGet --testdox
```