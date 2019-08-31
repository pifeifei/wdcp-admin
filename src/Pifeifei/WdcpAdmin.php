<?php
/**
 * wdcpAdmin API ，登录后台所有操作
 * 站点: 创建, 禁用,启用, 编辑, 删除, 查询, 列表
 * FTP : 创建, 禁用,启用, 编辑(只是限制属性), 删除, 查询, 列表, 改密
 * Mysql : TODO
 *
 * 使用本系统, 建议设置 后台ip和域名白名单, 这样知道密码也不会有问题.
 * 因为不能查询站点列表, 不能获取站点详情, 所以才有这个程序的出现
 */
namespace Pifeifei;

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SessionCookieJar;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Pifeifei\Exceptions\WdcpRuntimeException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DomCrawler\Crawler;


class WdcpAdmin
{
    private $debug = false;

    private $logCallback = false;

    private $options =[];

    private $defaultOptions =[
        'uri'      => 'http://localhost:8080',
        'username' => 'admin',
        'password' => 'wdlinux.cn',
    ];

    /**
     * @example
     * [
     *   'site' => [
     *      'gzip'=>1,
     *      'expires'=>1,
     *      'limitdir'=>1
     *   ],
     *   'ftp' => []
     * ]
     *
     * @var array
     */
    private $defaultConfig = [];

    const WDCP_LOGIN       = '/login?'; // 登录
    const WDCP_CHECK_LOGIN = '/sys/top?act=rt&callback=';

    // 站点 API
    const WDCP_SITE_LIST = '/site/list?'; //站点列表
    const WDCP_SITE_ADD  = '/site/add?';  //添加站点
    const WDCP_SITE_EDIT = '/site/edit?'; //修改站点
    const WDCP_SITE_DEL  = '/site/del?';  //删除站点

    // FTP API
    const WDCP_FTP_LIST = '/ftp/list?'; //FTP用户列表
    const WDCP_FTP_ADD  = '/ftp/add?'; //添加FTP用户
    const WDCP_FTP_EDIT = '/ftp/edit?'; //修改FTP用户
    const WDCP_FTP_STATUS = '/ftp/status?'; //修改FTP用户状态-
    const WDCP_FTP_DEL  = '/ftp/del?'; //删除FTP用户
    const WDCP_FTP_CHPWD= '/ftp/chpwd?'; //修改FTP密码

    // MySql API : TODO
    const WDCP_MYSQL_LIST = '/mysql/list?'; //列表mysql , 只有通过wdcp后台创建的,  mysql 创建的这里没有
    const WDCP_MYSQL_ADD  = '/mysql/add?';  //添加mysql 用户/数据库
    const WDCP_MYSQL_EDIT = '/mysql/edit?'; //修改mysql 用户/数据库
    const WDCP_MYSQL_DEL  = '/mysql/chgpw?';//删除mysql 用户密码

    private $errCode = 0;
    private $errMsg  = ""; // 保存api 返回错误, 与 本地错误拼接用

    /**
     * @var Crawler
     */
    private $crawler;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var CookieJar
     */
    private $cookie;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * 初始化
     *
     * @param array $options  url|username|password|debug|logCallback
     */
    public function __construct($options = [])
    {
        if(isset($options['debug'])){
            $this->debug = $options['debug'];
            unset($options['debug']);
        }

        if(isset($options['logCallback'])){
            $this->logCallback = $options['logCallback'];
            unset($options['logCallback']);
        }

        $this->options = array_replace($this->defaultOptions, $options);

        if(empty($this->options['uri'])){
            $this->errCode = 40001;
            $this->errMsg  = 'uri 不能为空';
            throw new WdcpRuntimeException($this->errMsg);
            //return false;
//        }else{
//            echo $this->options['uri'];
//            exit;
        }
        // $this->options['ftp_pwd'] = $this->options['ftp_pwd']===true ? substr(md5($this->options['uri'].'+'.$this->options['ftp_user']),8,15):$this->options['ftp_pwd'];

        $this->cookie = new SessionCookieJar($this->options['uri'], true);
        $this->client = new Client([
                'base_uri' => $this->options['uri'], //'http://httpbin.org/', //'http://cs.p/', //
                'cookies'  => $this->cookie,
                'allow_redirects'=> true,
                'timeout'  => 2,
                'html_errors'=> false,
                // 'debug' => true,
            ]);
        // $result = $this->login();
        $this->crawler = new Crawler(null, $this->options['uri']);
    }

