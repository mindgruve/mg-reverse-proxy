<?php

namespace Mindgruve\ReverseProxy;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface AdapterInterface
{

    /**
     * @return boolean
     */
    public function isEnabled();

    /**
     * @param Request $request
     * @param Response $response
     */
    public function setCacheHeaders(Request $request, Response $response);

    /**
     *
     */
    public function bootstrap();

    /**
     * @return string
     */
    public function getRawOutput();

}