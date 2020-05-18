# wdcp 后台 api 调用

> 原因:
> 因为官方 api 没有列表, 使用有了限制，所以开发了这个
> 
> PS: 强烈建议设置 IP 白名单，或对 8080 端口防火墙设置，来提升安全。


### composer 安装
composer require pifeifei/wdcp-admin

### API 使用

```php

// 配置
$config = [
    'username' => 'api_user',
    'password' => 'wdcpAdmin@123',
    'uri'      => 'http://localhost:8080/'
    // 'cookies' => new \GuzzleHttp\Cookie\FileCookieJar($cookieFile, true) // cookie登录
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
// ftp 列表
$wdcp->ftpList($keyword = '',$page=1);

// ftp搜索, 列表页带有搜索功能
$wdcp->ftpSearch($keyword='')

$username = 'ftp_username';
$ftpConfig=[
    'password' => 'password', // 可以自动生成, 返回参数包含密码,
    'dir' => "/www/web/{$username}", // 默认是用户名目录, 可自行指定
];
$wdcp->ftpAdd($username, $ftpConfig=[]);

// 修改密码, 访问速度等属性
$wdcp->ftpEdit($ftpId, $data=[]);

// 设置 ftp 状态
$wdcp->ftpStatus($ftpId, $status=0);

// 删除ftp
$wdcp->ftpDelete($ftpId);

// 修改 FTP 密码
$wdcp->ftpChpwd($ftpId, $oldPassword='', $newPassword = '');

// mysql TODO 

```

> [详细表单介绍 docs/form.md](docs/form.md)

### 运行测试
```shell
./vendor/bin/phpunit
```

### 授权协议
MIT