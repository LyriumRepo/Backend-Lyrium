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
                            <p style="margin: 0 0 8px; color: #333; font-size: 16px;">
                                Hola <strong>{{ $userName }}</strong>,
                            </p>
                            <p style="margin: 0 0 24px; color: #555; font-size: 15px;">
                                Un administrador ha creado una cuenta para ti en el sistema de Lyrium.
                                Aquí están tus credenciales de acceso:
                            </p>

                            {{-- Credentials box --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="background: #f0fdf4; border-radius: 10px; border: 1px solid #b7e4c7; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 20px 24px;">
                                        <p style="margin: 0 0 12px; font-size: 13px; color: #666; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">
                                            Tus credenciales
                                        </p>
                                        <table cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding: 4px 0; color: #555; font-size: 14px; width: 100px;">Email:</td>
                                                <td style="padding: 4px 0; color: #2d6a4f; font-weight: 700; font-size: 14px;">{{ $email }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 4px 0; color: #555; font-size: 14px;">Contraseña:</td>
                                                <td style="padding: 4px 0; font-size: 18px; font-weight: 700; letter-spacing: 3px; color: #2d6a4f; font-family: monospace;">{{ $password }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 4px 0; color: #555; font-size: 14px;">Rol:</td>
                                                <td style="padding: 4px 0; color: #333; font-size: 14px; font-weight: 600;">
                                                    @if($role === 'logistics_operator') Operador Logístico
                                                    @elseif($role === 'administrator') Administrador
                                                    @else {{ $role }}
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0 0 8px; color: #e63946; font-size: 13px; font-weight: 600;">
                                ⚠ Por seguridad, cambia tu contraseña después de iniciar sesión por primera vez.
                            </p>
                            <p style="margin: 0; color: #999; font-size: 13px;">
                                Si tienes dudas, contacta al administrador del sistema.
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