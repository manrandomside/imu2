<?php

namespace App\Models;

    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;

    class UserMatch extends Model // Nama kelas sekarang UserMatch
    {
        use HasFactory;

        // Mendefinisikan tabel yang terkait dengan model ini
        // Kita tetap menunjuk ke tabel 'matches' yang sudah ada
        protected $table = 'matches';

        // Mendefinisikan kolom yang dapat diisi secara massal
        protected $fillable = [
            'user1_id',
            'user2_id',
        ];

        /**
         * Mendefinisikan relasi dengan User pertama dalam match.
         */
        public function user1()
        {
            return $this->belongsTo(User::class, 'user1_id');
        }

        /**
         * Mendefinisikan relasi dengan User kedua dalam match.
         */
        public function user2()
        {
            return $this->belongsTo(User::class, 'user2_id');
        }
    }
    