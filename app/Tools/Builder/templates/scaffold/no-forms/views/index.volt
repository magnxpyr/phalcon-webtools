{{ content() }}
<h1>Search $plural$</h1>

<div class="col-md-6 col-sm-6">
	{{ form("$plural$/search", "method":"post", "autocomplete" : "off", "class": "form-horizontal") }}
		{{ link_to("$plural$/new", "Create $plural$") }}
		<fieldset>
			$captureFields$
			<div class="form-group">
				{{ submit_button("Search", "class": "btn btn-success col-sm-offset-4") }}
			</div>
		</fieldset>
	</form>
</div>
