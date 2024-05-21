<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>

<body
    style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; background-color: #f5f8fa; color: #353935; height: 100%; hyphens: auto; line-height: 1.4; margin: 0; -moz-hyphens: auto; -ms-word-break: break-all; width: 100% !important; -webkit-hyphens: auto; -webkit-text-size-adjust: none; word-break: break-word;">
    <style>
        @media only screen and (max-width: 600px) {
            .inner-body {
                width: 100% !important;
            }

            .footer {
                width: 100% !important;
            }
        }

        @media only screen and (max-width: 500px) {
            .button {
                width: 100% !important;
            }
        }
    </style>

    <table class="wrapper" width="100%" cellpadding="0" cellspacing="0"
        style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; background-color: #f5f8fa; margin: 0; padding: 0; width: 100%; -premailer-cellpadding: 0; -premailer-cellspacing: 0; -premailer-width: 100%;">
        <tr>
            <td align="center" style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box;">
                <table class="content" width="100%" cellpadding="0" cellspacing="0"
                    style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; margin: 0; padding: 0; width: 100%; -premailer-cellpadding: 0; -premailer-cellspacing: 0; -premailer-width: 100%;">
                    <!-- Email Body -->
                    <tr>
                        <td class="body" width="100%" cellpadding="0" cellspacing="0"
                            style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; background-color: #FFFFFF; border-top: 1px solid #EDEFF2; margin: 0; padding: 0; width: 100%; -premailer-cellpadding: 0; -premailer-cellspacing: 0; -premailer-width: 100%;">
                            <table class="inner-body" align="center" cellpadding="0" cellspacing="0"
                                style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; background-color: #FFFFFF; margin: 0 auto; padding: 0; width: 80vw; -premailer-cellpadding: 0; -premailer-cellspacing: 0; -premailer-width: 80vw;">
                                <!-- Body content -->
                                <tr>
                                    <td class="content-cell"
                                        style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; padding: 20px 35px 5px 35px;">
                                        <h1
                                            style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; color: #2F3133; font-size: 16px; font-weight: bold; margin-top: 0; text-align: left;">
                                            {{ $greetings }}</h1>
                                        @foreach ($content as $p)
                                            <p
                                                style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; color: #353935; font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left;">
                                                {!! $p !!} </p>
                                        @endforeach
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @if (count($summary) > 0)
                        <tr>
                            <td class="content-cell"
                                style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; padding: 0 35px 15px 35px;">
                                @foreach ($summary as $p)
                                    <p style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; color: #353935; font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left;">
                                        {!! $p !!} </p>
                                @endforeach
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td class="body" width="100%" cellpadding="0" cellspacing="0"
                            style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; background-color: #FFFFFF; border-bottom: 1px solid #EDEFF2; 1px solid #EDEFF2; margin: 0; padding: 0; width: 100%; -premailer-cellpadding: 0; -premailer-cellspacing: 0; -premailer-width: 100%;">
                            <table class="inner-body" align="center" cellpadding="0" cellspacing="0"
                            style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; background-color: #FFFFFF; margin: 0 auto; padding: 0; width: 80vw; -premailer-cellpadding: 0; -premailer-cellspacing: 0; -premailer-width: 80vw;">
                            <tr>
                                <td class="content-cell"
                                        style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; padding: 20px 35px 5px 35px;">
                                <p style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; color: #353935; font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left;">
                                    Sincerely,</p>
                                <p
                                    style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; color: #353935; font-size: 16px !important; line-height: 1.5em; margin-top: 0; text-align: left;">
                                    The Focused.com Onboarding Team</p>
                                </td>
                            </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <!-- Email Body tr-->
        <tr>
            <td style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box;">
                <table class="footer" align="center" cellpadding="0" cellspacing="0"
                    style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; margin: 0 auto; padding: 0; text-align: center; width: 80vw; -premailer-cellpadding: 0; -premailer-cellspacing: 0; -premailer-width: 80vw;">
                    <tr>
                        <td class="content-cell" align="center"
                            style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; padding: 35px;">
                            <p
                                style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; line-height: 1.5em; margin-top: 0; color: rgb(112, 112, 112); font-size: 12px; text-align: center;">
                                {{ $footer ?? '' }}&trade; & <a href="https://focused.com" style="text-decoration: none;"> Focused.com</a> &copy;
                                {{ date('Y') }}</p>

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
