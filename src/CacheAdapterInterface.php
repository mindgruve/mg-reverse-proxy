<?php

namespace Mindgruve\ReverseProxy;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\StoreInterface;
use Symfony\Component\HttpKernel\HttpCache\SurrogateInterface;

interface CacheAdapterInterface
{

    const RESPONSE_TYPE_PRIVATE = 'private';
    const RESPONSE_TYPE_PUBLIC = 'public';

    /**
     * @return boolean
     */
    public function isCachingEnabled();

    /**
     * @param Request $request
     * @return boolean
     */
    public function isShutdownFunctionEnabled(Request $request);

    /**
     * @param Request $request
     * @param Response $response
     */
    public function setCacheHeaders(Request $request, Response $response);

    /**
     * @return StoreInterface
     */
    public function getStore();

    /**
     * @return null | SurrogateInterface
     */
    public function getSurrogate();

    /**
     * @return array
     */
    public function getHttpCacheOptions();


    public function bootstrap();

    /**
     * @return string
     */
    public function getRawContent();

}