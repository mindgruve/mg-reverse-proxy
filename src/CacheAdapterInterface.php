<?php

namespace Mindgruve\ReverseProxy;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\StoreInterface;
use Symfony\Component\HttpKernel\HttpCache\SurrogateInterface;

interface CacheAdapterInterface
{

    /**
     * @return boolean
     */
    public function isCachingEnabled(Request $request);

    /**
     * @param Request $request
     * @param Response $response
     */
    public function setCacheHeaders(Request $request, Response $response);

    /**
     * @return StoreInterface
     */
    public function getStore(Request $request);

    /**
     * @return null | SurrogateInterface
     */
    public function getSurrogate(Request $request);

    /**
     * @return array
     */
    public function getHttpCacheOptions(Request $request);


    public function bootstrap(Request $request);

    /**
     * @return string
     */
    public function getRawContent(Request $request);

}