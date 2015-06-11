{{ content() }}

<div class="btn-toolbar">
    {{ link_to("$plural$/index", "Go Back", "class": "btn btn-success") }}
    {{ link_to("$plural$/new", "Create", "class": "btn btn-success") }}
</div>

<div class="panel panel-default">
    <!-- Table -->
    <table class="table">
        <thead>
            <tr>
$headerColumns$
            </tr>
        </thead>
        <tbody>
            {% if page.items is defined %}
                {% for $singularVar$ in page.items %}
                    <tr>
$rowColumns$
                        <td>{{ link_to("$plural$/edit/"~$singularVar$.$pk$, "Edit") }}</td>
                        <td>{{ link_to("$plural$/delete/"~$singularVar$.$pk$, "Delete") }}</td>
                    </tr>
                {% endfor %}
            {% endif %}
        </tbody>
    </table>
</div>
<nav>
    <ul class="pagination">
        <li>{{ link_to("$plural$/search", "First") }}</li>
        <li>{{ link_to("$plural$/search?page="~page.before, "Previous") }}</li>
        <li>{{ link_to("$plural$/search?page="~page.next, "Next") }}</li>
        <li>{{ link_to("$plural$/search?page="~page.last, "Last") }}</li>
    </ul>
</nav>