<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Certificate</title>
    <style>
        p {
            font-size: 22px;
        }
    </style>
</head>

<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; width: 100%; box-sizing: border-box;">
    <table width="100%" style="border-collapse: collapse;">
        <tr>
            <td colspan="3" style="text-align: center; padding: 0 50px; width: 100%;">
                <img src="https://pharmatop.s3.eu-north-1.amazonaws.com/logos/pharmatop_logo.svg" alt="logo"
                    style="float: left; width: 50%; margin-top: 80px;">
                <img src="https://pharmatop.s3.eu-north-1.amazonaws.com/logos/arrow.svg" alt="arrow"
                    style="float: right; width: 20%;">
            </td>
        </tr>
    </table>
    <table width="100%" style="border-collapse: collapse; margin-top: 80px;">
        <tr style="background-color: #6d2f92; color: white; width: 100%;">
            <td colspan="3" style="text-align: start; padding: 50px 50px 0 50px; font-size: 24px; width: 100%;">
                <h1 style="font-size: 45px;">{{ $course_name ?? 'Course' }}</h1>
            </td>
        </tr>
        <tr style="background-color: #6d2f92; color: white; width: 100%;">
            <td colspan="3" style="text-align: start; padding: 0 50px; font-size: 18px; width: 100%;">
                <p style="font-size: 40px;">{{ date('d.m.Y', strtotime($completion_date)) ?? date('d.m.Y') }}</p>
            </td>
        </tr>
    </table>
    <table width="100%" style="border-collapse: collapse; margin-top: 50px;">
        <tr>
            <td colspan="3" style="text-align: center; width: 100%;">
                <p style="color: #6d2f92; font-size: 40px;">POTVRDA O SUDJELOVANJU</p>
                <p style="margin: 0;">Kojom se potvrđuje da je</p>
            </td>
        </tr>
        <tr>
            <td colspan="3" style="text-align: center; font-size: 24px; width: 100%;">
                <p style="font-size: 40px;">{{ $student_name ?? 'User' }}</p>
            </td>
        </tr>
        <tr>
            <td colspan="3" style="text-align: center; font-size: 16px; width: 100%;">
                <p>sudjelovala na online PharmaTop edukaciji</p>
                <p style="color: #6d2f92;">{{ $course_name ?? 'Course' }}</p>
            </td>
        </tr>
    </table>
    <table width="100%" style="border-collapse: collapse; margin-top: 50px;">
        <tr>
            <td colspan="3" style="text-align: center; width: 100%;">
                <p>Organizator:</p>
                {{-- <p style="font-size: 20px;">PHARMATOP INSTITUTIONS</p> --}}
            </td>
        </tr>
        <tr>
            <td style="text-align: center; padding: 20px 0; width: 50%;">
                <p>{{ date('d.m.Y', strtotime($completion_date)) ?? date('d.m.Y') }}</p>
                <hr style="width: 50%; margin: auto; border: 1px solid gray;">
                <p style="font-size: 20px;">Datum</p>
            </td>
            <td style="text-align: center; padding: 20px 0; width: 50%;">
                <p>PHARMATOP d.o.o. PAKRAC</p>
                <hr style="width: 70%; margin: auto; border: 1px solid gray;">
                <p style="text-transform: uppercase;">Vesna Marianovic Jukic</p>
                <p style="font-size: 20px;">mag. pharm., univ. spec. oec.</p>
            </td>
        </tr>
    </table>
</body>

</html>