    public function valid()
    {
//        $response = $this->client->request('GET', self::WDCP_CHECK_LOGIN, ['allow_redirects'=>false]);
//        if(intval($response->getStatusCode())=== 200){
//            return true;
//        }
        $response = $this->client->get(self::WDCP_CHECK_LOGIN,  ['allow_redirects'=>false]);
        $result = $this->responseCheck($response, 'json');
        if($result !== false){
            return true;
        }

        try{
            $data = [
                'username'=> $this->options['username'],
                'passwd'=> $this->options['password']
            ];
            $response= $this->client->request('POST', self::WDCP_LOGIN, ['form_params'=>$data]);
            $body = $response->getBody();
            $body = json_decode($body, true);

            if($body['errCode']==='0'){
                return true;
            }else{
                $this->errCode = 40002;
                $this->errMsg = $body['msg'];
                return false;
            }
        } catch (GuzzleException $e) {
            $this->errCode = 44444;
            $this->errMsg = "wdcp error: ".$e->getMessage();
            return false;
        }
    }

    // 验证ftp账号密码
    public function validFtp(){}

    /**
     * 查询站点列表
     *
     * @param string $keyword
     * @param int $page  页数
     * @param int $dt 搜索类型, 1:domain搜索, 2: domains 搜索
     *
     * @return array|bool
     */
    public function siteList($keyword = '',$page=1, $dt=0)
    {
        if($this->valid() === false){
            return false;
        };

        $query = ['page'=> intval($page), 'keyword'=>$keyword];
        if($dt>0){
            $query['dt'] = $dt;
        }
        $response = $this->client->get(self::WDCP_SITE_LIST, ['query'=>$query]);
        $response->getStatusCode();
        $result = $this->responseCheck($response, 'html');
        if($result ===false){
            return false;
        }

        $crawler = new Crawler((string)$result, $this->options['uri']);
        $td = $crawler->filter('.layui-form tr td');

        $list = [];
        $step = 9;
        for($i=0; $i<$td->count()/$step; $i++){
            $domain = $td->eq($i*9+1)->html();
            preg_match("/\>([^\>\<]+)\</i",$domain, $domain);
            $domains= $td->eq($i*9+2)->html();
            $domains = empty($domains)? [] : preg_split("/[\r\n ,]+/", $domains);

            array_push($list, [
                    'id'    =>$td->eq($i*9)->html(),
                    'domain'=>@$domain[1],
                    'domains'=>$domains,
                    'vhostdir'=>str_replace('/www/web','',$td->eq($i*9+3)->html()),
                    'ssl'=>strpos($td->eq($i*9+4)->html(),'是')!==false,
                    'create_time'=>strtotime($td->eq($i*9+5)->html()),
                    'status'=>strpos($td->eq($i*9+7)->html(), 'checked')!==false,
            ]);

        }
        $pp = $crawler->filter('.layui-laypage a, .layui-laypage span');
        if($pp->count()>0){
            $pageTotal= $pp->eq($pp->count()-2)->text();
        }else{
            $pageTotal = count($list)>0?1:0;
        }

        return ['err'=>0,'msg'=>'ok', 'page'=>$page, 'pageTotal'=>$pageTotal, 'data'=>$list];
    }

    /**
     * 查询站点信息
     * @param string $keyword
     * @return array|bool
     */
    public function siteSearch($keyword='')
    {
        if(empty($keyword)){
            $this->errMsg = 40005;
            return false;
        }

        $result = $this->siteList($keyword,1,1);
        if($result !==false && !empty($result['data'])){
            return $result;
        }else{
            false;
        }

        $result = $this->siteList($keyword,1,2);
        if($result !==false && !empty($result['data'])){
            return $result;
        }else{
            false;
        }

        return ['err'=>0, 'msg'=>'没有查到相关站点:'.$keyword];
    }

