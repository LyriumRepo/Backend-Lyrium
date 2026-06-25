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

        $productIds = array_values(array_filter(array_column($prods, 'product_id')));
        $catsPorProducto = [];

        if (!empty($productIds)) {
            $rows = DB::table('category_product as cp')
                ->join('categories as c', 'cp.category_id', '=', 'c.id')
                ->leftJoin('categories as p', 'c.parent_id', '=', 'p.id')
                ->whereIn('cp.product_id', $productIds)
                ->select('cp.product_id', 'c.name as cat', 'p.name as parent')
                ->get();

            foreach ($rows as $row) {
                if (!isset($catsPorProducto[$row->product_id])) {
                    $catsPorProducto[$row->product_id] = [
                        'cat'    => $row->cat,
                        'parent' => $row->parent ?? null,
                    ];
                }
            }
        }

        $descsPorProducto = [];
        if (!empty($productIds)) {
            $descsPorProducto = DB::table('products')
                ->whereIn('id', $productIds)
                ->pluck('description', 'id')
                ->toArray();
        }

        foreach ($prods as &$p) {
            $pid = $p['product_id'] ?? 0;

            $catInfo         = $catsPorProducto[$pid] ?? null;
            $p['grupo']      = $this->clasificarGrupo(
                $p['nombre'],
                $catInfo['cat']    ?? null,
                $catInfo['parent'] ?? null
            );
            $p['factor_emp'] = self::EFICIENCIA[$p['grupo']] ?? 0.65;
            $p['peso_mat']   = self::PESO_MAT[$p['grupo']]   ?? 0.07;

            $rawDesc = $descsPorProducto[$pid] ?? '';
            if (!empty($rawDesc)) {
                $material = $this->detectarMaterial((string) $rawDesc);
                $this->aplicarAjusteMaterial($p, $material);
            }
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
            'nombre'     => (string) ($p['nombre']     ?? 'Producto'),
            'cantidad'   => max(1,     (int)   ($p['cantidad']   ?? 1)),
            'peso'       => max(0.001, min(50,  (float) ($p['peso']    ?? 0.5))),
            'precio'     => (float) ($p['precio']     ?? 0),
            'largo'      => max(0.5,   min(200, (float) ($p['largo']   ?? 30))),
            'ancho'      => max(0.5,   min(200, (float) ($p['ancho']   ?? 20))),
            'alto'       => max(0.5,   min(200, (float) ($p['alto']    ?? 15))),
            'product_id' => isset($p['product_id']) ? (int) $p['product_id'] : null,
        ];
    }


        private function clasificarGrupo(string $nombre, ?string $catBD = null, ?string $parentBD = null): string
    {
        if ($catBD !== null) {
            $grupo = $this->mapearCategoriaBD($catBD, $parentBD);
            if ($grupo !== 'GENERAL') return $grupo;
        }

        return $this->clasificarPorNombre($nombre);
    }

        private function mapearCategoriaBD(string $cat, ?string $parent = null): string
    {
        $c    = $this->norm($cat);
        $p    = $parent ? $this->norm($parent) : '';
        $full = trim("$p $c");   

        if (preg_match(
            '/limpieza|desinfect|quimico|lejia|cloro\b|detergente|insecticida|' .
            'blanqueador|proteccion.*limpieza|limpieza.*bano|limpieza.*cocina|' .
            'limpieza.*hogar|limpieza.*ropa|accesorios de limpieza|' .
            'equipo de proteccion/',
            $full)) return 'QUIMICO';

        if (preg_match(
            '/\bbebidas?\b|aguas\b|gaseosas|cervezas|jugos\b|licores|' .
            'vinos\b|rehidratantes|kombucha|otras bebidas/',
            $full)) return 'BEBIDA';

        if (preg_match('/\bfrutas?\b/', $full)) return 'FRESCO';
        if (preg_match(
            '/fresas|arandanos|aguaymantos|moras\b|frambuesas|sauco|cerezas|berries|' .
            'granadillas|maracuyas|granadas|pitahayas|tunas\b|guanabanas|chirimoyas|' .
            'guayabas|lucumas|manzanas|peras\b|membrillos|melocotones|duraznos|mangos|' .
            'naranjas|mandarinas|paltas|papayas|pinas\b|pepinos|platanos|uvas\b|' .
            'sandias|melones|tamarindos|carambolas|ciruelas|cocos\b|jugos naturales/',
            $full)) return 'FRESCO';

        if (preg_match('/\bverduras?\b/', $full)) return 'FRESCO';
        if (preg_match(
            '/berenjenas|beterragas|brocolis|coliflores|alcachofas|caiguas|' .
            'cebollas.*ajos|ajos\b|rocotos|ajies|curcumas|kiones|choclo|coles\b|' .
            'esparragos|hongos.*setas|lechugas.*espinacas|espinacas|acelgas|' .
            'albahacas|culantros|apios|poros\b|perejiles|papas.*camotes|' .
            'tomates.*pepinos|vainitas.*arvejas|zanahorias|zapallos/',
            $full)) return 'FRESCO';

        if (preg_match(
            '/lacteos.*frescos|lacteos\b|embutidos|fiambres|' .
            'chorizos|hotdogs|salames|salchichones|salchichas|mortadela|cecina/',
            $full)) return 'REFRIGERADO';
        if (preg_match('/^(huevos|leches|mantequillas|quesos|yogures|yogurt|jamones|kefir)$/', $c))
            return 'REFRIGERADO';

        if (preg_match(
            '/suplementos vitaminicos|suplemento vitaminico|' .
            'bienestar emocional|medicina natural|' .
            'equipos.*medicos|dispositivos.*medicos/',
            $full)) return 'MEDICO';
        if (preg_match(
            '/cardiologia|radiologia|dermatologia|endocrinologia|enfermeria|' .
            'gastroenterologia|geriatria|ginecologia|laboratorio clinico|' .
            'neurologia|nutriologia|odontologia|oftalmologia|oncologia|' .
            'pediatria|psicologia|psiquiatria|reumatologia|neumologia|' .
            'medicina fisica|rehabilitacion/',
            $full)) return 'MEDICO';
        if (preg_match(
            '/sistema circulatorio|sistema digestivo|sistema excretor|' .
            'sistema inmunologico|sistema linfatico|sistema muscular|' .
            'sistema nervioso|sistema oseo|sistema reproductivo|' .
            'sistema respiratorio|gusto\b|oido\b|olfato\b|vista\b|tacto\b/',
            $full)) return 'MEDICO';
        if (preg_match('/suplementos nutricionales/', $c))
            return 'MEDICO';

        if (preg_match('/calzado/', $p) || preg_match('/\bcalzado\b/', $c))
            return 'CALZADO';

        if (preg_match('/\bropa\b/', $p) || preg_match('/\bropa\b/', $c))
            return 'TEXTIL';
        if (preg_match(
            '/^(bras|buzos|camisetas de futbol|casacas y poleras|faldas|' .
            'licras|pantalones|polos|shorts|vestidos|' .
            'bebe mujercita|bebe varoncito|infante mujercita|infante varoncito)$/',
            $c)) return 'TEXTIL';

        if (preg_match(
            '/mascotas?|animales de granja|cobayas|conejos|hamsters|pajaros|' .
            'peces\b|perros|gatos|tortugas|ranas.*sapos/',
            $full)) return 'MASCOTA';

        if (preg_match(
            '/\babarrotes?\b|dulces.*snacks|panaderia|pasteleria|' .
            'infusiones|cacaos|cafes\b|cocoas|endulzantes|mermeladas|' .
            'kekes.*bizcochos|panes\b|panetones|pasteles|reposteria|' .
            'snacks|galletas|caramelos|chocolates|frutos secos|' .
            'conservas|aceites\b|harinas|fideos|pastas\b|cereales.*avenas|' .
            'sales.*condimentos/',
            $full)) return 'ALIMENTO';
        if (preg_match('/^desayunos?$/', $p)) return 'ALIMENTO';

        return 'GENERAL';
    }

        private function clasificarPorNombre(string $nombre): string
    {
        $n = $this->norm($nombre);

        if (preg_match(
            '/lejia|hipoclorito|insecticida|raticida|veneno\b|' .
            'desengrasante|limpiavidrios|amoniaco|blanqueador|clorox|' .
            'quitamanchas|quitagrasa|desincrustante|' .
            'detergente\b|suavizante\b|desinfectante\b|' .
            'limpiador\b|limpiapiso|limpiahogar|limpieza de\b/',
            $n)) return 'QUIMICO';

        if (preg_match(
            '/\bagua\b|agua mineral|agua alcalin|agua de mesa|' .
            'bebida\b|rehidratante|energizante|electrolit|' .
            'gaseosa\b|cerveza\b|\bvino\b|pisco\b|\blicor\b|\bron\b|whisky|' .
            'aguardiente|vodka\b|\bjugo\b|\bzumo\b|refresco\b|\bnectar\b|' .
            'chicha\b|kombucha|kefir de agua|shot\b|tonica\b|' .
            'limonada\b|naranjada\b|smoothie|batido\b/',
            $n)) return 'BEBIDA';

        if (preg_match(
            '/\bfresa|\barandano|\baguaymanto|\bmora\b|\bframbuesa|\bsauco|\bcereza|' .
            '\bberry\b|berries|\bgranadilla|\bmaracuya|\bgranada\b|\bpitahaya|\btuna\b|' .
            '\bguanabana|\bchirimoya|\bguayaba|\blucuma|' .
            '\bmanzana|\bpera\b|\bmembrillo|\bmelocoton|\bdurazno|\bmango|' .
            '\bnaranja|\bmandarina|\blima\b|\blimon|\btoronja|' .
            '\bpalta|\bpapaya|\bpina\b|' .
            '\bplatano|\buva\b|\bsandia|\bmelon\b|\btamarindo|\bcarambola|\bciruela|' .
            '\bcoco fresco|\bfrutas frescas|\bfruta fresca|\bfruta organica/',
            $n)) return 'FRESCO';

        if (preg_match(
            '/\bberenjena|\bbeterraga|\bbrocoli|\bcoliflor|\balcachofa|\bcaigua|' .
            '\bcebolla|\bajo\b|\brocoto|\baji\b|\bcurcuma|\bkion\b|\bjengibre\b|' .
            '\bchoclo|\bmaiz tierno|\bmaiz morado|' .
            '\bcol\b|\besparrago|\bespinaca|\bacelga|\balbahaca|\bculantro\b|\bapio\b|' .
            '\bporo\b|\bperejil|\bhongo|\bseta\b|\blechuga|' .
            '\bverdura|\bpapa\b|\bcamote|\byuca\b|\btomate|\bpimiento|' .
            '\bvainita|\barveja|\bfrejolito|\bolluquito|\bzanahoria|\bzapallo|' .
            '\bgerminado|\bbrote\b|\bmicroverdes|\bhierbas frescas/',
            $n)) return 'FRESCO';

        if (preg_match(
            '/\bleche\b|leche fresca|leche entera|leche descremada|' .
            'yogur\b|yogurt\b|\bqueso\b|mantequilla\b|crema de leche|' .
            'kefir\b|crema agria|' .
            'embutido|chorizo\b|hotdog\b|\bjamon\b|salame\b|salchichon|salchicha\b|' .
            'cecina\b|mortadela|\bhuevo/',
            $n)) return 'REFRIGERADO';

        if (preg_match(
            '/vitamina\b|suplemento|proteina\b|\bwhey\b|capsula\b|tableta\b|' .
            'pastilla\b|medicamento|farmaco|' .
            'termometro|glucosimetro|tensimetro|tensiómetro|' .
            'nebulizador|estetoscopio|oximetro|pulsioximetro|' .
            'colageno|omega.?3|omega.?6|omega.?9|probiotico|prebiotico|' .
            'magnesio\b|zinc\b|\bhierro\b|calcio\b|biotina\b|melatonina|' .
            'curcumina|resveratrol|coenzima|glutationa|' .
            'aceite esencial|tintura madre|esencia floral|homeopatia|' .
            'fitoterapia|fitomedicin|extracto de|' .
            'espirulina|clorela|chlorella|moringa\b|ashwagandha|maca andina|' .
            'sistema inmun|sistema nervios|sistema muscular|sistema oseo|' .
            'sistema digest|sistema circulat|sistema respirat|' .
            'bienestar emocional|medicina natural/',
            $n)) return 'MEDICO';

        if (preg_match(
            '/zapato|zapatilla|bota\b|botas\b|sandalia|chinela|mocasin|' .
            'calzado\b|\btenis\b|sneaker|stiletto|ballerina|chimpun|hiking\b/',
            $n)) return 'CALZADO';

        if (preg_match(
            '/\bropa\b|camiseta\b|\bpolo\b|pantalon\b|\bshort\b|\bbuzo\b|' .
            'pijama\b|boxer\b|calzon\b|calcetin\b|\bmedias\b|' .
            'bufanda|gorro\b|\bguante\b|chalina|pashmina|panuelo\b|' .
            'camisa\b|\bblusa\b|vestido\b|\bfalda\b|\bterno\b|' .
            'chompa|casaca\b|polera\b|\bbra\b|licra\b|' .
            'uniforme\b|sudadera|jersey\b|chaleco\b/',
            $n)) return 'TEXTIL';

        if (preg_match(
            '/mascota\b|perro\b|gato\b|felino|canino|' .
            '\bave\b|pajaro|canario|loro\b|periquito|' .
            'peces\b|\bpez\b|acuario|conejo\b|hamster|cobaya|' .
            'tortuga\b|\brana\b|reptil|terrario|' .
            'croqueta\b|pienso\b|pellet\b|' .
            'alimento.*para\b.*(perro|gato|ave|pez|conejo|hamster)|' .
            'comida.*para\b.*(perro|gato)|arena.*gato|arena sanitaria|' .
            'antipulgas|desparasitante|antiparasit|' .
            'comedero.*mascota|bebedero.*mascota|cama.*mascota/',
            $n)) return 'MASCOTA';

        if (preg_match(
            '/\barroz|\bazucar|\baceite\b|\bharina|\bfideos|\bpasta\b|' .
            '\bcereal|\bgranola|\bquinua|\bquinoa|\bkiwicha|\bavena\b|\bmaca\b|' .
            '\bcafe\b|\bcafeto|\binfusion|\bmanzanilla|\bhierba luisa|' .
            '\bte\b|\bte verde|\bte negro|\bte blanco|\bte herbal|\bmatcha\b|' .
            '\bchocolate|\bcacao\b|\bcocoa\b|' .
            '\bgalleta|\bsnack|\blenteja|\bgarbanzo|\bfrijol\b|' .
            '\bmenestra|\batun\b|\bconserva|\bmermelada|\bmiel\b|\bsal\b|' .
            '\bcondimento|\bespecia\b|\bsoja\b|\btofu\b|\btempeh\b|' .
            '\bspirulina\b|\bmoringa\b|\bcoco rallado|' .
            '\bmuesli|\btostada|\bpan\b|\bbizcocho|\bpaneton|\bpastel\b|\btorta\b|' .
            '\breposteria|\bkekes|\bcaramelo|\bfruto seco|' .
            '\bmani\b|\balmendra|\bnuez\b|\bpecana|\bpepita\b|\bsemilla\b|' .
            '\bchia\b|\blinaza\b|\bajonjoli\b|\bcanela\b|\boregano\b|' .
            '\bachiote\b|\bcomino\b|\bpimienta\b|' .
            '\bvinagre\b|\bsalsa\b|\bmayonesa|\bmostaza\b|\bketchup\b|\bsillao\b|' .
            '\bcrema de mani|\bmantequilla de mani|\bmantequilla de almendra|' .
            '\balgarrobina|\blucuma en polvo|' .
            '\bendulzante\b|\bstevia\b|\beritritol|\bxilitol|\bazucar de coco|' .
            '\baceitunas\b|\baceituna\b|' .
            '\bsuperfood|\bsuperalimento|\barroz integral|\barroz rojo|\barroz negro/',
            $n)) return 'ALIMENTO';

        return 'GENERAL';
    }


        private function detectarMaterial(string $descripcion): array
    {
        $d = $this->norm(strip_tags($descripcion));

        return [
            'vidrio' => (bool) preg_match(
                '/vidrio|cristal|glass|' .
                'frasco de vidrio|botella de vidrio|ampolla|vial|' .
                'envase de vidrio|tarro de vidrio|' .
                'pote de vidrio|copa de vidrio/',
                $d
            ),
            'ceramica' => (bool) preg_match(
                '/ceramica|ceramico|porcelana|barro|' .
                'arcilla|terracota|loza|gres/',
                $d
            ),
            'electronico' => (bool) preg_match(
                '/electronico|electronica|digital|tablet|' .
                'celular|smartphone|laptop|computadora|' .
                'cargador|bateria|batería|pantalla|' .
                'dispositivo|sensor|circuito|led|usb|' .
                'bluetooth|wifi|inalambrico|recargable/',
                $d
            ),
            'liquido' => (bool) preg_match(
                '/liquido|líquido|en gel|gel|suero|' .
                'serum|jarabe|tonico|tónico|' .
                'ml|mililitros|litros?|' .
                'spray|atomizador|dosificador|gotero/' ,
                $d
            ),
            'fragil' => (bool) preg_match(
                '/fragil|frágil|delicado|delicada|' .
                'manejese con cuidado|handle with care|breakable|' .
                'no golpear|no comprimir|mantener vertical|' .
                'este lado arriba/',
                $d
            ),
        ];
    }

        private function aplicarAjusteMaterial(array &$p, array $mat): void
    {
        if (!array_filter($mat)) return;

        $factor = $p['factor_emp'];
        $peso   = $p['peso_mat'];

        if ($mat['vidrio']) {
            $factor = min($factor, 0.45);
            $peso   = max($peso,   0.15);
        }

        if ($mat['ceramica']) {
            $factor = min($factor, 0.50);
            $peso   = max($peso,   0.12);
        }

        if ($mat['electronico']) {
            $factor = min($factor, 0.55);
            $peso   = max($peso,   0.12);
        }

        if ($mat['liquido'] && !$mat['vidrio']) {
            $factor = min($factor, 0.55);
            $peso   = max($peso,   0.10);
        }

        if ($mat['fragil'] && $factor > 0.55) {
            $factor = min($factor, 0.55);
            $peso   = max($peso,   0.10);
        }

        $p['factor_emp'] = $factor;
        $p['peso_mat']   = $peso;
    }

    private function norm(string $s): string
    {
        $s = mb_strtolower($s);
        $s = str_replace(
            ['á','é','í','ó','ú','ü','ñ','Á','É','Í','Ó','Ú','Ñ'],
            ['a','e','i','o','u','u','n','a','e','i','o','u','n'],
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
                if ($compatible) { $grupo[] = $prod; $asignado = true; break; }
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
        $pesoTotal     = $this->pesoEfectivo($items);
        $volTotal      = $this->volEfectivo($items);
        $itemMasGrande = $this->itemConMayorDimension($items);

        foreach ($this->cajas as $caja) {
            $volCaja = $caja['largo'] * $caja['ancho'] * $caja['alto'];
            if ($pesoTotal > (float) $caja['peso_max_kg']) continue;
            if ($volTotal  > $volCaja) continue;
            if (!$this->cabeEnCaja($itemMasGrande, $caja)) continue;
            return [[$this->buildCajaResult($caja, $pesoTotal)], false];
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
        $pesoTotal    = $this->pesoEfectivo($items);
        $volTotal     = $this->volEfectivo($items);
        $itemMasGrande = $this->itemConMayorDimension($items);

        foreach ($this->cajas as $caja) {
            $volCaja = $caja['largo'] * $caja['ancho'] * $caja['alto'];
            if ($pesoTotal > (float) $caja['peso_max_kg']) continue;
            if ($volTotal  > $volCaja) continue;
            if (!$this->cabeEnCaja($itemMasGrande, $caja)) continue;
            return $caja;
        }
        return null;
    }

        private function cabeEnCaja(array $item, array $caja): bool
    {
        $dims = [$item['largo'], $item['ancho'], $item['alto']];
        $box  = [$caja['largo'], $caja['ancho'], $caja['alto']];
        sort($dims);
        sort($box);
        return $dims[0] <= $box[0]
            && $dims[1] <= $box[1]
            && $dims[2] <= $box[2];
    }

        private function itemConMayorDimension(array $items): array
    {
        $mayor = $items[0];
        $maxDim = max($mayor['largo'], $mayor['ancho'], $mayor['alto']);
        foreach ($items as $item) {
            $d = max($item['largo'], $item['ancho'], $item['alto']);
            if ($d > $maxDim) { $mayor = $item; $maxDim = $d; }
        }
        return $mayor;
    }


        private function volEfectivo(array $items): float
    {
        if (empty($items)) return 0.0;
        $factorBase   = min(array_map(fn($p) => $p['factor_emp'] ?? 0.65, $items));
        $factorEscalado = $this->factorConCantidad($factorBase, count($items));
        $volBruto     = array_sum(array_map(fn($p) => $p['largo'] * $p['ancho'] * $p['alto'], $items));
        return $volBruto / $factorEscalado;
    }

        private function factorConCantidad(float $factorBase, int $n): float
    {
        if ($n <= 1) return $factorBase;
        $mejora = (1.0 - $factorBase) * (1.0 - 1.0 / sqrt((float) $n)) * 0.40;
        return min(0.85, $factorBase + $mejora);
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
