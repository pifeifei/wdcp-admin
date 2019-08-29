# wdcp 后台 api 调用

### composer 安装
composer require pifeifei/wdcp-admin

### API 使用

```php

// 配置
$config = [
    'username' => 'api_user',
    'password' => 'wdcpAdmin@123',
    'uri'      => 'http://localhost:8080/',
    'ftp_user' => 'api_ftp_user',
    'ftp_pwd'  => 'wdcpAdmin@123',
    'ftp_port' => 21,
];
// 创建对象
$wdcp = new \Pifeifei\WdcpAdmin($config);

// 添加站点
$siteName = 'test-domain.pp';
$siteConfig = [
    'gzip' => 1,
    'expires' => 1,
    'vhostdir' => 'test-domain-dir',
    // 这里是 wdcp 后台表单的参数
];
$siteInfo = $this->wdcpAdmin->siteAdd($siteName, $siteConfig);


// 获取站点详情
$siteEditInfo = $this->wdcpAdmin->getSiteEditFormArray($siteId);

// 站点添加域名
$addDomains = ['test1.pp', 'test2.pp'];
$siteAddDomain = $this->wdcpAdmin->siteAddDomainForSiteId($siteId, $addDomains);

// 删除域名
$addDomains = ['test1.pp'];
$siteRemoveDomain = $this->wdcpAdmin->siteRemoveDomainForSiteId($siteId, $addDomains);

// 判断是否有某个域名
$this->wdcpAdmin->siteHasDomainForSiteId($siteId, 'test1.pp');

// 删除站点
$this->wdcpAdmin->siteDelete($siteId);

// ftp 相关
// 待写

// mysql TODO 

```

### 运行测试
```shell
./vendor/bin/phpunit
```

### 授权协议
MIT