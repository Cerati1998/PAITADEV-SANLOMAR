<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Identity;
use App\Models\User;
use App\Services\SunatService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $branches = Branch::where('company_id', session('company')->id)->get();
        //$identities = Identity::whereIn('id', [1, 2])->get();
        $identities = Identity::find([1, 2]);
        $roles = Role::all();
        return view('users.index', compact('branches', 'identities', 'roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('users.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'tipoDoc' => ['required', 'exists:identities,id'],
            'numDoc' => [
                'required',
                Rule::when($request->tipoDoc == 1, 'numeric|digits:8'),
                Rule::when($request->tipoDoc == 2, 'numeric|digits:7'),
                Rule::unique('users', 'numDoc')
            ],
            'password' => ['required', 'string', 'confirmed', 'min:8', 'regex:/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[@*\/\-_])[a-zA-Z0-9@*\/\-_]{8,}$/'],
            'role' => ['required', 'exists:roles,id'],
            'branch_id' => ['required', 'exists:branches,id'],
        ], [
            'password.regex' => 'La contraseña debe contener al menos una letra, un número y un carácter especial (@,/, -).',

        ], [
            'branch_id' => 'sucursal',
            'name' => 'Nombres',
            'email' => 'Correo Electronico',
            'password' => 'Contraseña',
        ]);

        //Buscar o crear usuario
        $user = User::firstOrCreate([
            'email' => $data['email'],
        ], [
            'name' => $data['name'],
            'tipoDoc' => $data['tipoDoc'],
            'numDoc' => $data['numDoc'],
            'password' => bcrypt($data['password']),
        ]);

        //Verificar si el usuario ya pertenece a la empresa
        $userCompany = DB::table('company_user')
            ->where('user_id', $user->id)
            ->where('company_id', session('company')->id)
            ->first();

        if ($userCompany) {

            throw ValidationException::withMessages([
                'email' => 'El usuario ya pertenece a la empresa.',
            ]);
        }

        //Asignar usuario a la empresa
        $user->companies()->attach(session('company')->id);

        //Asignar usuario a la sucursal
        $user->branches()->attach($data['branch_id'], [
            'company_id' => session('company')->id,
        ]);

        //Asignar Rol
        $role = Role::find($data['role']);
        $user->assignRole($role->name);
        return $user;
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return view('users.show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        return view('users.edit');
    }

    public function searchDocument(Request $request)
    {
        $request->validate([
            'tipoDoc' => 'required|in:1', // 1: DNI
            'numDoc' => 'required|numeric|digits:8'
        ], [
            'tipoDoc.in' => 'Solo se permite consulta de DNI',
            'numDoc.digits' => 'El DNI debe tener 8 dígitos'
        ], [
            'tipoDoc' => 'Tipo de Documento',
            'numDoc' => 'Número de Documento'
        ]);

        $tipoDoc = (int) $request->tipoDoc;
        $numDoc = $request->numDoc;

        try {
            if ($tipoDoc !== 1) {
                throw new Exception("Solo se permite busqueda de persona natural por DNI");
            }

            $SunatService = app(SunatService::class);
            $response = $SunatService->consultarDNI($numDoc);

            if (!$response['success']) {
                throw new Exception($response['message'] ?? 'Error al hacer la consulta');
            }

            return response()->json([
                'nombres' => $response['data']['nombres'] . ' ' . $response['data']['apellidoPaterno'] . ' ' . $response['data']['apellidoMaterno'],
                'message' => 'Usuario encontrado'
            ], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }
}