    /**
     * 获取站点 form 表单数据
     * @param int $siteId
     * @return array|bool
     */
    private function getSiteAddAndEditFormArray($siteId = 0)
    {
        $siteId= intval($siteId);
        if($siteId === 0){
            $response = $this->client->get(self::WDCP_SITE_ADD);
        }else{
            $query = ['id'=>$siteId];
            $response = $this->client->get(self::WDCP_SITE_EDIT, ['query'=>$query]);
        }
        $result = $this->responseCheck($response, 'html');
        if($result ===false){
            return false;
        }
        $this->crawler->clear();
        $this->crawler->addContent((string)$result, 'html');
        $form = $this->crawler->filter('[lay-submit]')->form();
        $formArray = $form->getPhpValues();
        $formMid = $this->crawler->filterXPath('//label[contains(@class,"layui-form-label")]');
        for($i=0; $i< $formMid->count(); $i++){
            if(trim($formMid->eq($i)->text()) === '站点目录'){
                $vHostDir = trim($formMid->eq($i)->siblings()
                    ->filter('.layui-form-mid')
                    ->text());
                $vHostDir = substr($vHostDir, 9);
                break;
            }
        }
        $formArray['vhostdir'] = isset($vHostDir) ? $vHostDir : '';

        return $formArray;
    }
    public function getSiteAddFormArray(){return $this->getSiteAddAndEditFormArray();}
    public function getSiteEditFormArray($siteId=0){return $this->getSiteAddAndEditFormArray($siteId);}

    /**
     * 删除域名
     * @param int $siteId
     * @param array $domains
     * @return array|bool
     */
    public function siteRemoveDomainForSiteId($siteId=0, $domains=[])
    {
        if($this->valid() === false){
            return false;
        };

        if($siteId<=0){
            $this->errCode = 44444;
            $this->errMsg  = '编辑站点: 站点ID不能为空';
            return false;
        }

        if(empty($domains)){
            $this->errCode = 44444;
            $this->errMsg  = '站点添加域名: 域名不能为空';
            return false;
        }
        $domains = (array)$domains;

        $form = $this->getSiteEditFormArray($siteId);
        if($form ===false){
            return false;
        }

        $oldDomains = [];
        if(!empty($form['domains'])){
            $oldDomains = preg_split("/[, ]+/i", $form['domains']);
        }

        foreach ((array)$domains as $domain){
            $k = array_search($domain, $oldDomains);
            if($k!==false && isset($oldDomains[$k])){
                unset($oldDomains[$k]);

            }
        }
        $form['domains'] = implode(',', $oldDomains);

        $response = $this->client->post(self::WDCP_SITE_EDIT, ['form_params'=>$form]);
        $result = $this->responseCheck($response, 'json');
        if($result ===false){
            return false;
        }

        return $form;
    }

    /**
     * wdcp站点添加域名
     * @param int $siteId
     * @param array $domains
     * @return bool
     */
    public function siteAddDomainForSiteId($siteId=0, $domains=[])
    {
        if($this->valid() === false){
            return false;
        };

        if($siteId<=0){
            $this->errCode = 44444;
            $this->errMsg  = '编辑站点: 站点ID不能为空';
            return false;
        }

        if(empty($domains)){
            $this->errCode = 44444;
            $this->errMsg  = '站点添加域名: 域名不能为空';
            return false;
        }
        $domains = (array)$domains;

        $form = $this->getSiteEditFormArray($siteId);
        if($form ===false){
            return false;
        }

        $oldDomains = [];
        if(!empty($form['domains'])){
            $oldDomains = preg_split("/[, ]+/i", $form['domains']);
        }

        $newDomains = array_merge($oldDomains, $domains);
        $newDomains = array_unique($newDomains);
        $form['domains'] = implode(',', $newDomains);

        $response = $this->client->post(self::WDCP_SITE_EDIT, ['form_params'=>$form]);
        $result = $this->responseCheck($response, 'json');
        if($result ===false){
            return false;
        }

        return $form;
    }

