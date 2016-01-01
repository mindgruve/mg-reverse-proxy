<?php

namespace Mindgruve\ReverseProxy\Adapters;

use Mindgruve\ReverseProxy\AdapterInterface;
use Mindgruve\ReverseProxy\CachedReverseProxy;
use Mindgruve\ReverseProxy\Configuration;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WordPress implements AdapterInterface
{
    protected $filename;

    public function __construct($filename)
    {
        $this->filename = $filename;
    }

    public function bootstrap()
    {
        ob_start();
        include_once($this->filename);
    }

    public function getRawOutput()
    {
        $rawOutput = ob_get_contents();
        ob_end_clean();

        return $rawOutput;
    }

    /**
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->isLoggedIn();
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