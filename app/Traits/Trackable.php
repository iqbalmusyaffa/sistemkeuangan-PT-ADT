<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

trait Trackable
{
    public static function bootTrackable()
    {
        static::created(function ($model) {
            $model->logActivity('created');
        });

        static::updated(function ($model) {
            $model->logActivity('updated');
        });

        static::deleted(function ($model) {
            $model->logActivity('deleted');
        });
    }

    protected function logActivity($action)
    {
        $userName = Auth::check() ? Auth::user()->name : 'System';

        $changes = [
            'before' => $this->getOriginal(),
            'after' => $this->getChanges(),
        ];

        ActivityLog::create([
            'user_name' => $userName,
            'model_type' => get_class($this),
            'model_id' => $this->id,
            'action' => $action,
            'changes' => json_encode($changes),
        ]);
    }
}
