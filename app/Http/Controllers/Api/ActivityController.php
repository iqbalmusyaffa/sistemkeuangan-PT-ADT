<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\ActivityLog;

class ActivityController extends Controller
{
    public function index(Request $request)
    {

        $activityLogs = ActivityLog::latest()->paginate(10);

        return response()->json($activityLogs);
    }
}
