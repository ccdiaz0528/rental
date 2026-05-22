<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;

trait HasUserContext
{
    public ?int $selectedUserId = null;

    public function initializeHasUserContext(): void
    {
        if (! auth()->check() || ! auth()->user()->hasRole('admin')) {
            return;
        }

        $this->selectedUserId = (int) Cache::get($this->userContextCacheKey(), 0);
    }

    #[On('userContextChanged')]
    public function onUserContextChanged(int $userId): void
    {
        if (! auth()->check() || ! auth()->user()->hasRole('admin')) {
            return;
        }

        $this->selectedUserId = $userId;
    }

    public function updatedSelectedUserId(): void
    {
        if (! auth()->check() || ! auth()->user()->hasRole('admin')) {
            return;
        }

        Cache::forever($this->userContextCacheKey(), (int) $this->selectedUserId);
        $this->dispatch('userContextChanged', userId: (int) $this->selectedUserId);
    }

    public function refreshIfContextChanged(): void
    {
        if (! auth()->check() || ! auth()->user()->hasRole('admin')) {
            return;
        }

        $cached = (int) Cache::get($this->userContextCacheKey(), 0);

        if ($cached !== $this->selectedUserId) {
            $this->selectedUserId = $cached;
        }
    }

    public function applyUserScope(Builder $query, string $column = 'user_id'): Builder
    {
        if (! auth()->check()) {
            return $query;
        }

        if (auth()->user()->hasRole('admin') && $this->selectedUserId > 0) {
            $query->where($column, $this->selectedUserId);
        } elseif (! auth()->user()->hasRole('admin')) {
            $query->where($column, auth()->id());
        }

        return $query;
    }

    public function getUsersForSelector(): array
    {
        if (! auth()->check() || ! auth()->user()->hasRole('admin')) {
            return [];
        }

        return User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'user'))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public function getSelectedUserName(): ?string
    {
        if ($this->selectedUserId <= 0) {
            return null;
        }

        $cacheKey = $this->userContextCacheKey().'_name';

        return Cache::remember($cacheKey, 3600, fn () => User::find($this->selectedUserId)?->name);
    }

    private function userContextCacheKey(): string
    {
        return 'admin_user_'.auth()->id();
    }
}
