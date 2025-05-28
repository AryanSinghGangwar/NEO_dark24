<?php
require('fpdf.php');
ini_set('memory_limit', '1024M');

function sanitizeFileName($string) {
    return preg_replace('/[^A-Za-z0-9_\-]/', '_', $string);
}

function generateCertificate($name) {
    $templatePath = 'cert_template.jpg'; // Your certificate background image
    $fontPath = __DIR__ . '/Belleza.ttf'; // Path to your TTF font file

    if (!file_exists($templatePath)) {
        die("Certificate template not found.");
    }

    if (!file_exists($fontPath)) {
        die("Font file not found.");
    }

    // Load certificate image
    $image = imagecreatefromjpeg($templatePath);
    if (!$image) {
        die("Failed to load certificate template image.");
    }

    $black = imagecolorallocate($image, 0, 0, 0);

    $fontSize = 380; // Adjust font size appropriately

    // Calculate bounding box for text (not used here to center but you can use it)
    $bbox = imagettfbbox($fontSize, 0, $fontPath, $name);

    // Image width and height
    $imgWidth = imagesx($image);
    $imgHeight = imagesy($image);

    // Calculate X to center text horizontally
    $textWidth = $bbox[2] - $bbox[0];
    $x = 8299;

    // Y position - place it somewhere on image height
    $y = 5100;

    // Add name text on image
    imagettftext($image, $fontSize, 0, $x, $y, $black, $fontPath, $name);

    // Save temporary image
    $cleanName = sanitizeFileName($name);
    $tempJpg = "cert_$cleanName.jpg";
    imagejpeg($image, $tempJpg, 90);
    imagedestroy($image);

    // Create PDF
    $pdf = new FPDF('L', 'mm', 'A4');
    $pdf->AddPage();

    // Get A4 dimensions in pixels (approximate), or scale image to fit
    $pdf->Image($tempJpg, 0, 0, 297, 210); // A4 size in mm

    $pdfOutput = "NEO25_Certificate_$cleanName.pdf";

    // Output PDF as download
    $pdf->Output('D', $pdfOutput);

    // Delete temporary image
    unlink($tempJpg);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['certificate_form'])) {
    $nameInput = trim($_POST['name']);
    $phoneInput = trim($_POST['contact']);

    if (empty($nameInput) || empty($phoneInput)) {
        echo "<h3 style='color:red; text-align:center;'>Please provide both name and phone number.</h3>";
        exit;
    }

    $found = false;

    if (($handle = fopen('data.csv', 'r')) !== false) {
        fgetcsv($handle); // skip header
        while (($data = fgetcsv($handle, 0, ",", '"')) !== false) {
            // assuming $data[1] is phone and $data[0] is name
            if (trim($data[1]) === $phoneInput) {
                $found = true;
                $name = trim($data[0]); // Get official name from CSV
                break;
            }
        }
        fclose($handle);
    } else {
        echo "<h3 style='color:red; text-align:center;'>Failed to open data file.</h3>";
        exit;
    }

    if ($found) {
        generateCertificate($name);
    } else {
        echo "<h3 style='color:red; text-align:center;'>Phone number not found in our records. Please check and try again.</h3>";
    }
}
?>
