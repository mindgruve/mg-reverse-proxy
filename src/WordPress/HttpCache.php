<?php

namespace Mindgruve\ReverseProxy\WordPress;

use Symfony\Component\HttpKernel\HttpCache\HttpCache as BaseHttpCache;

class HttpCache extends BaseHttpCache
{
    protected function getOptions()
    {
        return array(
            'debug'                  => false,
            'default_ttl'            => 0,
            'private_headers'        => array('Authorization', 'Cookie'),
            'allow_reload'           => false,
            'allow_revalidate'       => false,
            'stale_while_revalidate' => 2,
            'stale_if_error'         => 60,
        );
    }
}