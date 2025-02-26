<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            font-size: 15px;
            line-height: 1.6;
            color: #555;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }

        .header img {
            max-width: 150px;
            height: auto;
        }

        .content {
            padding: 20px;
        }

        .contact-info,
        .invoice-dates {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .contact-info p,
        .invoice-dates p {
            margin: 5px 0;
            color: #555;
            font-size: 15px;
        }

        .invoice-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            padding: 12px 0;
            text-align: left;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        th:last-child, td:last-child {
            text-align: right;
        }

        .summary {
            text-align: right;
            font-size: 15px;
        }

        .summary-row {
            display: flex;
            justify-content: flex-end;
            margin: 5px 0;
        }

        .summary-row span:first-child {
            margin-right: 50px;
        }

        .total {
            font-weight: 600;
            font-size: 18px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }

        .bold {
            font-weight: 600;
        }

        .purple {
            color: #6c2eb9;
        }

        .right {
            text-align: right;
        }

        .left {
            text-align: left;
        }

        .center {
            text-align: center;
        }

        @media print {
            body {
                background-color: #fff;
                margin: 0;
                padding: 0;
            }

            .container {
                box-shadow: none;
                border-radius: 0;
            }

            @page {
                margin: 0.5cm;
                size: A4;
            }

            .header img {
                max-width: 130px;
            }

            .contact-info p,
            .invoice-details p {
                font-size: 12px;
            }

            table th,
            table td {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="https://pharmatop.s3.eu-north-1.amazonaws.com/logos/pharmatop_logo.svg" alt="PharmaTOP Logo">
        </div>
        <div class="content">

            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="vertical-align: top;">
                        <p>https://pharmatop.ai/</p>
                        <p>vesnamjukic@outlook.com</p>
                    </td>
                    <td style="text-align: right; vertical-align: top;">
                        <p>Business address</p>
                        <p>Zagreb, Croatia</p>
                    </td>
                </tr>
            </table>


            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="vertical-align: top;">
                        <p>Billed to</p>
                        <p class="bold">
                            @if (!empty($student_name))
                                {{ $student_name }}
                            @endif
                        </p>
                        <p>
                            @if (!empty($payment->city) && !empty($payment->country))
                                {{ $payment->city }}, {{ $payment->country }}
                            @endif
                        </p>
                        <p>
                            @if (!empty($payment->email))
                                {{ $payment->email }}
                            @endif
                        </p>
                    </td>
                    <td style="vertical-align: top; text-align: center;">
                        <p><strong>Invoice number</strong></p>
                        <p class="bold">
                            @if (!empty($payment->id))
                                {{ $payment->id }}
                            @endif
                        </p>
                    </td>
                    <td style="vertical-align: top; text-align: center;">
                        <p><strong>Invoice of ((EUR))</strong></p>
                        <p class="bold purple" style="font-size: 18px;">@if (!empty($payment->amount))
                            {{ number_format($payment->amount, 2) }}
                        @endif</p>
                    </td>
                    <td style="vertical-align: top; text-align: right;">
                    <p><strong>Invoice date</strong></p>
                    <p class="bold">{{ date('d M Y') }}</p>
                    </td>
                </tr>
            </table>

            <!-- Table of Invoice Items -->
            <table>
                <thead>
                    <tr>
                        <th>COURSE DETAIL</th>
                        <th>PRICE</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($course_details as $course)
                        <tr>
                            <td class="bold">{{ $course['name'] }}</td>
                            <td class="bold">€{{ $course['price'] }}</td>
                        </tr>
                        <tr>
                            <td>{{ $course['description'] }}</td>
                            <td></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>


            <!-- Summary Section -->
            <div class="summary">
                <div class="summary-row total">
                    <span>Total</span>
                    <span>€{{ number_format($total_price, 2) }}</span>
                </div>
            </div>

        </div>
    </div>
</body>
</html>
