# mg-reverse-proxy

MG-Reverse-Proxy provides a bridge between your web application and the Symfony HTTP Cache.  Using MG-Reverse-Proxy you can cache applications like Wordpress similar to how you would cache a Symfony application.  It works by transforming the output buffer and headers created by your application into a Symfony Reponse object that the can be intellegently cached.  It works great for legacy projects and applications not built on Symfony full stack framework (like WordPress, Joomla, even Magento sites).

Similar to Symfony HTTP Cache, MG-Reverse-Proxy will cache responses that are set to PUBLIC, have a positive MAX-AGE, and the request is a 'safe' HTTP methods (ie GET or HEAD).  

Since MG-Reverse-Proxy is a simple wrapper around the Symfony HTTPCache, the documentation provided by Symfony will be helpful in understanding the internals of  MG-Reverse-Proxy.  For more information on the Symfony HTTPCache see: http://symfony.com/doc/current/book/http_cache.html

## Use Cases
Content heavy sites are a good candidate for MG-Reverse-Proxy.  You can customize the cacheability of the responses by setting cache headers in your application or configuring your cache adapter (described below).    

You can use MG-Reverse-Proxy in situations where installing a dedicated caching solution like Varnish might not be posible.  In contrast to Varnish, MG-Reverse-Proxy is written in PHP which allows you to add a caching layer to web applications easier.

## Benefits of caching...

Initial request (or stale cached requests):

    Request -> Is caching enabled?  
            -> MG-Reverse-Proxy bootstraps your application and creates a Symfony HTTPCache object and HTTPKernel.
            -> Your application generates webpage (multiple hits to database)
            -> MG-Reverse-Proxy captures the outputbuffer and transforms it into a Symfony Response object
            -> The cache adapter sets headers if applicable
            -> The Response object is returned to the Symfony HTTPCache
            -> The Symfony HTTPCache stores result in local cache 
            -> Finally, the response is returned to user

Subsequent Requests:

    Request -> Is caching Enabled?   
            -> Webpage respone pulled from cache
            -> Returns cached response to user
            
    Note: With a cached response, your application is not bootstrapped at all!

## Cache Adapters
Cache Adapters are the entry point for you to add your caching business logic.  A Symfony Response object is created and provided to your Adapter so that you can set the Cache headers.  MG-Reverse-Proxy will then use that Response to intellegently cache.  Included in the source code are two sample adapters - a generic adapter, and one for WordPress.   You should write own adapter for your application either extends the **GenericCacheAdapter** or implements the **CacheAdapterInterface**.  

## Stores
Symfony HTTPCache has the concept of a cache store.  By default, this is a local directory on the file system.  If you want to use a different caching strategy (memcache, redis...), you can use your own implementation of the **Symfony\Component\HttpKernel\HttpCache\StoreInterface**  and pass it as an constructor argument.

##Example Usage - Wordpress index.php
The index.php file for WordPress looks like...

    <?php
    include_once(dirname( __FILE__ ) . '/wp-blog-header.php');

To enable caching for your WordPress application, you instantiate a cache store (which is a local directory for this example).  We instantiate a new CachedReverseProxy object, using the WordPress adapter, pass along the path to the bootstrap file, and the default MaxAge (which is 600 seconds in this example).

The new (cached) index page source code would look similar to..

    <?php 
    include_once(__DIR__ . '/../../application/vendor/autoload.php');

    use Symfony\Component\HttpKernel\HttpCache\Store;
    use Mindgruve\ReverseProxy\CachedReverseProxy;
    use Mindgruve\ReverseProxy\Adapters\WordPressAdapter;

    // Instantiate cache store
    $store = new Store(dirname(__FILE__) . '/wp-content/cache');
    
    // Instantiate Reverse Proxy
    $reverseProxy = new CachedReverseProxy(
        new WordPressAdapter(
            function(){
                include_once(dirname( __FILE__ ) . '/wp-blog-header.php')
            }, 
            600, $store
        )
    );
            
    // Run Cached Application        
    $reverseProxy->run();
      

## Modifying the WordPress Adapter
There are a number of entry points that you can use to modify the behavior of the WordPress adapter.  First extend **WordPressAdapter** and overwrite the relevant method.  

Here is a description of the important methods of your custom adapter:

**isCachingEnabled** (bool) - If true, caching is enabled.  If false, caching will be turned off, and all responses will hit your application.

The default of the generic adapter is true.  The default behavior of the WordPress adapter is to turn off caching anytime the user is logged in.

**setCacheHeaders** (Response) - This method is called to allow you to set custom cache headers for each request/response.  Using this method you can mark certain methods as public, modify the max-age, and set any cache header that you want.

The Generic adapter will not set any Headers, and will respect the cache headers set by your application.  The WordPress adapter will set this to private if the user is logged in, and public otherwise. This means all responses for anonymous users will be cached.

**bootstrap** (Void) - This method will call the Bootstrap function that you provide as a constructor argument.  For example WordPress Adapter will load the file to bootstrap Wordpress.

**getRawContent** (string) - This method returns back a string.  This is often the output buffer.  MG-Reverse-Proxy converts the return of getRawContent to a Symfony Response object.   

**getStore** (StoreInterface) - Returns the store that the HTTPCache can use to cache responses.  

**getSurrogate** (SurrogateInterface) - Useful for integration with Varnish.  See the Symfony documentation for more information.    

## Example - Updating WordPress Adapter - Marking Contact Page as Private
In this example, we assume you have a WordPress site with a contact page, and you are using MG-Reverse-Proxy to speed up the responsiveness of your site.  It all works well, except you have a contact form on the url **/contact**.   Caching this page is problematic because the CSRF token, and validation errors will get cached.  Remember, the sample WordPress adapter provided will cache any page viewed by a anonymous user.

You can update the cache adapter to implement your own business rules, by extending the WordPress adapter and overriding the setCacheHeaders() method.  The setCacheHeaders() method is called **after** your application generates a response, but **before** the resonse is sent to the HTTPCache.  This allows you to modify the caching logic without changing your application.

To exclude the url **/contact** from being cached your custom adapter could be...

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

Notice that we return a Symfony Response object with the cache header set to private (ie... do not cache) if the url matches  /contact.  

To use this custom adapter, update your index.php file to use this in the construction of your reverse proxy..

    // Instantiate Reverse Proxy
    $reverseProxy = new CachedReverseProxy(
        new CustomWordpressAdapter(
            function(){
                include_once(dirname( __FILE__ ) . '/wp-blog-header.php')
            }, 
            600, $store
        )
    );

**Note**:  The WordPress adapter is meant to be a quick way for developers to cache their WordPress application.  WordPress doesn't provide easy methods to set cache headers only certain pages, so the setCacheHeaders() method allows for you to inspect request/responses and modify the Response and set the headers appropriately.  

However, as you can imagine, the setCacheHeaders function can become very large if your busines rules to determine which pages should be cached are complex.  At some point, it probably makes sense to switch to the generic adpater and to refactor this caching logic out of the setCacheHeaders method and into your WordPress application.  

## Bugs / Patches / Features
Contributions are welcomed.  Feel free to fork and submit a pull request.  

## License

MIT License
Copyright (c) 2016 Mindgruve

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.