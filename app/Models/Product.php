<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    protected $dateFormat = 'U';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'price' => 'float',
    ];

    public function delete()
    {
        $this->updated_at = microtime(true) * 1000;
        $this->deleted_at = microtime(true) * 1000;
        $this->save();
    }

    public function restore()
    {
        $this->updated_at = microtime(true) * 1000;
        $this->deleted_at = null;
        $this->save();
    }
}
