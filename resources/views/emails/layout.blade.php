<!DOCTYPE html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>@yield('email_title', 'Lyrium')</title>
    <!--[if mso]>
    <noscript>
        <xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml>
    </noscript>
    <![endif]-->
    <style>
        /* Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body,
        table,
        td,
        a {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        table,
        td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }

        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            outline: none;
            text-decoration: none;
        }

        body {
            background-color: #F0F4F8;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            font-size: 15px;
            line-height: 1.6;
            color: #1E293B;
            margin: 0;
            padding: 0;
        }

        /* Wrapper */
        .email-wrapper {
            width: 100%;
            background-color: #F0F4F8;
            padding: 32px 16px;
        }

        /* Container */
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #FFFFFF;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
        }

        /* Header */
        .email-header {
            background: linear-gradient(135deg, #00BFC1 0%, #1a9d96 100%);
            padding: 32px 40px;
            text-align: center;
        }

        .email-header .logo-img {
            display: block;
            margin: 0 auto;
            max-width: 180px;
            height: auto;
        }

        .email-header .tagline {
            font-size: 13px;
            color: #e0faf8;
            margin-top: 8px;
            letter-spacing: 0.5px;
        }

        /* Body */
        .email-body {
            padding: 40px 40px 32px;
        }

        /* Footer */
        .email-footer {
            background-color: #F8FAFC;
            border-top: 1px solid #E2E8F0;
            padding: 24px 40px;
            text-align: center;
        }

        .email-footer p {
            font-size: 12px;
            color: #94A3B8;
            line-height: 1.7;
            margin: 0;
        }

        .email-footer a {
            color: #2563EB;
            text-decoration: none;
        }

        /* Typography */
        h1 {
            font-size: 22px;
            font-weight: 700;
            color: #1E293B;
            margin-bottom: 8px;
        }

        h2 {
            font-size: 16px;
            font-weight: 600;
            color: #1E3A5F;
            margin-bottom: 12px;
        }

        p {
            color: #475569;
            margin-bottom: 16px;
        }



        /* Status badge */
        .status-badge {
            display: inline-block;
            padding: 6px 18px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.3px;
            margin-bottom: 24px;
        }

        .status-confirmed {
            background: #DCFCE7;
            color: #15803D;
        }

        .status-pending {
            background: #FEF9C3;
            color: #92400E;
        }

        /* Info card */
        .info-card {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .info-card-title {
            font-size: 11px;
            font-weight: 700;
            color: #94A3B8;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 16px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 10px 0;
            border-bottom: 1px solid #E2E8F0;
        }

        .info-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .info-label {
            font-size: 13px;
            color: #64748B;
            font-weight: 500;
            min-width: 140px;
        }

        .info-value {
            font-size: 14px;
            color: #1E293B;
            font-weight: 600;
            text-align: right;
        }

        /* Highlight box */
        .highlight-box {
            background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%);
            border: 1px solid #BFDBFE;
            border-left: 4px solid #2563EB;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 24px;
        }

        .highlight-box p {
            margin: 0;
            font-size: 14px;
            color: #1E40AF;
            font-weight: 500;
        }

        /* Warning box */
        .warning-box {
            background: #FEF9C3;
            border: 1px solid #FDE047;
            border-left: 4px solid #EAB308;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 24px;
        }

        .warning-box p {
            margin: 0;
            font-size: 13px;
            color: #78350F;
        }

        /* CTA Button */
        .cta-button {
            display: block;
            width: fit-content;
            margin: 0 auto 24px;
            padding: 14px 36px;
            background: linear-gradient(135deg, #1E3A5F 0%, #2563EB 100%);
            color: #FFFFFF !important;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            border-radius: 10px;
            text-align: center;
            letter-spacing: 0.2px;
        }

        /* ICS notice */
        .ics-notice {
            background: #F0FDF4;
            border: 1px solid #BBF7D0;
            border-left: 4px solid #22C55E;
            border-radius: 10px;
            padding: 14px 18px;
            margin-bottom: 24px;
        }

        .ics-notice p {
            margin: 0;
            font-size: 13px;
            color: #15803D;
        }

        /* Divider */
        .divider {
            border: none;
            border-top: 1px solid #E2E8F0;
            margin: 24px 0;
        }

        /* Responsive */
        @media only screen and (max-width: 600px) {
        }
    </style>
</head>

<body>
    <div class="email-wrapper">
        <div class="email-container">

            {{-- HEADER --}}
            @if(!isset($hideHeader) || !$hideHeader)
            <div class="email-header">
                <img src="cid:logo-text" alt="Lyrium" class="logo-img" />
                @if(isset($showTagline) ? $showTagline : true)
                <div class="tagline">BioMarketplace · Salud y Bienestar</div>
                @endif
            </div>
            @endif

            {{-- BODY --}}
            <div class="email-body">
                @yield('email_content')
            </div>

            {{-- FOOTER --}}
            @if(!isset($hideFooter) || !$hideFooter)
            <div class="email-footer">
                <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
                    <tr>
                        <td style="padding: 0 0 16px;">
                            <table cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                                <tr>
                                    <td style="padding: 0 14px; text-align: center; vertical-align: top;">
                                        <a href="https://www.facebook.com/people/Lyrium-Biomarketplace/61579938364350/" target="_blank" style="text-decoration: none; display: inline-block;">
                                            <img src="https://fv5-4.files.fm/thumb_show.php?i=726g592gj8&view&v=1&PHPSESSID=53ba53ad2030b8e5aae3cf48c4ba83f8e248150a" width="44" height="44" alt="Facebook" style="display: block; margin: 0 auto;" />
                                        </a>
                                    </td>
                                    <td style="padding: 0;">
                                        <div style="width: 1px; height: 50px; background: #E2E8F0;"></div>
                                    </td>
                                    <td style="padding: 0 14px; text-align: center; vertical-align: top;">
                                        <a href="https://www.instagram.com/lyrium_biomarketplace/" target="_blank" style="text-decoration: none; display: inline-block;">
                                            <img src="https://cdn-icons-png.flaticon.com/128/4138/4138124.png" width="44" height="44" alt="Instagram" style="display: block; margin: 0 auto; border-radius: 10px;" />
                                        </a>
                                    </td>
                                    <td style="padding: 0;">
                                        <div style="width: 1px; height: 50px; background: #E2E8F0;"></div>
                                    </td>
                                    <td style="padding: 0 14px; text-align: center; vertical-align: top;">
                                        <a href="https://wa.me/51937093420" target="_blank" style="text-decoration: none; display: inline-block;">
                                            <img src="https://cdn-icons-png.flaticon.com/128/15713/15713434.png" width="44" height="44" alt="WhatsApp" style="display: block; margin: 0 auto;" />
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0;">
                            <p style="font-size: 11px; color: #94A3B8; margin: 0; letter-spacing: 0.5px;">
                                Lyrium BioMarketplace &middot; Salud y Bienestar
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            @endif

        </div>
    </div>
</body>

</html>