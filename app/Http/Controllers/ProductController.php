<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        return Product::all();
    }

    public function store(Request $request)
    {
        return Product::create($request->all());
    }

    public function update(Request $request, $id)
    {
        $object = Product::findOrFail($id);
        $object->fill($request->all());
        $object->save();
        return $object;
    }

    public function delete($id)
    {
        $object = Product::findOrFail($id);
        $object->delete();
        return null;
    }
}
