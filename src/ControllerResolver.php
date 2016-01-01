<?php

namespace Mindgruve\ReverseProxy;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;

class ControllerResolver implements ControllerResolverInterface
{
    /**
     * @var string
     */
    protected $rawOutput;

    /**
     * @var callable
     */
    protected $controller;

    /**
     * @param $rawOutput
     * @param callable $controller
     */
    public function __construct($rawOutput, Callable $controller)
    {
        $this->rawOutput = $rawOutput;
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
            $this->rawOutput,
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