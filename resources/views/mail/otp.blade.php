<!DOCTYPE html>
<html>

<head>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.3/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100">
    <div class="flex justify-center items-center min-h-screen py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full bg-white shadow-lg rounded-lg p-8">
            <div class="text-center mb-6">
                <!-- <img src="" alt="Logo" class="mx-auto h-16" /> -->
                <h3 class="text-lg font-medium text-gray-800 mb-4">Hello <?php echo !empty($name) ? $name : 'there'; ?>,</h3>
            </div>
            <div class="text-gray-700 mb-4">
                <p><?php echo !empty($msg) ? $msg : 'We hope this message finds you well.'; ?></p>
            </div>
            <div class="text-gray-600 mb-6">
                <p>If you have any further questions or need assistance, reach out to us at
                    <a class="text-blue-500 hover:underline"
                        href="mailto:vesnamjukic@outlook.com">vesnamjukic@outlook.com
                    </a>
                </p>
            </div>
            <div class="text-gray-600">
                <p>Best Regards,<br>The Pharma<strong>top</strong> Team</p>
            </div>
        </div>
    </div>
</body>

</html>
