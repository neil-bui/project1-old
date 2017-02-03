<script type="text/javascript">
adroll_adv_id = "<?php echo $adv_id ?>";
adroll_pix_id = "<?php echo $pix_id ?>";

<?php 
global $woocommerce, $wp, $product;

$adroll_query_vars = $wp->query_vars;
if ($adroll_query_vars["order-received"]) {
    $order_id = $adroll_query_vars["order-received"];
    $order = wc_get_order($order_id);
}
?>

// If this is an order-received page, will fill in these additional values for enhanced conversion tracking.
adroll_conversion_value = "<?php echo $order ? $order->get_total() : null; ?>";
adroll_currency = "<?php echo $order ? $order->get_order_currency() : null; ?>";
adroll_email = "<?php echo $order ? $order->billing_email : null; ?>";
adroll_custom_data = {
    "ORDER_ID": "<?php echo $order ? $order->id : null; ?>",
    "USER_ID": "<?php echo $order ? $order->customer_user : null; ?>"
};
adroll_product_id = "<?php echo $product->id; ?>";
adroll_checkout_ids = [<?php
                       if ($order) {
                         foreach ($order->get_items() as $key => $lineItem) {
                           echo '"' . $lineItem['product_id'] . '",';
                         }
                       }
                       ?>];

var oldonload = window.onload;
window.onload = function(){
    __adroll_loaded=true;
    var scr = document.createElement("script");
    var host = (("https:" == document.location.protocol) ? "https://s.adroll.com" : "http://a.adroll.com");
    scr.setAttribute('async', 'true');
    scr.type = "text/javascript";
    scr.src = host + "/j/roundtrip.js";
    ((document.getElementsByTagName('head') || [null])[0] ||
    document.getElementsByTagName('script')[0].parentNode).appendChild(scr);
    if(oldonload){oldonload()}
}();
</script>