<?php

// =====================================================
// JANSEWA DENTAL CLINIC - APPOINTMENT FORM HANDLER
// =====================================================

// Only allow POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    exit("Invalid request.");
}


// =====================================================
// GET FORM DATA
// =====================================================

$name = trim($_POST["name"] ?? "");
$phone = trim($_POST["phone"] ?? "");
$email = trim($_POST["email"] ?? "");
$reason = trim($_POST["reason"] ?? "");
$website = trim($_POST["website"] ?? "");

// =====================================================
// HONEYPOT ANTI-SPAM CHECK
// =====================================================

if ($website !== "") {
    exit("Invalid submission.");
}

// =====================================================
// BASIC VALIDATION
// =====================================================

if ($name === "" || $phone === "" || $email === "" || $reason === "") {
    exit("Please fill in all required fields.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit("Please enter a valid email address.");
}

if (!preg_match('/^[0-9+\-\s()]{7,15}$/', $phone)) {
    exit("Please enter a valid phone number.");
}


// =====================================================
// CLEAN DATA
// =====================================================

$name = htmlspecialchars($name, ENT_QUOTES, "UTF-8");
$phone = htmlspecialchars($phone, ENT_QUOTES, "UTF-8");
$email = htmlspecialchars($email, ENT_QUOTES, "UTF-8");
$reason = htmlspecialchars($reason, ENT_QUOTES, "UTF-8");


// =====================================================
// RATE LIMITING
// =====================================================

// Maximum number of valid form submissions
// allowed from one IP address within the time window.
$rateLimitMax = 3;
$rateLimitWindow = 10 * 60; // 10 minutes


// Get visitor IP address
$visitorIp = $_SERVER["REMOTE_ADDR"] ?? "unknown";


// Create a one-way hash of the IP address.
// We do not store the raw IP address in the filename.
$rateLimitKey = hash(
    "sha256",
    "jansewa-dental-clinic|" . $visitorIp
);


// Use PHP's temporary directory for the rate-limit file.
$rateLimitFile = rtrim(
    sys_get_temp_dir(),
    DIRECTORY_SEPARATOR
) . DIRECTORY_SEPARATOR . "jansewa_rate_" . $rateLimitKey . ".txt";


$currentTime = time();


// Open or create the rate-limit file.
$rateHandle = @fopen($rateLimitFile, "c+");


if ($rateHandle !== false) {

    // Lock the file so simultaneous requests
    // do not corrupt the stored timestamps.
    if (flock($rateHandle, LOCK_EX)) {

        // Read existing submission timestamps.
        rewind($rateHandle);

        $storedData = stream_get_contents($rateHandle);

        $timestamps = [];

        if ($storedData !== false && trim($storedData) !== "") {

            $storedLines = preg_split(
                "/\r\n|\r|\n/",
                trim($storedData)
            );

            foreach ($storedLines as $timestamp) {

                $timestamp = (int) trim($timestamp);

                // Keep only timestamps inside
                // the 10-minute window.
                if (
                    $timestamp > 0 &&
                    ($currentTime - $timestamp) < $rateLimitWindow
                ) {
                    $timestamps[] = $timestamp;
                }
            }
        }


        // Check whether the visitor has already
        // reached the allowed submission limit.
        if (count($timestamps) >= $rateLimitMax) {

            flock($rateHandle, LOCK_UN);
            fclose($rateHandle);

            http_response_code(429);

            exit(
                "Too many appointment requests were submitted. " .
                "Please wait a few minutes and try again."
            );
        }


        // Record the current submission.
        $timestamps[] = $currentTime;


        // Keep the file limited to the recent timestamps.
        $timestamps = array_slice($timestamps, -$rateLimitMax);


        // Replace the old contents with the new timestamps.
        rewind($rateHandle);
        ftruncate($rateHandle, 0);

        fwrite(
            $rateHandle,
            implode(PHP_EOL, $timestamps)
        );


        fflush($rateHandle);

        flock($rateHandle, LOCK_UN);
    }

    fclose($rateHandle);
}


// =====================================================
// DOCTOR EMAIL ADDRESS
// =====================================================

$to = "dr.rishubedi@gmail.com";

// =====================================================
// SEND EMAIL #1 — APPOINTMENT ENQUIRY TO DOCTOR
// =====================================================

$doctorSubject = "New Appointment Request - Jansewa Dental Clinic";


$doctorMessage = "
New appointment request received from the Jansewa Dental Clinic website.

--------------------------------------------------

Patient Name:
$name

Phone Number:
$phone

Email Address:
$email

Reason for Visit:
$reason

--------------------------------------------------

Please contact the patient to discuss and confirm
a suitable appointment date and time.

Jansewa Dental Clinic
20, Main Malka Ganj Road
Kamla Nagar, Delhi - 110007
";


$doctorHeaders = "From: Jansewa Dental Clinic From: Jansewa Dental Clinic <appointments@jansewadentalclinic.com>\r\n";
$doctorHeaders .= "Reply-To: $email\r\n";
$doctorHeaders .= "Content-Type: text/plain; charset=UTF-8\r\n";


// Send appointment enquiry to doctor first
$doctorSent = mail(
    $to,
    $doctorSubject,
    $doctorMessage,
    $doctorHeaders
);
// =====================================================
// WEBSITE RESPONSE
// =====================================================

if ($doctorSent) {

    echo "

    <!DOCTYPE html>

    <html lang='en'>

    <head>

        <meta charset='UTF-8'>

        <meta
            name='viewport'
            content='width=device-width, initial-scale=1.0'
        >

        <title>Appointment Request Received</title>

        <style>

            body {

                margin: 0;

                font-family: Arial, sans-serif;

                background: #FCFCFA;

                color: #35332F;

                display: flex;

                align-items: center;

                justify-content: center;

                min-height: 100vh;

                text-align: center;

            }


            .message-box {

                width: min(600px, 90%);

                padding: 50px;

                background: #FFFFFF;

                border-radius: 20px;

                box-shadow:
                    0 15px 40px rgba(53,51,47,.10);

            }


            h1 {

                margin-bottom: 15px;

            }


            p {

                color: #666666;

                line-height: 1.7;

            }


            a {

                display: inline-block;

                margin-top: 20px;

                padding: 13px 25px;

                background: #DB9558;

                color: #FFFFFF;

                text-decoration: none;

                border-radius: 30px;

            }

        </style>

    </head>


    <body>

        <div class='message-box'>

            <h1>Thank You!</h1>

            <p>
                Your appointment request has been received
                successfully.
            </p>

            <p>
                Our clinic team will contact you on the phone number
                you provided to discuss and confirm a suitable
                appointment date and time.
            </p>

            <p>
                Please note that this message confirms receipt
                of your request only. It does not confirm an
                appointment date or time.
            </p>

            <a href='index.html'>
                Return to Website
            </a>

        </div>

    </body>

    </html>

    ";

}
else {

    echo "

    <!DOCTYPE html>

    <html lang='en'>

    <head>

        <meta charset='UTF-8'>

        <meta
            name='viewport'
            content='width=device-width, initial-scale=1.0'
        >

        <title>Unable to Send Request</title>

        <style>

            body {

                margin: 0;

                font-family: Arial, sans-serif;

                background: #FCFCFA;

                color: #35332F;

                display: flex;

                align-items: center;

                justify-content: center;

                min-height: 100vh;

                text-align: center;

            }


            .message-box {

                width: min(600px, 90%);

                padding: 50px;

                background: #FFFFFF;

                border-radius: 20px;

                box-shadow:
                    0 15px 40px rgba(53,51,47,.10);

            }


            h1 {

                margin-bottom: 15px;

            }


            p {

                color: #666666;

                line-height: 1.7;

            }


            a {

                display: inline-block;

                margin-top: 20px;

                padding: 13px 25px;

                background: #DB9558;

                color: #FFFFFF;

                text-decoration: none;

                border-radius: 30px;

            }

        </style>

    </head>


    <body>

        <div class='message-box'>

            <h1>We're Sorry</h1>

            <p>
                We couldn't send your appointment request
                at this time.
            </p>

            <p>
                Please contact Jansewa Dental Clinic directly
                at +91 98917 47222.
            </p>

            <a href='index.html'>
                Return to Website
            </a>

        </div>

    </body>

    </html>

    ";

}

?>