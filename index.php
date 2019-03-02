<?php
require_once 'config.php';
$result = file_get_contents('https://pro-api.coinmarketcap.com/v1/cryptocurrency/listings/latest?CMC_PRO_API_KEY=' . CMC_API_KEY . '&limit=500');
?>

<html>
<head>
<title>Crypto Portfolio</title>
</head>
<body>
<script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
<script type="text/javascript">
	var ticker = <?= $result ?>;
	ticker = ticker.data;

	function add_allocation (symbol, quantity, target)
	{
		var div = $('<div class="coin" />');
		
		var name = $('<div data-field="name" class="inline" />');
		name.appendTo(div);
		
		var symbol_input = $('<input type="text" data-field="symbol" />');
		if ( ! symbol)
		{
			symbol_input.addClass('hint').val('BTC');
			symbol_input.click(hint_handler);
			symbol_input.keydown(hint_handler);
		}
		else
		{
			div.attr('data-symbol', symbol);
			symbol_input.val(symbol);
		}
		symbol_input.change(symbol_changed);
		symbol_input.keyup(symbol_changed);
		symbol_input.appendTo(div);
		
		var quantity_input = $('<input type="text" data-field="quantity" />');
		if ( ! quantity)
		{
			quantity_input.addClass('hint').val('123');
			quantity_input.click(hint_handler);
			quantity_input.keydown(hint_handler);
		}
		else
		{
			quantity_input.val(quantity);
			div.attr('data-quantity', quantity);
		}
		quantity_input.change(quantity_changed);
		quantity_input.keyup(quantity_changed);
		quantity_input.appendTo(div);
		
		var target_input = $('<input type="text" data-field="target" />');
		if ( ! target)
		{
			target_input.addClass('hint').val('25%');
			target_input.click(hint_handler);
			target_input.keydown(hint_handler);
		}
		else
		{
			target_input.val(target+'%');
			div.attr('data-target', target);
		}
		target_input.change(target_changed);
		target_input.keyup(target_changed);
		target_input.appendTo(div);
		
		var actual = $('<div data-field="actual" class="inline">0</div>');
		actual.appendTo(div);
		
		var suggestion = $('<div data-field="suggestion" class="inline">0</div>');
		suggestion.appendTo(div);

		var btcprice = $('<div data-field="btc_price" class="inline">0</div>');
		btcprice .appendTo(div);

		var change = $('<div data-field="btc_change" class="inline">0</div>');
		change.appendTo(div);

		var btcvalue = $('<div data-field="btc_value" class="inline">0</div>');
		btcvalue .appendTo(div);
		
		var price = $('<div data-field="price" class="inline">0</div>');
		price.appendTo(div);

		var change = $('<div data-field="change" class="inline">0</div>');
		change.appendTo(div);
		
		var value = $('<div data-field="value" class="inline">0</div>');
		value.appendTo(div);
		
		div.appendTo('#coins');
	}
	
	function symbol_changed ()
	{
		var div = $(this).parent();
		var symbol = $(this).val();
		div.attr('data-symbol', symbol);
		
		var coin = get_from_ticker(symbol);
		var link = $('<a/>').attr('href', link_to_coin(coin.slug)).attr('target', '_blank').html(coin.name);
		div.children('div[data-field="name"]').html($(image(coin.id))).append(link);
		
		if (coin.symbol)
			$(this).val(coin.symbol);
		
		refresh_portfolio();
	}
	
	function quantity_changed ()
	{
		var div = $(this).parent();
		div.attr('data-quantity', $(this).val());
		
		refresh_portfolio();
	}
	
	function target_changed ()
	{
		var input = $(this);
		var div = input.parent();
		var val = input.val().length == 0 ? '0' : input.val();
		val = val.replace(/%/g, '');
		input.val(val);
		
		setTimeout(function() { if (input.val().substr(-1) != '%' && ! input.is(":focus")) input.val(input.val()+'%'); }, 1000);
		div.attr('data-target', parseInt(input.val()));
		
		refresh_portfolio();
	}
	
	function refresh_portfolio ()
	{
		var target_total = 0;
		$('div.coin').each(function ()
		{
			var allocation = get_allocation($(this));
			if (allocation)
				target_total += parseFloat(allocation.target);
		});
		
		if (target_total != 100)
			$('#target_total').html('Target Total = '+target_total+'%').addClass('negative');
		else
			$('#target_total').html('').removeClass('negative');


		var total_btc = 0;
		var total_value = 0;
		
		$('div.coin').each(function ()
		{
			var allocation = get_allocation($(this));
			
			if (allocation == undefined)
				return;
			
			var coin = get_from_ticker(allocation.symbol);
			var btc_value = allocation.quantity * coin.price_btc;
			var value = allocation.quantity * coin.price_usd;
			
			total_btc += btc_value;
			total_value += value;
		});

		var btc = get_from_ticker('BTC');
		var btc_24h_price = btc.price_usd / ((100 + parseFloat(btc.percent_change_24h)) / 100);
		
		$('div.coin').each(function ()
		{
			var allocation = get_allocation($(this));
			
			if (allocation == undefined)
				return;
			
			var coin = get_from_ticker(allocation.symbol);
			var btc_value = allocation.quantity * coin.price_btc;
			var value = allocation.quantity * coin.price_usd;
			var actual = 100 * value / total_value;
			var suggestion = (allocation.target - actual) / 100 * total_value / coin.price_usd;
			var drift = Math.abs(allocation.target - actual);
			var drift_color = 'hsl(0, 100%, '+Math.round(drift>5?50:10*drift)+'%)';
			
			$(this).children('div[data-field="actual"]').html(actual.toLocaleString('en-US', { maximumFractionDigits: 1 })+'%').css('color', drift_color);
			$(this).children('div[data-field="suggestion"]').html((suggestion>0?'+':'') + suggestion.toLocaleString('en-US', { maximumFractionDigits: 0 }));
			$(this).children('div[data-field="btc_price"]').html(parseFloat(coin.price_btc).toFixed(8));
			$(this).children('div[data-field="btc_change"]').html(parseFloat(coin.percent_change_btc).toFixed(2) + '%');
			$(this).children('div[data-field="btc_value"]').html(parseFloat(btc_value).toFixed(4));
			$(this).children('div[data-field="price"]').html(parseFloat(coin.price_usd).toLocaleString('en-US', { minimumFractionDigits: 2 }));
			$(this).children('div[data-field="change"]').html(parseFloat(coin.percent_change_usd).toFixed(2) + '%');
			$(this).children('div[data-field="value"]').html(parseInt(value).toLocaleString('en-US'));

			$(this).children('div[data-field="btc_change"]').removeClass('positive').removeClass('negative');
			$(this).children('div[data-field="change"]').removeClass('positive').removeClass('negative');
			if (coin.percent_change_btc != 0) $(this).children('div[data-field="btc_change"]').addClass(coin.percent_change_btc > 0 ? 'positive' : 'negative');
			if (coin.percent_change_usd != 0) $(this).children('div[data-field="change"]').addClass(coin.percent_change_usd > 0 ? 'positive' : 'negative');
		});
		
		$('#btc_total span').html(parseFloat(total_btc).toFixed(8));
		$('#grand_total span').html(parseInt(total_value).toLocaleString('en-US'));
		
		store();
	}
	
	function hint_handler ()
	{
		if ($(this).hasClass('hint'))
		{
			$(this).val('');
			$(this).removeClass('hint');
			$(this).unbind('click');
			$(this).unbind('keydown');
		}
	}
	
	function get_from_ticker(symbol)
	{
		var no_coin_found = { name: "???", price_usd: "0" };
		
		if (typeof symbol != "string")
			return no_coin_found;
		
		var coin;
		
		if (coin = ticker[symbol])
			return coin;
		else if (coin = ticker[symbol.toUpperCase()])
			return coin;
		
		return no_coin_found;
	}
	
	function store ()
	{
		var allocations = [];
		
		$('div.coin').each(function () {
			var allocation = get_allocation($(this));
			if (allocation == undefined)
				return;
			allocations.push(allocation);
		});

		allocations.sort(function(a, b) {
			var targetA = parseFloat(a.target), targetB = parseFloat(b.target);
			var quantityA = parseFloat(a.quantity), quantityB = parseFloat(b.quantity);
			if (targetA == targetB) return (quantityA < quantityB) ? 1 : (quantityA > quantityB) ? -1 : 0;
			return (targetA < targetB) ? 1 : -1;
		});
		
		localStorage.setItem('allocations', JSON.stringify(allocations));
	}
	
	function load ()
	{
		var allocations = JSON.parse(localStorage.getItem('allocations'));
		for (var i in allocations)
		{
			var allocation = allocations[i];
			if (allocation == undefined)
				continue;
			add_allocation(allocation.symbol, allocation.quantity, allocation.target);
		}
		
		$('input[data-field="symbol"]').change();
	}
	
	function get_allocation (div)
	{
		var incomplete_allocation = undefined;
		
		if ($(div).find('input.hint').length)
			return incomplete_allocation;
		
		var symbol = div.attr('data-symbol');

		if (symbol.length == 0)
			return incomplete_allocation;		

		var quantity = div.attr('data-quantity').length == 0 ? '0' : div.attr('data-quantity');

		var target = div.attr('data-target').length == 0 ? '0' : div.attr('data-target') ;
		
		return {
			symbol: symbol,
			quantity: quantity,
			target: target
		}
	}
	
	function index_ticker ()
	{
		var btc_change = ticker[0].quote.USD.percent_change_24h;
		var btc_price = ticker[0].quote.USD.price;
		var btc_price_24h = btc_price / (btc_change / 100 + 1);

		var new_ticker = {};
		for (var i in ticker) {
			if (ticker[i].symbol in new_ticker) continue;

			var change = ticker[i].quote.USD.percent_change_24h;
			var price = ticker[i].quote.USD.price;
			var price_btc = price / btc_price;
			var price_24h = price / (change / 100 + 1);
			var price_24h_btc = price_24h / btc_price_24h; 

			new_ticker[ticker[i].symbol] = {
				'id': ticker[i].id,
				'name': ticker[i].name,
				'slug': ticker[i].slug,
				'price_usd': price,
				'price_btc': price / btc_price,
				'percent_change_usd': change,
				'percent_change_btc': (price_btc - price_24h_btc) / price_24h_btc * 100
			}
		}
		ticker = new_ticker;
	}
	
	function image (coin_id)
	{
		return '<img src="https://s2.coinmarketcap.com/static/img/coins/64x64/' + coin_id + '.png" />';
	}
	
	function link_to_coin (slug)
	{
		return 'http://coinmarketcap.com/currencies/' + slug;
	}

	$(document).ready(function () {
		index_ticker();
		$('#add_allocation').click(function() { add_allocation() });
		load();
		add_allocation();
	});
