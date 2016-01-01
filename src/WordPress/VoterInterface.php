<?php

namespace Mindgruve\ReverseProxy\WordPress\CacheVoters;

use Symfony\Component\HttpFoundation\Request;

interface VoterInterface
{
    /**
     * RETURN TRUE IF THE VOTER SUPPORTS THIS TYPE OF REQUEST
     *
     * @param Request $request
     * @return bool
     */
    public function supports(Request $request);

    /**
     * OPTIONS ARE:
     * CacheDecisionManager::VOTE_ABSTAIN
     * CacheDecisionManager::VOTE_PRIVATE
     * CacheDecisionManager::VOTE_PUBLIC
     *
     * @param Request $request
     * @return mixed
     */
    public function voteCacheability(Request $request);

    /**
     * OPTIONS ARE:
     * CacheDecisionManager::VOTE_ABSTAIN
     * INT value in seconds for the max-age for the reverse proxy cache.
     * The decision manager will use the smallest value returned by any voter.
     *
     * @param Request $request
     * @return mixed
     */
    public function voteMaxAge(Request $request);
}