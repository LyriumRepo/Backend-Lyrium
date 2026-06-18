<!DOCTYPE html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>@yield('email_title', 'Lyrium BioMarketplace')</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:24px 16px;">
<tr><td>
<table width="100%" cellpadding="0" cellspacing="0" style="max-width:580px;margin:0 auto;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.10);">
<tr><td>

  {{-- HEADER --}}
  <table width="100%" cellpadding="0" cellspacing="0">
    <tr>
      <td style="background:linear-gradient(160deg,#1a6b3a 0%,#14532d 60%,#0f4020 100%);padding:28px 40px 22px;text-align:center;">
        <img src="https://i.imgur.com/WdRKkWJ.png" width="210" alt="Lyrium BioMarketplace"
             style="display:block;margin:0 auto;border:0;max-width:210px;height:auto;">
        <p style="margin:10px 0 0;font-size:9px;color:rgba(187,247,208,0.75);letter-spacing:3.5px;text-transform:uppercase;">
          @yield('email_tagline', 'Salud &amp; Bienestar Natural &middot; Per&uacute;')
        </p>
      </td>
    </tr>
    <tr>
      <td style="height:3px;background:linear-gradient(90deg,#9cb04e,#64c695,#499bbf);font-size:0;">&nbsp;</td>
    </tr>
  </table>

  {{-- BODY --}}
  <table width="100%" cellpadding="0" cellspacing="0">
    <tr>
      <td style="padding:28px 36px 24px;">
        @yield('email_content')
      </td>
    </tr>
  </table>

  {{-- FOOTER --}}
  <table width="100%" cellpadding="0" cellspacing="0">
    <tr>
      <td style="height:3px;background:linear-gradient(90deg,#9cb04e,#64c695,#499bbf);font-size:0;">&nbsp;</td>
    </tr>
    <tr>
      <td style="background:linear-gradient(160deg,#1a6b3a 0%,#14532d 60%,#0f4020 100%);padding:20px 36px 24px;text-align:center;">
        <table cellpadding="0" cellspacing="0" style="margin:0 auto 14px;">
          <tr>
            <td style="padding:0 8px;text-align:center;vertical-align:middle;">
              <a href="https://www.facebook.com/people/Lyrium-Biomarketplace/61579938364350/" target="_blank" style="text-decoration:none;">
                <img src="https://i.imgur.com/eZoylMM.png" width="44" alt="Facebook"
                     style="display:block;width:44px;height:auto;border:0;">
              </a>
            </td>
            <td style="padding:0 8px;text-align:center;vertical-align:middle;">
              <a href="https://www.instagram.com/lyrium_biomarketplace/" target="_blank" style="text-decoration:none;">
                <img src="https://i.imgur.com/4Npw0dz.png" width="44" alt="Instagram"
                     style="display:block;width:44px;height:auto;border:0;">
              </a>
            </td>
            <td style="padding:0 8px;text-align:center;vertical-align:middle;">
              <a href="https://wa.me/51937093420" target="_blank" style="text-decoration:none;">
                <img src="https://i.imgur.com/n2ZgKSp.png" width="44" alt="WhatsApp"
                     style="display:block;width:44px;height:auto;border:0;">
              </a>
            </td>
            <td style="padding:0 8px;text-align:center;vertical-align:middle;">
              <a href="https://www.youtube.com/@LyriumBiomarketplace" target="_blank" style="text-decoration:none;">
                <img src="https://i.imgur.com/8fKQz68.png" width="44" alt="YouTube"
                     style="display:block;width:44px;height:auto;border:0;">
              </a>
            </td>
          </tr>
        </table>
        <p style="font-size:11px;font-weight:700;color:#bbf7d0;margin:0 0 3px;">Lyrium BioMarketplace &middot; Per&uacute;</p>
        <p style="font-size:10px;color:rgba(187,247,208,0.65);margin:0 0 10px;line-height:1.6;">lyriumbiomarketplace.com &middot; contacto@lyrium.pe &middot; +51 937 093 420</p>
        <p style="font-size:9px;color:rgba(187,247,208,0.40);margin:0;">&copy; 2025 Lyrium BioMarketplace. Todos los derechos reservados.</p>
      </td>
    </tr>
  </table>

</td></tr>
</table>
</td></tr>
</table>

</body>
</html>
