<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use ApiPlatform\Metadata\ApiResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use ApiPlatform\Metadata\Get;

#[ApiResource(
    operations: [
        new Get(uriTemplate: '/customers/{id}'),
    ]
)]

class Customer extends Model
{
    use HasFactory;
   
    protected $fillable = [
        'name', 'last_name', 'email', 'phone', 'address', 
        'city', 'state', 'postal_code', 'country',
        'email_verified_at'
    ];

    protected $hidden = ['limited_token'];
    
    // ✅ MÉTHODES VALIDATION EMAIL
    public function generateLimitedToken(): string 
    {
        $token = Str::random(64);
        $this->update([
            'limited_token' => $token,
            'token_expires_at' => Carbon::now()->addHours(48)
        ]);
        return $token;
    }
    
    public function validateToken(string $token): bool 
    {
        if ($this->limited_token !== $token) return false;
        if (Carbon::now()->isAfter($this->token_expires_at)) return false;
        
        $this->update([
            'email_verified_at' => Carbon::now(),
            'limited_token' => null,  // Token à usage unique
            'token_expires_at' => null
        ]);
        
        return true;
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', '%' . $search . '%')
            ->orWhere('last_name', 'like', '%'. $search . '%')
            ->orWhere('email', 'like', '%' . $search . '%')
            ->orWhere('phone', 'like', '%' . $search . '%')
            ->orWhere('address', 'like', '%' . $search . '%')
            ->orWhere('city', 'like', '%' . $search . '%')
            ->orWhere('state', 'like', '%' . $search . '%')
            ->orWhere('postal_code', 'like', '%' . $search . '%')
            ->orWhere('country', 'like', '%' . $search . '%');
    }

    public static function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email',
            'postal_code' => 'sometimes|max:20'
        ];
    }

    public static function messages()
    {
        return [
            'name.required' => 'A name is required',
            'last_name' => 'Last name is required',
            'email.required' => 'An email is required',
            'email.email' => 'The email must be a valid email address',
            'email.unique' => 'The email has already been taken',
            'postal_code.max' => 'The postal code may not be greater than 20 characters'
        ];
    }

}
