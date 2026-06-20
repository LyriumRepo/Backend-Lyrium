<?php

declare(strict_types=1);

namespace App\Services\Logistics;

use Illuminate\Support\Facades\DB;

class BoxCalculatorService
{
    private const DIV_VOL_PESO = 5000;
    private const MAX_PRODUCTOS = 200;

    private const G = [
        'QUIMICO'     => 'QUIMICO',
        'ALIMENTO'    => 'ALIMENTO',
        'FRESCO'      => 'FRESCO',
        'REFRIGERADO' => 'REFRIGERADO',
        'BEBIDA'      => 'BEBIDA',
        'MEDICO'      => 'MEDICO',
        'TEXTIL'      => 'TEXTIL',
        'CALZADO'     => 'CALZADO',
        'MASCOTA'     => 'MASCOTA',
        'GENERAL'     => 'GENERAL',
    ];
    private const PARES_INCOMPATIBLES = [
        ['QUIMICO', 'ALIMENTO'],
        ['QUIMICO', 'FRESCO'],
        ['QUIMICO', 'REFRIGERADO'],
        ['QUIMICO', 'BEBIDA'],
        ['QUIMICO', 'MEDICO'],
        ['QUIMICO', 'MASCOTA'],
        ['FRESCO',  'TEXTIL'],
        ['FRESCO',  'CALZADO'],
    ];
    private array $cajas;

    public function __construct()
    {
        $this->cajas = DB::table('box_types')
            ->where('activo', true)
            ->orderBy('orden')
            ->get(['nombre', 'largo', 'ancho', 'alto', 'peso_max_kg'])
            ->map(fn($r) => (array) $r)
            ->toArray();

        if (empty($this->cajas)) {
            $this->cajas = [
                ['nombre' => 'XXS', 'largo' => 15, 'ancho' => 10, 'alto' => 10, 'peso_max_kg' => 0.50],
                ['nombre' => 'XS',  'largo' => 25, 'ancho' => 20, 'alto' => 12, 'peso_max_kg' => 1.00],
                ['nombre' => 'S',   'largo' => 30, 'ancho' => 20, 'alto' => 12, 'peso_max_kg' => 2.00],
                ['nombre' => 'M',   'largo' => 40, 'ancho' => 30, 'alto' => 20, 'peso_max_kg' => 5.00],
                ['nombre' => 'ML',  'largo' => 50, 'ancho' => 40, 'alto' => 25, 'peso_max_kg' => 10.00],
                ['nombre' => 'L',   'largo' => 60, 'ancho' => 40, 'alto' => 30, 'peso_max_kg' => 10.00],
                ['nombre' => 'XL',  'largo' => 80, 'ancho' => 60, 'alto' => 40, 'peso_max_kg' => 25.00],
            ];
        }
    }

    public function calcular(array $productos): array
    {
        if (empty($productos)) {
            throw new \InvalidArgumentException('Se requiere al menos un producto.');
        }
        if (count($productos) > self::MAX_PRODUCTOS) {
            throw new \InvalidArgumentException('Máximo ' . self::MAX_PRODUCTOS . ' productos por llamada.');
        }

        $prods = array_map([$this, 'normalizarProducto'], $productos);
        foreach ($prods as &$p) {
            $p['grupo'] = $this->clasificarGrupo($p['nombre']);
        }
        unset($p);

        $grupos = $this->segregarPorIncompatibilidades($prods);

        $todasLasCajas = [];
        foreach ($grupos as $grupo) {
            $cajas = $this->calcularCajasParaGrupo($grupo);
            $todasLasCajas = array_merge($todasLasCajas, $cajas);
        }

        $eficiencia = $this->calcularEficiencia($prods, $todasLasCajas);
        $resumen    = $this->generarResumen($todasLasCajas);

        return [
            'resumen'    => $resumen,
            'cajas'      => $todasLasCajas,
            'eficiencia' => round($eficiencia, 4),
        ];
    }

    private function normalizarProducto(array $p): array
    {
        $peso  = max(0.001, min(50,  (float) ($p['peso']   ?? 0.5)));
        $largo = max(0.5,   min(200, (float) ($p['largo']  ?? 30)));
        $ancho = max(0.5,   min(200, (float) ($p['ancho']  ?? 20)));
        $alto  = max(0.5,   min(200, (float) ($p['alto']   ?? 15)));

        return [
            'nombre'   => (string) ($p['nombre']   ?? 'Producto'),
            'cantidad' => max(1, (int) ($p['cantidad'] ?? 1)),
            'peso'     => $peso,
            'precio'   => (float) ($p['precio']   ?? 0),
            'largo'    => $largo,
            'ancho'    => $ancho,
            'alto'     => $alto,
        ];
    }

