<?php
/**
 * This file is part of
 * Kimai-i Open Source Time Tracking // https://github.com/claude-ws01/Kimai-i
 * (c) 2015 Claude Nadon  https://github.com/claude-ws01
 * (c) 2006-2009 Kimai-Development-Team // http://www.kimai.org
 *
 * Kimai-i is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; Version 3, 29 June 2007
 *
 * Kimai-i is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Kimai; If not, see <http://www.gnu.org/licenses/>.
 */

include('../../libraries/tcpdf/tcpdf.php');

include('base_export_pdf.php');

class MYPDF extends BasePDF
{

    var $w = array();
    var $print_time;
    var $columns;

    var $moneySum;
    var $timeSum;

    /**
     * Create the array which hold the column widths. They depends on the maximum with
     * the time column and the money column need.
     *
     * @param int $max_time_width maximum width the time column needs.
     * @param int $max_time_width maximum width the time column needs.
     *
     * @return array containing the widths of the columns
     */
    public function columnWidths($max_time_width, $max_money_width)
    {
        return array($max_time_width,
            $this->getPageWidth() - $this->pagedim[ $this->page ]['lm'] - $this->pagedim[ $this->page ]['rm'] - $max_time_width - $max_money_width,
            $max_money_width);
    }


    /**
     * Split the string in lines and check if a line would overflow and cause more lines.
     *
     * @param string $string Text to check.
     * @param int    $line_width
     *
     * @return int Number of lines the text will need.
     */
    public function getHtmlStringLines($string, $line_width)
    {
        $htmlLines = explode("<br />", $string);
        $lineCount = count($htmlLines);
        foreach ($htmlLines as $line) {
            $lineCount += ceil($this->GetStringWidth($line) / $line_width);
        }

        return $lineCount;
    }


    /**
     * Print a footer on every page.
     */
    public function Footer()
    {
        global $kga;

        // Position at 1.5 cm from bottom
        $this->SetY(-15);

        // customer data
        /*$this->SetFont('helvetica', '', 8); // Set font
        $this->Cell(80, 10, $customerData['customerName'].' ('.$projectData['pct_name'].')', 0, 0, 'L');*/

        // Page number
        $this->SetFont('helvetica', 'I', 8); // Set font
        $this->Cell(30, 10, $kga['dict']['export_extension']['page'] . ' ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');

        //Datum
        $this->SetFont('helvetica', '', 8); // Set font
        $this->Cell(0, 10, date('d.m.Y H:i', $this->print_time), 0, 0, 'R');
    }

    /**
     * Put a new entry into the PDF document. Decide if it's a time entry or
     * expense entry and call the appropriate functions.
     *
     * @param array $columns array containing all fields which should be printed
     *                       (Columns is misleading, but they were columns in the browser.)
     * @param array $data    the data of this entry
     * @param array $widths  the widths of the columns
     */
    public function printRows($data, $widths)
    {

        $this->moneySum = 0;
        $this->timeSum  = 0;
        foreach ($data as $row) {
            //DEBUG// error_log('<<== ROW TYPE PDF ==>>' . $row['type']);
            if ($row['type'] === 'expense') {
                $this->printExpenseRow($widths, $row);
                $this->moneySum += $row['wage'];
            }
            else {
                $this->printTimeRow($widths, $row);
                $this->moneySum += $row['wage'];
                $this->timeSum += $row['decimal_duration'] === -1 ? 0 : $row['decimal_duration'];
            }
        }
    }

