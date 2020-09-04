<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GatewayTransaction extends Model
{

    protected $table = 'gateway_transactions';


    protected $casts = [
        'detail' => 'array',
    ];

    protected $fillable = ['detail','user_id',  'port', 'price', 'ref_id', 'tracking_code', 'card_number', 'status', 'ip', 'payment_date','invoice_id'];


    public function user()
    {
        return $this->belongsTo('App\User');
    }


    public function gateway_transaction_log()
    {
        return $this->hasOne('App/GatewayTransactionsLog');
    }


}
