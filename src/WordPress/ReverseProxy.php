<?php

namespace Mindgruve\ReverseProxy\WordPress;

use Mindgruve\ReverseProxy\WordPress\CacheVoters\VoterInterface;
use Symfony\Component\HttpKernel\HttpKernel as WordPressKernel;
use Symfony\Component\HttpKernel\HttpCache\Store;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Mindgruve\ReverseProxy\WordPress\CacheVoters\AdminVoter;

class ReverseProxy
{

    /**
     * THE PATH TO THE WORDPRESS INSTALLATION
     * @var string
     */
    protected $wordPress_ABSPATH;

    /**
     * A CACHE DIRECTORY FOR THE REVERSE PROXY
     * @var
     */
    protected $cacheDir;

    /**
     * THE DEFAULT MAX AGE
     * @var
     */
    protected $defaultMaxAge;

    /**
     * @var CacheDecisionManager
     */
    protected $cacheDecisionManager;

    /**
     * @param $wordPress_ABSPATH
     * @param $cacheDir
     * @param $defaultMaxAge
     */
    public function __construct($wordPress_ABSPATH, $cacheDir, $defaultMaxAge)
    {
        $this->wordPress_ABSPATH = $wordPress_ABSPATH;
        $this->cacheDir = $cacheDir;
        $this->defaultMaxAge = $defaultMaxAge;

        $this->cacheDecisionManager = new CacheDecisionManager();
        $this->cacheDecisionManager->addVoter('wordpress_admin', new AdminVoter());
    }

    /**
     * ADD A CACHE VOTER
     *
     * @param $key
     * @param VoterInterface $voter
     */
    public function addVoter($key, VoterInterface $voter)
    {
        $this->cacheDecisionManager->addVoter($key, $voter);
    }

    /**
     * REMOVE A CACHE VOTER
     *
     * @param $key
     */
    public function removeVoter($key)
    {
        $this->cacheDecisionManager->removeVoter($key);
    }

    /**
     * RUN THE REVERSE PROXY
     */
    public function run()
    {
        $kernel = new WordPressKernel(new EventDispatcher(), new WordPressBootstrap($this->wordPress_ABSPATH));
        $kernel = new HttpCache($kernel, new Store($this->cacheDir));

        $request = Request::createFromGlobals();
        $response = $kernel->handle($request);
        $this->cacheDecisionManager->applyCacheRules($this->defaultMaxAge, $request, $response);

        $response->send();
        $kernel->terminate($request, $response);
    }
}