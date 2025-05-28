
<?php
require('fpdf.php');
ini_set('memory_limit', '1024M');

function sanitizeFileName($string) {
    return preg_replace('/[^A-Za-z0-9_\-]/', '_', $string);
}

function generateCertificate($name) {
    $templatePath = 'cert_template.jpg'; // Your uploaded certificate background
    $fontPath = __DIR__ . '/Belleza.ttf'; // Font file

    // Load certificate template
    $image = imagecreatefromjpeg($templatePath);
    $black = imagecolorallocate($image, 0, 0, 0);
    $fontSize =380; // Adjust font size as needed

    // Calculate name position
    $bbox = imagettfbbox($fontSize, 0, $fontPath, $name);
    $x = 8299;
    $y = 5100; // Adjust Y-position as needed

    // Add name to image
    imagettftext($image, $fontSize, 0, $x, $y, $black, $fontPath, $name);

    // Save temp image
    $cleanName = sanitizeFileName($name);
    $tempJpg = "cert_$cleanName.jpg";
    imagejpeg($image, $tempJpg);
    imagedestroy($image);

    // Generate PDF
    $pdf = new FPDF('L', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->Image($tempJpg, 0, 0, 297, 210);
    $pdfOutput = "NEO25_Certificate_$cleanName.pdf";
    $pdf->Output('D', $pdfOutput);

    unlink($tempJpg); // Clean up
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['certificate_form'])) {
    $nameInput = trim($_POST['name']);
    $phoneInput = trim($_POST['contact']);

    $found = false;

    if (($handle = fopen('data.csv', 'r')) !== false) {
        fgetcsv($handle); // skip header
        while (($data = fgetcsv($handle)) !== false) {
            if ($data[1] == $phoneInput) {
                $found = true;
                $name = $data[0]; // Get official name from CSV
                break;
            }
        }
        fclose($handle);
    }

    if ($found) {
        generateCertificate($name);
    } else {
        echo "<h3 style='color:red; text-align:center;'>Phone number not found in our records. Please check and try again.</h3>";
    }
}
?>