    /**
     * Put a new expense entry into the PDF document.
     *
     * @param array $columns array containing all fields which should be printed
     *                       (Columns is misleading, but they were columns in the browser.)
     * @param array $w       the widths of the columns
     * @param array $row     the data of this entry
     */
    function printExpenseRow($w, $row)
    {
        global $kga;
        $date_string = '';
        if (isset($this->columns['date'])) {
            $date_string = $this->date($row['time_in']);
        }
        if (isset($this->columns['from'])) {
            $date_string .= ' ' . $this->time($row['time_in']);
        }


        $activity_string = (isset($this->columns['activity']) && !empty($row['activity_name'])) ? $kga['dict']['export_extension']['expense'] . ': <i>' . $row['activity_name'] . '</i>' : '';
        $user_string     = (isset($this->columns['user']) && !empty($row['username'])) ? $kga['dict']['export_extension']['by'] . ': <i>' . $row['username'] . '</i>' : '';
        $comment_string  = (isset($this->columns['comment']) && !empty($row['comment'])) ? $kga['dict']['comment'] . ': <i>' . nl2br($row['comment']) . '</i>' : '';
        $wage_string     = '<b>' . $this->money($row['wage']) . '</b>';

        $activity_fills_row = empty($user_string) || ($this->GetStringWidth($activity_string) + $this->GetStringWidth($user_string) > $w[1]);

        // Find out how many rows we use for this entry.
        $field_rows = 2;
        if (!empty($activity_string) && !empty($user_string) && $activity_fills_row) {
            $field_rows++;
        }
        if (empty($activity_string) && empty($user_string)) {
            $field_rows--;
        }
        if (empty($comment_string)) {
            $field_rows--;
        }

        $probable_comment_lines = $this->GetHtmlStringLines($comment_string, $w[1]);

        // check if page break is nessessary
        if ($this->getPageHeight() - $this->pagedim[ $this->page ]['bm'] - ($this->GetY() + ($field_rows + $probable_comment_lines + 4) * 6) < 0) {
            if (isset($this->columns['wage']) && isset($this->columns['dec_time'])) {
                $this->Ln();
                $this->writeHTMLCell($w[0] + $w[1] + $w[2], 6, $this->GetX(), $this->GetY(), $this->timespan($this->timeSum), '', 0, 0, true, 'R');
                $this->Ln();
                $this->writeHTMLCell($w[0] + $w[1], 6, $this->GetX(), $this->GetY(), $kga['dict']['export_extension']['subtotal'] . ':', '', 0, 0, true, 'R');
                $this->writeHTMLCell($w[2], 6, $this->GetX(), $this->GetY(), $this->money($this->moneySum), '', 0, 0, true, 'R');
            }
            else {
                if (isset($this->columns['wage'])) {
                    $this->Ln();
                    $this->writeHTMLCell($w[0] + $w[1], 6, $this->GetX(), $this->GetY(), $kga['dict']['export_extension']['subtotal'] . ':', '', 0, 0, true, 'R');
                    $this->writeHTMLCell($w[2], 6, $this->GetX(), $this->GetY(), $this->money($this->moneySum), '', 0, 0, true, 'R');
                }
                else {
                    if (isset($this->columns['dec_time'])) {
                        $this->Ln();
                        $this->writeHTMLCell($w[0] + $w[1], 6, $this->GetX(), $this->GetY(), $kga['dict']['export_extension']['subtotal'] . ':', '', 0, 0, true, 'R');
                        $this->writeHTMLCell($w[2], 6, $this->GetX(), $this->GetY(), $this->timespan($this->timeSum), '', 0, 0, true, 'R');
                    }
                }
            }
            $this->AddPage();
        }

        $this->Ln();
        $this->Cell($w[0], 6, $date_string, '', 0, 'R');

        for ($i = 0; $i < 3; $i++) {
            $handled_row = false;

            switch ($i) {
                case 0: // row with activity or activity and user
                    if ($activity_fills_row && !empty($activity_string)) {
                        $this->writeHTMLCell($w[1], 6, $this->GetX(), $this->GetY(), $activity_string, 'L');
                        $handled_row = true;
                    }
                    else {
                        if (!empty($activity_string) && !empty($user_string)) {
                            $this->writeHTMLCell($w[1] / 2, 6, $this->GetX(), $this->GetY(), $activity_string, 'L');
                            $this->writeHTMLCell($w[1] / 2, 6, $this->GetX(), $this->GetY(), $user_string, '');
                            $handled_row = true;
                        }
                        else {
                            if (!empty($user_string)) {
                                $this->writeHTMLCell($w[1], 6, $this->GetX(), $this->GetY(), $user_string, 'L');
                                $handled_row = true;
                            }
                        }
                    }
                    break;

                case 1: // row with user
                    if ($activity_fills_row && !empty($user_string)) {
                        $this->writeHTMLCell($w[1], 6, $this->GetX(), $this->GetY(), $user_string, 'L');
                        $handled_row = true;
                    }
                    break;

                case 2: // row with comment
                    if (!empty($comment_string)) {
                        $this->writeHTMLCell($w[1], 6, $this->GetX(), $this->GetY(), $comment_string, 'L');
                        $handled_row = true;
                    }
                    break;

            }

            if ($handled_row) {
                $field_rows--;

                if ($field_rows === 0) { // if this is the last row
                    $this->Ln($this->getLastH());
                    $this->Cell($w[0], 6, '');
                    $this->Cell($w[1], 6, '', 'T');
                    if (isset($this->columns['wage'])) {
                        $this->writeHTMLCell($w[2], 6, $this->GetX(), $this->GetY() - $this->getLastH(), $wage_string, '', 0, 0, true, 'R');
                    }
                    $this->Ln();
                    //$this->Ln();
                    break; // leave for loop
                }
                else {
                    $this->Ln();
                    $this->Cell($w[0], 6, '');
                }

            }

        }
    }

