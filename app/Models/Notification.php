<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;
    
    protected $table = 'notifications';
    protected $primaryKey = 'id_notification';
    
    protected $fillable = [
        'id_user',
        'title',
        'message',
        'type',
        'status',
        'data'
    ];
    
    protected $casts = [
        'data' => 'array'
    ];
    
    // Relationship with User model
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
}
