<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use stdClass;

class SyncController extends Controller
{
    public function index(Request $request)
    {
        $timestamp = Carbon::now()->timestamp * 1000;
        $lastPulledAt = $request->lastPulledAt != 'null' ? $request->lastPulledAt : 0;
        $schemeVersion = $request->schemeVersion;
        $migration = $request->migration;

        // Product::create([
        //     'id' => 'prod-1',
        //     'name' => 'carotas',
        //     'price' => 100
        // ]);
        // dd('si');

        // $lastPulledAt = '1604534400';
        // dd(json_encode($lastPulledAt));
        $models = [
            [
                'table' => 'products',
                'class' => Product::class
            ]
        ];

        $changes = new stdClass;

        foreach ($models as $model)
        {
            $changes->{$model['table']} = new stdClass;
            $changes->{$model['table']}->created = [];
            $changes->{$model['table']}->updated = [];
            $changes->{$model['table']}->deleted = [];

            $created = $model['class']::where(
                [
                    ['created_at', '>', $lastPulledAt],
                    ['deleted_at', '=', 0]
                ]
            )->get();
            if(count($created) > 0){
                // foreach($created as $created_item)
                // {
                //     $created_item->_status = 'created';
                //     $created_item->_changed = '';
                // }
                $changes->{$model['table']}->created = $created;
            }

            $updated = $model['class']::where(
                [
                    ['updated_at', '>', $lastPulledAt],
                    ['created_at', '<', $lastPulledAt],
                    ['deleted_at', '=', 0]
                ]
            )->get();

            if(count($updated) > 0){
                $changes->{$model['table']}->updated = $updated;
            }

            $deleted = $model['class']::select('id')->where('deleted_at', '>', $lastPulledAt)->get();

            if(count($deleted) > 0){
                foreach($deleted as $toDelete){
                    $changes->{$model['table']}->deleted [] = $toDelete->id;
                }
            }
        }

        // dd($changes);
        // dd(json_encode($changes));

        return response()->json(
            [
                'changes' => $changes,
                'timestamp' => $timestamp
            ]
        );
    }

    public function store(Request $request)
    {
        $changes = $request->changes;
        $lastPulledAt = $request->lastPulledAt;
        // dd(json_encode($changes));
        // return ['tocheck' => $changes];
        $models = [
            [
                'table' => 'products',
                'class' => Product::class
            ]
        ];

        DB::transaction(function () use ($models, $changes)
        {
            foreach ($models as $model)
            {
                // foreach($changes as $change)
                // {
                    // return [isset($changes[$model['table']])];
                if(isset($changes[$model['table']]))
                {
                    // return ['tocheck' => 'imghere'];
                    foreach($changes[$model['table']]['created'] as $index => $store)
                    {
                        if($index == 1)
                        {
                            // abort(500,'Mission Failed');
                        }
                        $model['class']::updateOrCreate(Arr::except($store, ['_status', '_changed']));
                    }

                    foreach($changes[$model['table']]['updated'] as $update)
                    {
                        $object = $model['class']::find($update['id']);

                        if($object && !is_null($object->deleted_at))
                        {
                            abort(422, $update['id'] . ' this record have been deleted on server already');
                        }

                        // $model['class']::firstOrCreate(Arr::except($update, ['_status', '_changed']));
                        $object->fill($update);
                        $object->save();
                    }

                    $model['class']::destroy($changes[$model['table']]['deleted']);
                }
                // }
            }
        });

        return ['message' => 'done'];
    }
}
