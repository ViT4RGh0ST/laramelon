<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    // protected $fillable = [
    //     'id',
    //     'name',
    //     'price',
    //     'created_at',
    //     'updated_at',
    //     'deleted_at'
    // ];

    protected $guarded = [];

    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $dateFormat = 'U';

    protected $casts = [
        'price' => 'float',
    ];

    public $timestamps = false;

    public function delete()
    {
        $this->updated_at = Carbon::now()->timestamp * 1000;
        $this->deleted_at = Carbon::now()->timestamp * 1000;
        $this->save();
    }

    public function restore()
    {
        $this->updated_at = Carbon::now()->timestamp * 1000;
        $this->deleted_at = null;
        $this->save();
    }
}
