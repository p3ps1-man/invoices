<?php

require_once 'vendor/autoload.php';

use Dompdf\Dompdf;
use Carbon\Carbon;
use Dotenv\Dotenv;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$mailClient = new PHPMailer(true);

try {
    $mailClient->isSMTP();
    $mailClient->SMTPAuth = true;
    $mailClient->Host = $_ENV['COMPANY_EMAIL_HOST'];
    $mailClient->Username = $_ENV['COMPANY_EMAIL'];
    $mailClient->Password = $_ENV['COMPANY_EMAIL_PASSWORD'];
    $mailClient->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mailClient->Port = 587;
    $mailClient->CharSet = 'UTF-8';

    $mailClient->setFrom($_ENV['COMPANY_EMAIL'], $_ENV['COMPANY_OWNER']);
} catch (Exception $e) {
    die("Failed creating email client");
}


$hours = isset($_POST['hours']) ? floatval($_POST['hours']) : 0;
$rate = isset($_POST['rate']) ? floatval($_POST['rate']) : floatval($_ENV["DEFAULT_RATE"]);

if ($rate === 0 || $rate === "" || $rate < 0) {
    $rate = floatval($_ENV["DEFAULT_RATE"]);
}
$total = $hours * $rate;

$now = Carbon::now();
$invoiceKey = $now->format('Y-m');
$invoiceDate = $now->format('d.m.Y');

$prevMonth = Carbon::now()->subMonth();

$lastDayOfMonth = $prevMonth->endOfMonth()->format('d.m.Y');
$firstDayOfMonth = $prevMonth->startOfMonth()->format('d.m.Y');
$dueDate = Carbon::now()->startOfMonth()->addDays(14)->format("d.m.Y");

$total = number_format($rate * $hours, 2, '.', ',');
$rate = number_format($rate, 2, '.', ',');

$directory = "invoices/";
if (!is_dir($directory)) {
    if (!mkdir($directory, 0777, true)) {
        die('Failed to create directory...');
    }
}

