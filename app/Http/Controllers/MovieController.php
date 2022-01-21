<?php

namespace App\Http\Controllers;

use App\Http\Resources\Feature;
use App\Http\Resources\PopularMovie;
use App\Http\Resources\PopularMovies;
use App\Models\Movie;
use App\Models\Title;
use App\Models\Type;
use Illuminate\Http\Request;

class MovieController extends Controller
{
    public function feature_movie()
    {
        $feature_movie = Movie::inRandomOrder()->where('feature',1)->where('published',1)->first();

        $response = [
            'id' => $feature_movie->id,
            'title' => Title::where('id',$feature_movie->title_id)->first()->title,
            'sub_title' => Title::where('id',$feature_movie->title_id)->first()->Sub_title,
            'thumbnail' => $feature_movie->thumbnail,
            'trailer' => $feature_movie->trailer,
            'video' => $feature_movie->video
        ];
        return response()->json($response, 200);
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
        $zizwa_plus_popular = Movie::where('published',1)->where('popular',1)->orderByDesc("updated_at")->take(10)->get();
        return PopularMovie::collection($zizwa_plus_popular);
    }

    public function coming_soon()
    {
        $coming_soon = Movie::where('published',1)->where('coming_soon',1)->orderBy('DESC','updated_at')->take(10)->get();
        return response()->json($coming_soon, 200);
    }



}
