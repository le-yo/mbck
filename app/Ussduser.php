<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Ussduser extends Model {

    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['difficulty_level','name','phone_no','email','session','progress','confirm_from','menu_item_id'];




}