    /**
     * Put a new time entry into the PDF document.
     *
     * @param array $columns array containing all fields which should be printed
     *                       (Columns is misleading, but they were columns in the browser.)
     * @param array $w       the widths of the columns
     * @param array $row     the data of this entry
     */
    function printTimeRow($w, $row)
    {
        global $kga;

        $from_date_string = '';
        if (isset($this->columns['date'])) {
            $from_date_string = $this->date($row['time_in']);
        }
        if (isset($this->columns['from'])) {
            $from_date_string .= ' ' . $this->time($row['time_in']);
        }

        $to_date_string = '';
        if (isset($this->columns['to'])) {
            if (isset($this->columns['date'])) {
                $to_date_string = $this->date($row['time_out']);
            }
            $to_date_string .= ' ' . $this->time($row['time_out']);
        }


        if (!empty($row['activity_name'])) {
            $activity_string = $kga['dict']['activity'] . ': <i>' . $row['activity_name'] . '</i>';
        }
        else {
            $activity_string = '';
        }

        if (!empty($row['username'])) {
            $user_string = $kga['dict']['export_extension']['done_by'] . ': <i>' . $row['username'] . '</i>';
        }
        else {
            $user_string = '';
        }

        if (!empty($row['location'])) {
            $location_string = $kga['dict']['location'] . ': <i>' . $row['location'] . '</i>';
        }
        else {
            $location_string = '';
        }

        if (!empty($row['ref_code'])) {
            $ref_code_string = $kga['dict']['xpe_ref_code'] . ': <i>' . $row['ref_code'] . '</i>';
        }
        else {
            $ref_code_string = '';
        }

        if (isset($this->columns['comment']) && !empty($row['comment'])) {
            $comment_string = $kga['dict']['comment'] . ': <i>' . nl2br($row['comment']) . '</i>';
        }
        else {
            $comment_string = '';
        }

        if (isset($this->columns['time']) && !empty($row['formatted_duration'])) {
            $time_string = $kga['dict']['export_extension']['duration'] . ': <i>' . $row['formatted_duration'] . ' ' . $kga['dict']['export_extension']['duration_unit'] . '</i>';
        }
        else {
            $time_string = '';
        }

        if (!empty($row['rate'])) {
            $rate_string = $kga['dict']['rate'] . ': <i>' . $row['rate'] . '</i>';
        }
        else {
            $rate_string = '';
        }

        if (!empty($row['wage'])) {
            $wage_string = '<b>' . $this->money($row['wage']) . '</b>';
        }
        else {
            $wage_string = '';
        }

        $activity_fills_row = empty($user_string) || ($this->GetStringWidth($activity_string) + $this->GetStringWidth($user_string) > $w[1]);

        $field_rows = 4; // number of rows in block of values

        if (!empty($activity_string) && !empty($user_string) && $activity_fills_row) {
            $field_rows++;
        }

        if (empty($activity_string) && empty($user_string)) {
            $field_rows--;
        }

        if (empty($location_string) && empty($ref_code_string)) {
            $field_rows--;
        }

        if (empty($comment_string)) {
            $field_rows--;
        }

        if (empty($time_string) && empty($rate_string)) {
            $field_rows--;
        }

        $probable_comment_lines = $this->getHtmlStringLines($comment_string, $w[1]);

        // check if page break is nessessary
        if ($this->getPageHeight() - $this->pagedim[ $this->page ]['bm'] - ($this->GetY() + ($field_rows + $probable_comment_lines + 4) * 6) < 0) {
            if (isset($this->columns['wage']) && isset($this->columns['dec_time'])) {
                $this->Ln();
                $this->writeHTMLCell($w[0] + $w[1] + $w[2], 6, $this->GetX(), $this->GetY(), $this->timespan($this->timeSum), '', 0, 0, true, 'R');
                $this->Ln();
                $this->writeHTMLCell($w[0] + $w[1], 6, $this->GetX(), $this->GetY(), $kga['dict']['export_extension']['subtotal'] . ':', '', 0, 0, true, 'R');
                $this->writeHTMLCell($w[2], 6, $this->GetX(), $this->GetY(), $this->money($this->moneySum), '', 0, 0, true, 'R');
            }
            else {
                if (isset($this->columns['wage'])) {
                    $this->Ln();
                    $this->writeHTMLCell($w[0] + $w[1], 6, $this->GetX(), $this->GetY(), $kga['dict']['export_extension']['subtotal'] . ':', '', 0, 0, true, 'R');
                    $this->writeHTMLCell($w[2], 6, $this->GetX(), $this->GetY(), $this->money($this->moneySum), '', 0, 0, true, 'R');
                }
                else {
                    if (isset($this->columns['dec_time'])) {
                        $this->Ln();
                        $this->writeHTMLCell($w[0] + $w[1], 6, $this->GetX(), $this->GetY(), $kga['dict']['export_extension']['subtotal'] . ':', '', 0, 0, true, 'R');
                        $this->writeHTMLCell($w[2], 6, $this->GetX(), $this->GetY(), $this->timespan($this->timeSum), '', 0, 0, true, 'R');
                    }
                }
            }
            $this->AddPage();
        }

        $this->Ln();
        $this->Cell($w[0], 6, $from_date_string, '', 0, 'R');


        for ($i = 0; $i < 5; $i++) {
            $handled_row = false;

            switch ($i) {
                case 0: // row with activity or activity and user
                    if ($activity_fills_row && !empty($activity_string)) {
                        $this->writeHTMLCell($w[1], 6, $this->GetX(), $this->GetY(), $activity_string, 'L');
                        $handled_row = true;
                    }
                    else {
                        if (!empty($activity_string) && !empty($user_string)) {
                            $this->writeHTMLCell($w[1] / 2, 6, $this->GetX(), $this->GetY(), $activity_string, 'L');
                            $this->writeHTMLCell($w[1] / 2, 6, $this->GetX(), $this->GetY(), $user_string, '');
                            $handled_row = true;
                        }
                        else {
                            if (!empty($user_string)) {
                                $this->writeHTMLCell($w[1], 6, $this->GetX(), $this->GetY(), $user_string, 'L');
                                $handled_row = true;
                            }
                        }
                    }
                    break;

                case 1: // row with user
                    if ($activity_fills_row && !empty($user_string)) {
                        $this->writeHTMLCell($w[1], 6, $this->GetX(), $this->GetY(), $user_string, 'L');
                        $handled_row = true;
                    }
                    break;

                case 2: // row with location and/or tracking number
                    if (!empty($location_string) && empty($ref_code_string)) {
                        $this->writeHTMLCell($w[1], 6, $this->GetX(), $this->GetY(), $location_string, 'L');
                        $handled_row = true;
                    }
                    else {
                        if (empty($location_string) && !empty($ref_code_string)) {
                            $this->writeHTMLCell($w[1], 6, $this->GetX(), $this->GetY(), $ref_code_string, 'L');
                            $handled_row = true;
                        }
                        else {
                            if (!empty($location_string) && !empty($ref_code_string)) {
                                $this->writeHTMLCell($w[1] / 2, 6, $this->GetX(), $this->GetY(), $location_string, 'L');
                                $this->writeHTMLCell($w[1] / 2, 6, $this->GetX(), $this->GetY(), $ref_code_string, '');
                                $handled_row = true;
                            }
                        }
                    }
                    break;

                case 3: // row with comment
                    if (!empty($comment_string)) {
                        $this->writeHTMLCell($w[1], 6, $this->GetX(), $this->GetY(), $comment_string, 'L');
                        $handled_row = true;
                    }
                    break;

                case 4: // row with time and/or rate
                    if (!empty($time_string) && empty($rate_string)) {
                        $this->writeHTMLCell($w[1], 6, $this->GetX(), $this->GetY(), $time_string, 'L');
                        $handled_row = true;
                    }
                    else {
                        if (empty($time_string) && !empty($rate_string)) {
                            $this->writeHTMLCell($w[1], 6, $this->GetX(), $this->GetY(), $rate_string, 'L');
                            $handled_row = true;
                        }
                        else {
                            if (!empty($time_string) && !empty($rate_string)) {
                                $this->writeHTMLCell($w[1] / 2, 6, $this->GetX(), $this->GetY(), $time_string, 'L');
                                $this->writeHTMLCell($w[1] / 2, 6, $this->GetX(), $this->GetY(), $rate_string, '');
                                $handled_row = true;
                            }
                        }
                    }
                    break;

            }

            if ($handled_row) {
                $field_rows--;

                if ($field_rows === 0) { // if this is the last row
                    $this->Ln($this->getLastH());
                    $this->Cell($w[0], 6, '');
                    $this->Cell($w[1], 6, '', 'T');
                    $this->writeHTMLCell($w[2], 6, $this->GetX(), $this->GetY() - $this->getLastH(), $wage_string, '', 0, 0, true, 'R');
                    $this->Ln();
                    //$this->Ln();
                    break; // leave for loop
                }
                else {
                    $this->Ln();
                    $this->Cell($w[0], 6, '');
                }

            }

        }


    }

}
global $kga;

