<?php
namespace Klik\Loggable;

use Illuminate\Support\Facades\Config;
use Yajra\Oci8\Eloquent\OracleEloquent as Eloquent;
use Illuminate\Database\Eloquent\Model;

class Loggable extends Eloquent
{

    /**
     * @var string
     */
    public $table = "MODELS_LOGS";


    public $sequence = 'MODELS_LOGS_SEQ';

 
    public function loggable()
    {
        return $this->morphTo();
    }

   
    public function user()
    {
        return $this->belongsTo(\Klik\User\Models\User::class, 'user_id');
    }
  

    public function getModelLogsAttribute($value)
    {
        return json_decode($value);
    }


}