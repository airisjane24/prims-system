<?php

namespace App\Services;

use App\Constant\MyConstant;
use App\Models\Document;
use App\Models\Notification;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Element\Text;
use Smalot\PdfParser\Parser;

class DocumentService
{
    private $validator;

    public function __construct(useValidator $validator)
    {
        $this->validator = $validator;
    }

    /* =========================================================
     | STORE DOCUMENT
     ========================================================= */
    public function store($request)
    {
        $validator = Validator::make($request->all(), $this->validator->documentValidator());

        if ($validator->fails()) {
            session()->flash('error', $validator->errors()->first());
            return [
                'error_code' => MyConstant::FAILED_CODE,
                'status_code' => MyConstant::BAD_REQUEST,
                'message' => $validator->errors()->first(),
            ];
        }

        try {
            $document = new Document();

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $ocrText = $this->extractTextFromFile($file);

                if (strlen(trim($ocrText)) < 30) {
                    return $this->reject('Failed to upload document or image is blurry.');
                }

                if ($this->checkForInappropriateContent($ocrText)) {
                    return $this->reject('Inappropriate content detected.');
                }

                $documentType = $this->determineDocumentType($ocrText);

                if ($documentType === 'Verification Certificate') {
                    return $this->reject('Unable to identify document type.');
                }

                $directory = public_path("assets/documents/{$documentType}");
                if (!file_exists($directory)) {
                    mkdir($directory, 0777, true);
                }

                $fileName = time() . '_' . $file->getClientOriginalName();
                $file->move($directory, $fileName);

                $document->document_type = $documentType;
                $document->file = $fileName;
            }

            $document->full_name = $request->full_name;
            $document->uploaded_by = $request->uploaded_by;
            $document->save();

            Notification::create([
                'type' => 'Document',
                'message' => 'A new document has been uploaded by ' . Auth::user()->name,
                'is_read' => 0
            ]);

            session()->flash('success', 'Document created successfully');
            return $this->success('Document created successfully');

        } catch (QueryException $e) {
            return $this->error();
        }
    }

    /* =========================================================
     | TEXT EXTRACTION (ANY FORMAT)
     ========================================================= */
    private function extractTextFromFile($file)
    {
        $ext = strtolower($file->getClientOriginalExtension());

        return match ($ext) {
            'pdf'   => $this->convertPdfToText($file->getPathname()),
            'docx'  => $this->convertDocxToText($file->getPathname()),
            'doc'   => $this->convertDocToText($file->getPathname()),
            'txt'   => file_get_contents($file->getPathname()),
            'rtf'   => strip_tags(file_get_contents($file->getPathname())),
            'jpg', 'jpeg', 'png', 'bmp', 'webp'
                    => $this->processImageWithTesseract($file->getPathname()),
            default => $this->processImageWithTesseract($file->getPathname()),
        };
    }

    /* =========================================================
     | DOCUMENT TYPE CLASSIFICATION (SAME LAYOUT SAFE)
     ========================================================= */
    private function determineDocumentType($text)
    {
        $text = strtolower(preg_replace('/\s+/', ' ', $text));

        // TITLE PRIORITY
        if (preg_match('/certificate of death|death certificate/', $text)) {
            return 'Death Certificate';
        }
        if (preg_match('/certificate of marriage|marriage certificate/', $text)) {
            return 'Marriage Certificate';
        }
        if (preg_match('/baptismal certificate|certificate of baptism/', $text)) {
            return 'Baptismal Certificate';
        }
        if (preg_match('/confirmation certificate/', $text)) {
            return 'Confirmation Certificate';
        }

        // WEIGHTED KEYWORDS
        $scores = [
            'Death Certificate' => 0,
            'Marriage Certificate' => 0,
            'Baptismal Certificate' => 0,
            'Confirmation Certificate' => 0,
        ];

        // Death
        foreach (['death', 'deceased', 'burial', 'cause of death'] as $k) {
            if (str_contains($text, $k)) $scores['Death Certificate'] += 5;
        }

        // Marriage
        foreach (['marriage', 'married', 'bride', 'groom'] as $k) {
            if (str_contains($text, $k)) $scores['Marriage Certificate'] += 4;
        }

        // Baptismal
        foreach (['baptism', 'baptized', 'godparent'] as $k) {
            if (str_contains($text, $k)) $scores['Baptismal Certificate'] += 4;
        }

        // Confirmation
        foreach (['confirmation', 'confirmed'] as $k) {
            if (str_contains($text, $k)) $scores['Confirmation Certificate'] += 4;
        }

        arsort($scores);
        $best = array_key_first($scores);

        return $scores[$best] > 0 ? $best : 'Verification Certificate';
    }

    /* =========================================================
     | FILE CONVERTERS
     ========================================================= */
    private function convertPdfToText($path)
    {
        return (new Parser())->parseFile($path)->getText();
    }

    private function convertDocxToText($path)
    {
        $phpWord = IOFactory::load($path);
        $text = '';

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                    foreach ($element->getElements() as $el) {
                        if ($el instanceof Text) {
                            $text .= $el->getText() . "\n";
                        }
                    }
                }
            }
        }
        return $text;
    }

    private function convertDocToText($path)
    {
        return $this->convertDocxToText($path);
    }

    private function processImageWithTesseract($path)
    {
        $out = public_path('assets/documents/out_' . time());
        $tesseract = 'C:\Program Files\Tesseract-OCR\tesseract.exe';

        shell_exec("\"$tesseract\" \"$path\" \"$out\"");
        return file_exists($out . '.txt') ? file_get_contents($out . '.txt') : '';
    }

    /* =========================================================
     | AI CONTENT CHECK
     ========================================================= */
    private function checkForInappropriateContent($text)
    {
        $script = base_path('python/svm_model.py');
        $output = shell_exec("python \"$script\" \"" . escapeshellarg($text) . "\"");
        return trim($output) === 'inappropriate';
    }

    /* =========================================================
     | HELPERS
     ========================================================= */
    private function reject($msg)
    {
        session()->flash('error', $msg);
        return [
            'error_code' => MyConstant::FAILED_CODE,
            'status_code' => MyConstant::BAD_REQUEST,
            'message' => $msg,
        ];
    }

    private function success($msg)
    {
        return [
            'error_code' => MyConstant::SUCCESS_CODE,
            'status_code' => MyConstant::OK,
            'message' => $msg,
        ];
    }

    private function error()
    {
        session()->flash('error', 'Internal server error');
        return [
            'error_code' => MyConstant::FAILED_CODE,
            'status_code' => MyConstant::INTERNAL_SERVER_ERROR,
            'message' => 'Internal server error',
        ];
    }
}
