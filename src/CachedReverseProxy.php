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

    protected $shutDownFunctionEnabled = false;

    public function __construct(CacheAdapterInterface $adapter)
    {
        $this->request = Request::createFromGlobals();
        $this->adapter = $adapter;

        if ($this->adapter->isShutdownFunctionEnabled($this->request)) {
            register_shutdown_function(array($this, 'handleShutdown'));
            $this->shutDownFunctionEnabled = true;
        }
    }

    /**
     *
     */
    public function handleShutdown()
    {
        if ($this->shutDownFunctionEnabled) {
            $this->buildAndCacheResponse();
        }
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
        $this->shutDownFunctionEnabled = false;
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

        /**
         * Grab the headers and update response object
         */
        $currentHeaders = headers_list();
        foreach ($currentHeaders as $header) {
            $headers = $this->parseHTTPHeaders($header);
            foreach ($headers as $key => $value) {
                $response->headers->set($key, $value);
            }
        }

        $response->setStatusCode($this->getHTTPStatusCode($response));
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

    public function getHTTPStatusCode(Response $response)
    {

        if (function_exists('http_response_code')) {
            return http_response_code();
        }

        foreach ($response->headers as $header) {
            if (preg_match("#^HTTP/\S+\s+(\d\d\d)#i", $header, $matches)) {
                return $matches[1];
            }
        }

        return 200;
    }

    public function parseHTTPHeaders($header)
    {
        /**
         * http_parse_headers is provided by a pecl extensions
         */
        if (function_exists('http_parse_headers')) {
            return http_parse_headers($header);
        }

        /**
         * Polyfill for the http_parse_headers() function
         */
        $retVal = array();
        $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));
        foreach ($fields as $field) {
            if (preg_match('/([^:]+): (.+)/m', $field, $match)) {
                $match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));
                if (isset($retVal[$match[1]])) {
                    $retVal[$match[1]] = array($retVal[$match[1]], $match[2]);
                } else {
                    $retVal[$match[1]] = trim($match[2]);
                }
            }
        }

        return $retVal;

    }
}