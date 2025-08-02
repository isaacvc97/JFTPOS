<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\MedicineDosage;
use App\Models\MedicineForm;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class AssistantController extends Controller
{
    public function procesar(Request $request)
    {
        $text = strtolower($request->input('text'));

        function detectarComando($texto) {
            $texto = strtolower(trim($texto));
            $texto = preg_replace('/[^\w\s]/u', '', $texto); // quitar signos

            $sinonimos = [
                'crear' => ['crear', 'crea', 'nuevo', 'registrar', 'añadir', 'agregar'],
                'buscar' => ['buscar', 'busca', 'consultar', 'ver'],
                'modificar' => ['modificar', 'edita', 'editar', 'actualiza', 'cambiar'],
                'eliminar' => ['eliminar', 'borra', 'borrar', 'quitar'],
            ];

            foreach ($sinonimos as $comandoBase => $variantes) {
                foreach ($variantes as $variante) {
                    if (str_starts_with($texto, $variante)) {
                        return $comandoBase;
                    }
                }
            }

            return 'desconocido';
        }
        $comando = detectarComando($text);
        // BUSCAR EXTERNO
        if (Str::startsWith($comando, 'buscar fuera')) {
            return response()->json(['error' => 'No disponible sin conexión a internet'], 400);
        }

        // BUSCAR
        if (Str::startsWith($comando, 'buscar')) {
            $nombre = $this->extraerValor($comando, '/buscar\s+(.+)/');
            return $this->buscarProducto($nombre);
        }

        // CREAR
        if (Str::startsWith($comando, 'crear')) {
            $producto = [
                'name' => $this->extraerValor($comando, '/nombre\s+(\w+)/'),
                'generic_name' => '',
                'description' => $this->extraerValor($comando, '/descripcion\s+([\w\s]+)/'),
                'dosages' => [[
                    'id' => null,
                    'concentration' => $this->extraerValor($comando, '/(\d+mg)/'),
                    'form' => ['name' => $this->extraerForma($comando)],
                    'presentations' => []
                ]]
            ];

            $controller = new MedicineController();
            $res = $controller->storeFull($request);  
            if(isset($res->errors)){
                dd($res);
                return redirect()->back()->withErrors($res);
            }
            return $this->crearProducto($producto);
        }

        // MODIFICAR
        if (Str::startsWith($comando, 'modificar')) {
            $nombre = $this->extraerValor($comando, '/modificar\s+(\w+)/');
            return $this->modificarProducto($nombre, $comando);
        }

        // ENVIAR MENSAJE
        if (Str::startsWith($comando, 'enviar mensaje a')) {
            $mensaje = $this->extraerValor($comando, '/enviar mensaje a\s+(.+)/');
            return $this->enviarMensaje($mensaje);
        }

        return response()->json(['error' => 'Comando no reconocido'], 422);
    }

    private function extraerValor($texto, $regex)
    {
        preg_match($regex, $texto, $match);
        return $match[1] ?? '';
    }

    private function extraerForma($texto)
    {
        $formas = ['tableta', 'jarabe', 'capsula', 'ampolla'];
        foreach ($formas as $forma) {
            if (Str::contains($texto, $forma)) {
                return $forma;
            }
        }
        return '';
    }

    private function buscarProducto($nombre)
    {
        $producto = Medicine::with(['dosages.form', 'dosages.presentations'])
            ->where('name', 'like', '%' . $nombre . '%')
            ->first();

        return response()->json(['ok' => true, 'data' => $producto]);
    }

    private function crearProducto($producto)
    {
        // Crear Form si no existe
        $formName = $producto['dosages'][0]['form']['name'];
        $form = MedicineForm::firstOrCreate(['name' => $formName]);

        // Crear producto
        $p = new Medicine();
        $p->name = $producto['name'];
        $p->generic_name = $producto['generic_name'];
        $p->description = $producto['description'];
        $p->save();

        // Crear Dosage
        $dosage = new MedicineDosage();
        $dosage->product_id = $p->id;
        $dosage->concentration = $producto['dosages'][0]['concentration'];
        $dosage->form_id = $form->id;
        $dosage->save();

        return response()->json(['ok' => true, 'data' => $p->load('dosages.form')]);
    }

    private function modificarProducto($nombre, $texto)
    {
        $producto = Medicine::where('name', 'like', '%' . $nombre . '%')->first();
        if (!$producto) {
            return response()->json(['error' => 'Producto no encontrado'], 404);
        }

        // Ejemplo: modificar nombre paracetamol descripcion analgesico
        if (Str::contains($texto, 'descripcion')) {
            $producto->description = $this->extraerValor($texto, '/descripcion\s+([\w\s]+)/');
        }

        $producto->save();
        return response()->json(['ok' => true, 'data' => $producto]);
    }

    private function enviarMensaje($mensaje)
    {
        try {
            $r = Http::post('http://whatsapp:8001/enviar', ['mensaje' => $mensaje]);
            return $r->json();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al enviar mensaje: ' . $e->getMessage()], 500);
        }
    }
}
