<?php

namespace App\Models;

use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $number
 * @property string $prompt
 * @property string|null $explanation
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['number', 'prompt', 'explanation'])]
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    /**
     * @return HasMany<QuestionOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('letter');
    }
}
