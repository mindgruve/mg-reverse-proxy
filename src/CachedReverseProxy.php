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

    protected $request;

    protected $rawOutput;

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
        $this->request = Request::createFromGlobals();
        $this->bootstrap();
        $this->rawOutput = $this->getRawOutput();
    }

    public function handleShutdown()
    {
        $this->run();
    }

    public function run()
    {

        $controllerResolver = new ControllerResolver($this->rawOutput, array($this, 'buildResponse'));
        $kernel = new HttpKernel(new EventDispatcher(), $controllerResolver);

        if ($this->adapter->isEnabled()) {
            $kernel = new HttpCache(
                $kernel,
                $this->configuration->getStore(),
                $this->configuration->getSurrogate(),
                $this->configuration->getHttpCacheOptions()
            );
        }


        $response = $kernel->handle($this->request);

        $response->send();
        $kernel->terminate($this->request, $response);
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
        $response = $this->setCacheHeaders($this->configuration, $this->request, $response);

        return $response;
    }

    public function setCacheHeaders(Configuration $configuration, Request $request, Response $response)
    {
        $response->setMaxAge($configuration->getMaxAge());
        if ($configuration->getDefaultResponseType() == Configuration::RESPONSE_TYPE_PRIVATE) {
            $response->setPrivate();
        }
        if ($configuration->getDefaultResponseType() == Configuration::RESPONSE_TYPE_PUBLIC) {
            $response->setPublic();
        }
        return $this->adapter->setCacheHeaders($request, $response);
    }

}