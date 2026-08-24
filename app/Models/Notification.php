<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Note: Laravel has a built-in Illuminate\Notifications\DatabaseNotification
 * model, but creating your own App\Models\Notification model is
 * perfectly fine and common if you want to add custom logic or
 * relationships directly to the notification records.
 */
class Notification extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'notifications';

    /**
     * The "type" of the primary key ID.
     * We set this to 'string' because we are using a UUID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that should be cast.
     * This automatically converts the 'data' column from JSON
     * to a PHP array and back.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    /**
     * Get the notifiable entity that the notification belongs to.
     * This defines the other side of the 'morphs' relationship
     * from the migration.
     */
    public function notifiable()
    {
        return $this->morphTo();
    }
}