$defaultStyle = "<style>
        *{ 
            font-family: DejaVu Sans; 
            font-size: 12px;
            box-sizing: border-box;
            line-height: normal;
        }
        p {
            margin: 0;
            padding: 0;
            line-height: 1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead tr {
            border-bottom: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        tbody tr {
            border-bottom: 1px solid black;
        }
        tbody td {
            padding: 8px;
            text-align: center;
        }
    </style>";

$html = <<<FAKTURA
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    $defaultStyle
</head>
<body>
    <p><b>{$_ENV["COMPANY_FULL_NAME"]}</b></p>
    <p><b>{$_ENV["COMPANY_INFO"]}</b></p>
    <p><b>Reg ID: {$_ENV["COMPANY_REG_ID"]} Tax ID: {$_ENV["COMPANY_TAX_ID"]}</b></p>
    <hr>
    
    <br>
    <p><b>{$_ENV["ADRESSING_COMPANY"]}</b></p>
    <p>{$_ENV["ADRESSING_COMPANY_INFO"]}</p>
    <p>VAT: {$_ENV["ADRESSING_COMPANY_TAX_ID"]}</p>

    <br>
    <p><b>Invoice $invoiceKey</b></p>
    
    <br>
    <p>{$_ENV["COMPANY_CITY"]}, $invoiceDate</p>
    <p>Due date $dueDate</p>
    <p>Date of service $lastDayOfMonth</p>

    <br>
    <table>
       <thead>
            <tr>
                <th>service description</th>
                <th>Qty</th>
                <th>Rate</th>
                <th>Total (in EUR)</th>
            </tr>
       </thead>
       <tbody>
            <tr>
                <td>development</td>
                <td>$rate</td>
                <td>$hours</td>
                <td>$total</td>
            </tr>
       </tbody>
    </table>

    <br>
    <p><b>TOTAL in EUR  $total</b></p>

    <br>
    <br>
    <p><b>Payment Instructions:</b></p>
    <br>
    {$_ENV["PAYMENT_INSTRUCTIONS"]}
</body>
</html>
<head>
FAKTURA;

$dompdf = new Dompdf();

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$fileName = "Invoice-" . $now->format("M-Y") . ".pdf";
file_put_contents($directory . $fileName, $dompdf->output());

try {
    $mailClient->addAddress($_ENV['ADRESSING_COMPANY_INVOICE_EMAIL']);
    $mailClient->Subject = "Invoice for company: " . $_ENV['COMPANY_NAME'];
    $mailClient->Body = <<<MAILBODY
    Hi,\n
    Here is the invoice for the company {$_ENV["COMPANY_NAME"]} for {$prevMonth->format('F Y')}.\n
    Kind regards,
    {$_ENV["COMPANY_OWNER"]}
    MAILBODY;
    $mailClient->addAttachment($directory . $fileName);

    if (!$mailClient->send()) {
        die("Failed sending email to company\n" . $mailClient->ErrorInfo);
    }
} catch (Exception $e) {
    die("Failed sending email to company");
}

$htmlSrpski = <<<FAKTURA
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    $defaultStyle
</head>
<body>

    <p><b>{$_ENV["COMPANY_FULL_NAME"]}</b></p>
    <p><b>{$_ENV["COMPANY_INFO"]}</b></p>
    <p><b>Reg broj: {$_ENV["COMPANY_REG_ID"]} JIB: {$_ENV["COMPANY_TAX_ID"]}</b></p>
    <p><b>Tekući račun: {$_ENV["COMPANY_BANK_ACCOUNT"]}</b></p>
    <p><b>Devizni račun: {$_ENV["COMPANY_IBAN"]}</b></p>
    <hr>
    
    <br>
    <p><b>Faktura $invoiceKey</b></p>

    <br>
    <p><b>{$_ENV["ADRESSING_COMPANY"]}</b></p>
    <p>{$_ENV["ADRESSING_COMPANY_INFO"]}</p>
    <p>VAT: {$_ENV["ADRESSING_COMPANY_TAX_ID"]}</p>

    <br>
    <p>{$_ENV["COMPANY_CITY"]}, $invoiceDate</p>
    <p>Datum usluge $firstDayOfMonth - $lastDayOfMonth</p>

    <br>
    <table>
       <thead>
            <tr>
                <th>Usluga</th>
                <th>Satnica</th>
                <th>Sati</th>
                <th>Ukupno (EUR)</th>
            </tr>
       </thead>
       <tbody>
            <tr>
                <td>Razvoj softvera</td>
                <td>$rate</td>
                <td>$hours</td>
                <td>$total</td>
            </tr>
       </tbody>
    </table>

    <br>
    <p><b>UKUPNO u EUR $total</b></p>

    <br>
    <br>
    <p style="text-align:right;margin-bottom:50px">Ovlašćeno lice:</p>
    <hr style="width:30%;text-align:right;margin-right:0">
</body>
</html>
<head>
FAKTURA;

$dompdf = new Dompdf();

$dompdf->loadHtml($htmlSrpski);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$fileName = "Faktura-" . $now->format("M-Y") . ".pdf";
file_put_contents($directory . $fileName, $dompdf->output());

try {
    Carbon::setLocale('sr');
    $mailClient->clearAddresses();
    $mailClient->clearAttachments();
    $mailClient->addAddress($_ENV['ACCOUNTANT_EMAIL']);
    $mailClient->Subject = "Faktura za " . $prevMonth->translatedFormat('F Y');
    $mailClient->Body = "Faktura za " . $prevMonth->translatedFormat('F Y');
    $mailClient->addAttachment($directory . $fileName);

    if (!$mailClient->send()) {
        die("Failed sending email to accountant\n" . $mailClient->ErrorInfo);
    }
} catch (Exception $e) {
    die("Failed sending email to accountant");
}

echo "INVOICES GENERATED";
