<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ussdLog extends Model
{
    //
    protected $fillable = ['phone', 'text', 'session_id', 'service_code'];

}
