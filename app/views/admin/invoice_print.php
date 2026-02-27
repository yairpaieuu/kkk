<?php
// Determine format from controller, default to slip
$format = $_GET['format'] ?? 'slip'; 

// Fetch Settings for Address/Phone (Using existing DB connection from Controller)
// $this->db is available because this file is included inside AdminController class
$settings = $this->db->query("SELECT * FROM settings WHERE id=1")->fetch(PDO::FETCH_ASSOC);

$siteName = $settings['site_name'] ?? 'KKK LED SHOP';
$sitePhone = $settings['phone'] ?? '09-123456789';
$siteAddress = $settings['address'] ?? 'No. 123, Digital Street, Yangon';

// Calculations
$subtotal = $order['total_amount'] + $order['discount_amount']; // Assuming total_amount in DB is the final paid amount
$discount = $order['discount_amount'] ?? 0;
$total = $order['total_amount'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?= $order['id'] ?> (<?= strtoupper($format) ?>)</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Courier+Prime:wght@400;700&family=Inter:wght@400;600;800&display=swap');
        
        /* --- RESET & COMMON --- */
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; background: #eee; -webkit-print-color-adjust: exact; }
        .page-container { background: white; margin: 20px auto; overflow: hidden; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; }
        
        /* --- FORMAT: SLIP (80mm) --- */
        <?php if($format === 'slip'): ?>
            @page { margin: 0; size: 80mm auto; }
            body { font-family: 'Courier Prime', monospace; font-size: 12px; }
            .page-container { width: 78mm; padding: 5mm; margin: 0 auto; box-shadow: none; }
            .store-name { font-size: 16px; font-weight: bold; margin-bottom: 5px; }
            .divider { border-bottom: 1px dashed #000; margin: 5px 0; }
            th { text-align: left; border-bottom: 1px solid #000; padding: 2px 0; font-size: 11px; }
            td { padding: 4px 0; vertical-align: top; }
            .footer { margin-top: 20px; font-size: 10px; text-align: center; }
            .address-block { font-size: 10px; margin-bottom: 10px; }
        
        /* --- FORMAT: A4 & A5 --- */
        <?php else: ?>
            @page { size: <?= $format ?>; margin: 0; }
            body { font-family: 'Inter', sans-serif; font-size: 12px; color: #333; }
            
            .page-container {
                width: <?= $format === 'a4' ? '210mm' : '148mm' ?>;
                min-height: <?= $format === 'a4' ? '297mm' : '210mm' ?>;
                padding: 15mm;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }

            /* Header */
            header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 20px; }
            .brand h1 { font-size: 24px; color: #2563eb; margin: 0 0 5px 0; }
            .invoice-details h2 { margin: 0; font-size: 18px; color: #333; text-align: right; }
            
            /* Table */
            .invoice-table { margin-bottom: 30px; }
            .invoice-table th { background: #f8fafc; color: #64748b; font-weight: 600; text-transform: uppercase; padding: 12px; font-size: 10px; border-bottom: 1px solid #e2e8f0; text-align: left; }
            .invoice-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; }
            .invoice-table tr:last-child td { border-bottom: none; }
            
            /* Totals */
            .totals-area { display: flex; justify-content: flex-end; }
            .totals-table { width: 50%; }
            .totals-table td { padding: 8px 0; }
            .grand-total { font-size: 16px; color: #2563eb; border-top: 2px solid #e2e8f0; padding-top: 10px; }

            /* Footer */
            .footer-a4 { position: fixed; bottom: 15mm; left: 15mm; right: 15mm; text-align: center; color: #94a3b8; font-size: 10px; border-top: 1px solid #eee; padding-top: 10px; }
        <?php endif; ?>

        @media print {
            body { background: white; }
            .page-container { margin: 0; box-shadow: none; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print();">

    <div class="page-container">
        
        <?php if($format === 'slip'): ?>
            <!-- SLIP LAYOUT -->
            <div class="text-center header">
                <div class="store-name"><?= htmlspecialchars($siteName) ?></div>
                <div class="address-block">
                    <?= nl2br(htmlspecialchars($siteAddress)) ?><br>
                    Phone: <?= htmlspecialchars($sitePhone) ?>
                </div>
            </div>
            <div class="divider"></div>
            
            <!-- Invoice Info -->
            <div class="info-row" style="display:flex; justify-content:space-between">
                <span>INV: #<?= $order['id'] ?></span>
                <span><?= date('d/m/y H:i', strtotime($order['created_at'])) ?></span>
            </div>
            <div class="info-row">
                <span>Pay: <?= htmlspecialchars($order['payment_method'] ?? 'Cash') ?></span>
            </div>

            <div class="divider"></div>
            <table>
                <thead>
                    <tr>
                        <th width="40%">Item</th>
                        <th width="20%" class="text-right">Qty</th>
                        <th width="20%" class="text-right">Price</th>
                        <th width="20%" class="text-right">Amt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td class="text-right"><?= $item['quantity'] ?></td>
                        <td class="text-right"><?= number_format($item['price']) ?></td>
                        <td class="text-right"><?= number_format($item['price'] * $item['quantity']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="divider"></div>
            
            <!-- TOTALS -->
            <div class="text-right" style="font-size: 11px;">
                <?php if($discount > 0): ?>
                    <div>Subtotal: <?= number_format($subtotal) ?></div>
                    <div style="margin: 2px 0;">Discount: -<?= number_format($discount) ?></div>
                    <div class="divider" style="margin: 5px 0;"></div>
                <?php endif; ?>
                
                <div class="font-bold" style="font-size: 14px; margin-top: 5px;">
                    TOTAL: <?= number_format($total) ?> MMK
                </div>
            </div>

            <div class="footer">
                ** THANK YOU **
            </div>

        <?php else: ?>
            <!-- A4 / A5 LAYOUT -->
            <header>
                <div class="brand">
                    <h1><?= htmlspecialchars($siteName) ?></h1>
                    <div style="color: #64748b; margin-top: 5px;">
                        <?= nl2br(htmlspecialchars($siteAddress)) ?><br>
                        Phone: <?= htmlspecialchars($sitePhone) ?>
                    </div>
                </div>
                <div class="invoice-details">
                    <h2>INVOICE</h2>
                    <div style="margin-top: 10px; color: #64748b;">
                        <div>No: #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></div>
                        <div>Date: <?= date('M d, Y', strtotime($order['created_at'])) ?></div>
                        <div>Payment: <strong style="color: #333;"><?= htmlspecialchars($order['payment_method'] ?? 'Cash') ?></strong></div>
                    </div>
                </div>
            </header>

            <div style="margin-bottom: 30px;">
                <strong style="color: #64748b; font-size: 10px; text-transform: uppercase;">Bill To:</strong>
                <div style="font-size: 14px; font-weight: bold; margin-top: 5px;"><?= htmlspecialchars($order['customer_name'] ?? 'Walk-in Customer') ?></div>
                <?php if(!empty($order['customer_address'])): ?>
                    <div style="font-size: 12px; color: #666; margin-top: 2px;"><?= htmlspecialchars($order['customer_address']) ?></div>
                <?php endif; ?>
            </div>

            <table class="invoice-table">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="50%">Description</th>
                        <th width="15%" class="text-right">Price</th>
                        <th width="10%" class="text-center">Qty</th>
                        <th width="20%" class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($items as $i => $item): ?>
                    <tr>
                        <td style="color: #94a3b8;"><?= $i + 1 ?></td>
                        <td class="font-bold"><?= htmlspecialchars($item['name']) ?></td>
                        <td class="text-right"><?= number_format($item['price']) ?></td>
                        <td class="text-center"><?= $item['quantity'] ?></td>
                        <td class="text-right font-bold"><?= number_format($item['price'] * $item['quantity']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="totals-area">
                <table class="totals-table">
                    <!-- Show Subtotal Only if Discount exists -->
                    <?php if($discount > 0): ?>
                    <tr>
                        <td class="text-right" style="color: #64748b;">Subtotal:</td>
                        <td class="text-right font-bold"><?= number_format($subtotal) ?> MMK</td>
                    </tr>
                    <tr>
                        <td class="text-right" style="color: #ef4444;">Discount:</td>
                        <td class="text-right" style="color: #ef4444;">- <?= number_format($discount) ?> MMK</td>
                    </tr>
                    <?php endif; ?>

                    <tr class="grand-total">
                        <td class="text-right">Total:</td>
                        <td class="text-right"><?= number_format($total) ?> MMK</td>
                    </tr>
                </table>
            </div>

            <div class="footer-a4">
                Thank you for your business! <br>
                For questions concerning this invoice, please contact <?= htmlspecialchars($sitePhone) ?>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>