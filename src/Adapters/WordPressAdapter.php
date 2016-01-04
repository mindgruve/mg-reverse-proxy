<?php

namespace Mindgruve\ReverseProxy\Adapters;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WordPressAdapter extends GenericAdapter
{

    /**
     * @return boolean
     */
    public function isCachingEnabled(Request $request)
    {
        return $this->isLoggedIn() == false;
    }

    /**
     * @return bool
     */
    protected function isLoggedIn()
    {
        foreach ($_COOKIE as $key => $value) {
            if (preg_match('/^wordpress_logged_in_(.*)/', $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function setCacheHeaders(Request $request, Response $response)
    {
        if ($this->isLoggedIn()) {
            $response->setPrivate();
        } else {
            $response->setPublic();
        }

        return $response;
    }
}