<!doctype html>
<?php
/**
 * This file is part of
 * Kimai - Open Source Time Tracking // http://www.kimai.org
 * (c) 2006-2013 Kimai-Development-Team
 *
 * Kimai is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; Version 3, 29 June 2007
 *
 * Kimai is distributed in the hope that it will be useful,
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

$timesheets     = $this->timesheets;
$amount      = $this->amount;
$vat         = $this->vat_rate;
$vatRate     = $this->vatRate;
$total       = $this->total;
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
    <link rel="stylesheet" href="invoices/invoice_en_HTML/style.css">
</head>
<body>
<header>
    <h1>Invoice</h1>
    <span><img alt="" src="invoices/invoice_en_HTML/logo.png"></span>
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
    <table class="timesheets">
        <thead>
        <tr>
            <th><span>Description</span></th>
            <th class="rate"><span>Rate</span></th>
            <th class="qty"><span>Qty</span></th>
            <th class="amount"><span>Total</span></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($timesheets as $row) {
            if ($row['type'] === 'timesheet') {
                ?>
                <tr>
                    <td><span><?php echo $row['description']; ?></span></td>
                    <td class="rate">
                        <span><?php echo $currency; ?></span>
                        <span><?php echo number_format($row['rate'], 2); ?></span>
                    </td>
                    <td class="qty"><span><?php echo number_format($row['hour'], 2); ?></span></td>
                    <td class="amount"><span><?php echo $currency; ?></span> <span><?php echo $row['amount']; ?></span></td>
                </tr>
            <?php }
            else { ?>
                <tr>
                    <td><span><?php echo $row['description']; ?></span></td>
                    <td class="rate">
                        <span><?php echo $currency; ?></span>
                        <span><?php echo number_format($row['value'], 2); ?></span>
                    </td>
                    <td class="qty"><span><?php echo number_format($row['multiplier'], 2); ?></span></td>
                    <td class="amount"><span><?php echo $currency; ?></span> <span><?php echo $row['amount']; ?></span></td>
                </tr><?php }
        } ?>
        </tbody>
    </table>
    <table class="totals">
        <tr>
            <th><span>Net</span></th>
            <td class="amount">
                <span><?php echo $currency; ?></span> <span><?php echo number_format($amount, 2); ?></span>
            </td>
        </tr>
        <tr>
            <th><span><?php echo $kga['lang']['vat'], ' ', $vatRate; ?>%</span></th>
            <td class="amount">
                <span><?php echo $currency; ?></span> <span><?php echo number_format($vat, 2); ?></span>
            </td>
        </tr>
        <tr>
            <th><span>Invoice Amount</span></th>
            <td class="amount">
                <span><?php echo $currency; ?></span> <span><?php echo number_format($total, 2); ?></span>
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
