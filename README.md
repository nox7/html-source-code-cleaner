# HTML Source Code Cleaner for PHP 8
A PHP 8 HTML source code cleaner for fast post-processing of HTML code to tabulate it in an opinionated manner.

## Example

```html
<div><p>Hello<br>There!</p></div>
<button type="button">You can
	<br>
	press me!</button><span>If you want to</span>
```

After running the source code cleaner.

```html
<div>
	<p>
		Hello<br>
		There!
	</p>
</div>
<button type="button">
	You can<br>
	press me!
</button>
<span>
	If you want to
</span>
```

## A More Complicated Example
```html

<style>	.text{
		text-align:center;
	}
</style><nav><div><a class="" href="#">
			<svg></svg><span>hello</span>
			asd</a></div></nav><div class="bg-white shadow p-4"><h3 class="mb-3">Page Views For When

		We no Longer Have the Right to Continue Viewing</h3><div class="form-group row"><div class="col-xl-6 mb-xl-0 mb-3"><div class="material-text-input always-has-text">	<label>From</label>
				<input type="date" class="form-control" id="page-views-date-start" value="1"></div>	</div>
		<div class="col-xl-6"><div class="material-text-input always-has-text">
				<label>To</label><input type="date" class="form-control" id="page-views-date-end" value="12">
			</div>
		</div><h3>Omg hello!</h3>
	</div>
	<div id="apply-page-view-date-change-button-container" class="my-2" style="display:none;">
		<button class="btn btn-sm btn-primary" type="button" id="apply-date-change-page-views">Apply</button>
	</div>
	<p><picture>
			<img src="">
		</picture>
		<br>
		Hello there!</p>
	<hr>
	<div id="page-views-graph-wrapper"><svg class="line-graph" id="page-views-graph-container"></svg></div>
	<script>
		var = window.else();
		addEvent("click", () => {

		});
	</script>
</div>

```
After cleaning

```html
<style>
	.text{
		text-align:center;
	}
</style>
<nav>
	<div>
		<a class="" href="#">
			<svg></svg>
			<span>hello</span>
			asd
		</a>
	</div>
</nav>
<div class="bg-white shadow p-4">
	<h3 class="mb-3">
		Page Views For When

We no Longer Have the Right to Continue Viewing
	</h3>
	<div class="form-group row">
		<div class="col-xl-6 mb-xl-0 mb-3">
			<div class="material-text-input always-has-text">
				<label>From</label>
				<input type="date" class="form-control" id="page-views-date-start" value="1">
			</div>
		</div>
		<div class="col-xl-6">
			<div class="material-text-input always-has-text">
				<label>To</label>
				<input type="date" class="form-control" id="page-views-date-end" value="12">
			</div>
		</div>
		<h3>Omg hello!</h3>
	</div>
	<div id="apply-page-view-date-change-button-container" class="my-2" style="display:none;">
		<button class="btn btn-sm btn-primary" type="button" id="apply-date-change-page-views">Apply</button>
	</div>
	<p>
		<picture>
			<img src="">
		</picture>
		<br>
		Hello there!
	</p>
	<hr>
	<div id="page-views-graph-wrapper">
		<svg class="line-graph" id="page-views-graph-container"></svg>
	</div>
	<script>
		var = window.else();
		addEvent("click", () => {
		});
	</script>
</div>
```
