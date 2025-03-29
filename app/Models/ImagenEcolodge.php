<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImagenEcolodge extends Model
{
    use HasFactory;

    protected $table = 'imagenes_ecolodges'; // Asegurar que Laravel la reconozca

    protected $fillable = ['ecolodge_id', 'ruta_imagen'];

    public function ecolodge()
    {
        return $this->belongsTo(Ecolodge::class);
    }
}
