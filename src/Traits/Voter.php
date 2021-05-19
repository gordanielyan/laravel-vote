<?php

namespace Overtrue\LaravelVote\Traits;

use Illuminate\Database\Eloquent\Model;
use Overtrue\LaravelVote\Vote;

/**
 * @property \Illuminate\Database\Eloquent\Collection $votes
 */
trait Voter
{
    public function vote(Model $object, int $votes = 1): Vote
    {
        return $votes > 0 ? $this->upVote($object, $votes) : $this->downVote($object, $votes);
    }

    public function upVote(Model $object, int $votes = 1)
    {
        /* @var Votable|Model $object */
        if ($this->hasVoted($object)) {
            $this->cancelVote($object);
        }

        $vote = app(config('vote.vote_model'));
        $vote->{config('vote.user_foreign_key')} = $this->getKey();
        $vote->votes = abs($votes);
        $object->votes()->save($vote);

        return $vote;
    }

    public function downVote(Model $object, int $votes = 1)
    {
        /* @var Votable|Model $object */
        if ($this->hasVoted($object)) {
            $this->cancelVote($object);
        }

        $vote = app(config('vote.vote_model'));
        $vote->{config('vote.user_foreign_key')} = $this->getKey();
        $vote->votes = abs($votes) * -1;
        $object->votes()->save($vote);

        return $vote;
    }

    public function cancelVote(Model $object): bool
    {
        /* @var Votable|Model $object */
        $relation = $object->votes()
            ->where('votable_id', $object->getKey())
            ->where('votable_type', $object->getMorphClass())
            ->where(config('vote.user_foreign_key'), $this->getKey())
            ->first();

        if ($relation) {
            $relation->delete();
        }

        return true;
    }

    public function hasVoted(Model $object): bool
    {
        return ($this->relationLoaded('votes') ? $this->votes : $this->votes())
            ->where('votable_id', $object->getKey())
            ->where('votable_type', $object->getMorphClass())
            ->count() > 0;
    }

    public function votes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(config('vote.vote_model'), config('vote.user_foreign_key'), $this->getKeyName());
    }

    public function getVotedItems(string $model)
    {
        return app($model)->whereHas(
            'voters',
            function ($q) {
                return $q->where(config('vote.user_foreign_key'), $this->getKey());
            }
        );
    }
}
