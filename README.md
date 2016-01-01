# mg-reverse-proxy

MG-Reverse-Proxy works by transforming the output buffer and headers created by your application into a Symfony Reponse object that it can use to intellegently cache using the Symfony HTTP Cache.  

## HTTP Caching Overview
In general, MG-Reverse-Proxy will cache responses that are set to PUBLIC and have a non-zero MAX-AGE, and will only cache 'safe' HTTP methods.  Since MG-Reverse-Proxy leverages the Symfony HTTPCache, there are many methods available to customize your response headers.

For more information on the Symfony HTTPCache see: http://symfony.com/doc/current/book/http_cache.html

## Use Cases
Content heavy sites are a good candidate for MG-Reverse-Proxy.  You can customize the cacheability
of the responses by setting cache headers in your application or configuring your cache adapter (described below).

##Example Usage - Wordpress index.php

    <?php
    /** Standard WordPress front-page **/
    include_once(dirname( __FILE__ ) . '/wp/wp-blog-header.php');

Becomes...

    <?php 
    include_once(__DIR__ . '/path/to/vendor/autoload.php');

    use Mindgruve\ReverseProxy\Configuration;
    use Symfony\Component\HttpKernel\HttpCache\Store;
    use Mindgruve\ReverseProxy\CachedReverseProxy;
    use Mindgruve\ReverseProxy\Adapters\WordPressAdapter;

    $store = new Store(dirname(__FILE__) . '/path/to/wp-content/cache');
    $proxyConfig = new Configuration(dirname( __FILE__ ) . '/path/to/wp-blog-header.php',$store);
    $reverseProxy = new CachedReverseProxy(new WordPressAdapter(), $proxyConfig);
    $reverseProxy->run();

Initial Request:

    Request -> Is Caching Enabled 
            -> MG-Reverse-Proxy 
            -> bootstraps WordPress 
            -> WordPress generates webpage (multiple hits to database)
            -> Cache Headers Set 
            -> Stores Result in local cache 
            -> Returns response to user

Subsequent Requests:

    Request -> Is Caching Enabled 
            -> MG-Reverse-Proxy 
            -> Generated webpage pulled from cache
            -> Returns response to user
            
    Note: With a cached response, your application is not bootstrapped at all.  This can dramatically reduce response times.
      

## Stores
Symfony HTTPCache has the concept of a cache store.  By default, this is a local directory on the file system.
If you want to use a different caching strategy (memcache, redis...), you can create your own implementation of the StoreInterface.

## Configuration
MG-Reverse-Proxy uses a configuration object to manage the configuration.  A description of these configuration options....

**$bootstrapFilePath** The path to the file that bootstraps your application.  For example, the wp-blog-header.php file in wordpress.   
**$store** The cache store that you want the reverse proxy to use.   
**$maxAge** The default max-age for your responses.  You can instruct MG-Reverse-Proxy to vary this per request by adjusting your adapter.   
**$defaultResponseType** Either 'public' or 'private'.  If you choose 'private', then by default none of your reponses will be cached, and the reverse is true for 'public'.   
**$surrogate** - See the documentation of the Symfony HTTPCache.  Useful if you are using Varnish.   
**$httpCacheOptions** - These are the options passed to the Symfony HTTPCache class.    
**$enableShutdownFunction** - Sometimes your application will return a response, then exit().  To enable cacheability of these responses, MG-Reverse-Proxy will register a shutdown function to obtain the output buffer and headers.  If you do not want these responses to be cached, set this parameter to false.

## Adapters





