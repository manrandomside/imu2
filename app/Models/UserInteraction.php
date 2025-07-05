<?php

namespace App\Models;

    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;

    class UserInteraction extends Model
    {
        use HasFactory;

        // Mendefinisikan tabel yang terkait dengan model ini
        protected $table = 'user_interactions';

        // Mendefinisikan kolom yang dapat diisi secara massal
        protected $fillable = [
            'user_id',
            'target_user_id',
            'action_type',
        ];

        /**
         * Mendefinisikan relasi dengan model User (user yang melakukan interaksi).
         */
        public function user()
        {
            return $this->belongsTo(User::class, 'user_id');
        }

        /**
         * Mendefinisikan relasi dengan model User (user yang menjadi target interaksi).
         */
        public function targetUser()
        {
            return $this->belongsTo(User::class, 'target_user_id');
        }
    }
    