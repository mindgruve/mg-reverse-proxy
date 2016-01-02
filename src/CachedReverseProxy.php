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
     * @var AbstractCacheAdapter
     */
    protected $adapter;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var
     */
    protected $rawContent;

    /**
     * @var bool
     */
    protected $bootstrapped = false;

    public function __construct(AbstractCacheAdapter $adapter)
    {
        $this->request = Request::createFromGlobals();
        $this->adapter = $adapter;

        if ($this->adapter->isShutdownFunctionEnabled()) {
            register_shutdown_function(array($this, 'handleShutdown'));
        }
    }

    /**
     *
     */
    public function handleShutdown()
    {
        $this->run();
    }

    /**
     *
     */
    public function run()
    {
        $controllerResolver = new ControllerResolver(array($this, 'buildResponse'));
        $kernel = new HttpKernel(new EventDispatcher(), $controllerResolver);

        if ($this->adapter->isCachingEnabled()) {
            $kernel = new HttpCache(
                $kernel,
                $this->adapter->getStore(),
                $this->adapter->getSurrogate(),
                $this->adapter->getHttpCacheOptions()
            );
        }


        $response = $kernel->handle($this->request);
        $response->send();
        $kernel->terminate($this->request, $response);
    }

    /**
     *
     */
    public function bootstrap()
    {
        if (!$this->bootstrapped) {
            $this->bootstrapped = true;
            $this->adapter->bootstrap();
        }
    }

    /**
     * @return string
     */
    public function getRawContent()
    {
        return $this->adapter->getRawContent();
    }

    /**
     * @return Response
     */
    public function buildResponse()
    {
        $this->bootstrap();
        $this->rawContent = $this->getRawContent();
        $response = new Response($this->rawContent);
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
        $response->setMaxAge($this->adapter->getDefaultMaxAge());
        if ($this->adapter->getDefaultResponseType() == AbstractCacheAdapter::RESPONSE_TYPE_PRIVATE) {
            $response->setPrivate();
        }
        if ($this->adapter->getDefaultResponseType() == AbstractCacheAdapter::RESPONSE_TYPE_PUBLIC) {
            $response->setPublic();
        }
        return $this->adapter->setCacheHeaders($request, $response);
    }

}