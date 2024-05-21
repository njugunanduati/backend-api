<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>

<body
    style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; background-color: #f5f8fa; color: #74787E; height: 100%; hyphens: auto; line-height: 1.4; margin: 0; -moz-hyphens: auto; -ms-word-break: break-all; width: 100% !important; -webkit-hyphens: auto; -webkit-text-size-adjust: none; word-break: break-word;">
    <style>

        .nomargin{
            margin: 0;
        }

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
                    <tr>
                        <td class="header" style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; padding: 15px 35px; text-align: left;">
                            Try {{ $coach_name }} as your Business Coach
                        </td>
                    </tr>
                    <!-- Email Body -->
                    <tr>
                        <td class="content-cell"
                            style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; font-size: 16px; padding: 0 35px 10px 35px;">
                            <p>Hi <b>{{ $referral_name }}</b>,</p>
                            <p>
                                I have referred you to {{ $coach_name }} who is an excellent Business Coach who has brought tremendous success to my business. 
                            </p>
                            <p>Coach Details:</p>
                            <p><b>Name:</b> {{ $coach_name }} </p>
                            <p><b>Email:</b> {{ $coach_data->email }} </p>
                            @if (strlen($coach_data->phone_number) > 0)
                                <p><b>Phone:</b> {{ $coach_data->phone_number }} </p>
                            @endif
                        </td>
                    </tr>
                    
                    <tr>
                        <td class="content-cell"
                            style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; font-size: 16px; padding: 0 35px 10px 35px;">
                            <p class="nomargin"
                                style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; color: #74787E; font-size: 16px; line-height: 1.5em; margin: 0; text-align: left;">
                                Sincerely,</p>
                            <p class="nomargin"
                                style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; color: #74787E; font-size: 16px; line-height: 1.5em; margin: 0; text-align: left;">
                                {{ $client_name }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <!-- Email Body tr-->
    </table>
    </td>
    </tr>
    </table>
</body>

</html>
