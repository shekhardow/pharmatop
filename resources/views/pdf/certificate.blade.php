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

        .certificate {
            /* background-color: #f9f9f9; */
            /* border: 1px solid #ddd; */
            padding: 20px;
            width: 100%;
            max-height: 100vh;
            margin: 0;
            /* box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .main-container {
            margin-bottom: 200px;
            text-align: center;
        }

        .description {
            margin-right: 40px;
        }

        h1 {
            font-size: 50px;
            font-weight: 500;
            margin-bottom: 0;
        }

        h2,
        h3 {
            font-weight: normal;
            margin-bottom: 50px;
        }

        .additional-content {
            font-size: 12px;
            color: #666;
            margin-top: 10px;
        }

        .bottom-container {
            margin: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .left-align img {
            /* width: 100px; */
            height: auto;
            display: block;
            margin: 0;
            padding: 0;
        }

        .right-align {
            text-align: right;
            padding-right: 40px;
        }

        .date {
            font-size: 12px;
            color: #666;
            margin: 0;
        }

        .signature {
            font-size: 14px;
            font-weight: bold;
            margin: 0;
        }

        .uppercase {
            text-transform: uppercase;
        }

        .bold {
            font-weight: 300;
            font-size: 25px;
        }
    </style>
</head>

<body>
    <div class="certificate">
        <div class="main-container">
            <h1>Certificate of Completion</h1>
            <h2>In recognition of completing a course</h2>
            <p class="uppercase">This is to certify that</p>
            <p class="bold"><?php echo !empty($student_name) ? $student_name : ''; ?></p>
            <p class="description">has successfully completed the course
                <strong><?php echo !empty($course_name) ? $course_name : ''; ?></strong> and
                has demonstrated a thorough understanding of the course material. The student has shown exceptional
                dedication and commitment to their studies, and has consistently
                demonstrated a high level of academic achievement.
            </p>
            <p class="description">This certificate is a testament to the student's hard work and perseverance, and we
                are confident that
                they
                will go on to achieve great things in their future endeavors.</p>
            <div class="additional-content">
                This certificate is issued by <strong>Pharmatop Institutions</strong>
                <?php echo !empty($completion_date) ? "on <strong>$completion_date</strong>." : ''; ?>
            </div>
        </div>
        <div class="bottom-container">
            <div class="left-align">
                <img src="<?php echo url('public/assets/pharmatop-logo.png'); ?>" alt="logo">
            </div>
            <div class="right-align">
                <p class="signature ">Signature: ________________________</p>
            </div>
        </div>
    </div>
</body>

</html>
