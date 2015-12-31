<?php

namespace Mindgruve\ReverseProxy\WordPress;

use Symfony\Component\HttpFoundation\Response;

class BootstrapController
{
    public static function bootstrapAction($wpBootStrapFile)
    {
        if(!file_exists($wpBootStrapFile)){
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
    }
}