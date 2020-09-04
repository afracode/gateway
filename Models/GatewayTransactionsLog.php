<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GatewayTransactionsLog extends Model
{
    public $timestamps = false;

    public function gateway_transaction()
    {
        return $this->belongsTo('App\Models\GatewayTransaction');
    }
}
