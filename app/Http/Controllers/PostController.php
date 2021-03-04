<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Post;
use App\Helpers\jwtAuth;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware('api.auth', ['except' => ['index', 'show', 'getImage', 'getPostsByCategory', 'getPostsByUser']]);
    }

    public function index()
    {
        $posts = Post::all()->load('category');

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'posts' => $posts
        ], 200);
    }

    public function show($id)
    {
        $post = Post::find($id)->load('category')->load('user');

        if (is_object($post)) {
            $data = [
                'code' => 200,
                'status' => 'success',
                'post' => $post
            ];
        } else {
            $data = [
                'code' => 404,
                'status' => 'error',
                'message' => 'No se encuentra el post mencionado'
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function store(Request $request)
    {
        //Recoger datos
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        if (!empty($params_array)) {
            //Conseguir usuario autentificado
            $user = $this->getIdentity($request);
            //Validar datos
            $validate = validator($params_array, [
                'title' => 'required',
                'content' => 'required',
                'category_id' => 'required'
            ]);
            if ($validate->fails()) {
                $data = [
                    'code' => 404,
                    'status' => 'error',
                    'message' => 'No se ha guardado el post mencionado, faltan campos requeridos'
                ];
            } else {
                //Guardar datos
                $post = new Post();
                $post->user_id = $user->sub;
                $post->category_id = $params->category_id;
                $post->title = $params->title;
                $post->content = $params->content;
                $post->image = $params->image;

                $post->save();

                $data = [
                    'code' => 200,
                    'status' => 'success',
                    'post' => $post
                ];
            }
        } else {
            $data = [
                'code' => 404,
                'status' => 'error',
                'message' => 'No se ha guardado el post mencionado, faltan campos requeridos'
            ];
        }
        //Devolver la respuesta
        return response()->json($data, $data['code']);
    }

    public function update($id, Request $request)
    {
        //Recoger los datos
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);
        if (!empty($params_array)) {
            //Validar los datos
            $validate = validator($params_array, [
                'title' => 'required',
                'content' => 'required',
                'category_id' => 'required'
            ]);
            if ($validate->fails()) {
                $data = [
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'No se logro actualizar, falta información'
                ];
            } else {
                //Eliminar datos que no se necesitan
                unset($params_array['id']);
                unset($params_array['user']);
                unset($params_array['created_at']);
                //Conseguir usuario autentificado
                $user = $this->getIdentity($request);
                //Buscar el registro
                $post = Post::where('id', $id)->where('user_id', $user->sub)->first();
                if (!empty($post) && is_object($post)) {
                    //Actualizar post
                    $post->update($params_array);

                    $data = [
                        'code' => 200,
                        'status' => 'success',
                        'post' => $params_array
                    ];
                } else {
                    $data = [
                        'code' => 400,
                        'status' => 'error',
                        'message' => 'No se logro actualizar, falta información'
                    ];
                }
            }
        } else {
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'No se logro actualizar, falta información'
            ];
        }
        //Devolver respuesta
        return response()->json($data, $data['code']);
    }

    public function destroy($id, Request $request)
    {
        //Conseguir usuario autentificado
        $user = $this->getIdentity($request);
        //Comprobar existencia
        $post = Post::where('id', $id)->where('user_id', $user->sub)->first();
        if (!empty($post)) {
            //Borrar registro
            $post->delete();
            //Devolver respuesta
            $data = [
                'code' => 200,
                'status' => 'success',
                'message' => 'Se borro el registro'
            ];
        } else {
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'No existe el post o el usuario que intenta eliminarlo no tiene permisos para eliminarlo'
            ];
        }
        return response()->json($data, $data['code']);
    }

    public function upload(Request $request)
    {
        //Recoger la imagen
        $image = $request->file('file0');
        //Validar la imagen
        $validate = validator($request->all(), [
            'file0' => 'required|image|mimes:jpg,jpeg,png,gif'
        ]);
        //Guardar la imagen
        if (empty($image) || $validate->fails()) {
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'Error al subir la imagen'
            ];
        } else {
            $image_name = time() . $image->getClientOriginalName();
            Storage::disk('images')->put($image_name, file($image));
            $data = [
                'code' => 200,
                'status' => 'success',
                'image' => $image_name
            ];
        }
        //Devolver datos
        return response()->json($data, $data['code']);
    }

    public function getImage($filename)
    {
        //Comprobar si existe el fichero
        $isset = Storage::disk('images')->exists($filename);
        if ($isset) {
            //Conseguir la imagen
            $file = Storage::disk('images')->get($filename);
            //Devolver la imagen
            return new Response($file, 200);
        } else {
            //Devolver respuesta
            $data = [
                'code' => 404,
                'status' => 'error',
                'message' => 'La imagen no existe'
            ];
        }
        return response()->json($data, $data['code']);
    }

    public function getPostsByCategory($id)
    {
        $posts = Post::where('category_id', $id)->get();

        return response()->json([
            'status' => 'success',
            'posts' => $posts
        ], 200);
    }

    public function getPostsByUser($id)
    {
        $posts = Post::where('user_id', $id)->get();
        return response()->json([
            'status' => 'success',
            'posts' => $posts
        ], 200);
    }

    private function getIdentity($request)
    {
        $jwtAuth = new jwtAuth();
        $token = $request->header('Authorization', null);
        $user = $jwtAuth->checkToken($token, true);
        return $user;
    }
}
