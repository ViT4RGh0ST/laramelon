<?php

namespace App\Http\Controllers;

use App\Models\Synchronization;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use stdClass;

class SyncController extends Controller
{
    public function index(Request $request)
    {
        $syncTimestamp = microtime(true) * 1000;
        $lastPulledAt = $request->lastPulledAt != 'null' ? $request->lastPulledAt : 0;
        // $schemeVersion = $request->schemeVersion;
        // $migration = $request->migration;

        $models = Synchronization::getModels();

        $changes = new stdClass;

        foreach ($models as $model)
        {
            $changes->{$model['table']} = new stdClass;
            $changes->{$model['table']}->created = [];
            $changes->{$model['table']}->updated = [];
            $changes->{$model['table']}->deleted = [];

            $created = $model['class']::where(
                [
                    ['created_at', '>=', $lastPulledAt],
                    ['created_at', '<=', $syncTimestamp],
                    ['deleted_at', '=', 0]
                ]
            )->get();

            if(count($created) > 0){
                $changes->{$model['table']}->created = $created;
            }

            $updated = $model['class']::where(
                [
                    ['created_at', '<=', $lastPulledAt],
                    ['updated_at', '>=', $lastPulledAt],
                    ['updated_at', '<=', $syncTimestamp],
                    ['deleted_at', '=', 0]
                ]
            )->get();

            if(count($updated) > 0){
                $changes->{$model['table']}->updated = $updated;
            }

            $deleted = $model['class']::select('id')->where(
                [
                    ['deleted_at', '>', $lastPulledAt],
                    ['deleted_at', '<=', $syncTimestamp],
                    ['deleted_at', '<>', 0]
                ]
            )->get();

            if(count($deleted) > 0){
                $changes->{$model['table']}->deleted = $deleted->map(function ($object){
                    return $object->id;
                });
            }
        }

        return response()->json(
            [
                'changes' => $changes,
                'timestamp' => $syncTimestamp
            ]
        );
    }

    public function store(Request $request)
    {
        $changes = $request->changes;
        // $lastPulledAt = $request->lastPulledAt;

        $models = Synchronization::getModels();

        DB::transaction(function () use ($models, $changes)
        {
            foreach ($models as $model)
            {
                if(isset($changes[$model['table']]))
                {
                    foreach($changes[$model['table']]['created'] as $index => $store)
                    {
                        $model['class']::updateOrCreate(Arr::except($store, ['_status', '_changed']));
                    }

                    foreach($changes[$model['table']]['updated'] as $update)
                    {
                        $object = $model['class']::find($update['id']);

                        if($object && $object->deleted_at != 0)
                        {
                            abort(422, $update['id'] . ' this record have been deleted on server already');
                        }

                        $object->fill($update);
                        $object->save();
                    }

                    $model['class']::destroy($changes[$model['table']]['deleted']);
                }
            }
        });

        return ['message' => 'done'];
    }
}
