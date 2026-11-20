<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * E-mail всех модераторов (+ дополнительный адрес из MAIL_MODERATOR_ADDRESS).
     *
     * @return Collection<int, string>
     */
    public static function moderatorEmails(): Collection
    {
        $emails = static::query()
            ->whereHas('role', fn ($query) => $query->where('slug', Role::MODERATOR))
            ->pluck('email');

        if ($extra = config('mail.moderator_address')) {
            $emails->push($extra);
        }

        return $emails->filter()->unique()->values();
    }

    public function isModerator(): bool
    {
        return $this->role?->slug === Role::MODERATOR;
    }

    public function isReader(): bool
    {
        return $this->role?->slug === Role::READER;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
