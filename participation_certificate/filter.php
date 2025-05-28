<?php
require('fpdf.php');

function sanitizeFileName(string $string): string {
    return preg_replace('/[^A-Za-z0-9_\-]/', '_', $string);
}

class PDFCertificate extends FPDF {
    protected $fontPath;
    protected $name;
    protected $template;

    public function __construct($orientation, $unit, $size, $template, $fontPath, $name) {
        parent::__construct($orientation, $unit, $size);
        $this->template = $template;
        $this->fontPath = $fontPath;
        $this->name = $name;
    }

    function Header() {
        // Place background image on full page
        $this->Image($this->template, 0, 0, $this->GetPageWidth(), $this->GetPageHeight());
    }

    function AddName() {
        // Add your custom font
        $this->AddFont('Belleza','','Belleza.php');  // Make sure Belleza.php font definition exists (see note below)

        $this->SetFont('Belleza','',33);  // Adjust font size here
        $this->SetTextColor(0, 0, 0);

        // Calculate width of the text for centering
        $textWidth = $this->GetStringWidth($this->name);

        // Center horizontally, vertical position adjust as needed
        $x = 170;
        $y = 116; // Adjust Y position as needed

        $this->SetXY($x, $y);
        $this->Write(0, $this->name);
    }
}

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
            $templatePath = 'cert_template.jpg'; // Your background image
            $fontPath = __DIR__ . '/Belleza.ttf'; // Your TTF font file

            if (!file_exists($templatePath)) {
                throw new RuntimeException("Certificate template image not found.");
            }

            if (!file_exists($fontPath)) {
                throw new RuntimeException("Font file not found.");
            }

            // Create PDF
            $pdf = new PDFCertificate('L', 'mm', 'A4', $templatePath, $fontPath, $name);
            $pdf->AddPage();

            // Register the font (FPDF needs font files converted to .php font files)
            // If you don't have Belleza.php, see note below how to create it
            $pdf->AddFont('Belleza','','Belleza.php');

            $pdf->AddName();

            $cleanName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $name);
            $pdfOutput = "NEO25_Certificate_$cleanName.pdf";
            $pdf->Output('D', $pdfOutput);
        } else {
            echo "<h3 style='color:red; text-align:center;'>Phone number not found in our records. Please check and try again.</h3>";
        }
    } catch (Exception $e) {
        echo "<h3 style='color:red; text-align:center;'>Error: " . htmlspecialchars($e->getMessage()) . "</h3>";
    }
}
?>