$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->columns     = $columns;
$pdf->date_format = $dateformat;
$pdf->time_format = $timeformat;
$pdf->print_time  = time();
$pdf->SetDisplayMode('default', 'continuous'); //PDF-Seitenanzeige fortlaufend

// determine page title
switch ($filter_type) {
    case 0:
        $pdf_title = $kga['dict']['export_extension']['pdf_headline_only_times'];
        break;
    case 1:
        $pdf_title = $kga['dict']['export_extension']['pdf_headline_only_expenses'];
        break;
    case -1:
    default:
        $pdf_title = $kga['dict']['export_extension']['pdf_headline'];
}
// determine filter values
switch ($filter_cleared) {
    case 0:
        $pdf_filter[] = $kga['dict']['export_extension']['cleared_open'];
        break;
    case 1:
        $pdf_filter[] = $kga['dict']['export_extension']['cleared_cleared'];
        break;
}

switch ($filter_refundable) {
    case 0:
        $pdf_filter[] = $kga['dict']['export_extension']['refundable_refundable'];
        break;
    case 1:
        $pdf_filter[] = $kga['dict']['export_extension']['refundable_not_refundable'];
        break;
}

$pdf->SetCreator(PDF_CREATOR);
$pdf->SetTitle($pdf_title);
$pdf->setPrintHeader(false);
$pdf->AddPage();

