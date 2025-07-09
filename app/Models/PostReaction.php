<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostReaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'user_id',
        'reaction_type',
    ];

    /**
     * Message that was reacted to
     */
    public function message()
    {
        return $this->belongsTo(GroupMessage::class, 'message_id');
    }

    /**
     * User who made the reaction
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}