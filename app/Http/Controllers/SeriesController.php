<?php

namespace App\Http\Controllers;

use App\Models\Series;
use Illuminate\Http\Request;

class SeriesController extends Controller
{
    //

    public function index()
    {
        $series = Series::where('published',1)->get();
        
        return response()->json($series, 200);

    }
}
