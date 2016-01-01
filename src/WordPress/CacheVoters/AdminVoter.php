<?php

namespace Mindgruve\ReverseProxy\WordPress\CacheVoters;

use Mindgruve\ReverseProxy\WordPress\CacheDecisionManager;
use Symfony\Component\HttpFoundation\Request;

class AdminVoter implements VoterInterface
{
    /**
     * @param Request $request
     * @return bool
     */
    public function supports(Request $request)
    {
        if (function_exists('is_user_logged_in')) {
            return true;
        }
    }

    /**
     * DO NOT CACHE IF USER IS LOGGED IN
     *
     * @return int
     */
    public function voteCacheability(Request $request)
    {
        if (!is_user_logged_in()) {
            return CacheDecisionManager::VOTE_PUBLIC;
        }
    }

    /**
     * VOTE ABSTAIN (ie - use the default max-age)
     *
     * @param Request $request
     * @return int
     */
    public function voteMaxAge(Request $request)
    {
        return CacheDecisionManager::VOTE_ABSTAIN;
    }
}