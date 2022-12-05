<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    use HasFactory;

    public function productos(){
        return $this->belongsToMany(Producto::class, 'pedido_productos')->withPivot('cantidad', 'precio')->withTrashed();
    }

    public function estado(){
        return $this->belongsTo(Estado::class);
    }

    protected function getPrecioTotalAttribute($value){
        return number_format($value, 2);
    }

    protected function getFechaCompraAttribute($value){
        return date('d/m/Y', strtotime($value));
    }

    protected function getFechaEntregaAttribute($value){
        if($value == null){
            return null;
        }
        return date('d/m/Y', strtotime($value));
    }

    protected function getFechaEnvioAttribute($value){
        if($value == null){
            return null;
        }
        return date('d/m/Y', strtotime($value));
    }
}
