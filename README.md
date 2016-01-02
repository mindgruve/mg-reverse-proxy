# mg-reverse-proxy

MG-Reverse-Proxy works by transforming the output buffer and headers created by your application into a Symfony Reponse object that it can use to intellegently cache using the Symfony HTTP Cache.  

## HTTP Caching Overview
In general, MG-Reverse-Proxy will cache responses that are set to PUBLIC and have a non-zero MAX-AGE, and will only cache 'safe' HTTP methods.  Since MG-Reverse-Proxy leverages the Symfony HTTPCache, there are many methods available to customize your response headers.

For more information on the Symfony HTTPCache see: http://symfony.com/doc/current/book/http_cache.html

## Use Cases
Content heavy sites are a good candidate for MG-Reverse-Proxy.  You can customize the cacheability
of the responses by setting cache headers in your application or configuring your cache adapter (described below).

## Cache Adapters
Configuration of MG-Reverse-Proxy is handled through cache adapters.  Included in the source code is an adapter for WordPress, but you can write your own by implementing the CacheAdapterInterface.

## Stores
Symfony HTTPCache has the concept of a cache store.  By default, this is a local directory on the file system.
If you want to use a different caching strategy (memcache, redis...), you can create your own implementation of the StoreInterface.

##Example Usage - Wordpress index.php
In this example, we replace the source code of the index.php file for Wordpress, and instead wrap the WordPress application using the ReverseProxy.

    <?php
    include_once(dirname( __FILE__ ) . '/wp/wp-blog-header.php');

Becomes...

    <?php 
    include_once(__DIR__ . '/../../application/vendor/autoload.php');

    use Symfony\Component\HttpKernel\HttpCache\Store;
    use Mindgruve\ReverseProxy\CachedReverseProxy;
    use Mindgruve\ReverseProxy\Adapters\WordPressAdapter;

    $store = new Store(dirname(__FILE__) . '/wp/wp-content/cache');
    $reverseProxy = new CachedReverseProxy(new WordPressAdapter(dirname( __FILE__ ) . '/wp/wp-blog-header.php', 600, $store));
    $reverseProxy->run();

If we take a look at the program flow...

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
            -> Returns cached response to user
            
    Note: With a cached response, your application is not bootstrapped at all.
      
## The WordPress Adapter
Included with is an adapter for WordPress.  A description of these constructor arguments....

**$bootstrapFile** The path to the file that bootstraps your application.  For example, the wp-blog-header.php file in wordpress.   
**$store** The cache store that you want the reverse proxy to use.   
**$maxAge** The default max-age for your responses.  You can instruct MG-Reverse-Proxy to vary this per request by adjusting your adapter.   
**$surrogate** - See the documentation of the Symfony HTTPCache.  Useful if you are using Varnish.   
**$httpCacheOptions** - These are the options passed to the Symfony HTTPCache class.  See the Symfony documetation at http://symfony.com/doc/current/book/http_cache.html#symfony-reverse-proxy   

## Modifying the WordPress Adapter
There are a number of entry points that you can use to modify the behavior of MG-Reverse-Proxy.  To use a custom wordpress adapter, extend the included class and overwrite the relevant method.  Or you can write your own adapter by implementing the ReverseCacheInterface.

**isCachingEnabled** (bool) - If false, caching will be turned off, and all responses will hit the WordPress application.  The default behavior of the WordPress adapter is to turn off caching anytime the user is logged in.  This allows admin users to see the non-cached version of the website.   
**isShutdownFunctionEnabled** (bool) - If true, MG-Reverse-Proxy will register a shutdown function to capture output sent to the user after an exit() call.  This is useful for API-Like calls that often exit after echoing their response.  The default is true.     
**setCacheHeaders** (Response) - This method is called to allow you to set custom cache headers.  Using this method you can mark certain methods as public.  By default, the WordPress adapter will set this to private if the user is logged in, and public otherwise.   
**bootstrap** (Void) - This method is called to bootstrap WordPress.    
**getRawContent** (string) - This method returns back a string of the output buffers, that MG-Reverse-Proxy converts to a Symfony Response object.    
**getDefaultResponseType** (string) 'private' | 'public' - This method is called to set the default response type.  The default is private, so that you must explicitly mark responses as public to make them cacheable.  If you want all responses to becacheable, and you explicitly mark the private responses, overwrite this method and return 'public'.    

## Example - Updating WordPress Adapter - Marking Contact Page as Private
Say you have a WordPress site with a contact page, and you are using MG-Reverse-Proxy to speed up the responsiveness of your site.  It all works well, except you have a contact form on the url **/contact**.   Caching this page is problematic because the CSRF token, and validation errors will get cached.  By default, the WordPress adapter will cache any page viewed by a anonymous user.  

To override this behavior, create a new class...

    use Mindgruve\ReverseProxy\Adapters\WordPressAdapter;
    
    class CustomWordpressAdapter extends WordPressAdapter {
    
        /**
         * @param Request $request
         * @param Response $response
         * @return Response
         */
        public function setCacheHeaders(Request $request, Response $response)
        {
            if(preg_match('/^contact', $request->getRequestUri()){
                $response->setPrivate;
                return response;
            }
        
            return parent::setCacheHeaders($request, $response)
        }
    }

Then, in your index.php file, use your custom adapter instead...

    $reverseProxy = new CachedReverseProxy(new CustomWordpressAdapter(dirname( __FILE__ ) . '/wp/wp-blog-header.php', 600, $store));

## MIT License
Copyright (c) 2016 Mindgruve

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
