<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int,string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int,string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        // Laravel 10+ can hash passwords automatically if you want,
        // but we'll hash explicitly in controllers for clarity.
        // 'password' => 'hashed',
    ];

    /**
     * Override the default reset notification so the reset link points to your frontend.
     *
     * @param string $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        // Frontend URL - set in .env as FRONTEND_URL
        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173'));

        // Build URL expected by your frontend (you can change path as needed)
        $resetUrl = $frontendUrl . '/reset-password?token=' . urlencode($token) . '&email=' . urlencode($this->email);

        // Configure the default ResetPassword notification to use our URL
        \Illuminate\Auth\Notifications\ResetPassword::createUrlUsing(function ($notifiable, $token) use ($resetUrl) {
            // We build the url ourselves to ensure it includes token/email and maps to frontend
            return $resetUrl;
        });

        // Send the notification (this will use the ResetPassword notification with our custom URL)
        $this->notify(new \Illuminate\Auth\Notifications\ResetPassword($token));
    }
}
