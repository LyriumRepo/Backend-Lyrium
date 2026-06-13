<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\GlossaryEntry;
use Illuminate\Database\Seeder;

final class GlossaryEntrySeeder extends Seeder
{
    public function run(): void
    {
        $entries = [
            [
                'key' => 'ENTEL',
                'description' => 'Pago Servicio línea corporativa móvil ENTEL',
                'search_patterns' => ['ENTEL', 'ENTE'],
                'default_amount' => 89.90,
                'account_reference' => 'ENTE000937093420',
            ],
            [
                'key' => 'M_FIBRA',
                'description' => 'Pago Servicio línea corporativa fija M FIBRA',
                'search_patterns' => ['M FIBRA', 'MI FIBRA', 'FIBRA', 'MI F'],
                'default_amount' => 100.00,
                'account_reference' => 'MI F020612731838',
            ],
            [
                'key' => 'TRANSF_TERCEROS',
                'description' => 'Transferencia a terceros',
                'search_patterns' => ['TRAN.CTAS.TERC.BM'],
            ],
            [
                'key' => 'CONTADOR',
                'description' => 'Transferencia a Contador',
                'search_patterns' => ['CONTADOR', 'CONTABLE'],
                'default_amount' => 1200.00,
                'account_reference' => 'TRAN.CTAS.TERC.BM',
            ],
            [
                'key' => 'REDES_CONTENIDO',
                'description' => 'Transferencia por servicio de Contenido de Redes',
                'search_patterns' => ['CONTENIDO DE REDES', 'CONTENIDO REDES'],
                'account_reference' => 'TRAN.CTAS.TERC.BM',
            ],
            [
                'key' => 'REDES_GESTION',
                'description' => 'Transferencia por servicio de Gestión de Redes',
                'search_patterns' => ['GESTIÓN DE REDES', 'GESTION DE REDES'],
                'account_reference' => 'TRAN.CTAS.TERC.BM',
            ],
            [
                'key' => 'BCP_MANTENIMIENTO_PRINCIPAL',
                'description' => 'Mantenimiento principal cuenta BCP',
                'search_patterns' => ['COM.MANTENIM', 'MANTENIMIENTO CTAS'],
                'account_reference' => 'COM.MANTENIM',
            ],
            [
                'key' => 'BCP_MANTENIMIENTO_ADICIONAL1',
                'description' => 'Mantenimiento adicional cuenta BCP (1)',
                'search_patterns' => ['MANT TD ADIC', 'MANTENIMIENTO ADICIONAL'],
                'account_reference' => 'MANT TD ADIC NEG',
            ],
            [
                'key' => 'BCP_MANTENIMIENTO_ADICIONAL2',
                'description' => 'Mantenimiento adicional cuenta BCP (2)',
                'search_patterns' => ['ENVIO.EST.CTA'],
                'account_reference' => 'ENVIO.EST.CTA',
            ],
            [
                'key' => 'TRANSF_OTRA_CTA',
                'description' => 'Transferencia a otra cuenta',
                'search_patterns' => ['TRANSF OTRA CUENTA'],
            ],
            [
                'key' => 'DEPOSITO_EFECTIVO',
                'description' => 'Depósito en efectivo',
                'search_patterns' => ['DEPOSITO EFECTIVO'],
                'is_income' => true,
            ],
            [
                'key' => 'DEPOSITO_EXTERNO',
                'description' => 'Depósito externo',
                'search_patterns' => ['DEPÓSITO EXTERNO', 'ABONO'],
                'is_income' => true,
                'account_reference' => 'TRAN.CTAS.TERC.BM',
            ],
            [
                'key' => 'TRANSF_AG_997005',
                'description' => 'Transferencia AG 997005',
                'search_patterns' => ['TRANSF. AG', '997005'],
                'is_income' => true,
            ],
            // MED (Medio de Atención)
            [
                'key' => 'MED_BPI',
                'description' => 'Banca por Internet',
                'search_patterns' => ['BPI'],
            ],
            [
                'key' => 'MED_CAJ',
                'description' => 'Cajero Automático',
                'search_patterns' => ['CAJ'],
            ],
            [
                'key' => 'MED_INT',
                'description' => 'Cargo Interno (automático)',
                'search_patterns' => ['INT'],
            ],
            [
                'key' => 'MED_VEN',
                'description' => 'Ventanilla',
                'search_patterns' => ['VEN'],
            ],
            [
                'key' => 'MED_POS',
                'description' => 'Punto de Venta',
                'search_patterns' => ['POS'],
            ],
            [
                'key' => 'MED_TLC',
                'description' => 'Telecrédito',
                'search_patterns' => ['TLC'],
            ],
            [
                'key' => 'MED_BPT',
                'description' => 'Banca por Teléfono',
                'search_patterns' => ['BPT'],
            ],
            // TIPO de operación
            [
                'key' => 'TIPO_4701',
                'description' => 'Cargo por transferencia/interbank',
                'search_patterns' => ['4701'],
            ],
            [
                'key' => 'TIPO_1201',
                'description' => 'Depósito en efectivo o cheque',
                'search_patterns' => ['1201'],
            ],
            [
                'key' => 'TIPO_4936',
                'description' => 'Mantenimiento de cuenta',
                'search_patterns' => ['4936'],
            ],
            [
                'key' => 'TIPO_4991',
                'description' => 'Envío de estado de cuenta',
                'search_patterns' => ['4991'],
            ],
            [
                'key' => 'TIPO_2202',
                'description' => 'Transferencia recibida',
                'search_patterns' => ['2202'],
            ],
            [
                'key' => 'TIPO_0101',
                'description' => 'Comisión por mantenimiento',
                'search_patterns' => ['0101'],
            ],
        ];

        foreach ($entries as $entry) {
            GlossaryEntry::updateOrCreate(
                ['key' => $entry['key']],
                $entry,
            );
        }
    }
}
