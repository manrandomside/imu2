<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupMessage extends Model
{
    use HasFactory;

    // Mendefinisikan tabel yang terkait dengan model ini
    protected $table = 'group_messages';

    // Mendefinisikan kolom yang dapat diisi secara massal
    protected $fillable = [
        'group_id',
        'sender_id',
        'message_content',
    ];

    /**
     * Mendefinisikan relasi dengan grup chat.
     */
    public function group()
    {
        return $this->belongsTo(ChatGroup::class, 'group_id');
    }

    /**
     * Mendefinisikan relasi dengan pengirim pesan.
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
