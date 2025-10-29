<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $payments = [
            [
                'id' => '001',
                'description' => 'Depósito en cuenta',
            ],
            [
                'id' => '002',
                'description' => 'Giro',
            ],
            [
                'id' => '003',
                'description' => 'Transferencia de fondos',
            ],
            [
                'id' => '004',
                'description' => 'Orden de pago',
            ],
            [
                'id' => '005',
                'description' => 'Tarjeta de débito',
            ],
            [
                'id' => '006',
                'description' => 'Tarjeta de crédito emitida en el país por una empresa del sistema financiero',
            ],
            [
                'id' => '007',
                'description' => '	Cheques con la cláusula de "NO NEGOCIABLE", "INTRANSFERIBLES", "NO A LA ORDEN" u otra equivalente, a que se refiere el inciso g) del artículo 5° de la ley',
            ],
            [
                'id' => '008',
                'description' => 'Efectivo, por operaciones en las que no existe obligación de utilizar medio de pago',
            ],
            [
                'id' => '009',
                'description' => 'Efectivo, en los demás casos',
            ],
            [
                'id' => '010',
                'description' => 'Medios de pago usados en comercio exterior',
            ],
            [
                'id' => '011',
                'description' => 'Documentos emitidos por las EDPYMES y las cooperativas de ahorro y crédito no autorizadas a captar depósitos del público',
            ],
            [
                'id' => '012',
                'description' => 'Tarjeta de crédito emitida en el país o en el exterior por una empresa no perteneciente al sistema financiero, cuyo objeto principal sea la emisión y administración de tarjetas de crédito',
            ],
            [
                'id' => '013',
                'description' => 'Tarjetas de crédito emitidas en el exterior por empresas bancarias o financieras no domiciliadas',
            ],
            [
                'id' => '101',
                'description' => 'Transferencias – Comercio exterior',
            ],
            [
                'id' => '102',
                'description' => 'Cheques bancarios - Comercio exterior',
            ],
            [
                'id' => '103',
                'description' => 'Orden de pago simple - Comercio exterior',
            ],
            [
                'id' => '104',
                'description' => 'Orden de pago documentario - Comercio exterior',
            ],
            [
                'id' => '105',
                'description' => 'Remesa simple - Comercio exterior',
            ],
            [
                'id' => '106',
                'description' => 'Remesa documentaria - Comercio exterior',
            ],
            [
                'id' => '107',
                'description' => 'Carta de crédito simple - Comercio exterior',
            ],
            [
                'id' => '108',
                'description' => 'Carta de crédito documentario - Comercio exterior',
            ],
            [
                'id' => '999',
                'description' => 'Otros medios de pago',
            ]
        ];

        foreach ($payments as $payment) {
            PaymentMethod::create($payment);
        }
    }
}