    private function clasificarGrupo(string $nombre): string
    {
        $n = $this->norm($nombre);

        if (preg_match('/lejia|cloro|detergente|suavizante|insecticida|desengrasante|limpiavidrios|veneno|raticida/', $n))
            return 'QUIMICO';

        if (preg_match('/fruta|verdura|ensalada|espinaca|lechuga|tomate|cebolla|papa|mango|palta|platano|naranja/', $n))
            return 'FRESCO';

        if (preg_match('/leche|yogurt|queso|mantequilla|embutido|jamon|salchicha|huevo|crema|lacteo/', $n))
            return 'REFRIGERADO';

        if (preg_match('/bebida|gaseosa|agua mineral|jugo|chicha|cerveza|vino|pisco|refresco|néctar|zumo/', $n))
            return 'BEBIDA';

        if (preg_match('/vitamina|suplemento|proteina|whey|capsula|tableta|medicamento|farmaco|termometro|glucosimetro|colágeno|omega/', $n))
            return 'MEDICO';

        if (preg_match('/camisa|camiseta|polo|pantalon|short|vestido|falda|blusa|ropa|tela|terno|chompa|casaca|buso|buzo|pijama|calzon|boxer|media|calcetin|corbata|bufanda|gorro|guante|chalina|pashmina|pañuelo/', $n))
            return 'TEXTIL';

        if (preg_match('/zapato|zapatilla|bota|sandalia|chinela|mocasin|calzado|tenis|sneaker|stiletto|ballerina/', $n))
            return 'CALZADO';

        if (preg_match('/mascota|perro|gato|comida para|alimento para|pellet|pienso|croqueta|collar mascota|correa mascota/', $n))
            return 'MASCOTA';

        if (preg_match('/arroz|azucar|aceite|harina|fideos|pasta|cereal|granola|quinua|kiwicha|avena|maca|cafe|te|chocolate|galleta|snack|maiz|lenteja|garbanzo|frijol|menestra|atun|conserva|mermelada|mantequilla vegetal|miel/', $n))
            return 'ALIMENTO';

        return 'GENERAL';
    }

