<?php

namespace Mindgruve\ReverseProxy\WordPress\CacheVoters;

use Symfony\Component\HttpFoundation\Request;

interface VoterInterface
{
    public function supports(Request $request);

    public function voteCacheability(Request $request);

    public function voteMaxAge(Request $request);
}