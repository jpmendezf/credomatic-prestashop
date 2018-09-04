<div class="card-wrapper"></div>
<form id="credomaticCard">
    <div class="form-group">
        <input type="text" class="form-control" name="number"  placeholder="4556561571489682" required>
    </div>
    <div class="form-group">
        <input type="text" class="form-control" name="name" placeholder="tarjetahabiente" required>
    </div>
    <div class="form-group">
        <input type="text" class="form-control" name="expiry" placeholder="expiración" required>
    </div>
    <div class="form-group">
        <input type="text" class="form-control" name="cvc" placeholder="cvc" required>
    </div>
    <div class="form-group">
        <input class="btn btn-primary" type="submit" value="Pagar">
    </div>
</form>
<form id="credomatic" action="https://credomatic.compassmerchantsolutions.com/api/transact.php" method="post">
    <input name="type"   value="{$type}" type="hidden">
    <input name="orderid" value="{$orderid}" type="hidden">
    <input name="key_id" value="{$keyId}" type="hidden">
    <input name="hash" value="{$hash}" type="hidden">
    <input name="time" value="{$time}" type="hidden">
    <input name="redirect" value="{$response}" type="hidden">
    <input name="amount" value="{$total}" type="hidden">
    <input name="ipaddress" value="{$custip}" type="hidden">
    <input name="address1" value="{$address}" type="hidden">
    <input name="city" value="{$city}" type="hidden">
    <input name="state" value="{$state}" type="hidden">
    <input name="zip" value="{$postal}" type="hidden">
    <input name="country" value="{$country}" type="hidden">
    <input name="phone" value="{$phone}" type="hidden">
    <input name="email" value="{$p_billing_email}" type="hidden">
</form>