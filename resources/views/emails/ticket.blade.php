<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="x-apple-disable-message-reformatting">
    <title></title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->

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
</head>

<body
    style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; background-color: #f5f8fa; color: #353935; height: 100%; hyphens: auto; line-height: 1.4; margin: 0; -moz-hyphens: auto; -ms-word-break: break-all; width: 100% !important; -webkit-hyphens: auto; -webkit-text-size-adjust: none; word-break: break-word;">


    <div class="body" width="100%" cellpadding="0" cellspacing="0"
        style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; background-color: #FFFFFF; border-top: 1px solid #EDEFF2; margin: auto; padding: 3%; width: 100%; -premailer-cellpadding: 0; -premailer-cellspacing: 0; -premailer-width: 100%;">
        <p
            style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; color: #353935; font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left;">
            <b>Ticket Priority - {!! $priority !!} </b>
        </p>

        <p
            style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; color: #353935; font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left;">
            <b>Ticket Type - {!! $ticket_type !!} </b>
        </p>

        <p
            style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; color: #353935; font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left;">
            {!! $content !!} </p>


        <p
            style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; color: #353935; font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left;">
            Regards,</p>
        <p
            style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; color: #353935; font-size: 16px !important; line-height: 1.5em; margin-top: 0; text-align: left;">
            {{ $first_name . ' ' . $last_name }}</p>
    </div>

</body>

</html>
