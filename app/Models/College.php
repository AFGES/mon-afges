<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class College extends Model
{
    /** @use HasFactory<\Database\Factories\CollegeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'can_vote',
    ];

    protected function casts(): array
    {
        return [
            'can_vote' => 'boolean',
        ];
    }

    public function delegates(): HasMany
    {
        return $this->hasMany(Delegate::class);
    }
}
