<?php

namespace Mindgruve\ReverseProxy\WordPress;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;

class ControllerResolver implements ControllerResolverInterface
{
    protected $wpBootStrapFile;

    public function __construct($wpBootStrapFile)
    {
        $this->wpBootStrapFile = $wpBootStrapFile;
    }

    public function getArguments(Request $request, $controller)
    {
        return array(
            $this->wpBootStrapFile,
        );
    }

    public function getController(Request $request)
    {
        return array('Mindgruve\ReverseProxy\WordPress\BootstrapController', 'bootstrapAction');

    }

}