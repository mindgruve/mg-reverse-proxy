<?php

namespace Mindgruve\ReverseProxy;

use Symfony\Component\HttpKernel\HttpCache\Store;
use Symfony\Component\HttpKernel\HttpCache\SurrogateInterface;

class Configuration
{
    protected $store;
    protected $surrogate;
    protected $httpCacheOptions;
    protected $enableShutdownFunction;

    public function __construct(Store $store = null, SurrogateInterface $surrogate = null, array $httpCacheOptions = array(), $enableShutdownFunction = true)
    {
        $this->store = $store;
        $this->surrogate;
        $this->httpCacheOptions = $httpCacheOptions;
        $this->enableShutdownFunction = $enableShutdownFunction;
    }

    public function getStore()
    {
        return $this->store;
    }

    public function getSurrogate()
    {
        return $this->surrogate;
    }

    public function getHttpCacheOptions()
    {
        return $this->httpCacheOptions;
    }

    public function isShutdownFunctionEnabled()
    {
        return $this->enableShutdownFunction;
    }


}