<?php

namespace App\Models;

 
use App\Models\Booking;
use App\Models\ContactMessage;
use App\Models\Favorite;
use App\Models\PortfolioProject;
use App\Models\RecurringAvailability;
use App\Models\RefundRequest;
use App\Models\Review;
use App\Models\VendorProfile;
use App\Models\VendorService;
use App\Models\WalletTransaction;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
     
    use HasFactory, Notifiable;

    




    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'location',
        'gender',
        'profile_image',
        'role',
        'status',
    ];

    




    protected $hidden = [
        'password',
        'remember_token',
    ];

    




    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function vendorProfile(): HasOne
    {
        return $this->hasOne(VendorProfile::class, 'user_id');
    }

    public function vendorServices(): HasMany
    {
        return $this->hasMany(VendorService::class, 'vendor_id');
    }

    public function recurringAvailabilities(): HasMany
    {
        return $this->hasMany(RecurringAvailability::class, 'vendor_id');
    }

    public function portfolioProjects(): HasMany
    {
        return $this->hasMany(PortfolioProject::class, 'vendor_id');
    }

    public function bookingsAsCustomer(): HasMany
    {
        return $this->hasMany(Booking::class, 'customer_id');
    }

    public function cancelledBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'cancelled_by');
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'vendor_id');
    }

    public function refundRequests(): HasMany
    {
        return $this->hasMany(RefundRequest::class, 'customer_id');
    }

    public function reviewsGiven(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    public function reviewsReceived(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewed_user_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class, 'customer_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    public function sentContactMessages(): HasMany
    {
        return $this->hasMany(ContactMessage::class, 'sender_id');
    }
}