$pdf->setFont('helvetica');

if (isset($_REQUEST['create_bookmarks'])) {
    $pdf->Bookmark($pdf_title, 0, 0);
}

//$pdf->ImageEps('kimai-logo.ai', 0, 10, 60, 0, "http://www.kimai.org", true, 'T', 'R'); // include company logo


$pdf->WriteHtml('<h1>' . $pdf_title . '</h1>');
$pdf->Ln();

$pdf->WriteHtml('<b>' . $kga['dict']['export_extension']['time_period'] . ':</b> ' .
                strftime($kga['conf']['date_format_2'], $in) . ' - ' . strftime($kga['conf']['date_format_2'], $out));

if (isset($pdf_filter)) {
    $pdf->Ln();
    $pdf->WriteHtml('<b>' . $kga['dict']['export_extension']['tab_filter'] . ':</b> ' . implode(' | ', $pdf_filter));
}

if (!empty($_REQUEST['document_comment'])) {
    $pdf->Ln();
    $pdf->WriteHtml($_REQUEST['document_comment']);
}

$pdf->Ln();


if (isset($_REQUEST['print_summary'])) {

    if (isset($_REQUEST['create_bookmarks'])) {
        $pdf->Bookmark($kga['dict']['export_extension']['summary'], 0, 0);
    }

    $pdf->WriteHtml('<h4>' . $kga['dict']['export_extension']['summary'] . '</h4>');
    $pdf->Ln();
    $pdf->printSummary(array($kga['dict']['activity'], $kga['dict']['export_extension']['duration'], $kga['dict']['export_extension']['costs']), $orderedExportData);

    $pdf->AddPage();

}

