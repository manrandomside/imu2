<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatGroupMember extends Model
{
    use HasFactory;

    // Mendefinisikan tabel yang terkait dengan model ini
    protected $table = 'chat_group_members';

    // Mendefinisikan kolom yang dapat diisi secara massal
    protected $fillable = [
        'group_id',
        'user_id',
        'role', // misal: 'member', 'admin_group'
        'joined_at',
    ];

    // Mendefinisikan kolom yang harus dianggap sebagai tipe data tanggal
    protected $casts = [
        'joined_at' => 'datetime',
    ];

    /**
     * Mendefinisikan relasi dengan grup chat.
     */
    public function group()
    {
        return $this->belongsTo(ChatGroup::class, 'group_id');
    }

    /**
     * Mendefinisikan relasi dengan anggota user.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
