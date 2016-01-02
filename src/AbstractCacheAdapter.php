<?php

namespace Mindgruve\ReverseProxy;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\StoreInterface;
use Symfony\Component\HttpKernel\HttpCache\SurrogateInterface;

abstract class AbstractCacheAdapter
{

    const RESPONSE_TYPE_PRIVATE = 'private';
    const RESPONSE_TYPE_PUBLIC = 'public';

    /**
     * @return boolean
     */
    abstract public function isCachingEnabled();

    /**
     * @return boolean
     */
    abstract public function isShutdownFunctionEnabled();

    /**
     * @param Request $request
     * @param Response $response
     */
    abstract public function setCacheHeaders(Request $request, Response $response);

    /**
     * @return StoreInterface
     */
    abstract public function getStore();

    /**
     * @return null | SurrogateInterface
     */
    abstract public function getSurrogate();

    /**
     * @return array
     */
    abstract public function getHttpCacheOptions();


    abstract public function bootstrap();

    /**
     * @return string
     */
    abstract public function getRawContent();

    /**
     * @return int
     */
    abstract public function getDefaultMaxAge();

    /**
     * @return string
     */
    abstract public function getDefaultResponseType();

}