<?php

namespace App\Http\Controllers;

use App\Http\Resources\ComingSoonMovie;
use App\Http\Resources\Feature;
use App\Http\Resources\FeatureMovie;
use App\Http\Resources\FrontMovie;
use App\Http\Resources\PopularMovie;
use App\Http\Resources\PopularMovies;
use App\Http\Resources\SeriesMovie;
use App\Models\Movie;
use App\Models\Title;

class MovieController extends Controller
{
    public function feature_movie()
    {
        $feature_movie = Movie::inRandomOrder()->where('feature',1)->where('published',1)->take(10)->get();
        return FeatureMovie::collection($feature_movie);
    }

    public function front_video()
    {
        $front_movie = Movie::inRandomOrder()->where('feature',1)->where('published',1)->first();
        return FrontMovie::make($front_movie);
    }

    public function all_feature_movies()
    {
        $feature_movies = Movie::inRandomOrder()->where('feature',1)->where('published',1)->get();
        return FeatureMovie::collection($feature_movies);
    }

    public function new_release()
    {
        $new_on_zizwa_plus = Movie::where('published',1)->where('type',1)->orderByDesc("updated_at")->get(10);
        return response()->json($new_on_zizwa_plus, 200);
    }

    public function originals()
    {
        $zizwa_plus_originals = Movie::where('published',1)->where('originals',1)->orderByDesc("updated_at")->take(10)->get();
        return response()->json($zizwa_plus_originals, 200);
    }

    public function popular()
    {
        $zizwa_plus_popular = Movie::where('published',1)->where('popular',1)->orderByDesc("updated_at")->take(10)->get();
        return PopularMovie::collection($zizwa_plus_popular);
    }

    public function all_popular()
    {
        $zizwa_plus_popular = Movie::where('published',1)->where('popular',1)->orderByDesc("updated_at")->get();
        return PopularMovie::collection($zizwa_plus_popular);
    }

    public function coming_soon()
    {
        $coming_soon = Movie::where('published',1)->where('coming_soon',1)->orderByDesc("updated_at")->take(10)->get();
        return ComingSoonMovie::collection($coming_soon);
    }

    public function all_coming_soon()
    {
        $coming_soon = Movie::where('published',1)->where('coming_soon',1)->orderByDesc("updated_at")->get();
        return ComingSoonMovie::collection($coming_soon);
    }

    public function all_series()
    {
        $series = Movie::where('published',1)->where('type','series')->orderByDesc("updated_at")->get();
        return SeriesMovie::collection($series);
    }

    public function all_movies()
    {
        $movies = Movie::where('published',1)->where('type','movie')->orderByDesc("updated_at")->get();
        return \App\Http\Resources\Movie::collection($movies);
    }

    public function movie_title()
    {
        $movie_title = Title::where("published", 1)->orderByDesc("updated_at")->get();
        return \App\Http\Resources\MovieTitle::collection($movie_title);
    }

    public function movie_by_title($id)
    {
        $movie_by_title = Movie::where('title_id',$id)->first();
        return \App\Http\Resources\Movie::make($movie_by_title);
    }



}
