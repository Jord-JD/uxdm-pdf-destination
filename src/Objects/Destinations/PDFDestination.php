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
            $dataItems = $dataRow->getDataItems();

            if ($this->rowNum === 0) {
                $fieldNames = [];
                foreach ($dataItems as $dataItem) {
                    $fieldNames[] = htmlentities($dataItem->fieldName);
                }
                $this->html .= '<table class="uxdm-table">';
                $this->html .= '<tr class="uxdm-fields"><th class="uxdm-field">';
                $this->html .= implode('</th><th class="uxdm-field">', $fieldNames);
                $this->html .= '</th></tr>';
            }

            $values = [];
            foreach ($dataItems as $dataItem) {
                $values[] = htmlentities($dataItem->value);
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

        file_put_contents($this->file, $pdfContent);
    }
}
