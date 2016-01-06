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
        if ($this->isLoggedIn($request)) {
            return false;
        }

        if ($this->isWordpressAdminPage($request)) {
            return false;
        }
    }

    /**
     * @return bool
     */
    protected function isLoggedIn(Request $request)
    {
        foreach ($request->cookies as $key => $value) {
            if (preg_match('/^wordpress_logged_in_(.*)/', $key)) {
                return true;
            }
        }

        return false;
    }

    protected function isWordpressAdminPage(Request $request)
    {
        if (preg_match('/wp-admin/', $request->getRequestUri())) {
            return true;
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
        $response->setMaxAge($this->defaultMaxAge);

        if ($this->isLoggedIn($request) || $this->isLoggedIn($request)) {
            $response->setPrivate();
        } else {
            $response->setPublic();
        }

        return $response;
    }
}