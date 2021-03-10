<!-- ORDERS - BEGIN unzercw -->
<?php
require_once __DIR__ . "/../../UnzerCw/init.php";

require_once 'UnzerCw/Entity/Util.php';
require_once 'UnzerCw/Util.php';

if(substr($order->info['payment_method'], 0, strlen('unzercw')) == 'unzercw') {
?>
    <table border="0" width="100%" cellspacing="0" cellpadding="0" class="cc" data-unzercw="Unzer">
<?php
    $transactions = UnzerCw_Entity_Util::findTransactionsEntityByOrderId($_GET['oID']);
    if(count($transactions) > 0) {
        foreach($transactions as $transaction) {
            $content = UnzerCw_Util::renderBackendPopupWindow(
                    unzercw_translate("View transaction"),
                    'TransactionManagement',
                    'edit',
                    array('transaction_id' => $transaction->getTransactionId()),
                    false
            );
            $button = str_replace("a href", "a class='btn' href", $content);
            ?><tr><td><?php echo $button; ?></td></tr><?php
        }
    }
    else if(class_exists($order->info['payment_class'])) {
        $method = new $order->info['payment_class']();
        $motoAdapter = $method->getAdapterFactory()->getAuthorizationAdapterByName(Customweb_Payment_Authorization_Moto_IAdapter::AUTHORIZATION_METHOD_NAME);
        $isMotoSupported = $motoAdapter->isAuthorizationMethodSupported(new UnzerCw_BackendOrderContext($_GET['oID'], $method));
        if($isMotoSupported) {
            $content = UnzerCw_Util::renderBackendPopupWindow(
                    unzercw_translate('Create new transaction with !paymentMethod.', array('!paymentMethod' => $method->getPaymentMethodDisplayName())),
                    'Moto',
                    'new',
                    array('order_id' => $_GET['oID']),
                    false
            );
            $button = str_replace("a href", "a class='btn' href", $content);
            ?><tr><td><?php echo $button; ?></td></tr><?php
        }
    }
    ?>
    <script>
    $(function() {
        setTimeout(function() {
            var element = $('[data-unzercw]');
            element.parent().siblings('.frame-head').children('label').html(element.data('unzercw'));
        }, 500);
    });
    </script>
    <?php
}
?>
<!-- ORDERS - END unzercw -->
