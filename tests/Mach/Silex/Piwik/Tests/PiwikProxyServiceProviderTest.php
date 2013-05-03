<?php

namespace Mach\Silex\Piwik\Tests;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Mach\Silex\Piwik\PiwikProxyServiceProvider;

class PiwikProxyServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The "piwik.proxy.url" config parameter should be set.
     */
    public function testRequiresProxyUrl()
    {
        $values = $this->getValues();
        unset($values['piwik.proxy.url']);

        $app = $this->createApplication($values);

        $piwikProxy = $app['piwik.proxy'];
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The "piwik.proxy.token" config parameter should be set.
     */
    public function testRequiresProxyToken()
    {
        $values = $this->getValues();
        unset($values['piwik.proxy.token']);

        $app = $this->createApplication($values);

        $piwikProxy = $app['piwik.proxy'];
    }

    public function testRespondsWithJavascriptIfNoQuery()
    {
        $app = $this->createApplication();

        $request = new Request();

        $response = $app['piwik.proxy']($request);

        $this->assertRegExp('/application\/javascript/', $response->headers->get('Content-Type'));
        $this->assertEquals('http://piwik.example.com/piwik.js', $response->getContent());
    }

    public function testRespondsWithGifIfQuery()
    {
        $app = $this->createApplication();
        $request = new Request(array('foo' => 'bar'));
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $response = $app['piwik.proxy']($request);

        $parsedUrl = parse_url($response->getContent());
        
        parse_str($parsedUrl['query'], $parsedUrl['query']);
        
        $this->assertRegExp('/image\/gif/', $response->headers->get('Content-Type'));
        $this->assertEquals('piwik.example.com', $parsedUrl['host']);
        $this->assertEquals('/piwik.php', $parsedUrl['path']);
        $this->assertArrayHasKey('cip', $parsedUrl['query']);
        $this->assertArrayHasKey('token_auth', $parsedUrl['query']);
        $this->assertArrayHasKey('foo', $parsedUrl['query']);
        $this->assertEquals('xyz', $parsedUrl['query']['token_auth']);
        $this->assertEquals('bar', $parsedUrl['query']['foo']);
        $this->assertEquals('127.0.0.1', $parsedUrl['query']['cip']);
    }

    public function createApplication(array $values = null)
    {
        if ($values === null) {
            $values = $this->getValues();
        }

        $app = new Application();
        $app->register(new PiwikProxyServiceProvider($this->mockRemoteContent()), $values);

        return $app;
    }

    public function mockRemoteContent()
    {
        $mock = $this->getMock('Mach\Silex\Piwik\RemoteContentInterface');
        $mock->expects($this->any())
            ->method('get')
            ->will($this->returnArgument(0));

        return $mock;
    }

    public function getValues()
    {
        return array(
            'piwik.proxy.url' => 'http://piwik.example.com/',
            'piwik.proxy.timeout' => 5,
            'piwik.proxy.token' => 'xyz',
            'piwik.proxy.cache' => 86400,
        );
    }
}