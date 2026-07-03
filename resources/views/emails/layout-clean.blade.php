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

  {{-- IMAGEN SUPERIOR (full-width, sin padding) --}}
  @hasSection('email_top')
  <table width="100%" cellpadding="0" cellspacing="0">
    <tr>
      <td style="padding:0;line-height:0;font-size:0;">
        @yield('email_top')
      </td>
    </tr>
  </table>
  @endif

  {{-- BODY con padding --}}
  <table width="100%" cellpadding="0" cellspacing="0">
    <tr>
      <td style="padding:28px 36px 24px;">
        @yield('email_content')
      </td>
    </tr>
  </table>

  {{-- IMAGEN INFERIOR (full-width, sin padding) --}}
  @hasSection('email_bottom')
  <table width="100%" cellpadding="0" cellspacing="0">
    <tr>
      <td style="padding:0;line-height:0;font-size:0;">
        @yield('email_bottom')
      </td>
    </tr>
  </table>
  @endif

</td></tr>
</table>
</td></tr>
</table>

</body>
</html>
