# 表单

### 站点


| 字段   | 必选 | 类型 | 生成规则 | 初始值 | 简介 |
| ------ |:------:| ---- | ----- | ------ | -----|
| domain | √ | string | example.pp |  | 站点名称，不带 http:// ，创建后不可修改  |
| vhostdir |  | string | 使用域名为目录 |  | 站点目录，可指定目录wdlinux或绝对目录/home/wdlinux，网站文件上传至public_html目录下 |
| dirindex                               |  | string | - |  | 默认首页，index.html,index.php |
| ssl                                     |  | int | 0,1,2 | 0 | https支持，0：不启用,1：启用, 2强制启用 |
| phps                                   |  | float | 0,5.2，5.3到7.3 | 0 | PHP版本 |
| note                                     |  | string | off, on | 空 | 备注信息 |
| ftp_flag                                 |  | int | 1,0 | 0 | 创建FTP帐户 |
| ftpuser                                  |  | string | 3-12个字符 | 空 | 用户名, 由字母、数字、下划线组成，且不可修改 |
| ftppasswd                                |  | string | 6-15个字符 |  | 密码 |
| cftppasswd                               |  | string | 6-15个字符 |  | 确认密码 |
| db_flag                                  |  | int | 0,1 | 0 | 创建数据库, 0:否, 1: 是 |
| dbname                                   |  | string | 5-15个字符 |  | 数据库名, 由字母、数字、下划线组成，**不能全为数字** |
| dbuser                                   |  |      | 3-15个字符 |  | 用户名, 由字母、数字、下划线组成 |
| dbpasswd                                 |  | string | 6-15个字符 |  | 密码, 由字母、数字、下划线组成 |
| cdbpasswd  |  | string | dbpasswd |  | 确认密码 |
| dbcharset  |  | string | utf8,gbk, latinl | utf8 | 编码 |
| domainm                                  |  | int | 1,0 | 0 | 绑定域名, 对泛域名的支持，一般不需要开启 |
| domains                                   |  | string |      |  | 绑定域名, 多个请用逗号“,”分隔 |
| rewrite    |  | string |      | 空 | 伪静态规则 |
| tport                            |  | int | 0-65535 | 0 | tomcat端口, 默认为0，不启用 |
| nport                                    |  | int | 0-65535 | 0 | nodejs端口, 默认为0，不启用 |
| cdn                                      |  | string | 域名 |  | 反向代理, 源站域名 |
| balance                                  |  | string | domain.com:port |  | 负载均衡, IP:端口，如192.168.0.100:8088;每个一行,分号";"结束 |
| lft                                      |  | string | jpg,png,gif |  | 防盗链类型, 如多个用逗号“,”分隔,如“jpg,gif,bmp” |
| laurl                                    |  | string |      |  | 允许的域名, 如多个用逗号“,”分隔, 不带http://，如 pifeifei.com,www.pifeifei.com |
| ldurl                                    |  | string |      |  | 盗链图片地址, 可访问的图片地址，不带http://，如 www.pifeifei.cn/images/logo.png |
| redir                                    |  | int | 0,1,2 | 0 | 301/302跳转, 0:不跳转, 1:301跳转, 2:302跳转 |
| reurl                                    |  | string |      |  | 跳转地址, 不带http://，如 www.pifeifei.cn |
| err400                                   |  | int | 0,1 | 1 | 错误提示页 |
| err401                                   |  | int | 0,1 | 0 | 错误提示页 |
| err403                                  |  | int | 0,1 | 1 | 错误提示页 |
| err404                                  |  | int | 0,1 | 1 | 错误提示页 |
| err405                                  |  | int | 0,1 | 0 | 错误提示页 |
| err500                                  |  | int | 0,1 | 0 | 错误提示页 |
| err503                                  |  | int | 0,1 | 1 | 错误提示页 |
| accesslog                                |  | int | 0,1 | 0 | 详细日志, |
| errorlog                                 |  | int | 0,1 | 0 | 错误日志 |
| gzip                                     |  | int | 0,1 | 0 | gzip压缩 |
| expires                                  |  | int | 0,1 | 0 | 客户端缓存 |
| limitdir                                 |  | int | 0,1 | 0 | 限制目录 |
| dirlist                                  |  | int | 0,1 | 0 | 浏览目录 |
| port                                     |  | int | 端口 | 0 | 使用端口 |
| useip                                    |  | ip | IP | 0 | 使用IP,  指定使用哪个IP |
| conn       |  | int | 0 | 0 | IP并发数, 默认为0，即不限制 |
| bw                                       |  | int | 0 | 0 | 连接线程速度, 默认为0，即不限制 |



### FTP 配置

| 字段        | 必选 | 类型   | 生成规则     | 初始值 | 简介                             |
| ----------- | ---- | ------ | ------------ | ------ | -------------------------------- |
| username    | √    | string | 3-12个字符   |        | 用户名, 由字母、数字、下划线组成 |
| password    | √    | string | 6-15个字符   |        | 密码, 由字母、数字、下划线组成   |
| password2   | √    | string | 6-15个字符   |        | 确认密码                         |
| dir         |      | string | 留空为用户名 |        | 目录, 可用绝对路径, 如 /www/web  |
| note        |      | string |              |        | 备注                             |
| quotasize   |      | int    |              | 0      | 空间大小                         |
| quotafiles  |      | int    |              | 0      | 文件数量                         |
| ulbandwidth |      | int    |              | 0      | 上传带宽                         |
| dlbandwidth |      | int    |              | 0      | 下载带宽                         |




### MySQL 配置

| 字段      | 必选 | 类型   | 生成规则          | 初始值    | 简介                             |
| --------- | ---- | ------ | ----------------- | --------- | -------------------------------- |
| username  | √    | string | 3-12字符          |           | 用户名, 由字母、数字、下划线组成 |
| password  | √    | string | 6-15字符          |           | 密码                             |
| cpassword | √    | string | 6-15字符          |           | 确认密码                         |
| dbname    | √    | string | 留空为用户名+db   |           | 数据库名                         |
| dbcharset |      | string | utf8, gbk, latin1 | utf8      | 数据库编码                       |
| dbhost    |      | string | IP, host, %等     | localhost | 主机名                           |
| note      |      | string |                   |           | 备注                             |