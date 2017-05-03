
<script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
<script type="text/javascript">
	var ticker = <?= file_get_contents('https://api.coinmarketcap.com/v1/ticker/') ?>;
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
		var slug = coin.name.replace(/\s+/g, '-').toLowerCase();
		var name = '<a href="https://coinmarketcap.com/currencies/' + slug + '" target="_blank">' + coin.name + '</a>';
		div.children('div[data-field="name"]').html(image(coin.id) + name);
		
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
		var val = input.val();
		val = val.replace(/%/g, '');
		input.val(val);
		
		setTimeout(function() { if (input.val().substr(-1) != '%' && ! input.is(":focus")) input.val(input.val()+'%'); }, 1000);
		div.attr('data-target', parseInt(input.val()));
		
		var target_total = 0;
		$('div.coin').each(function ()
		{
			var allocation = get_allocation($(this));
			if (allocation)
				target_total += parseFloat(allocation.target);
		});
		
		console.log(target_total);
		if (target_total != 100)
			$('#target_total').html('Target Sum = '+target_total+'%').addClass('negative');
		else
			$('#target_total').html('').removeClass('negative');
		
		refresh_portfolio();
	}
	
	function refresh_portfolio ()
	{
		var total_value = 0;
		
		$('div.coin').each(function ()
		{
			var allocation = get_allocation($(this));
			
			if (allocation == undefined)
				return;
			
			var coin = get_from_ticker(allocation.symbol);
			var value = allocation.quantity * coin.price_usd;
			
			total_value += value;
		});
		
		$('div.coin').each(function ()
		{
			var allocation = get_allocation($(this));
			
			if (allocation == undefined)
				return;
			
			var coin = get_from_ticker(allocation.symbol);
			var value = allocation.quantity * coin.price_usd;
			var actual = 100 * value / total_value;
			var suggestion = (allocation.target - actual) / 100 * total_value / coin.price_usd;
			
			$(this).children('div[data-field="actual"]').html(actual.toLocaleString('en-US', { maximumFractionDigits: 1 })+'%');
			$(this).children('div[data-field="suggestion"]').html((suggestion>0?'+':'') + suggestion.toLocaleString('en-US'));
			$(this).children('div[data-field="suggestion"]').removeClass('positive').removeClass('negative').addClass(suggestion > 0 ? 'positive' : 'negative');
			$(this).children('div[data-field="btc_price"]').html(coin.price_btc);
			$(this).children('div[data-field="price"]').html(parseFloat(coin.price_usd).toLocaleString('en-US', { minimumFractionDigits: 2 }));
			$(this).children('div[data-field="change"]').html((coin.percent_change_24h > 0 ? '+' : '') + coin.percent_change_24h + '%');
			$(this).children('div[data-field="change"]').removeClass('positive').removeClass('negative').addClass(coin.percent_change_24h > 0 ? 'positive' : 'negative');
			$(this).children('div[data-field="value"]').html(parseInt(value).toLocaleString('en-US'));
		});
		
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
		
		if ($(div).children('input.hint').length)
			return incomplete_allocation;
		
		var symbol = div.attr('data-symbol');
		var quantity = div.attr('data-quantity');
		var target = div.attr('data-target');
		
		return {
			symbol: symbol,
			quantity: quantity,
			target: target
		}
	}
	
	function index_ticker ()
	{
		var new_ticker = {};
		for (var i in ticker)
			new_ticker[ticker[i].symbol] = ticker[i];
		ticker = new_ticker;
	}
	
	function image (coin_id)
	{
		return '<img src="https://files.coinmarketcap.com/static/img/coins/16x16/'+coin_id+'.png" />';
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
		width: 100px;
		font-size: 13px;
		margin: 5px;
	}
	#header {
		font-weight: bold;
	}
	input {
		padding: 3px;
	}
	input.hint {
		color: #ccc;
		font-style: italic;
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
		width: 70px !important;
	}
	div[data-field="name"] {
		width: 150px !important;
	}
	#grand_total span:before {
		content: '$';
	}
	.positive {
		color: green;
	}
	.negative {
		color: red;
	}
	img {
		position: relative;
		top: 2px;
		margin-right: 4px;
	}
	#coins > div:nth-child(odd) {
		background: #eee;
	}
</style>

<div id="header">
	<div data-field="name"></div>
	<div data-field="symbol">Symbol</div>
	<div data-field="quantity">Quantity</div>
	<div data-field="target">Target %</div>
	<div data-field="actual">Actual %</div>
	<div data-field="suggestion">Suggestion</div>
	<div data-field="btc_price">BTC Price</div>
	<div data-field="price">Price</div>
	<div data-field="change">24h Change</div>
	<div data-field="value">Total Value</div>
</div>
<div id="coins"></div>

<div id="add_allocation">[ + ]</div>
<br>
<div id='target_total'></div>
<div id="grand_total"><b>Grand Total:</b> <span>0</span></div>
