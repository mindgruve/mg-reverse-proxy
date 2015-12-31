<?php

namespace Mindgruve\ReverseProxy\WordPress;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;

class WordPressBootstrap implements ControllerResolverInterface
{
    protected $WordPress_ABSPATH;

    /**
     * THE PATH TO THE WORDPRESS INSTALLATION
     * @param $WordPress_ABSPATH
     */
    public function __construct($WordPress_ABSPATH)
    {
        $this->WordPress_ABSPATH = $WordPress_ABSPATH;
    }

    /**
     * THE ARGUMENTS PASSED TO THE CALLABLE
     * @param Request $request
     * @param callable $controller
     * @return array
     */
    public function getArguments(Request $request, $controller)
    {
        return array(
            $this->WordPress_ABSPATH,
        );
    }

    /**
     * BOOTSTRAPS WORDPRESS, AND COLLECTS THE OUTPUT INTO A RESPONSE OBJECT
     * @param Request $request
     * @return callable
     */
    public function getController(Request $request)
    {
        $wpBootStrapFile = $this->WordPress_ABSPATH . '/wp-blog-header.php';
        return function () use ($wpBootStrapFile) {
            if (!file_exists($wpBootStrapFile)) {
                throw new \Exception('Unable to bootstrap WordPress.');
            }

            ob_start();
            include_once($wpBootStrapFile);
            $output = ob_get_contents();
            ob_end_clean();

            $response = new Response($output);
            $response->headers->add(getallheaders());
            $response->setStatusCode(http_response_code());

            return $response;
        };
    }
}