</script>

<style type="text/css">
	* {
		font-family: Arial;
		line-height: 1.3;
	}
	h2 {
		margin-top: 5px;
		margin-bottom: 25px;
	}
	a {
		color: blue;
		text-decoration: none;
	}
	a:hover {
		text-decoration: underline;
	}
	#header, #coins {
		white-space: nowrap;
		font-size: 0;
	}
	#header > div, .coin > * {
		display: inline-block;
		width: 80px;
		font-size: 12px;
		margin: 5px;
		text-align: right;
	}
	#header {
		font-weight: bold;
	}
	input {
		padding: 2px;
	}
	input.hint {
		color: #ccc;
	}
	input[data-field="target"]:after {
		content: '%';
	}
	div[data-field="price"]:before,
	div[data-field="value"]:before {
		content: '$';
	}
	#add_allocation:hover {
		cursor: pointer;
		color: gray;
	}

	input[data-field="symbol"],
	input[data-field="target"],
	div[data-field="symbol"],
	div[data-field="target"],
	div[data-field="actual"]
	{
		width: 60px !important;
	}
	div[data-field="name"] {
		font-size: 13px;
		width: 160px !important;
		text-align: left !important;
	}
	div[data-field="name"] img {
		width: 16px;
	}
	input[data-field="symbol"],
	div[data-field="symbol"] {
		text-align: center !important;
	}
	#grand_total span:before {
		content: '$';
	}
	.positive {
		color: #093;
	}
	.negative {
		color: #d14836;
	}
	img {
		position: relative;
		top: 3px;
		margin-right: 5px;
	}
	#coins > div:nth-child(odd) {
		background: #eee;
	}
	#coins > div:hover {
		background: #fbf4c7;
	}
</style>

<div style="float:right;">
	<div id="btc_total"><b>Total BTC:</b> <span>0</span></div>
	<div id="grand_total"><b>Total Value:</b> <span>0</span></div>
</div>

<h2><a href="http://coinmarketcap.com/" target="_blank">Crypto</a> Portfolio</h2>

<div id="target_total"></div>

<div id="header">
	<div data-field="name">Name</div>
	<div data-field="symbol">Symbol</div>
	<div data-field="quantity">Quantity</div>
	<div data-field="target">Target %</div>
	<div data-field="actual">Actual %</div>
	<div data-field="suggestion">Suggestion</div>
	<div data-field="btc_price">BTC Price</div>
	<div data-field="btc_change">BTC Change</div>
	<div data-field="btc_value">BTC Value</div>
	<div data-field="price">Price</div>
	<div data-field="change">$Change</div>
	<div data-field="value">Value</div>
	
</div>
<div id="coins"></div>

<div id="add_allocation">[ + ]</div>
</body>
</html>
