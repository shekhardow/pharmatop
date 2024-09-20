<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate of Completion</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 16px;
            margin: 0;
            padding: 0;
        }

        h1,
        h2 {
            margin: 0;
            padding: 5px 0;
        }

        h1 {
            font-size: 36px;
        }

        h2 {
            font-size: 20px;
            font-weight: normal;
        }

        .bold {
            font-weight: bold;
            font-size: 25px;
        }

        .description {
            margin: 10px 0;
            font-size: 18px;
        }

        .additional-content {
            font-size: 14px;
            color: #666;
            margin-top: 10px;
        }

        .left-align img {
            height: auto;
            max-height: 50px;
            display: block;
            margin: 0;
            padding: 0;
        }

        .right-align {
            text-align: right;
        }

        .signature {
            font-size: 16px;
            font-weight: bold;
            margin: 0;
        }
    </style>
</head>

<body>
    <div class="certificate">

        <table class="commonTable heading-container" style="width: 100%;">
            <tbody>
                <tr>
                    <td style="width: 100%; text-align: center;">
                        <h1>Certificate of Completion</h1>
                    </td>
                </tr>
                <tr>
                    <td style="width: 100%; text-align: center;">
                        <h2>In recognition of completing a course</h2>
                    </td>
                </tr>
            </tbody>
        </table>

        <table style="width: 100%; margin-top: 70px;">
            <tbody>
                <tr>
                    <td style="width: 100%; text-align: center;">
                        <p>This is to certify that</p>
                        <p class="bold">{{ $student_name }}</p>
                    </td>
                </tr>
                <tr>
                    <td style="width: 100%; text-align: center;">
                        <p class="description">has successfully completed the course
                            <strong>{{ $course_name }}</strong> and
                            has demonstrated a thorough understanding of the course material. The student has shown
                            exceptional
                            dedication and commitment to their studies, and has consistently
                            demonstrated a high level of academic achievement.
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="width: 100%; text-align: center;">
                        <p class="description">This certificate is a testament to the student's hard work and
                            perseverance, and we
                            are confident that they will go on to achieve great things in their future endeavors.</p>
                    </td>
                </tr>
                <tr>
                    <td style="width: 100%; text-align: center;">
                        <div class="additional-content">
                            This certificate is issued by <strong>Pharmatop Institutions</strong>
                            @if (!empty($completion_date))
                                on <strong>{{ $completion_date }}</strong>.
                            @endif
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <table style="width: 100%; margin-top: 200px;">
            <tr>
                <td style="vertical-align: middle; text-align: left; padding-top: 20px;">
                    <div class="left-align">
                        <img src="https://pharmatop.s3.eu-north-1.amazonaws.com/logos/pharmatop-logo.png"
                            alt="logo">
                    </div>
                </td>
                <td style="vertical-align: middle; text-align: right; padding-top: 50px;">
                    <div class="right-align">
                        <p class="signature">Signature: ________________________</p>
                    </div>
                </td>
            </tr>
        </table>

    </div>
</body>

</html>
