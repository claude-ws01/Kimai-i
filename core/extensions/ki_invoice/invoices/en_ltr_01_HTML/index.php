<!doctype html>
<?php
/**
 * This file is part of
 * Kimai-i Open Source Time Tracking // https://github.com/cloudeasy/Kimai-i
 * (c) 2015 Claude Nadon
 * (c) 2006-2013 Kimai-Development-Team // http://www.kimai.org
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

/**
 * An example HTML template for printing invoices.
 *
 * @author Kevin Papst <kpapst@gmx.net>
 */

global $kga;

$details     = $this->details;
$netTotal    = $this->netTotal;
$vatTotal    = $this->vatTotal;
$vatRate     = $this->vatRate;
$gTotal      = $this->gTotal;
$projects    = $this->projects;
$customer    = $this->customer;
$invoiceId   = $this->invoiceId;
$currency    = $this->currencySign;
$dateFormat  = $this->dateFormat;
$beginDate   = $this->beginDate;
$endDate     = $this->endDate;
$invoiceDate = $this->invoiceDate;


?>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice</title>
    <link rel="stylesheet" href="invoices/en_ltr_01_HTML/style.css">
</head>
<body>
<header>
    <h1>Invoice</h1>
    <span><img alt="" src="invoices/en_ltr_01_HTML/logo.png"></span>
    <address>
        <p>
            Compagny
            <br>
            12, 3rd street
            <br>
            12345 Some City </p>

        <p>(0123) 456-78901</p>
    </address>
</header>
<article>
    <address>
        <h2>Client</h2>

        <p>
            <?php echo $customer['company']; ?><br><?php echo $customer['contact']; ?>
            <br><?php echo $customer['street']; ?><br><?php echo $customer['zipcode']; ?>
            <?php echo $customer['city']; ?>
        </p>
    </address>
    <table class="meta">
        <tr>
            <th><span>Date</span></th>
            <td><span><?php echo strftime($dateFormat, $invoiceDate); ?></span></td>
        </tr>
        <tr>
            <th><span>Completed Date</span></th>
            <td><span><?php echo strftime($dateFormat, $endDate); ?></span></td>
        </tr>
        <tr>
            <th><span>Invoice Id</span></th>
            <td><span><?php echo $invoiceId; ?></span></td>
        </tr>
    </table>
    <table class="details">
        <thead>
        <tr>
            <th class="c_project"><span>Project</span></th>
            <th><span>Description</span></th>
            <th class="c_rate"><span>Rate</span></th>
            <th class="c_qty"><span>Qty</span></th>
            <th class="c_amount"><span>Total</span></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($details as $row) {
            if ($row['type'] === 'timesheet') {
                ?>
                <tr>
                    <td class="c_project"><span><?php echo $row['project_name']; ?></span></td>
                    <td class="c_description"><span><?php echo $row['description']; ?></span></td>
                    <td class="c_rate">
                        <span><?php echo $currency; ?></span>
                        <span><?php echo number_format($row['rate'], 2); ?></span>
                    </td>
                    <td class="c_qty"><span><?php echo number_format($row['hour'], 2); ?></span></td>
                    <td class="c_amount"><span><?php echo $currency; ?></span> <span><?php echo $row['amount']; ?></span>
                    </td>
                </tr>
            <?php }
            else { ?>
                <tr>
                <td class="c_project"><span><?php echo $row['project_name']; ?></span></td>
                <td class="c_description"><span><?php echo $row['description']; ?></span></td>
                <td class="c_rate">
                    <span><?php echo $currency; ?></span>
                    <span><?php echo number_format($row['value'], 2); ?></span>
                </td>
                <td class="c_qty"><span><?php echo number_format($row['multiplier'], 2); ?></span></td>
                <td class="c_amount"><span><?php echo $currency; ?></span> <span><?php echo $row['amount']; ?></span></td>
                </tr><?php }
        } ?>
        </tbody>
    </table>
    <table class="totals">
        <colgroup>
            <col class="t_label">
            <col class="c_amount">
        </colgroup>
        <tr>
            <td class="t_label"><span>Net</span></td>
            <td class="c_amount">
                <span><?php echo $currency; ?></span> <span><?php echo number_format($netTotal, 2); ?></span>
            </td>
        </tr>
        <tr>
            <td class="t_label"><span><?php echo $kga['dict']['vat'], ' ', $vatRate; ?>%</span></td>
            <td class="c_amount">
                <span><?php echo $currency; ?></span> <span><?php echo number_format($vatTotal, 2); ?></span>
            </td>
        </tr>
        <tr>
            <td class="t_label"><span>Invoice Amount</span></td>
            <td class="c_amount">
                <span><?php echo $currency; ?></span> <span><?php echo number_format($gTotal, 2); ?></span>
            </td>
        </tr>
    </table>
</article>
<aside>
    <h1><span></span></h1>

    <p>
        Please transfer the total amount. Within two weeks after receipt of the invoice, to the account below Use the subject line of your bank transfer please your invoice number. </p>

    <div>
        <p><span>Contact:</span>Company name, street Example 1, 12345 A city (0123) 456-78901, info@example.com</p>

        <p><span>Bank details:</span>Company name, Kto.Nr. 1234567890, 1234567890 BLZ, IBAN DE12345678901234567890</p>
    </div>
</aside>
</body>
</html>
