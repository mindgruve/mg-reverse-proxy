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
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var bool
     */
    protected $bootstrapped = false;

    public function __construct(AdapterInterface $adapter, Configuration $configuration)
    {
        $this->adapter = $adapter;
        $this->configuration = $configuration;
        if ($this->configuration->isShutdownFunctionEnabled()) {
            register_shutdown_function(array($this, 'handleShutdown'));
        }
    }

    public function handleShutdown()
    {
        $this->run();
    }

    public function run()
    {
        $rawOutput = $this->getRawOutput();
        $controllerResolver = new ControllerResolver($rawOutput, array($this, 'buildResponse'));
        $kernel = new HttpKernel(new EventDispatcher(), $controllerResolver);

        if ($this->adapter->isEnabled()) {
            $kernel = new HttpCache(
                $kernel,
                $this->configuration->getStore(),
                $this->configuration->getSurrogate(),
                $this->configuration->getHttpCacheOptions()
            );
        }

        $request = Request::createFromGlobals();
        $response = $kernel->handle($request);

        $this->setCacheHeaders($request, $response);
        $response->send();
        $kernel->terminate($request, $response);
    }

    public function bootstrap()
    {
        if (!$this->bootstrapped) {
            $this->adapter->bootstrap();
        }
        $this->bootstrapped = true;
    }

    public function getRawOutput()
    {
        return $this->adapter->getRawOutput();
    }

    public function buildResponse($rawOutput)
    {
        $response = new Response($rawOutput);
        $response->headers->add(getallheaders());
        $response->setStatusCode(http_response_code());

        return $response;
    }

    public function setCacheHeaders(Request $request, Response $response)
    {
        $this->adapter->setCacheHeaders($response, $request);
    }

}