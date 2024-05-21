<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request for Meeting</title>
    <style>
        body, table { margin: 0; padding: 0; border: 0; font-size: 100%; font: inherit; }
        html { width: 100%; margin: 0; padding: 0; font-family: 'Sarabun','Arial', sans-serif; background-color: #f5f8fa; color: #74787E; font-size: 16px; line-height: 1.4; }
        table { width: 100%; border-collapse: collapse; background-color: #ffffff; }
        .header, .footer { background-color: #f8f8f8; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .footer { }
    </style>
</head>
<body>
    <table>
        <tr>
            <td class="header">
                <!-- Logo or Blank -->
                {{-- <img src="{{ $logo }}" alt="Company Logo" style="max-width: 200px;"> --}}
                <h1>Greetings, {{ $coach }},</h1>
            </td>
        </tr>
        <tr>
            <td class="content">
                <!-- Main Content Table -->
                <table>
                    <!-- Content goes here -->
                    <p>The following is a meeting request from a Profit Acceleration Simulator Report for:</p>
                    <p>Name: <span>{{ $user_name }}</span></p>
                    <p>Email: <span>{{ $user_email }}</span></p>
                    <p>Phone Number: <span>{{ $phone_number }} </span></p>
                    <p>Best time to speak:</p>
                    <p class="meet-details">{{ $meet_details }}</p>
                    <p>
                        <span style="display: flex; align-items: center; justify-content: center; margin-block-start: 1em; margin-block-end: 1em; margin-inline-start: 0px; margin-inline-end: 0px;">
                            <a target="_blank" rel="noopener noreferrer" href="{{ $report_link }}" style="display: inline-block; padding: 10px 15px; margin-top: 20px; background-color: #039be1; color: #ffffff; text-align: center; text-decoration: none; border-radius: 5px; font-weight: bold;">Click to
                                View the
                                detailed simulation report</a>
                        </span>
                    </p>
                    <br>
                    <p>Sincerely,</p>
                    <p>Email: <a href="mailto:{{ $user_email }}">{{ $user_email }}</a></p>
                    <p>{{ $user_name }}</p>
                </table>
            </td>
        </tr>
        <tr>
            <td class="footer">
                <!-- Footer Content -->
                <p
                    style="font-family: 'Sarabun','Arial', sans-serif; box-sizing: border-box; line-height: 1.5em; margin-top: 0; color: rgb(112, 112, 112); font-size: 13px; text-align: center;">
                    Powered by Profit Acceleration Software™ &amp; <a target="_blank" rel="noopener noreferrer"
                        href="https://focused.com" style="text-decoration: none;"> Focused.com</a> ©
                        {{ date('Y') }}. All rights reserved.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>

