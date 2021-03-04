<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\User;
use App\Models\Category;

class PruebaController extends Controller
{
    public function index()
    {
        $titulo = 'ANIMALES';
        $animales = ['perro', 'gato', 'tigre'];

        return view('pruebas.index', array(
            'titulo' => $titulo,
            'animales' => $animales
        ));
    }

    public function TestORM()
    {
        /*
        $posts = Post::all();
        foreach ($posts as $post) {
            echo "<h1>" . $post->title . "</h1>";
            echo "<span style='color:gray;'>{$post->user->name} - {$post->category->name}</span>";
            echo "<p>" . $post->content . "</p>";
            echo "<hr>";
        }*/

        $categories = Category::all();
        foreach ($categories as $category) {
            echo "<h1>" . $category->name . "</h1>";
            foreach ($category->posts as $post) {
                echo "<h3>" . $post->title . "</h3>";
                echo "<span style='color:gray;'>{$post->user->name}</span>";
                echo "<p>" . $post->content . "</p>";
                echo "<hr>";
            }
        }
        die();
    }
}
