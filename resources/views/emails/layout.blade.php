<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    @php
        // Set the locale for translation
        App::setLocale($locale ?? config('app.fallback_locale', 'en'));
    @endphp
    <title>{{ $subject ?? __('emails.default_subject') }}</title>
    <style>
        @media only screen and (max-width: 600px) {
            .inner-body {
                width: 100% !important;
            }
        }

        @media only screen and (max-width: 500px) {
            .button {
                width: 100% !important;
            }
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
            background-color: #f8fafc;
            color: #2d3748;
            height: 100%;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            width: 100% !important;
        }
        
        .wrapper {
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
            width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            -premailer-width: 100%;
        }
        
        .content {
            margin: 0;
            padding: 0;
            width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            -premailer-width: 100%;
        }
        
        /* Header */
        .header {
            padding: 25px 0;
            text-align: center;
        }
        
        .header a {
            color: #3d4852;
            font-size: 19px;
            font-weight: bold;
            text-decoration: none;
        }
        
        /* Body */
        .body {
            background-color: #ffffff;
            border-bottom: 1px solid #e8e5ef;
            border-top: 1px solid #e8e5ef;
            margin: 0;
            padding: 0;
            width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            -premailer-width: 100%;
        }
        
        .inner-body {
            background-color: #ffffff;
            margin: 0 auto;
            padding: 0;
            width: 570px;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            -premailer-width: 570px;
        }
        
        /* Message */
        .greeting {
            color: #2d3748;
            font-weight: bold;
            margin-top: 0;
            text-align: left;
        }
        
        .subcopy {
            border-top: 1px solid #e8e5ef;
            margin-top: 25px;
            padding-top: 25px;
        }
        
        .main {
            background-color: #ffffff;
            border-radius: 3px;
            padding: 20px;
        }
        
        .paragraph {
            color: #2d3748;
            font-size: 16px;
            line-height: 1.5em;
            margin-top: 0;
            text-align: left;
        }
        
        .button {
            border-radius: 4px;
            color: #fff;
            display: inline-block;
            overflow: hidden;
            text-decoration: none;
            background-color: #2d3748;
            border-bottom: 8px solid #2d3748;
            border-left: 18px solid #2d3748;
            border-right: 18px solid #2d3748;
            border-top: 8px solid #2d3748;
        }
        
        /* Footer */
        .footer {
            margin: 0 auto;
            padding: 0;
            text-align: center;
            width: 570px;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            -premailer-width: 570px;
        }
        
        .footer p {
            color: #b0adc5;
            font-size: 12px;
            text-align: center;
        }
    </style>
</head>
<body>
    <table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                    <!-- Header -->
                    <tr>
                        <td class="header">
                            <a href="{{ config('app.url') }}">
                                {{ config('app.name') }}
                            </a>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td class="body" width="100%" cellpadding="0" cellspacing="0">
                            <table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td class="main">
                                        <!-- Greeting -->
                                        <h4 class="greeting">{{ $greeting ?? __('emails.greeting') }}</h4>

                                        <!-- Content -->
                                        @yield('content')

                                        <!-- Salutation -->
                                        <p class="paragraph">
                                            {{ $salutation ?? __('emails.salutation') }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td>
                            <table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td align="center">
                                        <p>{{ __('emails.footer_text') }}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
