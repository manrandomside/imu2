<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    // Mendefinisikan tabel yang terkait dengan model ini
    protected $table = 'messages';

    // Mendefinisikan kolom yang dapat diisi secara massal
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'match_id',
        'message_content',
        'read_at',
    ];

    // Mendefinisikan kolom yang harus dianggap sebagai tipe data tanggal
    protected $casts = [
        'read_at' => 'datetime',
    ];

    /**
     * Mendefinisikan relasi dengan pengirim pesan (User).
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Mendefinisikan relasi dengan penerima pesan (User).
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Mendefinisikan relasi dengan match yang terkait (UserMatch).
     */
    public function match()
    {
        return $this->belongsTo(UserMatch::class, 'match_id');
    }
}
