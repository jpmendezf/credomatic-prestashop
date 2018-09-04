{*
* 2007-2017 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2017 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<div class="row">
	<div class="col-xs-12 col-md-4">


		<!-- CREDIT CARD FORM STARTS HERE -->
		<div class="panel panel-default credit-card-box">
			<div class="panel-heading display-table" >
				<div class="row display-tr" >
					<h3 class="panel-title display-td" >Payment Details</h3>
					<div class="display-td" >
						<img class="img-responsive pull-right" src="{$modules_dir|escape:'htmlall':'UTF-8'}credomatic/views/img/accepted.png">
					</div>
				</div>
			</div>
			<div class="panel-body">
				<form role="form" id="payment-form" method="POST" action="{$credomatic_redirect|escape:'htmlall':'UTF-8'}">
					<div class="row">
						<div class="col-xs-12">
							<div class="form-group">
								<label for="ccnumber">CARD NUMBER</label>
								<div class="input-group">
									<input
											type="tel"
											class="form-control"
											name="ccnumber"
											placeholder="Valid Card Number"
											autocomplete="cc-number"
											required autofocus
											/>
									<span class="input-group-addon"><i class="fa fa-credit-card glyphicon glyphicon-credit-card"></i></span>
								</div>
							</div>
						</div>
					</div>

					{if $credomatic_ask_name == 'yes'}
					<div class="row">
						<div class="col-xs-6 col-md-6">
							<div class="form-group">
								<label for="firstname">FIRSTNAME</label>
								<input
										type="text"
										class="form-control"
										name="firstname"
										placeholder=""
										required
										/>
							</div>
						</div>
						<div class="col-xs-6 col-md-6 pull-right">
							<div class="form-group">
									<label for="lastname">LASTNAME</label>
									<input
											type="text"
											class="form-control"
											name="lastname"
											placeholder=""
											required
											/>

							</div>
						</div>
					</div>
					{/if}

					<div class="row">
						<div class="col-xs-7 col-md-7">
							<div class="form-group">
								<label for="ccexp">EXP DATE</label>
								<input
										type="tel"
										class="form-control"
										name="ccexp"
										placeholder="MM / YY"
										autocomplete="cc-exp"
										required
										/>
							</div>
						</div>
						<div class="col-xs-5 col-md-5 pull-right">
							<div class="form-group">
								{if $credomatic_ask_cvv == 'yes'}
									<label for="cvv">CV CODE</label>
									<input
											type="tel"
											class="form-control"
											name="cvv"
											placeholder="CVC"
											autocomplete="cc-csc"
											required
											/>
								{/if}
							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-xs-12">
							<input type="submit" class="subscribe btn btn-success btn-lg btn-block" value="Pay">
						</div>
					</div>
					<div class="row" style="display:none;">
						<div class="col-xs-12">
							<p class="payment-errors"></p>
						</div>
					</div>
				</form>
			</div>
		</div>
		<!-- CREDIT CARD FORM ENDS HERE -->
	</div>
</div>
