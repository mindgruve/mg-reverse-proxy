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
     * @return Response
     */
    public function setCacheHeaders(Request $request, Response $response);

    public function bootstrap();

    public function getRawOutput();

}