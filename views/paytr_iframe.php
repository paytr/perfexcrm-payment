<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php echo payment_gateway_head(_l('payment_for_invoice') . ' ' . format_invoice_number(2)); ?>

<!-- Ödeme formunun açılması için gereken HTML kodlar / Başlangıç -->
<script src="https://www.paytr.com/js/iframeResizer.min.js"></script>
<iframe src="https://www.paytr.com/odeme/guvenli/<?=$token?>" id="paytriframe" frameborder="0" scrolling="no" style="width: 100%;"></iframe>
<script>iFrameResize({},'#paytriframe');</script>
<!-- Ödeme formunun açılması için gereken HTML kodlar / Bitiş -->
<?php echo payment_gateway_footer(); ?>
