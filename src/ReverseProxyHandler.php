<?php

namespace Mindgruve\ReverseProxy;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\KernelInterface;


class ReverseProxyHandler
{

    public function __construct(KernelInterface $kernel, HttpCache $httpCache, ControllerResolverInterface $controllerResolver)
    {
        // create the Request object
        $request = Request::createFromGlobals();

        $dispatcher = new EventDispatcher();
        // ... add some event listeners

        // create your controller resolver
        $resolver = new ControllerResolver();
        // instantiate the kernel
        $kernel = new HttpKernel($dispatcher, $resolver);
        $kernel = new $httpCache($kernel);

        // actually execute the kernel, which turns the request into a response
        // by dispatching events, calling a controller, and returning the response
        $response = $kernel->handle($request);

        // send the headers and echo the content
        $response->send();

        // triggers the kernel.terminate event
        $kernel->terminate($request, $response);
    }
}