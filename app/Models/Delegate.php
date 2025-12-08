<?php declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\CurrentlyScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ScopedBy(CurrentlyScope::class)]
final class Delegate extends Model
{
    use SoftDeletes;

    public $fillable = [
        'started_at',
        'ended_at',
        'user_id',
        'organization_id',
        'college_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'ended_at' => 'date',
        ];
    }
}
