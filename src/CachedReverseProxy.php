<?php

namespace Mindgruve\ReverseProxy;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use Symfony\Component\HttpKernel\HttpKernel;

class CachedReverseProxy
{

    /**
     * @var CacheAdapterInterface
     */
    protected $adapter;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var bool
     */
    protected $bootstrapped = false;

    public function __construct(CacheAdapterInterface $adapter)
    {
        $this->request = Request::createFromGlobals();
        $this->adapter = $adapter;

        if ($this->adapter->isShutdownFunctionEnabled($this->request)) {
            register_shutdown_function(array($this, 'handleShutdown'));
        }
    }

    /**
     *
     */
    public function handleShutdown()
    {
        $this->buildAndCacheResponse();
    }

    /**
     *
     */
    public function run()
    {
        $this->buildAndCacheResponse();
    }

    protected function buildAndCacheResponse()
    {
        $controllerResolver = new ControllerResolver(array($this, 'buildResponse'));
        $kernel = new HttpKernel(new EventDispatcher(), $controllerResolver);

        if ($this->adapter->isCachingEnabled($this->request)) {
            $kernel = new HttpCache(
                $kernel,
                $this->adapter->getStore($this->request),
                $this->adapter->getSurrogate($this->request),
                $this->adapter->getHttpCacheOptions($this->request)
            );
        }


        $response = $kernel->handle($this->request);
        $response->send();
        $kernel->terminate($this->request, $response);
    }

    /**
     * Bootstrap application
     */
    public function bootstrap()
    {
        if (!$this->bootstrapped) {
            $this->bootstrapped = true;
            $this->adapter->bootstrap($this->request);
        }
    }

    /**
     * @return string
     */
    public function getRawContent()
    {
        return $this->adapter->getRawContent($this->request);
    }

    /**
     * @return Response
     */
    public function buildResponse()
    {
        $this->bootstrap();
        $rawContent = $this->getRawContent();
        $response = new Response($rawContent);
        $response->headers->add(getallheaders());
        $response->setStatusCode(http_response_code());
        $response = $this->setCacheHeaders($this->request, $response);

        return $response;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function setCacheHeaders(Request $request, Response $response)
    {
        return $this->adapter->setCacheHeaders($request, $response);
    }
}