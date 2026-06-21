<?php

declare(strict_types=1);

namespace App\Services\Logistics;

use Illuminate\Support\Facades\DB;

class BoxCalculatorService
{
    private const DIV_VOL_PESO  = 5000;
    private const MAX_PRODUCTOS = 200;

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

    private const EFICIENCIA = [
        'TEXTIL'      => 0.85,
        'CALZADO'     => 0.70,
        'ALIMENTO'    => 0.65,
        'GENERAL'     => 0.65,
        'MASCOTA'     => 0.65,
        'FRESCO'      => 0.60,
        'MEDICO'      => 0.55,
        'REFRIGERADO' => 0.50,
        'QUIMICO'     => 0.50,
        'BEBIDA'      => 0.45,
    ];

    private const PESO_MAT = [
        'TEXTIL'      => 0.03,
        'CALZADO'     => 0.05,
        'ALIMENTO'    => 0.07,
        'GENERAL'     => 0.07,
        'MASCOTA'     => 0.07,
        'FRESCO'      => 0.05,
        'MEDICO'      => 0.10,
        'REFRIGERADO' => 0.20,
        'QUIMICO'     => 0.15,
        'BEBIDA'      => 0.12,
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
                ['nombre' => 'XXS', 'largo' => 15, 'ancho' => 10, 'alto' => 10, 'peso_max_kg' =>  0.50],
                ['nombre' => 'XS',  'largo' => 25, 'ancho' => 20, 'alto' => 12, 'peso_max_kg' =>  1.00],
                ['nombre' => 'S',   'largo' => 30, 'ancho' => 20, 'alto' => 12, 'peso_max_kg' =>  2.00],
                ['nombre' => 'M',   'largo' => 40, 'ancho' => 30, 'alto' => 20, 'peso_max_kg' =>  5.00],
                ['nombre' => 'ML',  'largo' => 50, 'ancho' => 40, 'alto' => 25, 'peso_max_kg' => 10.00],
                ['nombre' => 'L',   'largo' => 60, 'ancho' => 40, 'alto' => 30, 'peso_max_kg' => 10.00],
                ['nombre' => 'XL',  'largo' => 80, 'ancho' => 60, 'alto' => 40, 'peso_max_kg' => 25.00],
            ];
        }
    }


    public function calcular(array $productos): array
    {
        if (empty($productos))
            throw new \InvalidArgumentException('Se requiere al menos un producto.');
        if (count($productos) > self::MAX_PRODUCTOS)
            throw new \InvalidArgumentException('Máximo ' . self::MAX_PRODUCTOS . ' productos por llamada.');

        $prods = array_map([$this, 'normalizarProducto'], $productos);

        foreach ($prods as &$p) {
            $p['grupo']      = $this->clasificarGrupo($p['nombre']);
            $p['factor_emp'] = self::EFICIENCIA[$p['grupo']] ?? 0.65;
            $p['peso_mat']   = self::PESO_MAT[$p['grupo']]   ?? 0.07;
        }
        unset($p);

        $grupos        = $this->segregarPorIncompatibilidades($prods);
        $todasLasCajas = [];

        foreach ($grupos as $grupo) {
            $todasLasCajas = array_merge($todasLasCajas, $this->calcularCajasParaGrupo($grupo));
        }

        return [
            'resumen'    => $this->generarResumen($todasLasCajas),
            'cajas'      => $todasLasCajas,
            'eficiencia' => round($this->calcularEficiencia($prods, $todasLasCajas), 4),
        ];
    }


    private function normalizarProducto(array $p): array
    {
        return [
            'nombre'   => (string) ($p['nombre']   ?? 'Producto'),
            'cantidad' => max(1,     (int)   ($p['cantidad'] ?? 1)),
            'peso'     => max(0.001, min(50,  (float) ($p['peso']  ?? 0.5))),
            'precio'   => (float) ($p['precio']   ?? 0),
            'largo'    => max(0.5,   min(200, (float) ($p['largo'] ?? 30))),
            'ancho'    => max(0.5,   min(200, (float) ($p['ancho'] ?? 20))),
            'alto'     => max(0.5,   min(200, (float) ($p['alto']  ?? 15))),
        ];
    }


    private function clasificarGrupo(string $nombre): string
    {
        $n = $this->norm($nombre);

        if (preg_match(
            '/lejia|hipoclorito|insecticida|raticida|veneno\b|' .
                'desengrasante|limpiavidrios|amoniaco|blanqueador|clorox|' .
                'quitamanchas|quitagrasa|desincrustante|' .
                'detergente\b|suavizante\b|desinfectante\b|' .
                'limpiador\b|limpiapiso|limpiahogar|limpieza de\b/',
            $n
        )) return 'QUIMICO';

        if (preg_match(
            '/\bagua\b|agua mineral|agua alcalin|agua de mesa|' .
                'bebida\b|rehidratante|energizante|electrolit|' .
                'gaseosa\b|cerveza\b|\bvino\b|pisco\b|\blicor\b|\bron\b|whisky|' .
                'aguardiente|vodka\b|cachina|macerado|' .
                '\bjugo\b|\bzumo\b|refresco\b|\bnectar\b|chicha\b|' .
                'kombucha|kefir de agua|bebida fermentada|' .
                'shot\b|tonica\b|isotonica|limonada\b|naranjada\b|' .
                'smoothie|batido\b/',
            $n
        )) return 'BEBIDA';

        if (preg_match(
            '/\bfresa|\barandano|\baguaymanto|\bmora\b|\bframbuesa|\bsauco|\bcereza|' .
                '\bberry\b|berries|\bgranadilla|\bmaracuya|\bgranada\b|\bpitahaya|\btuna\b|' .
                '\bguanabana|\bchirimoya|\bguayaba|\bpacae|\balgarrobo|\blucuma|' .
                '\bmanzana|\bpera\b|\bmembrillo|\bmelocoton|\bdurazno|\bmango|\babridor\b|' .
                '\bnaranja|\bmandarina|\blima\b|\blimon|\btoronja|' .
                '\bpalta|\bpapaya|\bpina\b|' .
                '\bplatano|\buva\b|\bsandia|\bmelon\b|\btamarindo|\bcarambola|\bciruela|' .
                '\bcoco fresco|\bfrutas frescas|\bfruta fresca|\bfruta organica/',
            $n
        )) return 'FRESCO';

        if (preg_match(
            '/\bberenjena|\bbeterraga|\bbrocoli|\bcoliflor|\balcachofa|\bcaigua|' .
                '\bcebolla|\bajo\b|\brocoto|\baji\b|\bcurcuma|\bkion\b|\bjengibre\b|' .
                '\bchoclo|\bmaiz tierno|\bmaiz morado|' .
                '\bcol\b|\besparrago|\bespinaca|\bacelga|\balbahaca|\bculantro\b|\bapio\b|' .
                '\bporo\b|\bperejil|\bhongo|\bseta\b|\blechuga|' .
                '\bverdura|\bpapa\b|\bcamote|\byuca\b|\btomate|\bpimiento|' .
                '\bvainita|\barveja|\bfrejolito|\bolluquito|\bzanahoria|\bzapallo|' .
                '\bgerminado|\bbrote\b|\bmicroverdes|\bhierbas frescas/',
            $n
        )) return 'FRESCO';

        if (preg_match(
            '/\bleche\b|leche fresca|leche entera|leche descremada|' .
                'yogur\b|yogurt\b|\bqueso\b|mantequilla\b|crema de leche|' .
                'kefir\b|crema agria|queso fresco|queso crema|' .
                'embutido|chorizo\b|hotdog\b|\bjamon\b|salame\b|salchichon|salchicha\b|' .
                'cecina\b|mortadela|\bhuevo/',
            $n
        )) return 'REFRIGERADO';

        if (preg_match(
            '/vitamina\b|suplemento|proteina\b|\bwhey\b|capsula\b|tableta\b|' .
                'pastilla\b|medicamento|farmaco|' .
                'termometro|glucosimetro|tensimetro|tensiómetro|esfigmo|' .
                'nebulizador|estetoscopio|otoscopio|oximetro|pulsioximetro|' .
                'colageno|omega.?3|omega.?6|omega.?9|probiotico|prebiotico|' .
                'magnesio\b|zinc\b|\bhierro\b|calcio\b|biotina\b|melatonina|' .
                'curcumina|resveratrol|coenzima|glutationa|' .
                'aceite esencial|tintura madre|esencia floral|' .
                'homeopatia|fitoterapia|fitomedicin|extracto de|' .
                'espirulina|clorela|chlorella|moringa\b|ashwagandha|maca andina|' .
                'sistema inmun|sistema nervios|sistema muscular|sistema oseo|' .
                'sistema digest|sistema circulat|sistema respirat|' .
                'bienestar emocional|medicina natural/',
            $n
        )) return 'MEDICO';

        if (preg_match(
            '/zapato|zapatilla|bota\b|botas\b|sandalia|chinela|mocasin|' .
                'calzado\b|\btenis\b|sneaker|stiletto|ballerina|chimpun|hiking\b|' .
                'trekking.*calzado|calzado.*trekking/',
            $n
        )) return 'CALZADO';

        if (preg_match(
            '/\bropa\b|camiseta\b|\bpolo\b|pantalon\b|\bshort\b|\bbuzo\b|' .
                'pijama\b|boxer\b|calzon\b|calcetin\b|\bmedias\b|' .
                'bufanda|gorro\b|\bguante\b|chalina|pashmina|panuelo\b|' .
                'camisa\b|\bblusa\b|vestido\b|\bfalda\b|\bterno\b|' .
                'chompa|casaca\b|polera\b|\bbra\b|licra\b|' .
                'camiseta de futbol|uniforme\b|sudadera|jersey\b|' .
                'chaleco\b|anorak|impermeable\b|cortaviento|' .
                'ropa.*bebe|ropa.*deportiv|ropa.*interior/',
            $n
        )) return 'TEXTIL';

        if (preg_match(
            '/mascota\b|perro\b|gato\b|felino|canino|' .
                '\bave\b|pajaro|canario|loro\b|periquito|' .
                'peces\b|\bpez\b|acuario|conejo\b|hamster|cobaya|' .
                'tortuga\b|rana\b|reptil|terrario|' .
                'croqueta\b|pienso\b|pellet\b|' .
                'alimento.*para\b.*(perro|gato|ave|pez|conejo|hamster)|' .
                'comida.*para\b.*(perro|gato)|premio.*perro|snack.*gato|' .
                'arena.*gato|arena sanitaria|' .
                'collar\b.*(perro|gato|mascota)|correa\b.*(perro|mascota)|' .
                'antipulgas|desparasitante|antiparasit|pipeta.*perro|' .
                'comedero.*mascota|bebedero.*mascota|cama.*mascota|' .
                'juguete.*perro|juguete.*gato/',
            $n
        )) return 'MASCOTA';

        if (preg_match(
            '/\barroz\b|azucar\b|\baceite\b|harina\b|fideos\b|\bpasta\b|' .
                'cereal\b|granola\b|quinua|quinoa|kiwicha|avena\b|\bmaca\b|' .
                'cafe\b|cafeto|infusion\b|manzanilla\b|hierba.*luisa|' .
                '\bte\b|te verde|te negro|te blanco|te herbal|matcha\b|' .
                'chocolate\b|cacao\b|cocoa\b|' .
                'galleta\b|\bsnack\b|lenteja\b|garbanzo|frijol\b|' .
                'menestra|atun\b|conserva\b|mermelada|miel\b|\bsal\b|' .
                'condimento|especia\b|soja\b|tofu\b|tempeh\b|' .
                'spirulina\b|moringa\b|coco rallado|' .
                'muesli|tostada\b|\bpan\b|bizcocho|paneton|pastel\b|\btorta\b|' .
                'reposteria|kekes|caramelo|fruto seco|' .
                'mani\b|almendra\b|nuez\b|pecana\b|pepita\b|semilla\b|' .
                'chia\b|linaza\b|ajonjoli\b|canela\b|oregano\b|' .
                'achiote\b|comino\b|pimienta\b|cumin\b|' .
                'vinagre\b|salsa\b|mayonesa|mostaza\b|ketchup\b|sillao\b|' .
                'crema de mani|mantequilla de mani|mantequilla de almendra|' .
                'algarrobina|lucuma en polvo|' .
                'endulzante\b|stevia\b|eritritol|xilitol|azucar de coco|' .
                'superfood|superalimento|aceitunas\b|aceituna\b|' .
                'arroz integral|arroz rojo|arroz negro|' .
                '\bhuevo\b.*codorniz|huevo de codorniz/',
            $n
        )) return 'ALIMENTO';

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
        return preg_replace('/[^a-z0-9\s]/', ' ', $s);
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
            if (!$asignado) $grupos[] = [$prod];
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
            for ($i = 0; $i < $p['cantidad']; $i++) $items[] = $p;
        }

        [$cajasUnicas, $esFallback] = $this->intentarCajaUnica($items);

        if ($esFallback) return $this->intentarMultiCaja($items);

        if (count($cajasUnicas) === 1 && in_array($cajasUnicas[0]['tipo'], ['L', 'XL'])) {
            $cajasMulti = $this->intentarMultiCaja($items);
            $pf1 = array_sum(array_column($cajasUnicas, 'pesoFacturable'));
            $pf2 = array_sum(array_column($cajasMulti,  'pesoFacturable'));
            return $pf2 < $pf1 ? $cajasMulti : $cajasUnicas;
        }

        return $cajasUnicas;
    }

    /** @return array{0: array, 1: bool} [$cajas, $esFallback] */
    private function intentarCajaUnica(array $items): array
    {
        $pesoTotal = $this->pesoEfectivo($items);
        $volTotal  = $this->volEfectivo($items);

        foreach ($this->cajas as $caja) {
            $volCaja = $caja['largo'] * $caja['ancho'] * $caja['alto'];
            if ($pesoTotal <= (float) $caja['peso_max_kg'] && $volTotal <= $volCaja) {
                return [[$this->buildCajaResult($caja, $pesoTotal)], false];
            }
        }

        $xl = end($this->cajas);
        return [[$this->buildCajaResult($xl, $pesoTotal)], true];
    }

    private function intentarMultiCaja(array $items): array
    {
        $cajas        = [];
        $currentItems = [];
        $currentPeso  = 0.0;

        foreach ($items as $item) {
            $currentItems[] = $item;
            $currentPeso   += $item['peso'] + $item['peso_mat'];
            $caja = $this->encontrarMejorCaja($currentItems);

            if ($caja === null) {
                array_pop($currentItems);
                $currentPeso -= $item['peso'] + $item['peso_mat'];

                if (!empty($currentItems)) {
                    $c = $this->encontrarMejorCaja($currentItems);
                    $cajas[] = $this->buildCajaResult($c ?? end($this->cajas), $currentPeso);
                }

                $currentItems = [$item];
                $currentPeso  = $item['peso'] + $item['peso_mat'];
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
        $pesoTotal = $this->pesoEfectivo($items);
        $volTotal  = $this->volEfectivo($items);

        foreach ($this->cajas as $caja) {
            $volCaja = $caja['largo'] * $caja['ancho'] * $caja['alto'];
            if ($pesoTotal <= (float) $caja['peso_max_kg'] && $volTotal <= $volCaja) {
                return $caja;
            }
        }
        return null;
    }

    private function volEfectivo(array $items): float
    {
        if (empty($items)) return 0.0;
        $factor   = min(array_map(fn($p) => $p['factor_emp'] ?? 0.65, $items));
        $volBruto = array_sum(array_map(fn($p) => $p['largo'] * $p['ancho'] * $p['alto'], $items));
        return $volBruto / $factor;
    }

    private function pesoEfectivo(array $items): float
    {
        return array_sum(array_map(fn($p) => $p['peso'] + ($p['peso_mat'] ?? 0.07), $items));
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
