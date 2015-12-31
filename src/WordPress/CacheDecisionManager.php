<?php

namespace Mindgruve\ReverseProxy\WordPress;

use Mindgruve\ReverseProxy\WordPress\CacheVoters\VoterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheDecisionManager
{
    const VOTE_PRIVATE = 'private';
    const VOTE_PUBLIC = 'public';
    const VOTE_ABSTAIN = 'abstain';

    const DECISION_AFFIRMATIVE = 'affirmative';
    const DECISION_CONSENSUS = 'consensus';
    const DECISION_UNANIMOUS = 'unanimous';

    /**
     * @var int
     */
    protected $maxAge;

    /**
     * @var array
     */
    protected $voters;

    /**
     * @var int
     */
    protected $strategy;

    /**
     * @param string $strategy
     * @param array $voters
     * @throws \Exception
     */
    public function __construct($defaultMaxAge, $strategy = self::DECISION_UNANIMOUS, array $voters = array())
    {
        $this->maxAge = $defaultMaxAge;
        $this->voters = $voters;
        $this->strategy = $strategy;

        if ($this->strategy != self::DECISION_AFFIRMATIVE
            && $this->strategy != self::DECISION_CONSENSUS
            && $this->strategy != self::DECISION_UNANIMOUS
        ) {
            throw new \Exception('The strategy provide is not valid');
        }
    }

    /**
     * ADD A CACHE VOTER
     *
     * @param $key
     * @param VoterInterface $voter
     */
    public function addVoter($key, VoterInterface $voter)
    {
        $this->voters[$key] = $voter;
    }

    /**
     * REMOVE A CACHE VOTER
     *
     * @param $key
     */
    public function removeVoter($key)
    {
        if (isset($this->voters[$key])) {
            unset($this->voters[$key]);
        }
    }

    /**
     * APPLY CACHE RULES AND DETERMINE MAX-AGE
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function applyCacheRules( Request $request, Response $response)
    {
        $response->setMaxAge($this->maxAge);
        $response->setPublic();

        switch ($this->strategy) {
            case self::DECISION_AFFIRMATIVE:
                $response = $this->decideAffirmative($request, $response);
                break;
            case self::DECISION_CONSENSUS:
                $response = $this->decideConsensus($request, $response);
                break;
            case self::DECISION_UNANIMOUS:
                $response = $this->decideUnanimous($request, $response);
        }

        foreach ($this->voters as $voter) {
            /**
             * @var VoterInterface $voter
             */
            if ($voter->supports($request, $response)) {
                $maxAge = $voter->voteMaxAge($request);
                if ($maxAge == self::VOTE_ABSTAIN) {
                    continue;
                }
                if ($maxAge < $maxAge) {
                    $this->maxAge = $maxAge;
                }
            }
        }
        $response->setMaxAge($this->maxAge);

        return $response;
    }

    /**
     * THE FIRST VOTER TO VOTE PUBLIC WILL MAKE THE RESPONSE PUBLIC
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function decideAffirmative(Request $request, Response $response)
    {
        foreach ($this->voters as $voter) {
            /**
             * @var VoterInterface $voter
             */
            if ($voter->supports($request)) {

                $vote = $voter->voteCacheability($request);
                if ($vote === self::VOTE_PRIVATE) {
                    $response->setPrivate();
                    return $response;
                }
                if ($vote === self::VOTE_PUBLIC) {
                    $response->setPublic();
                    return $response;
                }
            }
        }
        return $response;
    }

    /**
     * THERE MUST BE STRICTLY GREATER NUMBER OF PUBLIC VOTES
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function decideConsensus(Request $request, Response $response)
    {
        $publicVotes = 0;
        $privateVotes = 0;
        foreach ($this->voters as $voter) {
            /**
             * @var VoterInterface $voter
             */
            if ($voter->supports($request)) {

                $vote = $voter->voteCacheability($request);

                if ($vote === self::VOTE_PUBLIC) {
                    $publicVotes = $publicVotes + 1;
                } elseif ($vote == self::VOTE_PRIVATE) {
                    $privateVotes = $privateVotes + 1;
                }
            }
        }

        if ($publicVotes > $privateVotes) {
            $response->setPublic();
        } elseif($publicVotes < $privateVotes){
            $response->setPrivate();
        }

        return $response;
    }

    /**
     * THERE MUST BE AT LEAST ONE PUBLIC VOTE AND NO PRIVATE VOTES
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function decideUnanimous(Request $request, Response $response)
    {
        $publicVotes = 0;
        $privateVotes = 0;

        foreach ($this->voters as $voter) {
            /**
             * @var VoterInterface $voter
             */
            if ($voter->supports($request)) {

                $vote = $voter->voteCacheability($request);

                if ($vote === self::VOTE_PUBLIC) {
                    $publicVotes = $publicVotes + 1;
                } elseif ($vote == self::VOTE_PRIVATE) {
                    $privateVotes = $privateVotes + 1;
                }
            }
        }

        if ($publicVotes && $privateVotes == 0) {
            $response->setPublic();
        } elseif($privateVotes && $publicVotes == 0){
            $response->setPrivate();
        }

        return $response;
    }
}