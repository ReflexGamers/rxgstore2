{% extends isAjax ? 'Common/ajax.tpl' : 'Common/layout.tpl' %}

{% set jquery = true %}
{% set title = false %}
{% set headerImage = 'receipts.png' %}
{% set backLink = {'controller': 'users', 'action': 'profile', 'id': steam.id64(data.Order.user_id)} %}

{% block content %}

	{% set flash = session.flash() %}

	{% if flash %}

		<p>{{ flash }}</p>

	{% else %}

		{% include 'Orders/receipt.inc.tpl' with {'data': data} %}

	{% endif %}

{% endblock %}