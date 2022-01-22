<?php

namespace App\Http\Controllers;

use App\Http\Resources\ComingSoonMovie;
use App\Http\Resources\Feature;
use App\Http\Resources\FeatureMovie;
use App\Http\Resources\PopularMovie;
use App\Http\Resources\PopularMovies;
use App\Http\Resources\SeriesMovie;
use App\Models\Movie;

class MovieController extends Controller
{
    public function feature_movie()
    {
        $feature_movie = Movie::inRandomOrder()->where('feature',1)->where('published',1)->get();
        return FeatureMovie::collection($feature_movie);
    }

    public function new_release()
    {
        $new_on_zizwa_plus = Movie::where('published',1)->where('type',1)->orderByDesc("updated_at")->get(10);
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
        $coming_soon = Movie::where('published',1)->where('coming_soon',1)->orderByDesc("updated_at")->take(10)->get();
        return ComingSoonMovie::collection($coming_soon);
    }

    public function series()
    {
        $series = Movie::where('published',1)->where('type','series')->orderByDesc("updated_at")->take(10)->get();
        return SeriesMovie::collection($series);
    }

    public function movies()
    {
        $movies = Movie::where('published',1)->where('type','movie')->orderByDesc("updated_at")->take(10)->get();
        return \App\Http\Resources\Movie::collection($movies);
    }



}
