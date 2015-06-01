<?php

namespace CrowdFusion\Tests\ActiveEditsPlugin\Controller;

use CrowdFusion\Tests\Caching\Stores\Mock\MemcachedMock;
use CrowdFusion\ActiveEditsPlugin\Controller\HeartbeatCmsController;

class HeartbeatCmsControllerTest extends \PHPUnit_Framework_TestCase
{
    /** @var HeartbeatCmsController */
    protected $controller;

    /** @var \MemcachedCacheStore */
    protected $cache;

    /** @var \Request */
    protected $request;

    /** @var \stdClass */
    protected $user;

    protected function setUp()
    {
        $Logger = $this->getMock('LoggerInterface');

        $InputClean = $this
            ->getMockBuilder('InputCleanInterface')
            ->setConstructorArgs(array('/tmp', 'utf-8'))
            ->getMock()
        ;
        $Request       = new \Request($InputClean, '/');
        $this->request = $Request;

        $this->user        = new \stdClass();
        $this->user->Slug  = 'jonnytest';
        $this->user->Title = 'Jonny Test';

        $RequestContext = new \RequestContext();
        $RequestContext->setUser($this->user);

        $TransactionManager = $this->getMock('TransactionManagerInterface');

        $Nonces = $this
            ->getMockBuilder('Nonces')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $Permissions = $this->getMock('Permissions');
        $DateFactory = $this
            ->getMockBuilder('DateFactory')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $ModelMapper = $this->getMock('ModelMapper');

        $Session = $this
            ->getMockBuilder('Session')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $Response = $this
            ->getMockBuilder('Response')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->controller = new HeartbeatCmsController(
            $Logger,
            $Request,
            $RequestContext,
            $TransactionManager,
            $Nonces,
            $Permissions,
            $DateFactory,
            $ModelMapper,
            $Session,
            $Response,
            false
        );

        if (!class_exists('Memcached')) {
            MemcachedMock::create();
        }

        $this->cache = new \MemcachedCacheStore(
            new \NullLogger(),
            array(array(
                'host' => '127.0.0.1',
                'port' => 11211
            )),
            sprintf('test_%s_', md5(rand())),
            true,
            'cf_memcached_tests',
            false,
            false,
            false,
            false
        );

        $this->controller->setPrimaryCacheStore($this->cache);
    }

    protected function tearDown()
    {
        unset($this->controller);
    }

    public function testCreate()
    {
        $this->assertInstanceOf('\AbstractCmsController', $this->controller);
    }

    /**
     * @dataProvider getSlugs
     */
    public function testGetMembersAction($slug)
    {
        $this->request->addRouteParameters(array('slug' => $slug));

        ob_start();
        $this->controller->getMembersAction();
        ob_end_flush();

        $output = ob_get_contents();

        $this->assertJsonStringEqualsJsonString($output, json_encode(array(array(
            'slug'       => $this->user->Slug,
            'name'       => $this->user->Title,
            'activeDate' => null,
            'updateMeta' => false
        ))));
    }

    /**
     * @dataProvider getSlugs
     */
    public function testTotalMembersAction($slug)
    {
        $this->request->addRouteParameters(array('slugs' => $slug));

        ob_start();
        $this->controller->totalMembersAction();
        ob_end_flush();

        $output = ob_get_contents();

        $this->assertJsonStringEqualsJsonString($output, json_encode(array(
            $slug => array(array(
                'slug' => $this->user->Slug,
                'name' => $this->user->Title,
            ))
        )));
    }

    public function getSlugs()
    {
        $slugs = array();

        for ($i=0; $i<10; $i++) {
            $slugs[] = array(sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

        		// 32 bits for "time_low"
        		mt_rand(0, 0xffff), mt_rand(0, 0xffff),

        		// 16 bits for "time_mid"
        		mt_rand(0, 0xffff),

        		// 16 bits for "time_hi_and_version",
        		// four most significant bits holds version number 4
        		mt_rand(0, 0x0fff) | 0x4000,

        		// 16 bits, 8 bits for "clk_seq_hi_res",
        		// 8 bits for "clk_seq_low",
        		// two most significant bits holds zero and one for variant DCE1.1
        		mt_rand(0, 0x3fff) | 0x8000,

        		// 48 bits for "node"
        		mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    		));
        }

        return $slugs;
    }
}
