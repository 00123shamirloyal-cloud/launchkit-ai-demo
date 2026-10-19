<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Metadata/audit record for an AI operation. Store only redacted metadata here;
 * do not persist API keys, raw prompts, unredacted PII, or provider secrets.
 */
class AiLog extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'provider',
        'model',
        'action',
        'status',
        'input_tokens',
        'output_tokens',
        'cost',
        'request_id',
        'latency_ms',
        'metadata',
        'error_code',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'cost' => 'decimal:6',
            'latency_ms' => 'integer',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
