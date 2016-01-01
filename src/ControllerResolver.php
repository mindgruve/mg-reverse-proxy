<?php

namespace Mindgruve\ReverseProxy;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;

class ControllerResolver implements ControllerResolverInterface
{

    /**
     * @var callable
     */
    protected $controller;

    /**
     * @param callable $controller
     */
    public function __construct(Callable $controller)
    {
        $this->controller = $controller;
    }

    /**
     * @param Request $request
     * @param callable $controller
     * @return array
     */
    public function getArguments(Request $request, $controller)
    {
        return array(
        );
    }

    /**
     * @param Request $request
     * @return callable
     */
    public function getController(Request $request)
    {
        return $this->controller;
    }


}