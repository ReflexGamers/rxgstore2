{% import 'Common/functions.tpl' as fn %}
<!DOCTYPE html>
<html>
<head>
    {{ html.charset() }}
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width">
    <title>{{ title }}</title>
    {{ html.css([
        '//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css',
        '//fonts.googleapis.com/css?family=Roboto:400,300,700,400italic',
        '//fonts.googleapis.com/css?family=PT+Mono',
        'theme'
    ]|merge(styles ? (styles is iterable ? styles : [styles]) : [])) }}

    {# jQuery included here for Cake JS Helper #}
    {{ html.script(['jquery-1.10.2.min']) }}
</head>
<body>
<div id="background">&nbsp;</div>

<header class="header">
    <table class="headert">
        <tr>
            <td class="header_left">
                {{ html.link('SHOP', {'controller': 'Items', 'action': 'index'}) }}
                {{ html.link('FAQ', {'controller': 'Items', 'action': 'faq'}) }}
                {{ html.link('FORUMS', 'http://reflex-gamers.com') }}
                <span id="cart_link_content">
                    {% if cartItems %}
                        {% include 'Cart/link.inc.tpl' %}
                    {% endif %}
                </span>
                <input type="hidden" id="cart_update_location" value="{{ html.url({'controller': 'Cart', 'action': 'link'}) }}">
            </td>
            <td class="header_right">
                {% if user %}
                    <span class="login_name">
                        {{ html.link(user.name, {
                            'controller': 'Users',
                            'action': 'profile',
                            'id': user.steamid
                        }, {
                            'class': 'username_link'
                        }) }}
                    </span>
                    <img class="user_avatar" src="{{ user.avatar }}" />
                    {{ html.link('log out', {
                        'controller': 'Users',
                        'action': 'logout'
                    }, {
                        'class': 'logout'
                    }) }}
                {% else %}
                    {{ form.create('login', {
                        'url': {
                            'controller': 'Users',
                            'action': 'login'
                        },
                        'id': 'steam_signin'
                    }) }}
                        <input class="normal" type="image" src="http://cdn.steamcommunity.com/public/images/signinthroughsteam/sits_small.png" alt="Sign in through Steam">
                        <input type="checkbox" name="rememberme" id="rememberme"><label for="rememberme">remember me</label>
                    {{ form.end }}
                {% endif %}
            </td>
        </tr>
    </table>
</header>

<article id="content" class="content cf">

    {% block preheader %}{% endblock %}

    <h1 class="page_heading">
        {% block title %}
            {{ title }}
        {% endblock %}
    </h1>

    {% if showShoutbox %}
        {% include '/ShoutboxMessages/shoutbox.inc.tpl' %}
    {% endif %}

    {{ flash.render() }}

    {% block content %}{% endblock %}

</article>

<footer class="foot">
    <a href="http://reflex-gamers.com">Reflex Gamers</a> | <a href="http://steampowered.com">Powered by Steam</a>
    {% if access.check('AdminCP', 'read') %}
        | {{ html.link('Admin Control Panel', {
            'controller': 'Admin',
            'action': 'index'
        }) }}
    {% endif %}
</footer>

{{ js.writeBuffer() }}

{% if scripts %}
    {{ html.script(['common']|merge( (scripts is iterable) ? scripts : [scripts] )) }}
{% else %}
    {{ html.script('common') }}
{% endif %}

</body>
</html>