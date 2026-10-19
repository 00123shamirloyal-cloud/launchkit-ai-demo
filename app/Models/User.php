<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    /**
     * Billing identifiers and subscription state are intentionally excluded from
     * $fillable. Assign them only in trusted server-side billing/webhook code.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
    ];

    /** @var list<string> */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'subscription_ends_at' => 'datetime',
            'enterprise_purchased_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Role, $this> */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /** Projects for which this user is the client. */
    /** @return HasMany<Project, $this> */
    public function clientProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'client_id');
    }

    /** Projects assigned to this team member. */
    /** @return HasMany<Project, $this> */
    public function assignedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'assigned_to_id');
    }

    /** Projects created by this user, usually an admin or team member. */
    /** @return HasMany<Project, $this> */
    public function createdProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'created_by_id');
    }

    /** Invoices issued to this client. */
    /** @return HasMany<Invoice, $this> */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'client_id');
    }

    /** Invoices issued by this user. */
    /** @return HasMany<Invoice, $this> */
    public function issuedInvoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'issued_by_id');
    }

    /** @return HasMany<AiLog, $this> */
    public function aiLogs(): HasMany
    {
        return $this->hasMany(AiLog::class);
    }

    /** Check the cached billing state; it is set by verified Stripe webhooks. */
    public function hasActiveLaunchKitPlan(): bool
    {
        return $this->subscription_status === 'active';
    }
}
