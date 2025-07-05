<?php

namespace App\Models;

    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;

    class ChatGroup extends Model
    {
        use HasFactory;

        // Mendefinisikan tabel yang terkait dengan model ini
        protected $table = 'chat_groups';

        // Mendefinisikan kolom yang dapat diisi secara massal
        protected $fillable = [
            'name',
            'description',
            'creator_id',
            'is_approved',
        ];

        /**
         * Mendefinisikan relasi dengan user pembuat grup.
         */
        public function creator()
        {
            return $this->belongsTo(User::class, 'creator_id');
        }

        /**
         * Mendefinisikan relasi dengan anggota grup.
         */
        public function members()
        {
            return $this->hasMany(ChatGroupMember::class, 'group_id');
        }

        /**
         * Mendefinisikan relasi dengan pesan-pesan dalam grup.
         */
        public function messages()
        {
            return $this->hasMany(GroupMessage::class, 'group_id');
        }
    }
    