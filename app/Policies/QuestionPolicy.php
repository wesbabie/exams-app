<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\User;

class QuestionPolicy
{
    private function is(User $user, string ...$roles): bool
    {
        return $user->is_active && in_array($user->role, $roles, true);
    }

    /** Authors can only act on their own questions; exam officers and super admins can act on any. */
    private function ownsOrManages(User $user, Question $question): bool
    {
        return $user->role !== 'question_author' || $question->created_by === $user->id;
    }

    public function viewAny(User $user): bool
    {
        return $this->is($user, 'super_admin', 'exam_officer', 'question_author', 'reviewer');
    }

    public function view(User $user, Question $question): bool
    {
        return $this->viewAny($user) && $this->ownsOrManages($user, $question);
    }

    public function create(User $user): bool
    {
        return $this->is($user, 'super_admin', 'exam_officer', 'question_author');
    }

    public function update(User $user, Question $question): bool
    {
        return $question->status === 'draft' && $this->create($user) && $this->ownsOrManages($user, $question);
    }

    public function delete(User $user, Question $question): bool
    {
        return $this->update($user, $question);
    }

    public function submit(User $user, Question $question): bool
    {
        return $this->update($user, $question);
    }

    /** The author can take a question back out of review. */
    public function withdraw(User $user, Question $question): bool
    {
        return $question->status === 'in_review' && $this->create($user) && $this->ownsOrManages($user, $question);
    }

    /** Approve or return. Never the person who wrote it. */
    public function review(User $user, Question $question): bool
    {
        return $question->status === 'in_review'
            && $this->is($user, 'super_admin', 'exam_officer', 'reviewer')
            && $question->created_by !== $user->id;
    }

    public function retire(User $user, Question $question): bool
    {
        return $question->status === 'approved' && $this->is($user, 'super_admin', 'exam_officer');
    }

    /** An approved question is never edited in place. Changes go into a new version. */
    public function newVersion(User $user, Question $question): bool
    {
        return $question->status === 'approved'
            && ! $question->nextVersion()->exists()
            && $this->create($user)
            && $this->ownsOrManages($user, $question);
    }
}
