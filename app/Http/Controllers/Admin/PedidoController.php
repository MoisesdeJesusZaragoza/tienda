<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PedidoCanceladoMail;
use App\Mail\PedidoEnviadoMail;
use App\Mail\PedidoPersonalizadoMail;
use App\Models\Correo;
use App\Models\Pedido;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class PedidoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $pedidos = Pedido::all();
        return view('admin.pedido.index',[
            'pedidos' => $pedidos
        ]);
    }

    public function listado()
    {
        $pedidos = Pedido::all();
        return view('admin.pedido.listado',[
            'pedidos' => $pedidos
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $productos = Producto::get();
        return view('admin.pedido.create', [
            'productos' => $productos
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'precio_total' => 'required',
            'nombre' => 'required|string',
            'direccion' => 'required|string',
            'telefono' => 'required|integer',
            'email' => 'required|email',
        ],[
            'precio_total.required' => 'No se agregaron productos a este pedido',
            'required' => 'El campo :attribute es obligatorio',
            'email' => 'El campo :attribute debe ser un email válido',
            'integer' => 'El campo :attribute debe ser un número',
            'string' => 'El campo :attribute debe ser un texto',
        ],[
            'precio_total' => 'Precio total',
            'nombre' => 'Nombre',
            'direccion' => 'Direccion',
            'telefono' => 'Telefono',
            'email' => 'Email',
        ]);

        if ($validator->fails()) {
            return redirect()->route('pedido.listado')
                ->withErrors($validator)
                ->withInput();
        }

        try{
            // Generar codigo de compra
            $codigo = 'PED-'.date('YmdHis');
            $pedido = new Pedido();
            $pedido->codigo = $codigo;
            $pedido->nombre = $request->nombre;
            $pedido->direccion = $request->direccion;
            $pedido->telefono = $request->telefono;
            $pedido->email = $request->email;
            $pedido->estado_id = 1;
            $pedido->precio_total = $request->precio_total;
            $pedido->fecha_compra = date('Y-m-d');
            $pedido->save();
            // Guardar productos en pedido
            $productos = $request->productos;
            $precios = $request->precios;
            // Agregar cantidad y precio a cada producto
            foreach ($productos as $key => $producto) {
                $pedido->productos()->attach($key, ['cantidad' => $productos[$key], 'precio' => $precios[$key]]);
            }
            return redirect()->route('pedido.listado')->with('success','Pedido creado correctamente');
        }catch(\Exception $e){
            return redirect()->route('pedido.listado')->with('error', 'Error al crear el pedido');
        }
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $productos = Producto::get();
        $pedido = Pedido::find($id);
        return view('admin.pedido.edit',[
            'productos' => $productos,
            'pedido' => $pedido
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try{
            $pedido = Pedido::find($id);
            $pedido->nombre = $request->nombre;
            $pedido->direccion = $request->direccion;
            $pedido->telefono = $request->telefono;
            $pedido->email = $request->email;
            $pedido->precio_total = $request->precio_total;
            $pedido->save();
            // Guardar productos en pedido
            $productos = $request->productos;
            $precios = $request->precios;
            $pedido->productos()->detach();
            foreach ($productos as $key => $producto) {
                $pedido->productos()->attach($key, ['cantidad' => $productos[$key], 'precio' => $precios[$key]]);
            }
            return redirect()->route('pedido.listado')->with('success','Pedido actualizado correctamente');
        }
        catch(\Exception $e){
            return redirect()->route('pedido.listado')->withErrors('Error al actualizar el pedido');
            // return redirect()->route('pedido.listado')->withErrors($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try{
            $pedido = Pedido::find($id);
            $pedido->delete();
            return redirect()->route('pedido.listado')->with('success','Pedido eliminado correctamente');
        }
        catch(\Exception $e){
            return redirect()->route('pedido.listado')->withErrors('Error al eliminar el pedido');
        }
    }

    public function envio($id){
        $pedido = Pedido::find($id);
        return view('admin.pedido.envio',[
            'pedido' => $pedido
        ]);
    }

    public function envioUpdate(Request $request, $id){
        $pedido = Pedido::find($id);
        $status = $request->status;
        // Enviado
        if($status == 2){
            $pedido->estado_id = 2;
            $pedido->fecha_envio = date('Y-m-d');
            $pedido->clave = $request->clave;
            $pedido->save();
        }
        // Entregado
        if($status == 3){
            $pedido->estado_id = 3;
            $pedido->fecha_entrega = date('Y-m-d');
            $pedido->save();
        }
        // Pendiente de nuevo
        if($status == 1){
            $pedido->estado_id = 1;
            $pedido->fecha_envio = null;
            $pedido->clave = null;
            $pedido->fecha_entrega = null;
            $pedido->save();
        }
        // Cancelado
        if($status == 4){
            $pedido->estado_id = 4;
            $pedido->save();
        }
        return redirect()->route('pedido.listado')->with('success','Pedido actualizado correctamente');
    }

    public function correos($id)
    {
        $correos = Correo::where('pedido_id', $id)->get();
        $pedido = Pedido::find($id);
        return view('admin.pedido.correos', [
            'pedido' => $pedido,
            'correos' => $correos
        ]);
    }

    public function enviar(Request $request, $id){
        // dd($request->all());
        if($request->asunto == ''){
            return redirect()->route('pedido.listado')->withErrors('Debe ingresar un asunto');
        }
        else{
            if($request->asunto == 'Envio'){
                try{
                    Mail::to($request->correo)->send(new PedidoEnviadoMail($id));
                }
                catch(\Exception $e){
                    // return redirect()->route('pedido.listado')->withErrors('Error al enviar el correo');
                    return redirect()->route('pedido.listado')->withErrors($e->getMessage());
                }
            }
            if($request->asunto == 'Cancelacion'){
                try{
                    Mail::to($request->correo)->send(new PedidoCanceladoMail($id));
                }
                catch(\Exception $e){
                    // return redirect()->route('pedido.listado')->withErrors('Error al enviar el correo');
                    return redirect()->route('pedido.listado')->withErrors($e->getMessage());
                }
            }
            if($request->asunto == 'Personalizado'){
                try{
                    Mail::to($request->correo)->send(new PedidoPersonalizadoMail($id,$request->asunto_personalizado ,$request->mensaje));
                    $request->asunto = $request->asunto_personalizado;
                }
                    catch(\Exception $e){
                    // return redirect()->route('pedido.listado')->withErrors('Error al enviar el correo');
                    return redirect()->route('pedido.listado')->withErrors($e->getMessage());
                }
            }
            $correo = new Correo();
            $correo->correo = $request->correo;
            $correo->asunto = $request->asunto;
            $correo->fecha = date('Y-m-d');
            $correo->pedido_id = $id;
            $correo->save();
            return redirect()->route('pedido.listado')->with('success', 'Enviado');
        }
    }

    public function pdf($id){
        $pedido = Pedido::find($id);
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('admin.pdf.enviado', [
            'pedido' => $pedido,
        ]);
        return $pdf->download('pedido.pdf');
    }

}
