<?php

namespace Mindgruve\ReverseProxy\Adapters;

use Mindgruve\ReverseProxy\CacheAdapterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\StoreInterface;
use Symfony\Component\HttpKernel\HttpCache\SurrogateInterface;

class GenericAdapter implements CacheAdapterInterface
{
    /**
     * @var StoreInterface
     */
    protected $store;

    /**
     * @var SurrogateInterface
     */
    protected $surrogate;

    /**
     * @var array
     */
    protected $httpCacheOptions;

    /**
     * @var callable
     */
    protected $bootstrapFunction;

    /**
     * @var int
     */
    protected $defaultMaxAge;

    public function __construct(
        callable $bootstrapFunction,
        $defaultMaxAge,
        StoreInterface $store,
        array $httpCacheOptions = array(),
        SurrogateInterface $surrogate = null
    ) {
        $this->bootstrapFunction = $bootstrapFunction;
        $this->defaultMaxAge = $defaultMaxAge;
        $this->store = $store;
        $this->surrogate = $surrogate;
        $this->httpCacheOptions = $httpCacheOptions;
    }

    /**
     * @return boolean
     */
    public function isCachingEnabled(Request $request)
    {
        return true;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function setCacheHeaders(Request $request, Response $response)
    {
        return $response;
    }

    /**
     * @return StoreInterface
     */
    public function getStore(Request $request)
    {
        return $this->store;
    }

    /**
     * @return null | SurrogateInterface
     */
    public function getSurrogate(Request $request)
    {
        return $this->surrogate;
    }

    /**
     * @return array
     */
    public function getHttpCacheOptions(Request $request)
    {
        return $this->httpCacheOptions;
    }

    /**
     * @param Request $request
     * @return mixed|void
     */
    public function bootstrap(Request $request)
    {
        ob_start();

        call_user_func($this->bootstrapFunction, array($request));
    }

    /**
     * @return string
     */
    public function getRawContent(Request $request)
    {
        $rawContent = ob_get_contents();
        ob_end_clean();

        return $rawContent;
    }
}