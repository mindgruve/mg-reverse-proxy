<?php

namespace Mindgruve\ReverseProxy\Adapters;

use Mindgruve\ReverseProxy\AdapterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WordPressAdapter implements AdapterInterface
{

    /**
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->isLoggedIn() == false;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function setCacheHeaders( Request $request, Response $response)
    {
        if (!$this->isLoggedIn()) {
            $response->setPublic();
        }
        return $response;
    }

    protected function isLoggedIn()
    {
        foreach ($_COOKIE as $key => $value) {
            if (preg_match('/^wordpress_logged_in_(.*)/', $key)) {
                return true;
            }
        }
        return false;
    }
}