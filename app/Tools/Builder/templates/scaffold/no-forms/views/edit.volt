{{ content() }}
<h1>Edit $plural$</h1>

<div class="col-md-6 col-sm-6">
    {{ form("$plural$/save", "method":"post", "class": "form-horizontal") }}
        {{ link_to("$plural$", "Go Back") }}
        <fieldset>
            {{ hidden_field("id") }}
            $captureFields$
            <div class="form-group">
                {{ submit_button("Save", "class": "btn btn-success col-sm-offset-4") }}
            </div>
        </fieldset>
    </form>
</div>