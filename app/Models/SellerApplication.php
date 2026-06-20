<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SellerApplication extends Model
{
    use HasFactory;

    public const ESTADO_ACEPTADO = 'ACEPTADO';
    public const ESTADO_REVISION = 'REVISION';
    public const ESTADO_RECHAZADO = 'RECHAZADO';

    public const RIESGO_BAJO = 'bajo';
    public const RIESGO_MEDIO = 'medio';
    public const RIESGO_ALTO = 'alto';

    protected $fillable = [
        'user_id',
        'store_id',
        'nombre_comercial',
        'ruc',
        'dni',
        'telefono',
        'correo',
        'categoria',
        'razon_social',
        'sunat_data',
        'tipo_evidencia',
        'evidencia_valor',
        'etapa',
        'score',
        'riesgo',
        'estado',
        'diagnostico',
    ];

    protected function casts(): array
    {
        return [
            'diagnostico' => 'array',
            'sunat_data' => 'array',
            'etapa' => 'integer',
            'score' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function isAceptado(): bool { return $this->estado === self::ESTADO_ACEPTADO; }
    public function isRevision(): bool { return $this->estado === self::ESTADO_REVISION; }
    public function isRechazado(): bool { return $this->estado === self::ESTADO_RECHAZADO; }
}
