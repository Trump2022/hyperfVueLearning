<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property string $email 
 * @property string $token 
 * @property \Carbon\Carbon $created_at 
 */
class PasswordResetToken extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'password_reset_tokens';

    /**
     * The attributes that are mass assignable.
     * @var list<string>
     */
    protected array $fillable = ['email', 'token', 'created_at'];

    /**
     * The attributes that should be cast to native types.
     * @var array<string, mixed>
     */
    protected array $casts = ['created_at' => 'datetime'];

    protected string $primaryKey = 'email';

    protected string $keyType = 'string';

    public bool $incrementing = false;

    public bool $timestamps = false;

}
