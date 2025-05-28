<?php
require('fpdf.php');
ini_set('memory_limit', '1024M');

// Sanitize file name for safe saving
function sanitizeFileName(string $string): string {
    return preg_replace('/[^A-Za-z0-9_\-]/', '_', $string);
}

// Generate certificate PDF
function generateCertificate(string $name): void {
    $templatePath = 'cert_template.jpg';
    $fontPath = __DIR__ . '/Belleza.ttf';

    if (!file_exists($templatePath)) {
        throw new RuntimeException("Certificate template not found.");
    }

    if (!file_exists($fontPath)) {
        throw new RuntimeException("Font file not found.");
    }

    $image = @imagecreatefromjpeg($templatePath);
    if (!$image) {
        throw new RuntimeException("Failed to load certificate template image.");
    }

    $black = imagecolorallocate($image, 0, 0, 0);
    $fontSize = 380;

    $bbox = imagettfbbox($fontSize, 0, $fontPath, $name);
    if (!$bbox) {
        throw new RuntimeException("Failed to calculate text bounding box.");
    }

    // Custom positioning — adjust if needed
    $x = 8299;
    $y = 5100;

    imagettftext($image, $fontSize, 0, $x, $y, $black, $fontPath, $name);

    $cleanName = sanitizeFileName($name);
    $tempJpg = "cert_$cleanName.jpg";
    imagejpeg($image, $tempJpg, 90);
    imagedestroy($image);

    $pdf = new FPDF('L', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->Image($tempJpg, 0, 0, 297, 210);

    $pdfOutput = "NEO25_Certificate_$cleanName.pdf";
    $pdf->Output('D', $pdfOutput);

    if (file_exists($tempJpg)) {
        unlink($tempJpg);
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['certificate_form'])) {
    $nameInput = trim($_POST['name'] ?? '');
    $phoneInput = trim($_POST['contact'] ?? '');

    if ($nameInput === '' || $phoneInput === '') {
        echo "<h3 style='color:red; text-align:center;'>Please provide both name and phone number.</h3>";
        exit;
    }

    $found = false;
    $name = '';

    try {
        if (($handle = fopen('data.csv', 'r')) !== false) {
            // Skip header
            fgetcsv($handle, 0, ",", '"', "\\");

            while (($data = fgetcsv($handle, 0, ",", '"', "\\")) !== false) {
                if (isset($data[1]) && trim($data[1]) === $phoneInput) {
                    $found = true;
                    $name = trim($data[0]);
                    break;
                }
            }
            fclose($handle);
        } else {
            throw new RuntimeException("Failed to open data file.");
        }

        if ($found) {
            generateCertificate($name);
        } else {
            echo "<h3 style='color:red; text-align:center;'>Phone number not found in our records. Please check and try again.</h3>";
        }
    } catch (Exception $e) {
        echo "<h3 style='color:red; text-align:center;'>Error: " . htmlspecialchars($e->getMessage()) . "</h3>";
    }
}
?>
