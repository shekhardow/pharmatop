<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate of Completion</title>
    <style>
        /* Global styles */
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            margin: 0;
            /* Remove margin to take full page width */
            padding: 0;
            /* Remove padding to take full page width */
        }

        /* Certificate container */
        .certificate {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 20px;
            width: 100%;
            max-height: 100vh;
            margin: 0;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Header styles */
        h1 {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
        }

        /* Student and course information */
        h2,
        h3 {
            font-weight: bold;
            margin-bottom: 5px;
            text-align: center;
        }

        /* Date style */
        .date {
            font-size: 12px;
            color: #666;
            text-align: center;
            margin-top: 20px;
        }

        /* Signature style */
        .signature {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            margin-top: 10px;
        }

        /* Additional content */
        .additional-content {
            font-size: 12px;
            color: #666;
            text-align: center;
            margin-top: 10px;
        }

        /* Bottom container */
        .bottom-container {
            margin-bottom: 0;
            display: flex;
            justify-content: space-between;
        }

        .left-align {
            text-align: left;
        }

        .right-align {
            text-align: right;
            margin-left: auto;
        }
    </style>
</head>

<body>
    <div class="certificate">
        <div>
            <h1>Certificate of Completion</h1>
            <p>This is to certify that</p>
            <h2>{{ $student_name }}</h2>
            <p>has successfully completed the course</p>
            <h3>{{ $course_name }}</h3>
            <p>with a duration of {{ $course_duration ?? '50 Days' }} hours</p>
            <p>and has demonstrated a thorough understanding of the course material.</p>
            <p>The course covered a range of topics, including:</p>
            <ul>
                <li>Introduction to the subject matter</li>
                <li>Key concepts and principles</li>
                <li>Practical applications and case studies</li>
                <li>Assessment and evaluation techniques</li>
            </ul>
            <p>The student has shown exceptional dedication and commitment to their studies, and has consistently
                demonstrated a high level of academic achievement.</p>
            <p>This certificate is a testament to the student's hard work and perseverance, and we are confident that
                they
                will go on to achieve great things in their future endeavors.</p>
            <div class="additional-content">
                This certificate is issued by [Institution Name] and is valid for a period of [Validity Period].
            </div>
        </div>
        <div class="bottom-container">
            <div class="left-align">
                <p>Seal of [Institution Name]</p>
                <p class="verification-code">Verification Code: ________________________</p>
            </div>
            <div class="right-align">
                <div class="date">
                    Date: {{ $completion_date }}
                </div>
                <div class="signature">
                    Signature: ________________________
                </div>
            </div>
        </div>
    </div>
</body>

</html>
