<?php

namespace Mach\Silex\Piwik;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PiwikProxyServiceProvider implements ServiceProviderInterface
{
    protected $remoteContent = null;

    public function __construct(RemoteContentInterface $remoteContent = null)
    {
        $this->remoteContent = ($remoteContent === null) ? new FileGetContents() : $remoteContent;
    }

    public function register(Application $app)
    {
        $remoteContent = $this->remoteContent;
        $app['piwik.proxy.http'] = $app->share(function ($app) use ($remoteContent) {
            return $remoteContent;
        });

        $app['piwik.proxy'] = $app->share(function ($app) {
            if (!isset($app['piwik.proxy.url'])) {
                throw new \RuntimeException('The "piwik.proxy.url" config parameter should be set.');
            }

            if (!isset($app['piwik.proxy.token'])) {
                throw new \RuntimeException('The "piwik.proxy.token" config parameter should be set.');
            }

            if (!isset($app['piwik.proxy.timeout'])) {
                $app['piwik.proxy.timeout'] = 5;
            }

            if (!isset($app['piwik.proxy.cache'])) {
                $app['piwik.proxy.cache'] = 86400;
            }

            return function (Request $request, array $options = array()) use ($app) {
                $defaults = array(
                    'url' => $app['piwik.proxy.url'],
                    'token' => $app['piwik.proxy.token'],
                    'timeout' => $app['piwik.proxy.timeout'],
                    'cache' => $app['piwik.proxy.cache'],
                );

                $options = array_merge($defaults, $options);

                if (substr($options['url'], -1) !== '/') {
                    $options['url'] .= '/';
                }

                if ($request->query->count() > 0) {
                    $query = $request->query->all();
                    $query['cip'] = $request->server->get('REMOTE_ADDR');
                    $query['token_auth'] = $options['token'];

                    $url = sprintf('%spiwik.php?%s', $options['url'], http_build_query($query));

                    return new Response(
                        $app['piwik.proxy.http']->get($url, array('http' => array(
                            'user_agent' => $request->server->get('HTTP_USER_AGENT'),
                            'timeout' => $options['timeout'],
                            'header' => sprintf("Accept-Language: %s\r\n", str_replace(array("\n", "\t", "\r"), '', $request->server->get('HTTP_ACCEPT_LANGUAGE'))),
                        ))),
                        200,
                        array('Content-Type' => 'image/gif')
                    );
                }

                $modifiedSince = false;
                if ($request->server->has('HTTP_IF_MODIFIED_SINCE')) {
                    $modifiedSince = $request->server->get('HTTP_IF_MODIFIED_SINCE');
                    if (false !== ($semicolon = strpos($modifiedSince, ';'))) {
                        $modifiedSince = strtotime(substr($modifiedSince, 0, $semicolon));
                    }
                }

                $lastModified = time() - $options['cache'];

                $headers = array('Vary' => 'Accept-Encoding');

                if (!empty($modifiedSince) && $modifiedSince < $lastModified) {
                    return new Response('', 304, $headers);
                }

                $headers['Content-Type'] = 'application/javascript; charset=UTF-8';

                return Response::create(
                    $app['piwik.proxy.http']->get(sprintf('%spiwik.js', $options['url'])),
                    200,
                    $headers
                )->setLastModified(new \DateTime('now'));
            };
        });
    }

    public function boot(Application $app)
    {
    }
}
