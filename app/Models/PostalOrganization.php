<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostalOrganization extends Model
{
    protected $connection = 'postal_main';

    protected $table = 'organizations';

    public $timestamps = false;
}
