<?php

namespace JordJD\uxdm\Objects\Destinations;

use JordJD\uxdm\Interfaces\DestinationInterface;
use Dompdf\Dompdf;

class PDFDestination implements DestinationInterface
{
    private $file = '';
    private $html = '';
    private $htmlPrefix = '';
    private $htmlSuffix = '';
    private $paperSize = 'A4';
    private $paperOrientation = 'portrait';
    private $rowNum = 0;
    private $fieldNames = [];

    public function __construct($file)
    {
        $directory = dirname($file);

        if (!is_dir($directory)) {
            throw new \InvalidArgumentException('No such directory: ' . $directory);
        }

        $this->file = $file;
    }

    public function setHtmlPrefix($htmlPrefix): void
    {
        $this->htmlPrefix = $htmlPrefix;
    }

    public function setHtmlSuffix($htmlSuffix): void
    {
        $this->htmlSuffix = $htmlSuffix;
    }

    public function setPaperSize($paperSize): void
    {
        $this->paperSize = $paperSize;
    }

    public function setPaperOrientation($paperOrientation): void
    {
        $this->paperOrientation = $paperOrientation;
    }

    public function putDataRows(array $dataRows): void
    {
        foreach ($dataRows as $dataRow) {
            if ($this->rowNum === 0) {
                foreach ($dataRow->getDataItems() as $dataItem) {
                    $this->fieldNames[] = $dataItem->fieldName;
                }
                $this->html .= '<table class="uxdm-table">';
                $this->html .= '<tr class="uxdm-fields"><th class="uxdm-field">';
                $this->html .= implode('</th><th class="uxdm-field">', array_map([$this, 'escapeHtml'], $this->fieldNames));
                $this->html .= '</th></tr>';
            }

            $row = $dataRow->toArray();
            $values = [];
            foreach ($this->fieldNames as $fieldName) {
                $values[] = $this->escapeHtml(array_key_exists($fieldName, $row) ? $row[$fieldName] : '');
            }
            $this->html .= '<tr class="uxdm-values"><td class="uxdm-value">';
            $this->html .= implode('</td><td class="uxdm-value">', $values);
            $this->html .= '</td></tr>';

            $this->rowNum++;
        }
    }

    public function finishMigration(): void
    {
        $this->html .= '</table>';

        $htmlToRender = $this->htmlPrefix.
            $this->html.
            $this->htmlSuffix;

        $dompdf = new Dompdf();
        $dompdf->loadHtml($htmlToRender);
        $dompdf->setPaper($this->paperSize, $this->paperOrientation);

        $dompdf->render();
        $pdfContent = $dompdf->output();

        if (file_put_contents($this->file, $pdfContent) === false) {
            throw new \RuntimeException('Unable to write PDF file: '.$this->file);
        }
    }

    private function escapeHtml($value): string
    {
        if (is_array($value)) {
            $encoded = json_encode($value);
            $value = $encoded === false ? '' : $encoded;
        } elseif (is_object($value) && !method_exists($value, '__toString')) {
            $encoded = json_encode($value);
            $value = $encoded === false ? '' : $encoded;
        }

        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
