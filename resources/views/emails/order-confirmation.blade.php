@extends('emails.layout-clean')

@section('email_title', 'Compra confirmada — Pedido #' . $orderNumber)

@section('email_top')
<img src="https://fv5-5.files.fm/thumb_show.php?i=msu7t9u4py&view&v=1&PHPSESSID=53ba53ad2030b8e5aae3cf48c4ba83f8e248150a"
     width="100%" alt="Gracias por tu compra - Lyrium Biomarketplace"
     style="display:block;width:100%;height:auto;border:0;" />
@endsection

@section('email_content')

{{-- ===== DETALLE DEL PEDIDO ===== --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin:0;">
    <tr>
        <td style="padding:0 16px;">

            {{-- Título turquesa --}}
            <table width="100%" cellpadding="0" cellspacing="0"
                style="background:#2BBFBF;border-radius:0;">
                <tr>
                    <th colspan="3" style="padding:12px 16px;font-size:13px;font-weight:900;
                        color:#ffffff;text-transform:uppercase;letter-spacing:1.5px;text-align:left;">
                        Detalle de su pedido
                    </th>
                </tr>
                {{-- Cabeceras columnas --}}
                <tr style="background:#1a9d96;">
                    <td width="55%" style="padding:9px 10px;font-size:11px;font-weight:800;
                        color:#e0faf8;text-transform:uppercase;letter-spacing:0.8px;text-align:left;">
                        Producto
                    </td>
                    <td width="15%" style="padding:9px 4px;font-size:11px;font-weight:800;
                        color:#e0faf8;text-transform:uppercase;letter-spacing:0.8px;text-align:center;">
                        Cant.
                    </td>
                    <td width="30%" style="padding:9px 10px;font-size:11px;font-weight:800;
                        color:#e0faf8;text-transform:uppercase;letter-spacing:0.8px;text-align:center;">
                        Total
                    </td>
                </tr>
            </table>

            {{-- Filas productos --}}
            <table width="100%" cellpadding="0" cellspacing="0"
                style="border:1.5px solid #d5f0ef;border-top:none;">
                @foreach($items as $item)
                <tr style="background:#ffffff;border-bottom:1px solid #d5f0ef;">
                    <td width="55%" style="padding:12px 10px;font-size:13px;font-weight:700;
                        color:#2BBFBF;text-align:left;word-break:break-word;">
                        {{ $item->name }}
                    </td>
                    <td width="15%" style="padding:12px 4px;font-size:13px;font-weight:800;
                        color:#1a3a3a;text-align:center;">
                        {{ $item->quantity }}
                    </td>
                    <td width="30%" style="padding:12px 10px;font-size:13px;font-weight:700;
                        color:#2BBFBF;text-align:center;white-space:nowrap;">
                        S/ {{ number_format($item->line_total, 2) }}
                    </td>
                </tr>
                @endforeach

                {{-- Subtotal --}}
                <tr style="background:#ffffff;border-bottom:1px solid #d5f0ef;">
                    <td style="padding:8px 10px;"></td>
                    <td style="padding:8px 4px;font-size:13px;font-weight:600;
                        color:#555;text-align:right;padding-right:12px;">
                        Subtotal
                    </td>
                    <td style="padding:8px 10px;font-size:13px;font-weight:600;
                        color:#555;text-align:center;white-space:nowrap;">
                        S/ {{ number_format($subtotal, 2) }}
                    </td>
                </tr>

                {{-- Envío --}}
                <tr style="background:#ffffff;border-bottom:1px solid #d5f0ef;">
                    <td style="padding:8px 10px;"></td>
                    <td style="padding:8px 4px;font-size:13px;font-weight:600;
                        color:#555;text-align:right;padding-right:12px;">
                        Envío
                    </td>
                    <td style="padding:8px 10px;font-size:13px;font-weight:600;
                        color:#555;text-align:center;white-space:nowrap;">
                        @if((float)$shippingCost > 0)
                            S/ {{ number_format($shippingCost, 2) }}
                        @else
                            Gratis
                        @endif
                    </td>
                </tr>

                @if((float)$discount > 0)
                {{-- Descuento --}}
                <tr style="background:#ffffff;border-bottom:1px solid #d5f0ef;">
                    <td style="padding:8px 10px;"></td>
                    <td style="padding:8px 4px;font-size:13px;font-weight:600;
                        color:#16A34A;text-align:right;padding-right:12px;">
                        Descuento
                    </td>
                    <td style="padding:8px 10px;font-size:13px;font-weight:600;
                        color:#16A34A;text-align:center;white-space:nowrap;">
                        -S/ {{ number_format($discount, 2) }}
                    </td>
                </tr>
                @endif
            </table>

            {{-- Fila Total a pagar --}}
            <table width="100%" cellpadding="0" cellspacing="0" style="margin:0;">
                <tr style="background:#2BBFBF;">
                    <td style="padding:12px 10px;"></td>
                    <td style="padding:12px 4px;font-size:13px;font-weight:900;
                        color:#ffffff;text-align:right;padding-right:12px;
                        text-transform:uppercase;letter-spacing:0.5px;">
                        Total a pagar
                    </td>
                    <td style="padding:12px 10px;font-size:15px;font-weight:900;
                        color:#ffffff;text-align:center;white-space:nowrap;">
                        S/ {{ number_format($total, 2) }}
                    </td>
                </tr>
            </table>

        </td>
    </tr>
</table>

{{-- ===== NÚMERO DE ORDEN ===== --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0 8px;">
    <tr>
        <td style="text-align:center;padding:0 16px;">
            <p style="margin:0;font-size:10px;font-weight:700;color:#aaa;
                text-transform:uppercase;letter-spacing:2px;">
                Orden N° {{ $orderNumber }}
            </p>
        </td>
    </tr>
</table>

{{-- ===== REDES SOCIALES ===== --}}
<table width="100%" cellpadding="0" cellspacing="0"
    style="border-top:1.5px solid #d1f0eb;margin:8px 0 0;padding:20px 0 16px;">
    <tr>
        <td style="text-align:center;padding:20px 16px 16px;">
            <table cellpadding="0" cellspacing="0" style="margin:0 auto;">
                <tr>
                    <td style="padding:0 12px;text-align:center;vertical-align:middle;">
                        <a href="https://www.facebook.com/people/Lyrium-Biomarketplace/61579938364350/"
                            target="_blank" style="text-decoration:none;">
                            <img src="https://fv5-4.files.fm/thumb_show.php?i=726g592gj8&view&v=1&PHPSESSID=53ba53ad2030b8e5aae3cf48c4ba83f8e248150a"
                                width="44" height="44" alt="Facebook"
                                style="display:block;border-radius:50%;" />
                        </a>
                    </td>
                    <td style="padding:0;"><div style="width:1px;height:50px;background:#c8e8e4;"></div></td>
                    <td style="padding:0 12px;text-align:center;vertical-align:middle;">
                        <a href="https://www.instagram.com/lyrium_biomarketplace/"
                            target="_blank" style="text-decoration:none;">
                            <img src="https://cdn-icons-png.flaticon.com/128/4138/4138124.png"
                                width="44" height="44" alt="Instagram"
                                style="display:block;border-radius:10px;" />
                        </a>
                    </td>
                    <td style="padding:0;"><div style="width:1px;height:50px;background:#c8e8e4;"></div></td>
                    <td style="padding:0 12px;text-align:center;vertical-align:middle;">
                        <a href="https://wa.me/51937093420"
                            target="_blank" style="text-decoration:none;">
                            <img src="https://cdn-icons-png.flaticon.com/128/15713/15713434.png"
                                width="44" height="44" alt="WhatsApp"
                                style="display:block;" />
                        </a>
                    </td>
                    <td style="padding:0;"><div style="width:1px;height:50px;background:#c8e8e4;"></div></td>
                    <td style="padding:0 12px;text-align:center;vertical-align:middle;">
                        <a href="https://www.youtube.com/@LyriumBiomarketplace"
                            target="_blank" style="text-decoration:none;">
                            <img src="https://cdn-icons-png.flaticon.com/128/1384/1384060.png"
                                width="44" height="44" alt="YouTube"
                                style="display:block;" />
                        </a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>


@endsection

@section('email_bottom')
<img src="https://fv5-4.files.fm/thumb_show.php?i=67vwf5vakf&view&v=1&PHPSESSID=b6862738416edfc1629012e3aecc89734866bb64"
     width="100%" alt="Lyrium Biomarketplace 2025"
     style="display:block;width:100%;height:auto;border:0;" />
@endsection
