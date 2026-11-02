<?php

namespace App\Models;

use Database\Factories\ChallengeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $number
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['number', 'description'])]
class Challenge extends Model
{
    /** @use HasFactory<ChallengeFactory> */
    use HasFactory;
}
