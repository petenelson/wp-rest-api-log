<?php

$id = absint( filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT ) );




?>
<div id="poststuff" class="wrap">

	<div class="postbox request-headers">
		<h3 class=""><span>Request Headers</span></h3>

		<div class="inside collapsed"><pre><code></code></pre></div>

	</div>

	<div class="postbox querystring-parameters">
		<h3 class=""><span>Query Parameters</span></h3>

		<div class="inside visible"><pre><code></code></pre></div>

	</div>

	<div class="postbox body-parameters">
		<h3 class=""><span>Body Parameters</span></h3>

		<div class="inside visible"><pre><code></code></pre></div>

	</div>

	<div class="postbox response-headers">
		<h3 class=""><span>Response Headers</span></h3>

		<div class="inside collapsed"><pre><code></code></pre></div>

	</div>

	<div class="postbox response-body">
		<h3 class=""><span>Response</span></h3>

		<div class="inside visible"><pre><code></code></pre></div>

	</div>


</div>