    private function norm(string $s): string
    {
        $s = mb_strtolower($s);
        $s = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n', 'a', 'e', 'i', 'o', 'u', 'n'],
            $s
        );
        return preg_replace('/[^a-z0-9\s]/', '', $s);
    }

    private function segregarPorIncompatibilidades(array $productos): array
    {
        $grupos = [];

        foreach ($productos as $prod) {
            $asignado = false;

            foreach ($grupos as &$grupo) {
                $compatible = true;
                foreach ($grupo as $existente) {
                    if ($this->sonIncompatibles($prod['grupo'], $existente['grupo'])) {
                        $compatible = false;
                        break;
                    }
                }
                if ($compatible) {
                    $grupo[] = $prod;
                    $asignado = true;
                    break;
                }
            }
            unset($grupo);

            if (!$asignado) {
                $grupos[] = [$prod];
            }
        }

        return $grupos;
    }

    private function sonIncompatibles(string $ga, string $gb): bool
    {
        if ($ga === $gb) return false;
        foreach (self::PARES_INCOMPATIBLES as [$a, $b]) {
            if (($ga === $a && $gb === $b) || ($ga === $b && $gb === $a)) return true;
        }
        return false;
    }

    private function calcularCajasParaGrupo(array $grupo): array
    {
        $items = [];
        foreach ($grupo as $p) {
            for ($i = 0; $i < $p['cantidad']; $i++) {
                $items[] = $p;
            }
        }

        [$cajasUnicas, $esFallback] = $this->intentarCajaUnica($items);

        // FIX BUG 3: si ninguna caja cabía (fallback), forzar multi-caja directamente
        // sin comparar pesoFacturable (porque 1 caja de fallback no es físicamente válida)
        if ($esFallback) {
            return $this->intentarMultiCaja($items);
        }

        // Si resultó L o XL real, comparar si multi es más barato en peso facturable
        if (count($cajasUnicas) === 1 && in_array($cajasUnicas[0]['tipo'], ['L', 'XL'])) {
            $cajasMulti = $this->intentarMultiCaja($items);
            $pesoFact1  = array_sum(array_column($cajasUnicas, 'pesoFacturable'));
            $pesoFact2  = array_sum(array_column($cajasMulti,  'pesoFacturable'));
            return $pesoFact2 < $pesoFact1 ? $cajasMulti : $cajasUnicas;
        }

        return $cajasUnicas;
    }

    /**
     * @return array{0: array, 1: bool}  [$cajas, $esFallback]
     */
    private function intentarCajaUnica(array $items): array
    {
        $pesoTotal = array_sum(array_map(fn($p) => $p['peso'], $items));
        $volTotal  = array_sum(array_map(
            fn($p) => $p['largo'] * $p['ancho'] * $p['alto'],
            $items
        )) / 0.7;

        foreach ($this->cajas as $caja) {
            $volCaja   = $caja['largo'] * $caja['ancho'] * $caja['alto'];
            $pesoMaxKg = (float) $caja['peso_max_kg'];

            if ($pesoTotal <= $pesoMaxKg && $volTotal <= $volCaja) {
                // Caja real encontrada — no es fallback
                return [[$this->buildCajaResult($caja, $pesoTotal)], false];
            }
        }

        // FIX BUG 3: marcar como fallback para que calcularCajasParaGrupo fuerce multi-caja
        $xl = end($this->cajas);
        return [[$this->buildCajaResult($xl, $pesoTotal)], true];
    }

    private function intentarMultiCaja(array $items): array
    {
        $cajas        = [];
        $currentItems = [];
        $currentPeso  = 0;

        foreach ($items as $item) {
            $currentItems[] = $item;
            $currentPeso   += $item['peso'];
            $caja = $this->encontrarMejorCaja($currentItems);

            if ($caja === null) {
                // Este item hace overflow — sacar y cerrar el batch anterior
                array_pop($currentItems);
                $currentPeso -= $item['peso'];

                if (!empty($currentItems)) {
                    $c = $this->encontrarMejorCaja($currentItems);
                    $cajas[] = $this->buildCajaResult($c ?? end($this->cajas), $currentPeso);
                }

                // Empezar nuevo batch con el item que no cabía
                $currentItems = [$item];
                $currentPeso  = $item['peso'];
            }
        }

        if (!empty($currentItems)) {
            $c = $this->encontrarMejorCaja($currentItems);
            $cajas[] = $this->buildCajaResult($c ?? end($this->cajas), $currentPeso);
        }

        return empty($cajas) ? $this->intentarCajaUnica($items)[0] : $cajas;
    }

    private function encontrarMejorCaja(array $items): ?array
    {
        $pesoTotal = array_sum(array_map(fn($p) => $p['peso'], $items));
        $volTotal  = array_sum(array_map(fn($p) => $p['largo'] * $p['ancho'] * $p['alto'], $items)) / 0.7;

        foreach ($this->cajas as $caja) {
            $volCaja = $caja['largo'] * $caja['ancho'] * $caja['alto'];
            if ($pesoTotal <= (float) $caja['peso_max_kg'] && $volTotal <= $volCaja) {
                return $caja;
            }
        }
        return null;
    }

    private function buildCajaResult(array $caja, float $pesoReal): array
    {
        $pesoVol  = round(($caja['largo'] * $caja['ancho'] * $caja['alto']) / self::DIV_VOL_PESO, 3);
        $pesoFact = round(max($pesoReal, $pesoVol), 3);

        return [
            'tipo'            => $caja['nombre'],
            'largo'           => (int) $caja['largo'],
            'ancho'           => (int) $caja['ancho'],
            'alto'            => (int) $caja['alto'],
            'pesoReal'        => round($pesoReal, 3),
            'pesoVolumetrico' => $pesoVol,
            'pesoFacturable'  => $pesoFact,
        ];
    }

    private function calcularEficiencia(array $productos, array $cajas): float
    {
        $volProductos = array_sum(array_map(
            fn($p) => $p['largo'] * $p['ancho'] * $p['alto'] * $p['cantidad'],
            $productos
        ));
        $volCajas = array_sum(array_map(fn($c) => $c['largo'] * $c['ancho'] * $c['alto'], $cajas));
        return $volCajas > 0 ? min(1.0, $volProductos / $volCajas) : 0.0;
    }

    private function generarResumen(array $cajas): string
    {
        if (empty($cajas)) return '0 cajas';
        $tipos  = array_count_values(array_column($cajas, 'tipo'));
        $partes = [];
        foreach ($tipos as $tipo => $cant) {
            $partes[] = "{$cant} caja" . ($cant > 1 ? 's' : '') . " {$tipo}";
        }
        return implode(' + ', $partes);
    }
}
