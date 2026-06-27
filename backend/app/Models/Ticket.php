<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory, BelongsToTenant;

    public const STATUSES = ['open', 'pending', 'resolved', 'closed'];
    public const PRIORITIES = ['low', 'medium', 'high', 'urgent'];

    protected $fillable = [
        'organization_id',
        'requester_id',
        'assignee_id',
        'subject',
        'description',
        'status',
        'priority',
        'tags',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
