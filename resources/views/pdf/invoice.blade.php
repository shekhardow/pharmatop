<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        p {
            font-size: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        th,
        td {
            padding: 12px 0;
            font-size: 20px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th:last-child,
        td:last-child {
            text-align: right;
        }

        .summary-row span:first-child {
            margin-right: 50px;
        }
    </style>
</head>

<body style="margin: 0; padding: 50px; font-family: Arial, sans-serif; width: 100%; box-sizing: border-box;">
    <div class="container">
        <div class="" style="padding: 20px; border-bottom: 1px solid #eee;">
            <img src="https://pharmatop.s3.eu-north-1.amazonaws.com/logos/pharmatop_logo.svg" alt="logo"
                style="width: 100%">
        </div>
        <div style="padding: 5px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="vertical-align: top;">
                        <p>https://www.pharmatop.ai/</p>
                        <p>vesnamjukic@outlook.com</p>
                    </td>
                    <td style="text-align: right; vertical-align: top;">
                        <p><strong>Business Address</strong></p>
                        <p>Zagreb, Croatia</p>
                    </td>
                </tr>
            </table>

            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="vertical-align: top;">
                        <p><strong>Invoice Recipient</strong></p>
                        <p>
                            @if (!empty($payment->name))
                                {{ $payment->name }}
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
                        <p><strong>Invoice Id</strong></p>
                        <p>
                            @if (!empty($payment->id))
                                {{ str_pad($payment->id ?? 0, 10, '0', STR_PAD_LEFT) }}
                            @endif
                        </p>
                    </td>
                    <td style="vertical-align: top; text-align: center;">
                        <p><strong>Amount</strong></p>
                        <p style="color: #6c2785;">
                            @if (!empty($payment->amount))
                                {{ number_format($payment->amount, 2) }}
                            @endif
                        </p>
                    </td>
                    <td style="vertical-align: top; text-align: right;">
                        <p><strong>Invoice Date</strong></p>
                        <p>{{ date('dS M Y') }}</p>
                    </td>
                </tr>
            </table>

            <table>
                <thead>
                    <tr>
                        <th style="font-weight: bold">COURSE</th>
                        <th style="font-weight: bold">PRICE</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($course_details as $course)
                        <tr>
                            <td>{{ $course['name'] }}</td>
                            <td>{{ $course['price'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="text-align: right;">
                <div class="summary-row"
                    style="display: flex; justify-content: flex-end; margin: 5px 0; font-size: 20px;">
                    <span><strong>Total</strong></span>
                    <span>{{ ($payment->currency ?? '') . ' ' . number_format($total_price, 2) }}</span>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
