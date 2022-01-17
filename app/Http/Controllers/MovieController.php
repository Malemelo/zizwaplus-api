<?php

namespace App\Http\Controllers;

use App\Http\Resources\Feature;
use App\Models\Movie;
use App\Models\Type;
use Illuminate\Http\Request;

class MovieController extends Controller
{
    public function feature_movie()
    {
        $feature_movie = Movie::where('feature',1)->where('published',1)->first();
        return Feature::make($feature_movie);
    }

    public function new_release()
    {
        $new_on_zizwa_plus = Movie::where('published',1)->where('type',1)->orderBy('DESC','updated_at')->get(10);
        return response()->json($new_on_zizwa_plus, 200);
    }

    public function originals()
    {
        $zizwa_plus_originals = Movie::where('published',1)->where('originals',1)->orderByDesc("updated_at")->paginate(5);
        return response()->json($zizwa_plus_originals, 200);
    }

    public function popular()
    {
        $zizwa_plus_popular = Movie::where('published',1)->where('popular',1)->orderByDesc("updated_at")->paginate(5);
        return response()->json($zizwa_plus_popular, 200);
    }

    public function coming_soon()
    {
        $coming_soon = Movie::where('published',1)->where('coming_soon',1)->orderBy('DESC','updated_at')->get(10);
        return response()->json($coming_soon, 200);
    }



}
