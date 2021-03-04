<?php


namespace App\Http\Controllers;

use App\Helpers\jwtAuth;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\User;
use Dotenv\Validator;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function pruebas(Request $request)
    {
        return "Acción de pruebas de UserController";
    }

    public function pruebaTecnica(Request $request)
    {
        $usuarios = array(
            array('nombre' => 'Alex', 'apellido' => 'Escobar', 'telefono' => '3212123213'),
            array('nombre' => 'Juan', 'apellido' => 'Gomez', 'telefono' => '3212123213'),
            array('nombre' => 'Andres', 'apellido' => 'Marin', 'telefono' => '3212123213'),
            array('nombre' => 'Angie', 'apellido' => 'Rivera', 'telefono' => '3212123213')
        );

        foreach ($usuarios as $usuario) {
            echo $usuario['nombre'] . " " . $usuario['apellido'] . " " . $usuario['telefono'] . ".<br>";
        }
    }

    public function register(Request $request)
    {
        //Recoger los datos
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);
        if (!empty($params_array) && !empty($params)) {
            //Limpiar datos
            $params_array = array_map('trim', $params_array);
            //Validar el usuario y comprueba que el email ya exista, esto a partir de la regla validacion unique
            $validate = \validator($params_array, [
                'name' => 'required|alpha',
                'surname' => 'required|alpha',
                'email' => 'required|email|unique:users',
                'password' => 'required|alpha_num'
            ]);

            if ($validate->fails()) {
                $data = array(
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'El usuario no se ha creado correctamente',
                    'errors' => $validate->errors()
                );
            } else {
                //Cifrar la contraseña
                $pwd = hash('SHA256', $params->password);
                //Crear el usuario
                $user = new User();
                $user->name = $params_array['name'];
                $user->surname = $params_array['surname'];
                $user->email = $params_array['email'];
                $user->password = $pwd;
                $user->role = 'ROLE_USER';

                $user->save();

                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'El usuario se ha creado correctamente',
                );
            }
        } else {
            $data = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'Los datos no son correctos',
            );
        }

        return response()->json($data, $data['code']);
    }

    public function login(Request $request)
    {
        $jwtAuth = new \App\Helpers\jwtAuth();

        // Recibir datos
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);
        // Validar datos
        $validate = \validator($params_array, [
            'email' => 'required|email',
            'password' => 'required|alpha_num'
        ]);

        if ($validate->fails()) {
            $signup = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'No se ha logrado iniciar sesión',
                'errors' => $validate->errors()
            );
        } else {
            // Cifrar contraseña
            $pwd = hash('SHA256', $params->password);
            // Devolver token
            $signup = $jwtAuth->signUp($params->email, $pwd);
            if (!empty($params->gettoken)) {
                $signup = $jwtAuth->signUp($params->email, $pwd, true);
            }
        }
        return response()->json($signup, 200);
    }

    public function update(Request $request)
    {
        // Comprobar autenticación
        $token = $request->header('Authorization');
        $jwtAuth = new \App\Helpers\jwtAuth();
        $checkToken = $jwtAuth->checkToken($token);
        // Recoger los datos por post
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);
        if ($checkToken && !empty($params_array)) {
            // Sacar usuario identificado
            $user = $jwtAuth->checkToken($token, true);
            // Validar datos
            $validate = \validator($params_array, [
                'name' => 'required|alpha',
                'surname' => 'required|alpha',
                'email' => 'required|email|unique:users' . $user->sub
            ]);
            // Quitar los campos
            unset($params_array['id']);
            unset($params_array['role']);
            unset($params_array['password']);
            unset($params_array['created_at']);
            // Actualizar datos
            $user_update = User::where('id', $user->sub)->update($params_array);
            // Devolver array
            $data = array(
                'status' => 'success',
                'code' => 200,
                'user' => $user,
                'changes' => $params_array
            );
        } else {
            $data = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'El usuario no esta identificado',
            );
        }
        return response()->json($data, $data['code']);
    }

    public function upload(Request $request)
    {
        // Recoger datos
        $image = $request->file('file0');
        // Validar imagen
        $validate = validator($request->all(), [
            'file0' => 'required|image|mimes:jpg,jpeg,png,gif'
        ]);
        // Subir imagen
        if (!$image || $validate->fails()) {
            $data = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'Error al subir imagen',
            );
        } else {
            $image_name = time() . $image->getClientOriginalName();
            \Illuminate\Support\Facades\Storage::disk('users')->put($image_name, file($image));

            $data = array(
                'status' => 'success',
                'code' => 200,
                'image' => $image_name,
            );
        }
        return response()->json($data, $data['code']);
    }

    public function getImage($filename)
    {
        $isset = \Illuminate\Support\Facades\Storage::disk('users')->exists($filename);
        if ($isset) {
            $file = \Illuminate\Support\Facades\Storage::disk('users')->get($filename);
            return new Response($file, 200);
        } else {
            $data = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'No existe la imagen',
            );
            return response()->json($data, $data['code']);
        }
    }

    public function detail($id)
    {
        $user = User::find($id);
        if (is_object($user)) {
            $data = array(
                'status' => 'success',
                'code' => 200,
                'user' => $user,
            );
        } else {
            $data = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'No existe la imagen',
            );
        }
        return response()->json($data, $data['code']);
    }
}
