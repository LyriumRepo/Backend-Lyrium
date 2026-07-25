<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completa tu perfil — Lyrium Biomarketplace</title>
</head>
<body style="margin:0; padding:0; background-color:#eef6f1; font-family:'Segoe UI',Arial,sans-serif;">

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"
    style="border-collapse:collapse; background-color:#eef6f1; padding:32px 16px;">
<tr><td align="center">

<table border="0" cellpadding="0" cellspacing="0" width="560"
    style="border-collapse:collapse; max-width:560px; width:100%;
           box-shadow:0 8px 32px rgba(27,67,50,0.12); border-radius:18px; overflow:hidden;">

    <!-- HEADER -->
    <tr>
        <td align="center"
            style="background:linear-gradient(140deg,#1B4332 0%,#2A5A4D 55%,#3D7A6B 100%);
                   padding:32px 30px 28px 30px;">
            <table border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="center" valign="middle" style="padding-right:14px;">
                        <img src="cid:logo-icon" width="54" height="54"
                            style="display:block; border-radius:12px; border:2px solid rgba(255,255,255,0.2);"
                            alt="Lyrium">
                    </td>
                    <td align="left" valign="middle">
                        <img src="cid:logo-text" width="148" height="auto"
                            style="display:block;" alt="LYRIUM">
                        <p style="margin:4px 0 0 0; font-size:10px; color:#8ecfb4;
                            letter-spacing:3.5px; text-transform:uppercase; font-weight:700;">
                            Biomarketplace
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- SALUDO -->
    <tr>
        <td align="center"
            style="background-color:#ffffff; padding:40px 40px 8px 40px;">

            <div style="font-size:48px; line-height:1; margin-bottom:18px;">🌱</div>

            <h1 style="margin:0 0 10px 0; font-size:26px; font-weight:800; line-height:1.2;
                color:#1B4332; font-family:'Arial Black',Arial,sans-serif;">
                Hola, {{ $name }}
            </h1>

            <p style="margin:0; font-size:14px; color:#64748B; font-weight:500;">
                Tu perfil de Lyrium todavía está incompleto
            </p>
        </td>
    </tr>

    <!-- MENSAJE PRINCIPAL -->
    <tr>
        <td align="center"
            style="background-color:#ffffff; padding:24px 40px 28px 40px;">

            <p style="margin:0 0 20px 0; font-size:15px; color:#374151; line-height:1.85;
                max-width:440px; margin-left:auto; margin-right:auto; text-align:center;">
                Completar tu perfil nos ayuda a reconocerte mejor, avisarte de promociones
                relevantes y agilizar tus próximas compras en <strong style="color:#2A5A4D;">Lyrium</strong>.
            </p>

            @if(count($missingFieldLabels) > 0)
            <!-- Campos pendientes -->
            <table border="0" cellpadding="0" cellspacing="0" width="100%"
                style="max-width:440px; margin:0 auto 24px auto; border-collapse:collapse;">

                <tr>
                    <td style="background:#f0faf4; border-radius:12px; padding:20px 24px;">

                        <p style="margin:0 0 12px 0; font-size:12px; color:#3d7057; font-weight:700;
                            text-transform:uppercase; letter-spacing:1px;">
                            Te falta completar
                        </p>

                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                            @foreach($missingFieldLabels as $index => $label)
                            <tr>
                                <td style="padding:8px 0; {{ $index < count($missingFieldLabels) - 1 ? 'border-bottom:1px solid #d4ede3;' : '' }}">
                                    <span style="font-size:18px;">🌿</span>
                                    <span style="font-size:14px; color:#1B4332; font-weight:600; margin-left:10px;">
                                        {{ $label }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </table>

                    </td>
                </tr>
            </table>
            @endif

        </td>
    </tr>

    <!-- BOTÓN CTA -->
    <tr>
        <td align="center"
            style="background-color:#ffffff; padding:0 40px 44px 40px;">

            <a href="{{ $profileUrl }}"
                style="display:inline-block;
                       background:linear-gradient(135deg,#2A5A4D 0%,#3D7A6B 100%);
                       color:#ffffff; font-size:15px; font-weight:700;
                       text-decoration:none; padding:15px 42px; border-radius:50px;
                       letter-spacing:0.4px;
                       box-shadow:0 5px 16px rgba(42,90,77,0.35);">
                Ir a mi perfil 🌿
            </a>

            <p style="margin:18px 0 0 0; font-size:12px; color:#94A3B8; line-height:1.6;">
                Tu sesión está activa. Solo haz clic para acceder.
            </p>
        </td>
    </tr>

    <!-- FOOTER -->
    <tr>
        <td align="center"
            style="background:linear-gradient(140deg,#1B4332 0%,#2A5A4D 100%);
                   padding:26px 30px 28px 30px;">

            <table border="0" cellpadding="0" cellspacing="0" style="margin:0 auto;">
                <tr>
                    <td align="center" valign="middle" style="padding-right:10px;">
                        <img src="cid:logo-icon" width="28" height="28"
                            style="display:block; border-radius:6px; opacity:0.85;"
                            alt="Lyrium">
                    </td>
                    <td align="left" valign="middle">
                        <p style="margin:0; font-size:11px; color:#8ecfb4;
                            font-weight:700; letter-spacing:2.5px; text-transform:uppercase;">
                            Lyrium Biomarketplace
                        </p>
                    </td>
                </tr>
            </table>

            <p style="margin:14px 0 0 0; font-size:11px; color:#5a9e7a; line-height:1.9;">
                contacto@lyrium.pe &nbsp;·&nbsp; lyriumbiomarketplace.com<br>
                Villa María del Triunfo, Lima — Perú 🇵🇪
            </p>

            <p style="margin:14px 0 0 0; font-size:10px; color:#3d7057; line-height:1.6;">
                Recibiste este correo porque tienes una cuenta de cliente en Lyrium.<br>
                © {{ date('Y') }} Lyrium E.I.R.L. — RUC 20612731838
            </p>
        </td>
    </tr>

</table>

</td></tr>
</table>

</body>
</html>
