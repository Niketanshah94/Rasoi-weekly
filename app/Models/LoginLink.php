<?php

namespace App\Models;

use App\Enums\LoginIntent;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

/**
 * A one-time login link sent by email: signed, valid for 15 minutes, used once (spec §1).
 *
 * @property string $id
 * @property string $email
 * @property LoginIntent $intent
 * @property int|null $country_id
 * @property Carbon $expires_at
 * @property Carbon|null $used_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Country|null $country
 */
#[Fillable(['email', 'intent', 'country_id', 'expires_at'])]
class LoginLink extends Model
{
    use HasUuids;

    public const VALID_MINUTES = 15;

    public static function issue(string $email, LoginIntent $intent, ?Country $country = null): self
    {
        return self::create([
            'email' => $email,
            'intent' => $intent,
            'country_id' => $country?->id,
            'expires_at' => now()->addMinutes(self::VALID_MINUTES),
        ]);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'intent' => LoginIntent::class,
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * The signed address to put in the email.
     */
    public function url(): string
    {
        return URL::temporarySignedRoute('login-link.show', $this->expires_at, ['loginLink' => $this->id]);
    }

    public function isUsable(): bool
    {
        return $this->used_at === null && $this->expires_at->isFuture();
    }

    /**
     * Marks the link used. Returns false if another request got there first.
     */
    public function claim(): bool
    {
        return self::whereKey($this->id)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->update(['used_at' => now()]) === 1;
    }
}
