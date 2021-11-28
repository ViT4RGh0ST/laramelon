<?php

namespace App\Models;

class Synchronization
{
    /**
     * The models that supports sync
     *
     * @var array
     */
    public static function getModels () {
        return [
            [
                'table' => 'products',
                'class' => Product::class
            ]
        ];
    }
}
