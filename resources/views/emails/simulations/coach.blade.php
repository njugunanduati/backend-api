<!DOCTYPE html>
<html lang="en" style="width: 100%; margin: 0; padding: 0; font-family: 'Sarabun', 'Arial', sans-serif; background-color: #f5f8fa; color: #74787E; font-size: 16px; line-height: 1.4;">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $user_name }} Simulation</title>
    <style>
        body,
        table {
            margin: 0;
            padding: 0;
            border: 0;
            font-size: 100%;
            font: inherit;
        }

        .table thead tr {
            padding: 5px;
            background-color: #14143f;
            color: #ffffff;
            font-weight: bold;
            font-size: 13px;
            border: 1px solid #14143f;
            text-align: center;
        }

        .table td {
            padding: 5px;
            text-align: center;
            color: #636363;
            border: 1px solid #dddfe1;
        }

        .table tbody td {
            color: #636363;
            border: 1px solid #dddfe1;
        }

        .table tbody tr {
            background-color: #f9fafb;
        }

        .table tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }
    </style>
</head>

<body>
    <table style="width: 100%; border-collapse: collapse; background-color: #ffffff;">
        <tr>
            <td style="background-color: #f8f8f8; padding: 20px; text-align: center;">
                <!-- Logo or Blank or Greeting -->
                <h1 style="font-family: 'Sarabun', 'Arial', sans-serif; font-size: 24px; color: #74787E;">Greetings,
                    {{ $coach }}</h1>
            </td>
        </tr>

        <tr>
            <td class="content" style="padding: 20px;">
                <!-- Main Content Table -->
                <table style="width: 100%; border-collapse: collapse; background-color: #ffffff;">
                    <!-- Content goes here -->
                    <p>I have successfully run a simulation on your Profit Acceleration Simulator.</p>
                    <p>
                        <span
                            style="display: flex; display: table; margin: 0 auto; justify-content: center; margin-block-start: 1em; margin-block-end: 1em; margin-inline-start: 0px; margin-inline-end: 0px;">
                            <a target="_blank" rel="noopener noreferrer" href="{{ $report_link }}"
                                style="display: inline-block;   padding: 10px 15px; margin-top: 20px; background-color: #039be1; color: #ffffff; text-align: center; text-decoration: none; border-radius: 5px; font-weight: bold;">Click
                                to
                                View the
                                detailed simulation report</a>
                        </span>
                    </p>
                    <br />
                    <table class="table"
                        style="border-collapse: collapse; width: 100%; border-collapse: collapse; background-color: #ffffff; margin-bottom: 2.5rem">
                        <!-- ... [rest of the first table] ... -->
                        <thead>
                            <tr>
                                <th>Annual Revenue</th>
                                <th>Gross Profit Margin</th>
                                <th>Net Profit Margin</th>
                                <th>Percentage Profit Impact</th>
                                <th>Net_profit</th>
                                <th>New Annual Profit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>{{ $simulation_data['currency'] . number_format($simulation_data['annual_revenue']) }}</td>
                                <td>{{ $simulation_data['gross_profit_margin'] }}
                                <td>{{ $simulation_data['net_profit_margin'] }}</td>
                                <td>{{ $simulation_data['percentage_impact'] }}
                                <td>{{ $simulation_data['currency'] . number_format($simulation_data['net_profit']) }}</td>
                                <td>{{ $simulation_data['currency'] . number_format($simulation_data['new_annual_profit']) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Second Table -->
                    <table class="table"
                        style="border-collapse: collapse; width: 80%; border-collapse: collapse; background-color: #ffffff; margin: auto">
                        <!-- ... [rest of the second table] ... -->
                        <thead>
                            <tr>
                                <td style="color:#ffffff">#</td>
                                <th>Strategies</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>Cut Costs</td>
                                <td>{{ $simulation_data['currency'] . number_format($simulation_data['cut_costs']) }}</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>Create or Refine your Market Dominating Position (MDP)</td>
                                <td>{{ $simulation_data['currency'] . number_format($simulation_data['market_dominating_position']) }}
                                </td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>Create a More Compelling Offer</td>
                                <td>{{ $simulation_data['currency'] . number_format($simulation_data['compelling_offer']) }}</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>Increase Prices</td>
                                <td>{{ $simulation_data['currency'] . number_format($simulation_data['increase_prices']) }}</td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>Create Upsells & Cross-sells</td>
                                <td>{{ $simulation_data['currency'] . number_format($simulation_data['upsell_cross_sell']) }}</td>
                            </tr>
                            <tr>
                                <td>6</td>
                                <td>Create Bundling Options</td>
                                <td>{{ $simulation_data['currency'] . number_format($simulation_data['bundling']) }}</td>
                            </tr>
                            <tr>
                                <td>7</td>
                                <td>Plan Your Downsells</td>
                                <td>{{ $simulation_data['currency'] . number_format($simulation_data['downsell']) }}</td>
                            </tr>
                            <tr>
                                <td>8</td>
                                <td>Identify and Plan to Sell Additional Products & Services</td>
                                <td>{{ $simulation_data['currency'] . number_format($simulation_data['additional_products_services']) }}
                                </td>
                            </tr>
                            <tr>
                                <td>9</td>
                                <td>Create a Drip Campaign</td>
                                <td>{{ $simulation_data['currency'] . number_format($simulation_data['drip_campaign']) }}</td>
                            </tr>
                            <tr>
                                <td>10</td>
                                <td>Form Alliances & Joint Ventures</td>
                                <td>{{ $simulation_data['currency'] . number_format($simulation_data['alliances_joint_ventures']) }}
                                </td>
                            </tr>
                            <tr>
                                <td>11</td>
                                <td>Get More Leads</td>
                                <td>{{ $simulation_data['currency'] . number_format($simulation_data['more_leads']) }}</td>
                            </tr>
                            <tr>
                                <td>12</td>
                                <td>Create a Comprehensive Digital Marketing Campaign</td>
                                <td>{{ $simulation_data['currency'] . number_format($simulation_data['digital_marketing']) }}</td>
                            </tr>


                        </tbody>
                    </table>
                    <br>
                    <p>Sincerely,</p>
                    <p>Email: <a href="mailto:{{ $user_email }}">{{ $user_email }}</a></p>
                    <p>{{ $user_name }}</p>

            </td>

        </tr>

    </table>


    </table>
    </td>
    </tr>
    <tr>
        <td style="background-color: #f8f8f8; text-align: center; padding: 20px;">
            <!-- Footer Content -->
            <p style="font-family: 'Sarabun', 'Arial', sans-serif; line-height: 1.5em; margin-top: 0; color: #707070; font-size: 13px; padding:20px; text-align: center;">
                Powered by Profit Acceleration Software™ &amp; <a target="_blank" rel="noopener noreferrer"
                    href="https://focused.com" style="text-decoration: none; color: #039be1;">Focused.com</a> ©
                {{ date('Y') }}. All rights reserved.
            </p>
        </td>
    </tr>
    </table>
</body>

</html>
