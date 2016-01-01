<?php

namespace Mindgruve\ReverseProxy;

use Symfony\Component\HttpKernel\HttpCache\SurrogateInterface;
use Symfony\Component\HttpKernel\HttpCache\StoreInterface;

class Configuration
{

    const RESPONSE_TYPE_PRIVATE = 'private';
    const RESPONSE_TYPE_PUBLIC = 'public';

    protected $maxAge;
    protected $store;
    protected $surrogate;
    protected $httpCacheOptions;
    protected $enableShutdownFunction;
    protected $defaultResponseType;
    protected $bootstrapFilePath;

    public function __construct(
        $bootstrapFilePath,
        StoreInterface $store,
        $maxAge = 600,
        $defaultResponseType = self::RESPONSE_TYPE_PRIVATE,
        SurrogateInterface $surrogate = null,
        array $httpCacheOptions = array(),
        $enableShutdownFunction = true)
    {
        $this->bootstrapFilePath = $bootstrapFilePath;
        $this->maxAge = $maxAge;
        $this->store = $store;
        $this->surrogate;
        $this->httpCacheOptions = $httpCacheOptions;
        $this->enableShutdownFunction = $enableShutdownFunction;
    }

    public function getBootstrapFilePath(){
        return $this->bootstrapFilePath;
    }

    public function getMaxAge()
    {
        return $this->maxAge;
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

    public function getDefaultResponseType()
    {
        return $this->defaultResponseType;
    }

}