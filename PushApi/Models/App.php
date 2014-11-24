<?php

namespace PushApi\Models;

use \Illuminate\Database\Eloquent\Model as Eloquent;

class App extends Eloquent
{
    public $timestamps = false;
    protected $fillable = array('name', 'secret');
    protected $hidden = array('created');

    // Only can be created MAX num of apps
    const MAX_APPS_ENABLED = 1;

    public static function boot()
    {
        parent::boot();

        static::creating(function($app) {
        	$app->secret = generateSecret();
        });
    }

    private static function generateSecret() {
        return substr(md5("fakers__" . rand()), 0, 16);
    }
}