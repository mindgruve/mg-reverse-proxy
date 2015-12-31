<?php

namespace Mindgruve\ReverseProxy\WordPress;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class Kernel extends HttpKernel implements HttpKernelInterface
{
    /**
     * @var int
     */
    protected $maxAge;

    /**
     * @var CacheDecisionManager
     */
    protected $cacheDecisionManager;

    /**
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $WordPress_ABSPATH
     * @param CacheDecisionManager $cacheDecisionManager
     * @param int $maxAge
     */
    public function __construct($WordPress_ABSPATH, EventDispatcher $eventDispatcher, CacheDecisionManager $cacheDecisionManager, $maxAge = 0)
    {
        $this->maxAge = $maxAge;
        $this->cacheDecisionManager = $cacheDecisionManager;
        $resolver = new ControllerResolver($WordPress_ABSPATH);
        parent::__construct($eventDispatcher, $resolver);
    }

    /**
     * HANDLE THE REQUEST, APPLY CACHE RULES, THEN RETURN RESPONSE
     *
     * @param Request $request
     * @param int $type
     * @param bool $catch
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        $response = parent::handle($request, $type, $catch);
        $this->cacheDecisionManager->applyCacheRules($this->maxAge, $request, $response);

        return $response;
    }
}