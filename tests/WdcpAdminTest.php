<?php
/**
 * Created by pifeifei.
 * User: pifeifei <pifeifei1989@qq.com>
 * Date: 2019-08-26 19:49
 */

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Pifeifei\WdcpAdmin;

class WdcpAdminTest extends BaseTestCase
{
    /**
     * @var WdcpAdmin
     */
    protected $wdcpAdmin;

    protected $wdcpConfig = [
        'uri'      => 'http://192.168.1.82:53088/',
        'username' => 'api_user',
        'password' => 'wdcpAdmin@123',
//        'ftp_user' => 'api_ftp_user',
//        'ftp_pwd'  => 'wdcpAdmin@123',
//        'ftp_port' => 21,
//        'debug'    => false,
//        'logCallback'=> false
    ];

    protected $wdcpTestFtpUser = [
        'username' => '',
        ''
    ];

    protected $wdcpConfig2 = [];

    public function setUp()
    {
        $this->wdcpAdmin = new WdcpAdmin($this->wdcpConfig);
    }

    public function testLogin()
    {
        $this->assertTrue($this->wdcpAdmin->valid());

    }

    public function testSite()
    {
        // add site
        $siteName = 'test-domain.pp';
        $siteConfig = [
            'gzip' => 1,
            'expires' => 1,
            'vhostdir' => 'test-domain-dir'
        ];
        $siteInfo = $this->wdcpAdmin->siteAdd($siteName, $siteConfig);
        $this->assertIsArray($siteInfo);
        $siteId = $siteInfo['id'];
        $this->assertGreaterThan(0, $siteInfo);

        // get site info
        $siteEditInfo = $this->wdcpAdmin->getSiteEditFormArray($siteId);
        $this->assertEquals($siteInfo['domain'], $siteEditInfo['domain']);
        $this->assertEquals($siteInfo['domains'], $siteEditInfo['domains']);
        $this->assertEquals($siteInfo['vhostdir'], $siteEditInfo['vhostdir']);

        // add domain name for site
        $addDomains = ['test1.pp', 'test2.pp'];
        $siteAddDomain = $this->wdcpAdmin->siteAddDomainForSiteId($siteId, $addDomains);
        $siteEditInfo = $this->wdcpAdmin->getSiteEditFormArray($siteId);
        $this->assertEquals($siteAddDomain['domains'], $siteEditInfo['domains']);
        unset($addDomains, $siteAddDomain);

        // delete domain name for site
        $addDomains = ['test1.pp'];
        $siteRemoveDomain = $this->wdcpAdmin->siteRemoveDomainForSiteId($siteId, $addDomains);
        $siteEditInfo = $this->wdcpAdmin->getSiteEditFormArray($siteId);
        $this->assertEquals($siteRemoveDomain['domains'], $siteEditInfo['domains']);
        unset($siteRemoveDomain);

        $this->assertFalse($this->wdcpAdmin->siteHasDomainForSiteId($siteId, 'test1.pp'));
        
        unset($siteEditInfo, $addDomains, $siteInfo);

        // delete site
        $this->assertTrue($this->wdcpAdmin->siteDelete($siteId));
    }

    public function testGetOption()
    {
        $this->assertEquals($this->wdcpConfig, $this->wdcpAdmin->getOption());
    }

    public function testFtp()
    {
        $ftpInfo = $this->wdcpAdmin->ftpAdd('testFtpUser', [
            'password' => '1234567890abcde'
        ]);
        $this->assertIsArray($ftpInfo);

        $this->assertIsArray($this->wdcpAdmin->ftpList());

        $this->wdcpAdmin->ftpDelete($ftpInfo['id']);
    }
}