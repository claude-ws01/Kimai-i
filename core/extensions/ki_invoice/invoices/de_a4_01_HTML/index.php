<!doctype html>
<?php
/**
 * This file is part of
 * Kimai-i Open Source Time Tracking // https://github.com/claude-ws01/Kimai-i
 * (c) 2015 Claude Nadon  https://github.com/claude-ws01
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
    <link rel="stylesheet" href="invoices/de_a4_01_HTML/style.css">
</head>
<body>
<header>
    <h1>Rechnung</h1>
    <address>
        <p>
            Firmen Name
            <br>
            Beispielstraße 1
            <br>
            12345 Eine Stadt </p>

        <p>(0123) 456-78901</p>
    </address>
    <span><img alt="" src="invoices/de_a4_01_HTML/logo.png"></span>
</header>
<article>
    <h1>Empfänger</h1>
    <address>
        <p>
            <?php echo $customer['company']; ?><br><?php echo $customer['contact']; ?>
            <br><?php echo $customer['street']; ?><br><?php echo $customer['zipcode']; ?>
            <?php echo $customer['city']; ?>
        </p>
    </address>
    <table class="meta">
        <tr>
            <th><span>Datum</span></th>
            <td><span><?php echo strftime($dateFormat, $invoiceDate); ?></span></td>
        </tr>
        <tr>
            <th><span>Leistungsdatum</span></th>
            <td><span><?php echo strftime($dateFormat, $endDate); ?></span></td>
        </tr>
        <tr>
            <th><span>Rechnungsnummer</span></th>
            <td><span><?php echo $invoiceId; ?></span></td>
        </tr>
    </table>
    <table class="inventory">
        <thead>
        <tr>
            <th><span>Bezeichnung</span></th>
            <th><span>Auftragsnummer</span></th>
            <th><span>Einzelpreis</span></th>
            <th><span>Anzahl</span></th>
            <th><span>Gesamtpreis</span></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($details as $row) {
            if ($row['type'] == 'timesheet') {
                ?>
                <tr>
                <td><span><?php echo $row['description']; ?></span></td>
                <td><span><?php echo $row['ref_code']; ?></span></td>
                <td><span><?php echo $currency; ?></span> <span><?php echo number_format($row['rate'], 2); ?></span>
                </td>
                <td><span><?php echo number_format($row['hour'], 2); ?></span></td>
                <td><span><?php echo $currency; ?></span> <span><?php echo $row['amount']; ?></span></td>
                </tr><?php }
            else { ?>
                <tr>
                <td><span><?php echo $row['description']; ?></span></td>
                <td></td>
                <td><span><?php echo $currency; ?></span>
                    <span><?php echo number_format($row['value'], 2); ?></span></td>
                <td><span><?php echo number_format($row['multiplier'], 2); ?></span></td>
                <td><span><?php echo $currency; ?></span> <span><?php echo $row['amount']; ?></span></td>
                </tr><?php }
        } ?>
        </tbody>
    </table>
    <table class="balance">
        <tr>
            <th><span>Rechnungsbetrag (netto)</span></th>
            <td><span data-prefix><?php echo $currency; ?></span>
                <span><?php echo number_format($netTotal, 2); ?></span>
            </td>
        </tr>
        <tr>
            <th><span>zzgl. <?php echo $vatRate; ?>% Umsatzsteuer</span></th>
            <td><span data-prefix><?php echo $currency; ?></span>
                <span><?php echo number_format($vatTotal, 2); ?></span>
            </td>
        </tr>
        <tr>
            <th><span>Rechnungsendbetrag</span></th>
            <td><span data-prefix><?php echo $currency; ?></span> <span><?php echo number_format($gTotal, 2); ?></span>
            </td>
        </tr>
    </table>
</article>
<aside>
    <h1><span></span></h1>

    <p>
        Bitte überweisen Sie den Gesamtbetrag, innerhalb von zwei Wochen nach Erhalt der Rechnung,
        auf das unten genannte Konto. Verwenden Sie als Betreff Ihrer Überweisung bitte Ihre Rechnungsnummer. </p>

    <div>
        <p><span>Kontakt:</span>Firmen Name, Beispielstraße 1, 12345 Eine Stadt, (0123) 456-78901, info@example.com</p>

        <p><span>Bankverbindung:</span>Firmen Name, Kto.Nr. 1234567890, BLZ 1234567890, IBAN DE12345678901234567890</p>
    </div>
</aside>
</body>
</html>
