<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id','value'
    ];

    public function saveToken($user_id,$val) {
        $this->create([
            'user_id'=>$user_id,
            'value'=>$val,
        ]);
    }

    public function updateToken($user_id,$val){
        $this->where('user_id',$user_id)->update([
            'value'=>$val,
        ]);
    }
}
