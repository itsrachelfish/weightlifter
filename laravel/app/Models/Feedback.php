<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $table = 'feedback';

    protected $fillable = ['message', 'type', 'options', 'response'];

    // Feedback belong to an application
    public function application()
    {
        return $this->belongsTo('App\Models\Application');
    }

    // Feedback is requested by a specific user (judges)
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    // Feedback can be associated to either an answer or a document
    public function regarding()
    {
        return $this->morphTo();
    }
}