    /**
     * @param int $siteId
     * @param string $domain
     * @return bool
     */
    public function siteHasDomainForSiteId($siteId=0, $domain='')
    {
        if($this->valid() === false){
            return false;
        };

        if($siteId<=0){
            $this->errCode = 44444;
            $this->errMsg  = '编辑站点: 站点ID不能为空';
            return false;
        }

        if(empty($domain)){
            $this->errCode = 44444;
            $this->errMsg  = '站点添加域名: 域名不能为空';
            return false;
        }

        $form = $this->getSiteEditFormArray($siteId);
        if($form ===false){
            return false;
        }

        $siteDomains = [];
        if(!empty($form['domains'])){
            $siteDomains = preg_split("/[, ]+/i", $form['domains']);
        }

        if(in_array($domain, $siteDomains) || $domain === $form['domain']){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 新建站点
     * @param string $siteName 站点名
     * @param array $siteConfig
     * @return bool|array  站点信息
     */
    public function siteAdd($siteName = '', $siteConfig=[])
    {
        if($this->valid() === false){
            return false;
        };

        if(empty($siteName)){
            $this->errCode = 40003;
            $this->errMsg = WdcpAdminErrorCode::getErrText($this->errCode);
            return false;
        }

        if(!isset($siteConfig['vhostdir'])){
            if(strlen($siteName) !== mb_strlen($siteName)){
                $this->errCode = 40000; // 站点名称只包含英文;
                return false;
            }
            $domain = $siteName;
            $domain = substr($domain, -3) === '.pp'? substr($domain,0, -3):$domain;
            $siteConfig['vhostdir'] = str_replace("/[\.\\\\\/*]/i",'-',(empty($domain)?$siteName:$domain));
        }else{
            $siteConfig['vhostdir'] = str_replace("/[\.\\\\\/*]/i",'-',$siteConfig['vhostdir']);
        }
        try{
            $siteConfig['domain'] = $siteName;
            $siteConfig['domains'] = isset($siteConfig['domains']) ? (is_array($siteConfig['domains'])? implode(', ', $siteConfig['domains']):(string)$siteConfig['domains']) :'';

            // $response = $this->client->get(self::WDCP_SITE_ADD);
            // $body = $response->getBody();
            // $this->crawler->addContent((string)$body, 'html');
            // $form = $this->crawler->filter('[lay-submit]')->form();
            // $data = array_replace(
            //      $form->getPhpValues(),
            //      isset($this->defaultConfig['site']) ? $this->defaultConfig['site'] : [],
            //      (array)$siteConfig
            //);

            $form = $this->getSiteAddFormArray();
            if($form ===false){
                return false;
            }
            $data = array_replace(
                $form,
                isset($this->defaultConfig['site']) ? $this->defaultConfig['site'] : [],
                (array)$siteConfig
            );

            $response = $this->client->post(self::WDCP_SITE_ADD, ['form_params'=>$data]);
            $result = $this->responseCheck($response, 'json');
            // echo 'getBody():' .$body.'<hr>';
            // {"errCode":"1","msg":"域名已存在！"}
            // {"errCode":"0","id":"3","msg":"新建站点成功！"}
            if($result ===false){
                return false;
            }
//            dump($result);
            $data['id'] = $result['id'];
            return $data;
        }catch(WdcpRuntimeException $e){
            $this->errCode= 40008; // 请求超时
            $this->errMsg = WdcpAdminErrorCode::getErrText($this->errCode);
            return false;
        }catch(\Exception $e){
            $this->errCode= -1; //
            $this->errMsg = WdcpAdminErrorCode::getErrText($this->errCode);
            return false;
        }
    }

    /**
     * 修改站点
     *
     * @param int $siteId
     * @param array $siteConfig
     *
     * @return array|bool
     */
    public function siteEdit($siteId= 0, $siteConfig=[])
    {
        if($this->valid() === false){
            return false;
        };

        if($siteId<=0){
            $this->errCode = 44444;
            $this->errMsg  = '编辑站点: 站点ID不能为空';
            return false;
        }

        if(empty($siteConfig)){
            $this->errCode = 44444;
            $this->errMsg  = '编辑站点: 站点配置不能为空';
            return false;
        }

        //        $query = ['id'=>$siteId];
        //        $response = $this->client->get(self::WDCP_SITE_EDIT, ['query'=>$query]);
        //        $result = $this->responseCheck($response, 'html');
        //        if($result ===false){
        //            return false;
        //        }
        //        $this->crawler->addContent((string)$result, 'html');
        //        $form = $this->crawler->filter('[lay-submit]')->form();
        $form = $this->getSiteEditFormArray($siteId);
        if($form ===false){
            return false;
        }

        $data = array_replace($form, (array)$siteConfig);
        $vhostdir = $this->crawler->filter('.layui-field-box .layui-form-mid')->eq(5)->html();
        $response = $this->client->post(self::WDCP_SITE_EDIT, ['form_params'=>$data]);
        $result = $this->responseCheck($response, 'json');
        if($result ===false){
            return false;
        }
        $data['vhostdir'] = $vhostdir;
        return $data;
    }

    /**
     * @deprecated  请使用 siteSetStatus()
     * 站点状态修改
     *
     * @param int  $siteId  站点ID
     * @param int|string $status 0,off, disable: 禁用; 1, on, enable: 启用 ,
     * @param string $domain
     * @return array
     */
    public function siteStatus($siteId, $status=1, $domain='')
    {
        if($this->siteSetStatus($siteId, $status, $domain)){
            return ['err'=>0, 'msg'=>$this->errMsg];
        } else {
            return ['err'=>1, 'msg'=>$this->errMsg];
        }
    }

    /**
     * 站点状态修改
     * @param int  $siteId  站点ID
     * @param int|string $status 0,off, disable: 禁用; 1, on, enable: 启用 ,
     * @param string $domain
     * @return bool
     */
    public function siteSetStatus($siteId, $status=1, $domain='')
    {
        if($this->valid() === false){
            return false;
        }
        if(empty($siteId) || $siteId<0){
            $this->errCode = 44444;
            $this->errMsg  = '站点删除: ID 不能为空';
            return false;
        }
        if(is_int($status)){
            $act = $status===0?'off':'on';
        }else{
            $status = strtolower($status);
            $act = $status==='on' || $status ==='enable' ?'on':'off';
        }

        $query = ['id'=>$siteId, 'act'=>$act, 'domain'=>$domain];
        $response = $this->client->get(self::WDCP_SITE_LIST, ['query'=>$query]);
        $result = $this->responseCheck($response, 'json');
        if($result ===false){
            return false;
        }

        if(intval($result['errCode'])===0){
//            return ['err'=>0, 'msg'=>$result['msg']];
            return true;
        }else{
            $this->setError(41000, $result['msg']);
            return false;
        }
    }

    /**
     * 删除站点 - 建议使用 siteStatus 禁用站点, 避免误操作
     *
     * @param  int $sideId 站点id
     * @param  string $domain ??????
     *
     * @return array|bool
     */
    public function siteDelete($sideId, $domain='')
    {
        if($this->valid() === false){
            return false;
        };

        $query = ['id'=>$sideId, 'domain'=>$domain];
        $response = $this->client->get(self::WDCP_SITE_DEL, ['query'=>$query]);
        $result = $this->responseCheck($response, 'json');
        if($result ===false){
            return false;
        }

        if(intval($result['errCode'])===0){
            return true;
        }else{
            $this->errMsg = $result['msg'];
            return false;
        }
    }

    /**
     * ftp列表
     * @param string $keyword
     * @param int $page
     * @return array|bool
     */
    public function ftpList($keyword = '',$page=1)
    {
        if($this->valid() === false){
            return false;
        };

        $query = ['page'=> intval($page), 'keyword'=>$keyword];
        $response = $this->client->get(self::WDCP_FTP_LIST, ['query'=>$query]);
        $result = $this->responseCheck($response, 'html');
        if($result ===false){
            return false;
        }

        $crawler = new Crawler((string)$result, $this->options['uri']);
        $td = $crawler->filter('.layui-form tr td');

        $list = [];
        $step = 7;
        for($i=0; $i<$td->count()/$step; $i++){

            array_push($list, [
                'id'      =>$td->eq($i*$step)->html(),
                'username'=>$td->eq($i*$step+1)->html(),
                'dir'     =>$td->eq($i*$step+2)->html(),
                'remarks' =>$td->eq($i*$step+3)->html(),
                'create_time'=>strtotime($td->eq($i*$step+4)->html()),
                'status'  =>strpos($td->eq($i*$step+5)->html(), 'checked')!==false,
            ]);

        }
        $pp = $crawler->filter('.layui-laypage a, .layui-laypage span');
        if($pp->count()>0){
            $pageTotal= $pp->eq($pp->count()-2)->html();
        }else{
            $pageTotal = count($list)>0?1:0;
        }

        return ['err'=>0,'msg'=>'ok', 'page'=>$page, 'pageTotal'=>$pageTotal, 'data'=>$list];

    }

    /**
     * 查询FTP信息
     * @param string $keyword
     * @return array|bool
     */
    public function ftpSearch($keyword='')
    {

        if(empty($keyword)){
            $this->errMsg = 44444;
            return false;
        }
        return $this->ftpList($keyword);
    }

    /**
     * 添加FTP用户
     * @param $username
     * @param array $ftpConfig  ['password', 'dir', 'note', 'quotasize','']
     * @return bool|array
     */
    public function ftpAdd($username, $ftpConfig=[])
    {
        if($this->valid() === false){
            return false;
        };

        $response = $this->client->get(self::WDCP_FTP_ADD);

        $result = $this->responseCheck($response, 'html');
        if($result ===false){
            return false;
        }

        $this->crawler->clear();
        $this->crawler->addContent((string)$result, 'html');
        $form = $this->crawler->filter('[lay-submit]')->form();
        $data = array_replace(
            $form->getPhpValues(),
            isset($this->defaultConfig['ftp']) ? $this->defaultConfig['ftp'] : [],
            $ftpConfig);
        $data['username'] = $username;
        if($data['password']===true || empty($data['password'])){
            $data['password'] = substr(md5($username+$this->options['uri']+$this->options['ftp_port']),8, 14);
        }
        $data['password2'] = $data['password'];
        $data['dir'] = empty($data['dir'])? "/www/web/{$data['username']}":$data['dir'];

        $response = $this->client->post(self::WDCP_FTP_ADD, ['form_params'=>$data]);
        $result = $this->responseCheck($response, 'json');
        if($result ===false){
            return false;
        }

        if(intval($result['errCode'])===0){
            $data['id'] = @$result['id'];
            return $data;
//            return ['err'=>0, 'msg'=>$result['msg'], 'data'=> $data];
        }else{
            $this->setError(42000, $result['msg']);
            return false;
//            return ['err'=>1, 'msg'=>$result['msg']];
        }

    }

    /**
     * FTP 修改(不能改密码)
     * @param $ftpId
     * @param array $data [quotasize, quotafiles, ulbandwidth, dlbandwidth]
     * @return array|bool
     */
    public function ftpEdit($ftpId, $data=[])
    {

        if($this->valid() === false){
            return false;
        };

        $query = ['id'=>$ftpId];
        $response = $this->client->get(self::WDCP_FTP_EDIT, ['query'=>$query]);
        $result = $this->responseCheck($response, 'html');
        if($result ===false){
            return false;
        }

        $this->crawler->clear();
        $this->crawler->addContent((string)$result, 'html');
        $form = $this->crawler->filter('[lay-submit]')->form();
        $data = array_replace(
            $form->getPhpValues(),
            isset($this->defaultConfig['ftp']) ? $this->defaultConfig['ftp'] : [],
            (array)$data
        );

        unset($data['password'],$data['dir'], $data['username']);
        $response = $this->client->post(self::WDCP_FTP_EDIT, ['form_params'=>$data]);
        $result = $this->responseCheck($response, 'json');
        if($result ===false){
            return false;
        }

        if(intval($result['errCode'])===0){
            return ['err'=>0, 'msg'=>$result['msg'], 'data'=>$data];
        }else{
            return ['err'=>1, 'msg'=>$result['msg']];
        }
    }

    /**
     * @deprecated  请使用 ftpSetStatus()
     * FTP状态修改
     * @param int        $ftpId
     * @param int|string $status 0,off, disable: 禁用; 1, on, enable: 启用 ,
     * @return array|bool
     */
    public function ftpStatus($ftpId, $status=0)
    {
        if($this->ftpSetStatus($ftpId, $status)){
            return ['err'=>0, 'msg'=>$this->errMsg];
        } else {
            return ['err'=>1, 'msg'=>$this->errMsg];
        }
    }

    /**
     * FTP状态修改
     * @param int        $ftpId
     * @param int|string $status 0,off, disable: 禁用; 1, on, enable: 启用 ,
     * @return bool
     */
    public function ftpSetStatus($ftpId, $status=0)
    {
        if($this->valid() === false){
            return false;
        }
        if(empty($ftpId) || $ftpId<0){
            $this->errCode = 42000;
            $this->errMsg  = 'FTP用户状态: ID 不能为空';
            return false;
        }
        if(is_int($status)){
            $act = $status===0?'off':'on';
        }else{
            $status = strtolower($status);
            $act = $status==='on' || $status ==='enable' ?'on':'off';
        }

        $query = ['id'=>$ftpId, 'act'=>$act];
        $response = $this->client->get(self::WDCP_FTP_STATUS, ['query'=>$query]);
        $result = $this->responseCheck($response, 'json');
        if($result ===false){
            return false;
        }

        if(intval($result['errCode'])===0){
            return true;
        } else {
            $this->errCode = 42000;
            $this->errMsg = $result['msg'];
            return false;
        }
    }

    /**
     * 删除ftp用户
     * @param $ftpId
     * @return array|bool
     */
    public function ftpDelete($ftpId)
    {
        if($this->valid() === false){
            return false;
        };

        if(empty($ftpId) || $ftpId<0){
            $this->errCode = 44444;
            $this->errMsg  = '删除FTP用户: ID 不能为空';
            return false;
        }

        $query = ['id'=>$ftpId];
        $response = $this->client->get(self::WDCP_FTP_DEL,
            [
                'query'=>$query,
                'headers'=>['X-Requested-With'=>'XMLHttpRequest']
            ]);
        $result = $this->responseCheck($response, 'json');
        if($result ===false){
            return false;
        }
        if(intval($result['errCode'])===0){
            return ['err'=>0, 'msg'=>$result['msg']];
        }else{
            return ['err'=>1, 'msg'=>$result['msg']];
        }
    }

    /**
     * 修改ftp 密码
     * @param $ftpId
     * @param string $oldPassword
     * @param string $newPassword
     * @return array|bool
     */
    public function ftpChpwd($ftpId, $oldPassword='', $newPassword = '')
    {
        if($this->valid() === false){
            return false;
        };

        if(empty($ftpId) || $ftpId<0){
            $this->errCode = 44444;
            $this->errMsg  = 'FTP用户状态: ID 不能为空';
            return false;
        }

        if(empty($oldPassword) || empty($newPassword)){
            $this->errCode = 44444;
            $this->errMsg  = 'FTP用户改密: 新旧密码不能为空';
            return false;
        }

        $data = [
            'id'       => $ftpId,
            'password' => $oldPassword,
            'password1'=> $newPassword,
            'password2'=> $newPassword,
        ];
        $response = $this->client->post(self::WDCP_FTP_CHPWD,
            [
                'form_params'=>$data,
                'headers'=>['X-Requested-With'=>'XMLHttpRequest']
            ]);
        $result = $this->responseCheck($response, 'json');
        if($result ===false){
            return false;
        }
        if(intval($result['errCode'])===0){
            return ['err'=>0, 'msg'=>$result['msg']];
        }else{
            return ['err'=>1, 'msg'=>$result['msg']];
        }

    }

    public function mysqlList() {}
    public function mysqlAdd() {}
    public function mysqlDel() {}
    public function mysqlChgpw() {}

    // 无用
    public function serverIp()
    {

        if (isset($_SERVER)) {
            if (isset($_SERVER['SERVER_ADDR'])) {
                $server_ip = $_SERVER['SERVER_ADDR'];
            } else {
                $server_ip = $_SERVER['LOCAL_ADDR'];
            }
        } else {
            $server_ip = getenv('SERVER_ADDR');
        }
        return $server_ip;
    }


    /**
     * @param string $name
     * @param array $config
     * @return $this
     */
    public function setConfig($name, $config = [])
    {
        $name = strtolower($name);

        $this->defaultConfig[$name] = array_merge(
            $this->defaultConfig[$name],
            $config
        );
        return $this;
    }

    /**
     * @param $name
     * @return array
     */
    public function getConfig($name)
    {
        return isset($this->defaultConfig[strtolower($name)]) ? $this->defaultConfig[strtolower($name)] : [];
    }

    /**
     * 设置 option
     * @param $options
     * @return $this
     */
    public function setOption($options)
    {
        $this->options = array_replace($this->defaultOptions, $options);
        return $this;
    }

    public function getOption()
    {
        return $this->options;
    }

    /**
     * TODO : 设置是否开启调试模式
     *
     * @param bool $debug
     * @return $this
     */
    public function debug($debug = false)
    {
        $this->debug = $debug;
        return $this;
    }

    /**
     * 设置日志记录
     * @param bool|string $function  普通函数或闭包函数
     * @return $this
     */
    public function setLogCallback($function = false)
    {

        $this->logCallback = $function;
        return $this;
    }

    /**
     * 验证
     * @param ResponseInterface $response
     * @param string  $type   类型 json|html
     * @return bool
     */
    private function responseCheck($response, $type='html'){
        if(!($response instanceof \GuzzleHttp\Psr7\Response)){
            $this->errCode = 44444;
            $this->errMsg  = 'response 类型错误';
            return false;
        }

        if($response->getStatusCode()===200){
            $body = $response->getBody();
            if($type ==='html'){
                $result = json_decode((string)$body, true);
                if (JSON_ERROR_NONE === json_last_error()) {
                    $this->errCode = 44444;
                    $this->errMsg = '返回类型(json)错误! '.(!empty($result['msg'])?$result['msg']:'请检查参数!') ;
                    return false;
                }
                return $body;
            }else if($type ==='json'){
                $result = json_decode((string)$body, true);
                if (JSON_ERROR_NONE !== json_last_error()) {
                    $this->errCode = 44444;
                    $this->errMsg = '返回类型(html)错误!'.'json_decode error: ' . json_last_error_msg();
                    return false;
                }
                if(isset($result['errCode']) && intval($result['errCode'])===0) {
                    return $result;
                } else if(isset($result['msg']) === false) {
                    return true;
                } else {
                    $this->errCode = 40002;
                    $this->errMsg = $result['msg'];//WdcpAdminErrorCode::getErrText($this->errCode);
                    return false;
                }
            }else{
                $this->errCode = 44444;
                $this->errMsg = '未定义类型 type :' . $type;
                return false;
            }

        }else{
            // 返回非200代码, 每次请求验证是否是否可以链接
            $this->errCode = 40002;
            $this->errMsg  = $response->getReasonPhrase();
            return false;
        }
    }

    /**
     * @param string $method  get post  getJson  postJson
     * @param array  $args
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function __call($method, $args)
    {
        if (count($args) < 1) {
            throw new \InvalidArgumentException('Magic request methods require a URI and optional options array');
        }

        $uri = $args[0];
        $opts = isset($args[1]) ? $args[1] : [];

        $type = 'html';
        if(substr($method, -4) === 'Json'){
            $method = substr($method, 0, -4);
            $type = 'json';
//        }else{
//            $method = substr($method, 0);
        }
        try{
            $this->response = $this->client->request($method, $uri, $opts);
        }catch(WdcpRuntimeException $e){
            $this->errCode = 44444;
            $this->errMsg = '请求超时, 配置错误或服务器不可访问:'.$this->options['uri'];
            return false;
        }
        $result = $this->responseCheck($this->response, $type);
        if($result ===false){
            return false;
        }

        return $result;
    }

    /**
     * @param int $errCode
     * @param string $errMsg
     * @return $this
     */
    private function setError($errCode, $errMsg)
    {
        $this->errCode = $errCode;
        $this->errMsg = $errMsg;
        return $this;
    }

    /**
     * @return string
     */
    public function getError()
    {
        return WdcpAdminErrorCode::getErrText($this->errCode) . $this->errMsg;
    }

    /**
     * 日志记录，可被重载。
     * @param mixed $log 输入日志
     * @return mixed
     */
    protected function log($log)
    {
        if ($this->options['debug'] && function_exists($this->options['logCallback'])) {
            if (is_array($log)) {
                $log = print_r($log, true);
            }

            call_user_func($this->options['logCallback'], $log);
        }

        return $this;
    }
}

/**
 * error code
 * 仅用作类内部使用，不用于官方API接口的errCode码
 */
class WdcpAdminErrorCode
{
    public static $error = -1;
    public static $OK = 0;
    public static $MissingURIParameters = 40001;
    public static $WdcpErrorMsg = 40002;
    public static $MissingSiteName = 40003;
    public static $DomainIsOnlyEnglish = 40004;
    public static $RequestTimeOut = 40008;
    public static $default =44444;
    public static $errCode=array(
               -1 => '系统繁忙，此时请开发者稍候再试',
                0 => '处理成功',
            40001 => '缺少uri参数',
            40002 => 'wdcp 错误提示: ',
            40003 => '缺少站点名称 ',
            40004 => '站点名仅支持英文', // 目录/http.conf/nginx.conf 等待测试中文支持情况
            40005 => 'keyword 不能为空',
            40008 => '连接超时，连接可能不可用！',
            // 站点
            40100 => 'wdcp site error: ',
            40101 => 'wdcp siteAdd: 缺少 domain 参数',
            40201 => 'wdcp siteEdit: 缺少站点ID 参数',
            40301 => 'wdcp siteDel: 缺少站点ID 参数',
            // ftp
            41000 => 'wdcp ftp error: ',
            41001 => '',
            // mysql
            42000 => 'wdcp mysql error: ',
            42001 => '',

            44444 => '默认, 待写入错误函数',
    );
    public static function getErrText($err) {
        if (isset(self::$errCode[$err])) {
            return self::$errCode[$err];
        }else {
            return false;
        }
    }
}
