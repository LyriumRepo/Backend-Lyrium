<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f7f6;">
    <table width="100%" cellpadding="0" cellspacing="0" style="padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="480" cellpadding="0" cellspacing="0" style="background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                    {{-- Header --}}
                    <tr>
                        <td style="background: #2d6a4f; padding: 24px 32px;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 22px; font-weight: 600;">
                                Lyrium BioMarketplace
                            </h1>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding: 32px;">
                            <p style="margin: 0 0 16px; color: #333; font-size: 16px;">
                                Hola <strong>{{ $userName }}</strong>,
                            </p>
                            <p style="margin: 0 0 24px; color: #555; font-size: 15px;">
                                Usa el siguiente código para verificar tu correo electrónico:
                            </p>

                            {{-- OTP Code --}}
                            <div style="text-align: center; margin: 0 0 24px;">
                                <span style="display: inline-block; font-size: 36px; font-weight: 700; letter-spacing: 10px; color: #2d6a4f; background: #f0fdf4; padding: 16px 32px; border-radius: 10px; border: 2px dashed #b7e4c7;">
                                    {{ $code }}
                                </span>
                            </div>

                            <p style="margin: 0 0 8px; color: #666; font-size: 14px;">
                                Este código expira en <strong>10 minutos</strong>.
                            </p>
                            <p style="margin: 0; color: #999; font-size: 13px;">
                                Si no solicitaste esta verificación, puedes ignorar este correo de forma segura.
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background: #f8faf9; padding: 16px 32px; border-top: 1px solid #e8ede9;">
                            <p style="margin: 0; color: #aaa; font-size: 12px; text-align: center;">
                                &copy; {{ date('Y') }} Lyrium BioMarketplace. Todos los derechos reservados.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
