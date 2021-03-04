<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Category;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('api.auth', ['except' => ['index', 'show']]);
    }

    public function index(Request $request)
    {
        $categories = Category::all();

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'categories' => $categories
        ]);
    }

    public function show($id)
    {
        $category = Category::find($id);

        if (is_object($category)) {
            $data = [
                'code' => 200,
                'status' => 'success',
                'category' => $category
            ];
        } else {
            $data = [
                'code' => 404,
                'status' => 'error',
                'message' => 'La categoria no existe'
            ];
        }
        return response()->json($data, $data['code']);
    }

    public function store(Request $request)
    {
        //Recoger los datos
        $json = $request->input('json', null);
        $params_arrray = json_decode($json, true);
        if (!empty($params_arrray)) {
            //Validar datos
            $validate = validator($params_arrray, [
                'name' => 'required'
            ]);
            //Guardar la categoria
            if ($validate->fails()) {
                $data = [
                    'code' => 404,
                    'status' => 'error',
                    'message' => 'No se ha guardado la categoria'
                ];
            } else {
                $category = new Category();
                $category->name = $params_arrray['name'];
                $category->save();

                $data = [
                    'code' => 200,
                    'status' => 'success',
                    'category' => $category
                ];
            }
        } else {
            $data = [
                'code' => 404,
                'status' => 'error',
                'message' => 'No se ha enviado ninguna categoria'
            ];
        }
        //Devolver resultado
        return response()->json($data, $data['code']);
    }

    public function update($id, Request $request)
    {
        //Recoger datos
        $json = $request->input('json', null);
        $params_arrray = json_decode($json, true);
        if (!empty($params_arrray)) {
            //Validar datos
            $validate = validator($params_arrray, [
                'name' => 'required'
            ]);
            //Quitar datos innecesarios
            unset($params_arrray['id']);
            unset($params_arrray['created_at']);
            //Actualizar datos
            $category = Category::where('id', $id)->update($params_arrray);
            $data = [
                'code' => 200,
                'status' => 'success',
                'category' => $params_arrray
            ];
        } else {
            $data = [
                'code' => 404,
                'status' => 'error',
                'message' => 'No se ha enviado ninguna categoria'
            ];
        }
        //Devolver respuesta
        return response()->json($data, $data['code']);
    }
}
