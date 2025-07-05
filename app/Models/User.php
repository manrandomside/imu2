<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail; // Ditambahkan: Jika Anda berencana menggunakan fitur verifikasi email Laravel
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // Ditambahkan: Jika Anda berencana menggunakan Laravel Sanctum untuk API Token

class User extends Authenticatable // implements MustVerifyEmail (buka komentar ini jika Anda ingin fitur verifikasi email)
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens; // Ditambahkan HasApiTokens jika pakai Sanctum

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'full_name', // Menggantikan 'name'
        'username',
        'email',
        'password',
        'prodi',
        'fakultas',
        'gender',
        'description',
        'interests',
        'role',
        'profile_picture',
        'verification_doc_path',
        'is_verified',
        'match_categories', // DITAMBAHKAN
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'interests' => 'array', // Penting: Ini untuk mengkonversi JSON ke array PHP dan sebaliknya
            'is_verified' => 'boolean', // Penting: Untuk mengkonversi 0/1 menjadi true/false
            'match_categories' => 'array', // DITAMBAHKAN
        ];
    }
}