$pdf->WriteHtml('<h4>' . $kga['dict']['export_extension']['full_list'] . '</h4>');
$pdf->Ln();


$firstRun = true;
foreach ($orderedExportData as $customer) {

    if ($firstRun) {
        $firstRun = false;
    }
    else {
        if (isset($_REQUEST['customer_new_page'])) {
            $pdf->AddPage();
        }
    }

    // process each customer in first dimension

    $project_ids = array_keys($customer);

    // get customer name from first row of first project
    $customer_name = $customer[ $project_ids[0] ][0]['customer_name'];

    $pdf->Ln();
    $pdf->WriteHtml("<h2>$customer_name</h2>");

    foreach ($project_ids as $project_id) {
        // process each project in second dimension

        $project_name = $customer[ $project_id ][0]['project_name'];

        $pdf->Ln();
        $pdf->WriteHtml("<h4>$project_name</h4>");

        $max_money_width = 0;
        $max_time_width  = 0;
        // calculate maximum width for time and money
        // and add to summary array
        foreach ($customer[ $project_id ] as $row) {

            // maximum width calculation
            $max_money_width = max($max_money_width, $pdf->GetStringWidth($pdf->money($row['wage'])));

            $time_width = 0;
            if (isset($columns['date'])) {
                $time_width += $pdf->GetStringWidth(strftime($dateformat, $row['time_in']));
            }
            if (isset($columns['from']) && isset($columns['to'])) {
                $time_width += max($pdf->GetStringWidth(strftime($timeformat, $row['time_in'])),
                                   $pdf->GetStringWidth(strftime($timeformat, $row['time_out'])));
            }
            else {
                if (isset($columns['from'])) {
                    $time_width += $pdf->GetStringWidth(strftime($timeformat, $row['time_in']));
                }
                else {
                    $time_width += $pdf->GetStringWidth(strftime($timeformat, $row['time_out']));
                }
            }


            $max_time_width = max($max_time_width, $time_width);


        }
        $max_time_width += 10;
        $max_money_width += 10;
        $widths = $pdf->columnWidths($max_time_width, $max_money_width);

        $pdf->printRows($customer[ $project_id ], $widths);


        if (isset($columns['wage']) && isset($columns['dec_time'])) {
            $pdf->Ln();
            $pdf->writeHTMLCell($widths[0] + $widths[1] + $widths[2], 6, $pdf->GetX(), $pdf->GetY(), $pdf->timespan($pdf->timeSum), '', 0, 0, true, 'R');
            $pdf->Ln();
            $pdf->writeHTMLCell($widths[0] + $widths[1], 6, $pdf->GetX(), $pdf->GetY(), $kga['dict']['export_extension']['finalamount'] . ':', '', 0, 0, true, 'R');
            $pdf->writeHTMLCell($widths[2], 6, $pdf->GetX(), $pdf->GetY(), $pdf->money($pdf->moneySum), '', 0, 0, true, 'R');
        }
        else {
            if (isset($columns['wage'])) {
                $pdf->Ln();
                $pdf->writeHTMLCell($widths[0] + $widths[1], 6, $pdf->GetX(), $pdf->GetY(), $kga['dict']['export_extension']['finalamount'] . ':', '', 0, 0, true, 'R');
                $pdf->writeHTMLCell($widths[2], 6, $pdf->GetX(), $pdf->GetY(), $pdf->money($pdf->moneySum), '', 0, 0, true, 'R');
            }
            else {
                if (isset($columns['dec_time'])) {
                    $pdf->Ln();
                    $pdf->writeHTMLCell($widths[0] + $widths[1], 6, $pdf->GetX(), $pdf->GetY(), $kga['dict']['export_extension']['finalamount'] . ':', '', 0, 0, true, 'R');
                    $pdf->writeHTMLCell($widths[2], 6, $pdf->GetX(), $pdf->GetY(), $pdf->timespan($pdf->timeSum), '', 0, 0, true, 'R');
                }
            }
        }

    }


}


$pdf->Output('invoice_' . date('Y-m-d_H-i-s', $pdf->print_time) . '.pdf', ((isset($_REQUEST['download_pdf'])) ? 'D' : 'I')); // D=Download I=Eingebunden

